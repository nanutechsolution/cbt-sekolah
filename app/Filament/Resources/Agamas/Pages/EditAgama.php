<?php

namespace App\Filament\Resources\Agamas\Pages;

use App\Filament\Resources\Agamas\AgamaResource;
use App\Models\Agama;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditAgama extends EditRecord
{
    protected static string $resource = AgamaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Agama $record): bool => ! $record->trashed()),
            RestoreAction::make(),
        ];
    }
}
