<?php

namespace App\Filament\Resources\Kelas\RelationManagers;

use App\Models\Kelas;
use Filament\Actions\AttachAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DetachAction;
use Filament\Actions\DetachBulkAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class MapelPeminatanRelationManager extends RelationManager
{
    protected static string $relationship = 'mapelPeminatan';

    protected static ?string $title = 'Mapel Peminatan';

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord instanceof Kelas
            && $ownerRecord->tingkat >= 11
            && parent::canViewForRecord($ownerRecord, $pageClass);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nama')
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
                    ->searchable(),
                IconColumn::make('is_peminatan')
                    ->label('Peminatan')
                    ->boolean(),
            ])
            ->headerActions([
                AttachAction::make()
                    ->label('Tambah Mapel Peminatan')
                    ->preloadRecordSelect()
                    ->recordSelectOptionsQuery(
                        fn (Builder $query): Builder => $query->where('is_peminatan', true),
                    ),
            ])
            ->recordActions([
                DetachAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DetachBulkAction::make(),
                ]),
            ])
            ->emptyStateHeading('Belum ada mapel peminatan')
            ->emptyStateDescription('Tambahkan mata pelajaran peminatan untuk kelas 11 atau 12 ini.');
    }
}
