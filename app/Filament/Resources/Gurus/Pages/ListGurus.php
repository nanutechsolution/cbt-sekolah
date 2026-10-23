<?php

namespace App\Filament\Resources\Gurus\Pages;

use App\Filament\Resources\Gurus\GuruResource;
use App\Filament\Support\ExcelImportActions;
use App\Models\Guru;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListGurus extends ListRecords
{
    protected static string $resource = GuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...ExcelImportActions::make('guru', Guru::class),
            CreateAction::make(),
        ];
    }
}
