<?php

namespace App\Filament\Resources\Agamas\Pages;

use App\Filament\Resources\Agamas\AgamaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListAgamas extends ListRecords
{
    protected static string $resource = AgamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
