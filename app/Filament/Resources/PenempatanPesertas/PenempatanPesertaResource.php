<?php

namespace App\Filament\Resources\PenempatanPesertas;

use App\Filament\Resources\PenempatanPesertas\Pages\CreatePenempatanPeserta;
use App\Filament\Resources\PenempatanPesertas\Pages\EditPenempatanPeserta;
use App\Filament\Resources\PenempatanPesertas\Pages\ListPenempatanPesertas;
use App\Filament\Resources\PenempatanPesertas\Schemas\PenempatanPesertaForm;
use App\Filament\Resources\PenempatanPesertas\Tables\PenempatanPesertasTable;
use App\Models\PenempatanPeserta;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

class PenempatanPesertaResource extends Resource
{
    protected static ?string $model = PenempatanPeserta::class;

    protected static ?string $slug = 'penempatan-peserta';

    protected static ?string $recordTitleAttribute = 'no_peserta';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Ujian';

    protected static ?string $navigationLabel = 'Penempatan Peserta';

    protected static ?int $navigationSort = 2;

    protected static ?string $modelLabel = 'Penempatan Peserta';

    protected static ?string $pluralModelLabel = 'Penempatan Peserta';

    public static function form(Schema $schema): Schema
    {
        return PenempatanPesertaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PenempatanPesertasTable::configure($table);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with(['siswa.kelas', 'tahunAjaran', 'jenisUjian', 'ruang', 'sesi']);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return parent::getRecordRouteBindingEloquentQuery()
            ->with(['siswa.kelas', 'tahunAjaran', 'jenisUjian', 'ruang', 'sesi']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPenempatanPesertas::route('/'),
            'create' => CreatePenempatanPeserta::route('/create'),
            'edit' => EditPenempatanPeserta::route('/{record}/edit'),
        ];
    }
}
