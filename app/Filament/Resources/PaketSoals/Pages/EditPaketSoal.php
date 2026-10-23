<?php

namespace App\Filament\Resources\PaketSoals\Pages;

use App\Filament\Resources\PaketSoals\PaketSoalResource;
use App\Models\PaketSoal;
use App\Services\PaketSoalLockService;
use App\Services\PaketSoalService;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EditPaketSoal extends EditRecord
{
    protected static string $resource = PaketSoalResource::class;

    public function getSubheading(): ?string
    {
        /** @var PaketSoal $record */
        $record = $this->getRecord();

        if (! $record->isTerkunci()) {
            return null;
        }

        return PaketSoalLockService::PESAN_TERKUNCI;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('markReady')
                ->label('Tandai Siap')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->visible(fn (PaketSoal $record): bool => ! $record->trashed() && ! $record->isTerkunci())
                ->action(function (PaketSoal $record): void {
                    try {
                        app(PaketSoalService::class)->markReady($record);
                        Notification::make()->title('Paket soal siap digunakan.')->success()->send();
                    } catch (ValidationException $exception) {
                        Notification::make()
                            ->title('Paket soal belum bisa ditandai siap')
                            ->body(collect($exception->errors())->flatten()->implode(' '))
                            ->danger()
                            ->send();
                    }
                }),
            Action::make('duplikat')
                ->label('Duplikat')
                ->icon('heroicon-o-document-duplicate')
                ->color('gray')
                ->requiresConfirmation()
                ->modalHeading('Duplikat paket soal?')
                ->modalDescription('Soal, opsi, gambar, dan audio akan disalin ke paket baru berstatus draft.')
                ->visible(fn (PaketSoal $record): bool => ! $record->trashed())
                ->authorize(fn (PaketSoal $record): bool => Auth::user()?->can('duplicate', $record) ?? false)
                ->action(function (PaketSoal $record): void {
                    $user = Auth::user();

                    if (! $user) {
                        return;
                    }

                    $hasil = app(PaketSoalService::class)->duplikat($record, $user);
                    $body = "Kode salinan: {$hasil->paket->kode}.";

                    if ($hasil->fileHilang > 0) {
                        $body .= " {$hasil->fileHilang} file gambar/audio sumber tidak ditemukan dan tidak disalin.";
                    }

                    $notification = Notification::make()
                        ->title('Paket soal berhasil diduplikasi')
                        ->body($body);

                    if ($hasil->fileHilang > 0) {
                        $notification->warning()->send();
                    } else {
                        $notification->success()->send();
                    }

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $hasil->paket]), navigate: true);
                }),
            DeleteAction::make()
                ->authorizationTooltip()
                ->visible(fn (PaketSoal $record): bool => ! $record->trashed()),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var PaketSoal $record */
        $record = $this->getRecord();

        if ($record->isTerkunci()) {
            return array_intersect_key($data, array_flip(['kode', 'nama']));
        }

        $user = Auth::user();

        $mapelBerubah = (int) $data['mapel_id'] !== (int) $record->mapel_id
            || (int) $data['tingkat'] !== (int) $record->tingkat
            || (int) ($data['tahun_ajaran_id'] ?? 0) !== (int) $record->tahun_ajaran_id;

        if ($mapelBerubah) {
            abort_unless(
                $user?->can('createForMapel', [
                    PaketSoal::class,
                    (int) $data['mapel_id'],
                    (int) $data['tingkat'],
                    $data['tahun_ajaran_id'] ?? null,
                ]),
                403,
            );
        }

        return $data;
    }
}
