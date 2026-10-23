<?php

namespace App\Filament\Guru\Resources\PaketSoals;

use App\Enums\UserRole;
use App\Filament\Guru\Resources\PaketSoals\Pages\CreatePaketSoal;
use App\Filament\Guru\Resources\PaketSoals\Pages\EditPaketSoal;
use App\Filament\Guru\Resources\PaketSoals\Pages\ListPaketSoals;
use App\Filament\Resources\PaketSoals\PaketSoalResource as AdminPaketSoalResource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class PaketSoalResource extends AdminPaketSoalResource
{
    protected static ?int $navigationSort = 1;

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = Auth::user();

        if (! $user || $user->role === UserRole::Admin) {
            return $query;
        }

        return $query->tampilUntukUser($user);
    }

    public static function getRecordRouteBindingEloquentQuery(): Builder
    {
        return static::getEloquentQuery()
            ->withoutGlobalScopes([SoftDeletingScope::class])
            ->with(['mapel', 'jenisUjian', 'tahunAjaran', 'dibuatOleh']);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPaketSoals::route('/'),
            'create' => CreatePaketSoal::route('/create'),
            'edit' => EditPaketSoal::route('/{record}/edit'),
        ];
    }
}
