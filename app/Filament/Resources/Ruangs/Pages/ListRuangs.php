<?php

namespace App\Filament\Resources\Ruangs\Pages;

use App\Filament\Resources\Ruangs\RuangResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

class ListRuangs extends ListRecords
{
    protected static string $resource = RuangResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
