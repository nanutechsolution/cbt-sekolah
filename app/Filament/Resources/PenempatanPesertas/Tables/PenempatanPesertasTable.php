<?php

namespace App\Filament\Resources\PenempatanPesertas\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PenempatanPesertasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('no_peserta')
                    ->label('No. Peserta')
                    ->badge()
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.nama')
                    ->label('Siswa')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa.kelas.nama')
                    ->label('Kelas')
                    ->searchable(),
                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->toggleable(),
                TextColumn::make('jenisUjian.nama')
                    ->label('Jenis Ujian')
                    ->toggleable(),
                TextColumn::make('ruang.nama')
                    ->label('Ruang')
                    ->searchable(),
                TextColumn::make('sesi.nama')
                    ->label('Sesi')
                    ->searchable(),
            ])
            ->filters([
                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('ruang_id')
                    ->label('Ruang')
                    ->relationship('ruang', 'nama')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('sesi_id')
                    ->label('Sesi')
                    ->relationship('sesi', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('no_peserta');
    }
}
