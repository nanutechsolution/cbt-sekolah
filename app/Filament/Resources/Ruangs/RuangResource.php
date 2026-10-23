<?php

namespace App\Filament\Resources\Ruangs;

use App\Filament\Resources\Ruangs\Pages\CreateRuang;
use App\Filament\Resources\Ruangs\Pages\EditRuang;
use App\Filament\Resources\Ruangs\Pages\ListRuangs;
use App\Filament\Resources\Ruangs\Schemas\RuangForm;
use App\Filament\Resources\Ruangs\Tables\RuangsTable;
use App\Models\Ruang;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class RuangResource extends Resource
{
    protected static ?string $model = Ruang::class;

    protected static ?string $slug = 'ruang';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Ruang';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'Ruang';

    protected static ?string $pluralModelLabel = 'Ruang';

    public static function form(Schema $schema): Schema
    {
        return RuangForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RuangsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRuangs::route('/'),
            'create' => CreateRuang::route('/create'),
            'edit' => EditRuang::route('/{record}/edit'),
        ];
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }
}
