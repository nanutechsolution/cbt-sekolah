<?php

namespace App\Filament\Resources\Sesis\Pages;

use App\Filament\Resources\Sesis\SesiResource;
use App\Models\Sesi;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditSesi extends EditRecord
{
    protected static string $resource = SesiResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Sesi $record): bool => ! $record->trashed()),
            RestoreAction::make(),
        ];
    }
}
