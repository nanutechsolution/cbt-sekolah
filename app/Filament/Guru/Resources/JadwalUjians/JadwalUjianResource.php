<?php

namespace App\Filament\Guru\Resources\JadwalUjians;

use App\Filament\Guru\Resources\JadwalUjians\Pages\CreateJadwalUjian;
use App\Filament\Guru\Resources\JadwalUjians\Pages\EditJadwalUjian;
use App\Filament\Guru\Resources\JadwalUjians\Pages\ListJadwalUjians;
use App\Filament\Resources\JadwalUjians\JadwalUjianResource as AdminJadwalUjianResource;
use App\Models\PaketSoal;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class JadwalUjianResource extends AdminJadwalUjianResource
{
    protected static ?int $navigationSort = 2;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user) {
            return $query->whereRaw('1 = 0');
        }

        return $query->whereIn(
            'paket_soal_id',
            PaketSoal::query()->tampilUntukUser($user)->select('id'),
        );
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
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
