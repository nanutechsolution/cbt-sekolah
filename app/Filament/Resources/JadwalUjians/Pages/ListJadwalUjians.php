<?php

namespace App\Filament\Resources\JadwalUjians\Pages;

use App\Filament\Resources\JadwalUjians\JadwalUjianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJadwalUjians extends ListRecords
{
    protected static string $resource = JadwalUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
