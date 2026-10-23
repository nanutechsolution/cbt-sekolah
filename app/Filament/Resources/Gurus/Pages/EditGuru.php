<?php

namespace App\Filament\Resources\Gurus\Pages;

use App\Filament\Resources\Gurus\GuruResource;
use App\Models\Guru;
use App\Services\GuruService;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditGuru extends EditRecord
{
    protected static string $resource = GuruResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Guru $record): bool => ! $record->trashed()),
            RestoreAction::make(),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Guru $record */
        $record = $this->getRecord();

        return [
            ...$data,
            'username' => $record->user?->username,
            'email' => $record->user?->email,
            'is_aktif' => $record->user?->is_aktif ?? true,
            'password' => null,
        ];
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var Guru $record */
        return app(GuruService::class)->update($record, $data);
    }
}
