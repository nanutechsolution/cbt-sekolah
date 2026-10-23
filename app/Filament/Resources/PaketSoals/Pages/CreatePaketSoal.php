<?php

namespace App\Filament\Resources\PaketSoals\Pages;

use App\Filament\Resources\PaketSoals\PaketSoalResource;
use App\Models\PaketSoal;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreatePaketSoal extends CreateRecord
{
    protected static string $resource = PaketSoalResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        abort_unless(
            $user?->can('createForMapel', [
                PaketSoal::class,
                (int) $data['mapel_id'],
                (int) $data['tingkat'],
                $data['tahun_ajaran_id'] ?? null,
            ]),
            403,
        );

        $data['dibuat_oleh'] = $user->id;

        return $data;
    }
}
