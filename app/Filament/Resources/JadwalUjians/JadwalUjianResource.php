<?php

namespace App\Filament\Resources\JadwalUjians;

use App\Filament\Resources\JadwalUjians\Pages\CreateJadwalUjian;
use App\Filament\Resources\JadwalUjians\Pages\EditJadwalUjian;
use App\Filament\Resources\JadwalUjians\Pages\ListJadwalUjians;
use App\Filament\Resources\JadwalUjians\RelationManagers\PesertaUjianRelationManager;
use App\Filament\Resources\JadwalUjians\Schemas\JadwalUjianForm;
use App\Filament\Resources\JadwalUjians\Tables\JadwalUjiansTable;
use App\Models\JadwalUjian;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class JadwalUjianResource extends Resource
{
    protected static ?string $model = JadwalUjian::class;

    protected static ?string $slug = 'jadwal-ujian';

    protected static ?string $recordTitleAttribute = 'nama';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static string|UnitEnum|null $navigationGroup = 'Ujian';

    protected static ?string $navigationLabel = 'Jadwal Ujian';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Jadwal Ujian';

    protected static ?string $pluralModelLabel = 'Jadwal Ujian';

    public static function form(Schema $schema): Schema
    {
        return JadwalUjianForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return JadwalUjiansTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            PesertaUjianRelationManager::class,
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['paketSoal.mapel', 'dibuatOleh'])
            ->withCount(['kelas', 'pesertaUjian']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['paketSoal.mapel', 'kelas', 'dibuatOleh'])
            ->withCount(['kelas', 'pesertaUjian']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListJadwalUjians::route('/'),
            'create' => CreateJadwalUjian::route('/create'),
            'edit' => EditJadwalUjian::route('/{record}/edit'),
        ];
    }
}
