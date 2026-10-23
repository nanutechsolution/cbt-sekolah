<?php

namespace App\Filament\Resources\JadwalUjians\Tables;

use App\Enums\StatusJadwalUjian;
use App\Filament\Guru\Pages\AnalisisButir as GuruAnalisisButir;
use App\Filament\Pages\AnalisisButir;
use App\Models\JadwalUjian;
use App\Services\JadwalUjianService;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Validation\ValidationException;

class JadwalUjiansTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Jadwal')
                    ->searchable()
                    ->sortable()
                    ->wrap(),
                TextColumn::make('paketSoal.nama')
                    ->label('Paket Soal')
                    ->searchable()
                    ->wrap(),
                TextColumn::make('paketSoal.mapel.nama')
                    ->label('Mata Pelajaran')
                    ->toggleable(),
                TextColumn::make('mulai')
                    ->label('Mulai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('selesai')
                    ->label('Selesai')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('durasi_menit')
                    ->label('Durasi')
                    ->suffix(' menit')
                    ->sortable(),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (StatusJadwalUjian $state): string => match ($state) {
                        StatusJadwalUjian::Aktif => 'success',
                        StatusJadwalUjian::Selesai => 'gray',
                        default => 'warning',
                    })
                    ->sortable(),
                TextColumn::make('kelas_count')
                    ->label('Kelas')
                    ->counts('kelas')
                    ->badge(),
                TextColumn::make('peserta_ujian_count')
                    ->label('Peserta')
                    ->counts('pesertaUjian')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusJadwalUjian::class),
                SelectFilter::make('paket_soal_id')
                    ->label('Paket Soal')
                    ->relationship('paketSoal', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (JadwalUjian $record): bool => $record->status !== StatusJadwalUjian::Selesai),
                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon('heroicon-o-play')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (JadwalUjian $record): bool => $record->status === StatusJadwalUjian::Nonaktif)
                    ->action(function (JadwalUjian $record): void {
                        try {
                            app(JadwalUjianService::class)->aktifkan($record);
                            Notification::make()->title('Jadwal ujian diaktifkan.')->success()->send();
                        } catch (ValidationException $exception) {
                            Notification::make()
                                ->title('Jadwal belum bisa diaktifkan')
                                ->body(collect($exception->errors())->flatten()->implode(' '))
                                ->danger()
                                ->send();
                        }
                    }),
                Action::make('selesaikan')
                    ->label('Selesaikan')
                    ->icon('heroicon-o-stop')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (JadwalUjian $record): bool => $record->status === StatusJadwalUjian::Aktif)
                    ->action(function (JadwalUjian $record): void {
                        app(JadwalUjianService::class)->selesaikan($record);
                        Notification::make()->title('Jadwal ujian ditandai selesai.')->success()->send();
                    }),
                ActionGroup::make([
                    Action::make('cetakKartu')
                        ->label('Kartu Peserta')
                        ->icon('heroicon-o-identification')
                        ->url(fn (JadwalUjian $record): string => route('cetak.kartu', $record))
                        ->openUrlInNewTab(),
                    Action::make('cetakDaftarHadir')
                        ->label('Daftar Hadir')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->url(fn (JadwalUjian $record): string => route('cetak.daftar-hadir', $record))
                        ->openUrlInNewTab(),
                    Action::make('cetakBeritaAcara')
                        ->label('Berita Acara')
                        ->icon('heroicon-o-document-text')
                        ->url(fn (JadwalUjian $record): string => route('cetak.berita-acara', $record))
                        ->openUrlInNewTab(),
                ])
                    ->label('Cetak')
                    ->icon('heroicon-o-printer'),
                Action::make('analisis')
                    ->label('Analisis Butir')
                    ->icon('heroicon-o-chart-bar-square')
                    ->url(function (JadwalUjian $record): string {
                        $page = Filament::getCurrentPanel()?->getId() === 'guru'
                            ? GuruAnalisisButir::class
                            : AnalisisButir::class;

                        return $page::getUrl(['jadwal' => $record->id]);
                    }),
                DeleteAction::make()
                    ->visible(fn (JadwalUjian $record): bool => (int) $record->peserta_ujian_count === 0),
            ])
            ->defaultSort('mulai', 'desc');
    }
}
