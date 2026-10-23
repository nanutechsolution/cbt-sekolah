<?php

namespace App\Filament\Resources\JadwalUjians\Pages;

use App\Filament\Resources\JadwalUjians\JadwalUjianResource;
use App\Models\JadwalUjian;
use App\Services\JadwalUjianService;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class EditJadwalUjian extends EditRecord
{
    protected static string $resource = JadwalUjianResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (JadwalUjian $record): bool => (int) $record->peserta_ujian_count === 0),
        ];
    }

    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var JadwalUjian $record */
        $record = $this->getRecord();

        $data['kelas'] = $record->kelas()->pluck('kelas.id')->all();

        return $data;
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $user = Auth::user();

        abort_if($user === null, 403);

        app(JadwalUjianService::class)->pastikanBolehMenyimpan($user, $data);

        return $data;
    }

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var JadwalUjian $record */
        return app(JadwalUjianService::class)->simpan($data, $record);
    }
}
