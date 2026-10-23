<?php

namespace App\Filament\Support;

use App\Services\ExcelImportService;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExcelImportActions
{
    /**
     * @return array<int, Action>
     */
    public static function make(string $type, string $modelClass): array
    {
        return [
            Action::make('import')
                ->label('Import Excel')
                ->icon(Heroicon::OutlinedDocumentArrowUp)
                ->color('success')
                ->visible(fn (): bool => Auth::user()?->can('create', $modelClass) ?? false)
                ->modalHeading('Import data '.$type)
                ->modalDescription('Unggah file .xlsx sesuai template. Baris yang tidak valid akan dilewati dan dilaporkan.')
                ->schema([
                    FileUpload::make('file')
                        ->label('File Excel')
                        ->disk('local')
                        ->directory('imports')
                        ->acceptedFileTypes([
                            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                        ])
                        ->maxSize(5120)
                        ->required(),
                ])
                ->action(function (array $data) use ($type): void {
                    $file = $data['file'] ?? null;

                    if (! is_string($file) || ! str_ends_with(strtolower($file), '.xlsx')) {
                        Notification::make()
                            ->title('File tidak valid')
                            ->body('Gunakan file .xlsx sesuai template.')
                            ->danger()
                            ->send();

                        return;
                    }

                    $path = Storage::disk('local')->path($file);

                    try {
                        $result = app(ExcelImportService::class)->import($type, $path);
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Import gagal')
                            ->body($exception->getMessage())
                            ->danger()
                            ->send();

                        return;
                    } finally {
                        Storage::disk('local')->delete($file);
                    }

                    $notification = Notification::make()
                        ->title($result->hasErrors() ? 'Import selesai dengan catatan' : 'Import berhasil')
                        ->body($result->summary());

                    if ($result->hasErrors()) {
                        $notification
                            ->body($result->summary().' '.implode(' ', $result->errorMessages()))
                            ->warning();
                    } else {
                        $notification->success();
                    }

                    $notification->send();
                }),
            Action::make('template')
                ->label('Template Excel')
                ->icon(Heroicon::OutlinedDocumentArrowDown)
                ->color('gray')
                ->visible(fn (): bool => Auth::user()?->can('create', $modelClass) ?? false)
                ->url(route('admin.import-template', $type))
                ->openUrlInNewTab(),
        ];
    }
}
