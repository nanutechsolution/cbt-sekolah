<?php

namespace App\Filament\Resources\Jurusans\Pages;

use App\Filament\Resources\Jurusans\JurusanResource;
use App\Filament\Support\ExcelImportActions;
use App\Models\Jurusan;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListJurusans extends ListRecords
{
    protected static string $resource = JurusanResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ...ExcelImportActions::make('jurusan', Jurusan::class),
            CreateAction::make(),
        ];
    }
}
