<?php

namespace App\Filament\Resources\PenempatanPesertas\Pages;

use App\Filament\Resources\PenempatanPesertas\PenempatanPesertaResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditPenempatanPeserta extends EditRecord
{
    protected static string $resource = PenempatanPesertaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
