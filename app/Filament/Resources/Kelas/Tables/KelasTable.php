<?php

namespace App\Filament\Resources\Kelas\Tables;

use App\Models\Kelas;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class KelasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Nama Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tingkat')
                    ->label('Tingkat')
                    ->badge()
                    ->sortable(),
                TextColumn::make('jurusan.nama')
                    ->label('Jurusan')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('waliKelas.nama')
                    ->label('Wali Kelas')
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('siswa_count')
                    ->label('Siswa')
                    ->counts('siswa')
                    ->badge()
                    ->sortable(),
                TextColumn::make('mapel_peminatan_count')
                    ->label('Mapel Peminatan')
                    ->counts('mapelPeminatan')
                    ->badge()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->label('Dibuat')
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
                SelectFilter::make('tingkat')
                    ->label('Tingkat')
                    ->options(collect(range(1, 12))->mapWithKeys(fn (int $tingkat): array => [$tingkat => (string) $tingkat])->all()),
                SelectFilter::make('jurusan_id')
                    ->label('Jurusan')
                    ->relationship('jurusan', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Kelas $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->visible(fn (Kelas $record): bool => ! $record->trashed()),
                RestoreAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('nama');
    }
}
