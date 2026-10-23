<?php

namespace App\Filament\Resources\Mapels\Pages;

use App\Filament\Resources\Mapels\MapelResource;
use App\Filament\Support\ExcelImportActions;
use App\Models\Mapel;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListMapels extends ListRecords
{
    protected static string $resource = MapelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...ExcelImportActions::make('mapel', Mapel::class),
            CreateAction::make(),
        ];
    }
}
