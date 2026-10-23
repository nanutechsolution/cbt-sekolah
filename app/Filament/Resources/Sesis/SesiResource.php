<?php

namespace App\Filament\Resources\Sesis;

use App\Filament\Resources\Sesis\Pages\CreateSesi;
use App\Filament\Resources\Sesis\Pages\EditSesi;
use App\Filament\Resources\Sesis\Pages\ListSesis;
use App\Filament\Resources\Sesis\Schemas\SesiForm;
use App\Filament\Resources\Sesis\Tables\SesisTable;
use App\Models\Sesi;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class SesiResource extends Resource
{
    protected static ?string $model = Sesi::class;

    protected static ?string $slug = 'sesi';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClock;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Sesi';

    protected static ?int $navigationSort = 4;

    protected static ?string $modelLabel = 'Sesi';

    protected static ?string $pluralModelLabel = 'Sesi';

    public static function form(Schema $schema): Schema
    {
        return SesiForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SesisTable::configure($table);
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
            'index' => ListSesis::route('/'),
            'create' => CreateSesi::route('/create'),
            'edit' => EditSesi::route('/{record}/edit'),
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
