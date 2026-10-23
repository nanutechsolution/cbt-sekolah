<?php

namespace App\Filament\Resources\TahunAjarans\Tables;

use App\Enums\Semester;
use App\Models\TahunAjaran;
use App\Services\TahunAjaranService;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class TahunAjaransTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('semester')
                    ->label('Semester')
                    ->badge()
                    ->sortable(),
                IconColumn::make('is_aktif')
                    ->label('Aktif')
                    ->boolean()
                    ->trueColor('success')
                    ->falseColor('gray'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('semester')
                    ->label('Semester')
                    ->options(Semester::class),
                TernaryFilter::make('is_aktif')
                    ->label('Status')
                    ->trueLabel('Aktif')
                    ->falseLabel('Tidak aktif'),
            ])
            ->recordActions([
                Action::make('aktifkan')
                    ->label('Aktifkan')
                    ->icon(Heroicon::OutlinedCheckCircle)
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('Aktifkan tahun ajaran?')
                    ->modalDescription('Tahun ajaran lain yang sedang aktif akan dinonaktifkan.')
                    ->visible(fn (TahunAjaran $record): bool => ! $record->is_aktif)
                    ->authorize('update')
                    ->action(fn (TahunAjaran $record) => app(TahunAjaranService::class)->activate($record))
                    ->successNotificationTitle('Tahun ajaran berhasil diaktifkan'),
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->defaultSort('nama', 'desc')
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
