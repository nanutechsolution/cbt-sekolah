<?php

namespace App\Filament\Resources\LogAktivitas\Tables;

use App\Enums\JenisAksiLog;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class LogAktivitasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('pengguna_nama')
                    ->label('Pengguna')
                    ->placeholder('Sistem')
                    ->searchable(),
                TextColumn::make('aksi')
                    ->label('Aksi')
                    ->badge()
                    ->formatStateUsing(fn (JenisAksiLog $state): string => $state->getLabel())
                    ->color(fn (JenisAksiLog $state): string => match ($state) {
                        JenisAksiLog::TambahWaktu => 'info',
                        JenisAksiLog::BukaKunciDevice => 'warning',
                        JenisAksiLog::ResetPeserta => 'danger',
                        JenisAksiLog::SelesaikanManual => 'success',
                        JenisAksiLog::BuatAkun => 'info',
                        JenisAksiLog::UbahAkun => 'info',
                        JenisAksiLog::AktifkanAkun => 'success',
                        JenisAksiLog::NonaktifkanAkun => 'danger',
                        JenisAksiLog::ResetPassword => 'warning',
                        JenisAksiLog::UbahProfil => 'info',
                        JenisAksiLog::ResetSecurityKey => 'warning',
                        JenisAksiLog::UbahSecurityKey => 'warning',
                    }),
                TextColumn::make('entitas_label')
                    ->label('Objek'),
                TextColumn::make('keterangan')
                    ->label('Keterangan')
                    ->wrap()
                    ->searchable(),
                TextColumn::make('data_sebelum')
                    ->label('Data Sebelum')
                    ->formatStateUsing(fn (mixed $state): string => $state === null
                        ? '-'
                        : (string) json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->limit(120)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('data_sesudah')
                    ->label('Data Sesudah')
                    ->formatStateUsing(fn (mixed $state): string => $state === null
                        ? '-'
                        : (string) json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))
                    ->limit(120)
                    ->wrap()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('ip_address')
                    ->label('IP')
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('aksi')
                    ->label('Aksi')
                    ->options(JenisAksiLog::class),
                SelectFilter::make('pengguna_id')
                    ->label('Pengguna')
                    ->relationship('pengguna', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->defaultSort('created_at', 'desc');
    }
}
