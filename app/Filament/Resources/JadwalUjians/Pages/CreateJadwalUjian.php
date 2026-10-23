<?php

namespace App\Filament\Resources\JadwalUjians\Pages;

use App\Filament\Resources\JadwalUjians\JadwalUjianResource;
use App\Services\JadwalUjianService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class CreateJadwalUjian extends CreateRecord
{
    protected static string $resource = JadwalUjianResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = Auth::user();

        abort_if($user === null, 403);

        app(JadwalUjianService::class)->pastikanBolehMenyimpan($user, $data);

        $data['dibuat_oleh'] = $user->id;

        return $data;
    }

    protected function handleRecordCreation(array $data): Model
    {
        return app(JadwalUjianService::class)->simpan($data);
    }
}
