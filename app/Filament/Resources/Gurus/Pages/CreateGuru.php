<?php

namespace App\Filament\Resources\Gurus\Pages;

use App\Filament\Resources\Gurus\GuruResource;
use App\Services\GuruService;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Database\Eloquent\Model;

class CreateGuru extends CreateRecord
{
    protected static string $resource = GuruResource::class;

    protected function handleRecordCreation(array $data): Model
    {
        return app(GuruService::class)->create($data);
    }
}
