<?php

namespace App\Filament\Resources\JenisUjians;

use App\Filament\Resources\JenisUjians\Pages\CreateJenisUjian;
use App\Filament\Resources\JenisUjians\Pages\EditJenisUjian;
use App\Filament\Resources\JenisUjians\Pages\ListJenisUjians;
use App\Filament\Resources\JenisUjians\Schemas\JenisUjianForm;
use App\Filament\Resources\JenisUjians\Tables\JenisUjiansTable;
use App\Models\JenisUjian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use UnitEnum;

class JenisUjianResource extends Resource
{
    protected static ?string $model = JenisUjian::class;

    protected static ?string $slug = 'jenis-ujian';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    protected static string|UnitEnum|null $navigationGroup = 'Master Data';

    protected static ?string $navigationLabel = 'Jenis Ujian';

    protected static ?int $navigationSort = 5;

    protected static ?string $modelLabel = 'Jenis Ujian';

    protected static ?string $pluralModelLabel = 'Jenis Ujian';

    public static function form(Schema $schema): Schema
    {
        return JenisUjianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JenisUjiansTable::configure($table);
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
            'index' => ListJenisUjians::route('/'),
            'create' => CreateJenisUjian::route('/create'),
            'edit' => EditJenisUjian::route('/{record}/edit'),
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
