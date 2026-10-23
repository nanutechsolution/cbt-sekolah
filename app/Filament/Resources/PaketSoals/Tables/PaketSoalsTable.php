<?php

namespace App\Filament\Resources\PaketSoals\Tables;

use App\Enums\StatusPaketSoal;
use App\Models\PaketSoal;
use App\Services\PaketSoalService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class PaketSoalsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kode')
                    ->label('Kode')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('nama')
                    ->label('Nama Paket')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('jenisUjian.nama')
                    ->label('Jenis Ujian')
                    ->searchable()
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),
                TextColumn::make('soal_count')
                    ->label('Soal')
                    ->counts('soal')
                    ->badge()
                    ->sortable(),
                TextColumn::make('dibuatOleh.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('deleted_at')
                    ->label('Dihapus')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                TrashedFilter::make()
                    ->label('Status Data')
                    ->placeholder('Aktif')
                    ->trueLabel('Semua data')
                    ->falseLabel('Terhapus'),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusPaketSoal::class),
                SelectFilter::make('mapel_id')
                    ->label('Mata Pelajaran')
                    ->relationship('mapel', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('tingkat')
                    ->label('Tingkat')
                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $tingkat): array => [$tingkat => (string) $tingkat])->all()),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (PaketSoal $record): bool => ! $record->trashed()),
                Action::make('duplikat')
                    ->label('Duplikat')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('gray')
                    ->requiresConfirmation()
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
                    }),
                Action::make('markReady')
                    ->label('Tandai Siap')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (PaketSoal $record): bool => ! $record->trashed() && ! $record->isTerkunci() && $record->status === StatusPaketSoal::Draft)
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
                Action::make('markDraft')
                    ->label('Kembalikan ke Draft')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('gray')
                    ->requiresConfirmation()
                    ->visible(fn (PaketSoal $record): bool => ! $record->trashed() && ! $record->isTerkunci() && $record->status === StatusPaketSoal::Siap)
                    ->action(function (PaketSoal $record): void {
                        try {
                            app(PaketSoalService::class)->markDraft($record);
                            Notification::make()->title('Paket soal dikembalikan ke draft.')->success()->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Paket soal tidak bisa dikembalikan ke draft')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        }
                    }),
                DeleteAction::make()
                    ->authorizationTooltip()
                    ->visible(fn (PaketSoal $record): bool => ! $record->trashed()),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make()
                        ->authorizeIndividualRecords('delete'),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('updated_at', 'desc');
    }
}
