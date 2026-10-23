<?php

namespace App\Filament\Resources\JadwalUjians\RelationManagers;

use App\Enums\StatusPesertaUjian;
use App\Models\PesertaUjian;
use App\Services\PesertaUjianService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class PesertaUjianRelationManager extends RelationManager
{
    protected static string $relationship = 'pesertaUjian';

    protected static ?string $title = 'Peserta & Monitoring';

    protected static ?string $modelLabel = 'Peserta Ujian';

    public function table(Table $table): Table
    {
        return $table
            ->poll('10s')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('siswa.kelas'))
            ->columns([
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->searchable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StatusPesertaUjian $state): string => match ($state) {
                        StatusPesertaUjian::Mengerjakan => 'warning',
                        StatusPesertaUjian::Selesai => 'success',
                        StatusPesertaUjian::Ditangguhkan => 'danger',
                        default => 'gray',
                    }),
                TextColumn::make('jawaban_count')
                    ->label('Jawaban')
                    ->counts('jawaban')
                    ->badge()
                    ->color('info'),
                TextColumn::make('jumlah_pelanggaran')
                    ->label('Pelanggaran')
                    ->badge()
                    ->color(fn (int $state): string => $state > 0 ? 'danger' : 'gray'),
                TextColumn::make('waktu_mulai')
                    ->label('Mulai')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('waktu_batas')
                    ->label('Batas')
                    ->dateTime()
                    ->placeholder('-')
                    ->toggleable(),
                TextColumn::make('terakhir_aktif')
                    ->label('Aktif Terakhir')
                    ->since()
                    ->placeholder('-')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('nilai')
                    ->label('Nilai')
                    ->placeholder('-')
                    ->numeric(decimalPlaces: 2),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusPesertaUjian::class),
            ])
            ->recordActions([
                Action::make('tambah_waktu')
                    ->label('Tambah Waktu')
                    ->icon('heroicon-o-clock')
                    ->color('info')
                    ->modalHeading('Tambah Waktu Peserta')
                    ->modalDescription('Waktu peserta akan ditambah tanpa menghapus jawaban. Waktu tidak boleh melewati jam selesai jadwal ujian.')
                    ->schema([
                        TextInput::make('menit')
                            ->label('Tambahan waktu (menit)')
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(PesertaUjianService::MAKS_TAMBAH_WAKTU_MENIT)
                            ->default(10)
                            ->required()
                            ->helperText('Maksimal '.PesertaUjianService::MAKS_TAMBAH_WAKTU_MENIT.' menit dan tidak boleh melewati jam selesai jadwal.'),
                    ])
                    ->visible(fn (PesertaUjian $record): bool => $record->status === StatusPesertaUjian::Mengerjakan
                        && $record->waktu_batas !== null
                        && (Auth::user()?->can('tambahWaktu', $record) ?? false))
                    ->action(function (PesertaUjian $record, array $data): void {
                        Gate::authorize('tambahWaktu', $record);

                        try {
                            $peserta = app(PesertaUjianService::class)->tambahWaktu(
                                $record,
                                (int) $data['menit'],
                            );

                            Notification::make()
                                ->title('Waktu peserta ditambahkan.')
                                ->body('Batas waktu baru: '.$peserta->waktu_batas?->format('d/m/Y H:i'))
                                ->success()
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Gagal menambah waktu')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('buka_kunci_device')
                    ->label('Buka Kunci Device')
                    ->icon('heroicon-o-lock-open')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Buka Kunci Device')
                    ->modalDescription('Jawaban tetap tersimpan. Perangkat lama tidak dapat mengakses sesi ini lagi; peserta dapat melanjutkan pada perangkat baru.')
                    ->visible(fn (PesertaUjian $record): bool => $record->status === StatusPesertaUjian::Mengerjakan
                        && filled($record->device_token)
                        && (Auth::user()?->can('bukaKunciDevice', $record) ?? false))
                    ->action(function (PesertaUjian $record): void {
                        Gate::authorize('bukaKunciDevice', $record);

                        try {
                            app(PesertaUjianService::class)->bukaKunciDevice($record);

                            Notification::make()
                                ->title('Kunci device peserta dibuka.')
                                ->body('Peserta dapat melanjutkan di perangkat baru tanpa kehilangan jawaban.')
                                ->success()
                                ->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Gagal membuka kunci device')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('akhiri')
                    ->label('Akhiri & Nilai')
                    ->icon('heroicon-o-check')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (PesertaUjian $record): bool => $record->status === StatusPesertaUjian::Mengerjakan
                        && (Auth::user()?->can('selesaikanManual', $record) ?? false))
                    ->action(function (PesertaUjian $record): void {
                        Gate::authorize('selesaikanManual', $record);

                        app(PesertaUjianService::class)->selesaikanManual($record);
                        Notification::make()->title('Peserta diselesaikan dan dinilai.')->success()->send();
                    }),
                Action::make('reset')
                    ->label('Reset Peserta')
                    ->icon('heroicon-o-arrow-path')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Semua jawaban, pelanggaran, dan nilai peserta akan dihapus. Peserta harus memulai dari awal.')
                    ->visible(fn (PesertaUjian $record): bool => Auth::user()?->can('reset', $record) ?? false)
                    ->action(function (PesertaUjian $record): void {
                        Gate::authorize('reset', $record);

                        app(PesertaUjianService::class)->reset($record);
                        Notification::make()->title('Peserta berhasil direset.')->success()->send();
                    }),
            ]);
    }
}
