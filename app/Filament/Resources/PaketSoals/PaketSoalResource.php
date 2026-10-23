<?php

namespace App\Filament\Resources\PaketSoals;

use App\Filament\Resources\PaketSoals\Pages\CreatePaketSoal;
use App\Filament\Resources\PaketSoals\Pages\EditPaketSoal;
use App\Filament\Resources\PaketSoals\Pages\ListPaketSoals;
use App\Filament\Resources\PaketSoals\RelationManagers\SoalRelationManager;
use App\Filament\Resources\PaketSoals\Schemas\PaketSoalForm;
use App\Filament\Resources\PaketSoals\Tables\PaketSoalsTable;
use App\Models\PaketSoal;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class PaketSoalResource extends Resource
{
    protected static ?string $model = PaketSoal::class;

    protected static ?string $slug = 'paket-soal';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Bank Soal';

    protected static ?string $navigationLabel = 'Paket Soal';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Paket Soal';

    protected static ?string $pluralModelLabel = 'Paket Soal';

    public static function form(Schema $schema): Schema
    {
        return PaketSoalForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PaketSoalsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            SoalRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->with(['mapel', 'jenisUjian', 'tahunAjaran', 'dibuatOleh']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaketSoals::route('/'),
            'create' => CreatePaketSoal::route('/create'),
            'edit' => EditPaketSoal::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['mapel', 'jenisUjian', 'tahunAjaran', 'dibuatOleh']);
    }
}
