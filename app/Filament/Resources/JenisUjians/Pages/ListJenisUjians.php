<?php

namespace App\Filament\Resources\JenisUjians\Pages;

use App\Filament\Resources\JenisUjians\JenisUjianResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJenisUjians extends ListRecords
{
    protected static string $resource = JenisUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
