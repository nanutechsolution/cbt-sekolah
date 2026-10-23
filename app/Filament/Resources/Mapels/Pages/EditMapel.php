<?php

namespace App\Filament\Resources\Mapels\Pages;

use App\Filament\Resources\Mapels\MapelResource;
use App\Models\Mapel;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditMapel extends EditRecord
{
    protected static string $resource = MapelResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Mapel $record): bool => ! $record->trashed()),
            RestoreAction::make(),
        ];
    }
}
