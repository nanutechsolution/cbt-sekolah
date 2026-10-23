<?php

namespace App\Filament\Resources\PenempatanPesertas\Pages;

use App\Filament\Resources\PenempatanPesertas\PenempatanPesertaResource;
use App\Models\JenisUjian;
use App\Models\Kelas;
use App\Models\PenempatanPeserta;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PenempatanPesertaResult;
use App\Services\PenempatanPesertaService;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ListPenempatanPesertas extends ListRecords
{
    protected static string $resource = PenempatanPesertaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('tempatkanPeserta')
                ->label('Tempatkan Peserta')
                ->icon(Heroicon::OutlinedUserGroup)
                ->color('primary')
                ->authorize(fn (): bool => Auth::user()?->can('tempatkan', PenempatanPeserta::class) ?? false)
                ->modalHeading('Tempatkan Peserta')
                ->modalDescription('Buat penempatan untuk semua siswa aktif di kelas yang dipilih. Siswa yang sudah ditempatkan pada tahun ajaran dan jenis ujian yang sama akan dilewati.')
                ->modalSubmitActionLabel('Tempatkan')
                ->schema([
                    Section::make('Data Penempatan')
                        ->schema([
                            Select::make('tahun_ajaran_id')
                                ->label('Tahun Ajaran')
                                ->options(fn (): array => TahunAjaran::query()
                                    ->orderByDesc('nama')
                                    ->pluck('nama', 'id')
                                    ->all())
                                ->default(fn (): ?int => TahunAjaran::query()
                                    ->where('is_aktif', true)
                                    ->value('id'))
                                ->searchable()
                                ->required(),
                            Select::make('jenis_ujian_id')
                                ->label('Jenis Ujian')
                                ->options(fn (): array => JenisUjian::query()
                                    ->orderBy('nama')
                                    ->pluck('nama', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                            Select::make('kelas_id')
                                ->label('Kelas')
                                ->options(fn (): array => Kelas::query()
                                    ->orderBy('nama')
                                    ->pluck('nama', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                            Select::make('ruang_id')
                                ->label('Ruang')
                                ->options(fn (): array => Ruang::query()
                                    ->orderBy('nama')
                                    ->pluck('nama', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                            Select::make('sesi_id')
                                ->label('Sesi')
                                ->options(fn (): array => Sesi::query()
                                    ->orderBy('nama')
                                    ->pluck('nama', 'id')
                                    ->all())
                                ->searchable()
                                ->required(),
                            TextInput::make('awalan_no_peserta')
                                ->label('Awalan Nomor Peserta')
                                ->required()
                                ->maxLength(27)
                                ->trim()
                                ->helperText('Contoh: 2026- menghasilkan nomor 2026-001, 2026-002, dan seterusnya.')
                                ->columnSpanFull(),
                        ])
                        ->columns(2),
                ])
                ->action(function (array $data): void {
                    Gate::authorize('tempatkan', PenempatanPeserta::class);

                    $user = Auth::user();

                    abort_unless($user instanceof User, 403);

                    $hasil = app(PenempatanPesertaService::class)->tempatkan($user, $data);

                    $this->kirimNotifikasiPenempatan($hasil);
                }),
            CreateAction::make(),
        ];
    }

    private function kirimNotifikasiPenempatan(PenempatanPesertaResult $hasil): void
    {
        $ringkasan = "{$hasil->dibuat} peserta ditempatkan";

        if ($hasil->dilewati > 0) {
            $ringkasan .= ", {$hasil->dilewati} dilewati karena sudah ditempatkan";
        }

        $ringkasan .= '.';

        if (filled($hasil->nomorAwal) && filled($hasil->nomorAkhir)) {
            $ringkasan .= " Nomor peserta: {$hasil->nomorAwal} sampai {$hasil->nomorAkhir}.";
        }

        if ($hasil->kapasitasTerlampaui()) {
            Notification::make()
                ->title('Kapasitas ruang terlampaui')
                ->body("{$ringkasan} Ruang {$hasil->namaRuang} pada sesi {$hasil->namaSesi} berisi {$hasil->jumlahPesertaRuangSesi} peserta, melebihi kapasitas {$hasil->kapasitasRuang}.")
                ->warning()
                ->send();

            return;
        }

        if ($hasil->dibuat === 0) {
            Notification::make()
                ->title('Tidak ada peserta baru ditempatkan')
                ->body($ringkasan)
                ->info()
                ->send();

            return;
        }

        Notification::make()
            ->title('Peserta berhasil ditempatkan')
            ->body($ringkasan)
            ->success()
            ->send();
    }
}
