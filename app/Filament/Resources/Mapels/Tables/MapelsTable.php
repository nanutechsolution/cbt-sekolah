<?php

namespace App\Filament\Resources\Mapels\Tables;

use App\Models\Mapel;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class MapelsTable
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
                    ->label('Nama Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('kelompok')
                    ->label('Kelompok')
                    ->badge()
                    ->placeholder('-')
                    ->searchable()
                    ->sortable(),
                IconColumn::make('is_peminatan')
                    ->label('Peminatan')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
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
                TernaryFilter::make('is_peminatan')
                    ->label('Peminatan')
                    ->trueLabel('Ya')
                    ->falseLabel('Bukan'),
            ])
            ->recordActions([
                EditAction::make()
                    ->visible(fn (Mapel $record): bool => ! $record->trashed()),
                DeleteAction::make()
                    ->visible(fn (Mapel $record): bool => ! $record->trashed()),
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
