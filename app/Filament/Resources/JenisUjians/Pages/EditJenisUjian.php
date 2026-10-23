<?php

namespace App\Filament\Resources\JenisUjians\Pages;

use App\Filament\Resources\JenisUjians\JenisUjianResource;
use App\Models\JenisUjian;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;

class EditJenisUjian extends EditRecord
{
    protected static string $resource = JenisUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (JenisUjian $record): bool => ! $record->trashed()),
            RestoreAction::make(),
        ];
    }
}
