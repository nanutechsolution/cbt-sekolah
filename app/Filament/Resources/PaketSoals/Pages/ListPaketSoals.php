<?php

namespace App\Filament\Resources\PaketSoals\Pages;

use App\Filament\Resources\PaketSoals\PaketSoalResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListPaketSoals extends ListRecords
{
    protected static string $resource = PaketSoalResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
