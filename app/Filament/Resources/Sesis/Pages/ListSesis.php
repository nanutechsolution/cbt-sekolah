<?php

namespace App\Filament\Resources\Sesis\Pages;

use App\Filament\Resources\Sesis\SesiResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListSesis extends ListRecords
{
    protected static string $resource = SesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
