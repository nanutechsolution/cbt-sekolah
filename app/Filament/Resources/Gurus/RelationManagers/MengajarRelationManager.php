<?php

namespace App\Filament\Resources\Gurus\RelationManagers;

use App\Models\TahunAjaran;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class MengajarRelationManager extends RelationManager
{
    protected static string $relationship = 'mengajar';

    protected static ?string $title = 'Kelas dan Mapel yang Diajar';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('kelas_id')
                    ->label('Kelas')
                    ->relationship('kelas', 'nama')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('mapel_id')
                    ->label('Mata Pelajaran')
                    ->relationship('mapel', 'nama')
                    ->searchable()
                    ->preload()
                    ->required(),
                Select::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->searchable()
                    ->preload()
                    ->required()
                    ->default(fn (): ?int => TahunAjaran::query()->where('is_aktif', true)->value('id')),
            ])
            ->columns(1);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('kelas.nama')
                    ->label('Kelas')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('mapel.nama')
                    ->label('Mata Pelajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tahunAjaran.nama')
                    ->label('Tahun Ajaran')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('tahunAjaran.semester')
                    ->label('Semester')
                    ->badge(),
            ])
            ->filters([
                SelectFilter::make('tahun_ajaran_id')
                    ->label('Tahun Ajaran')
                    ->relationship('tahunAjaran', 'nama')
                    ->searchable()
                    ->preload(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Tambah Penugasan'),
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
            ->emptyStateHeading('Belum ada penugasan mengajar')
            ->emptyStateDescription('Tambahkan kelas dan mata pelajaran yang diajar guru ini.');
    }
}
