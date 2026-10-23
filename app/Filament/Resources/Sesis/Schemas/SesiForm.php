<?php

namespace App\Filament\Resources\Sesis\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\TimePicker;
use Filament\Schemas\Schema;

class SesiForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama Sesi')
                    ->placeholder('Pagi')
                    ->required()
                    ->maxLength(50)
                    ->trim(),
                TimePicker::make('jam_mulai')
                    ->label('Jam Mulai')
                    ->required()
                    ->seconds(false)
                    ->format('H:i')
                    ->displayFormat('H:i')
                    ->minutesStep(5)
                    ->native(false),
                TimePicker::make('jam_selesai')
                    ->label('Jam Selesai')
                    ->required()
                    ->seconds(false)
                    ->format('H:i')
                    ->displayFormat('H:i')
                    ->minutesStep(5)
                    ->native(false)
                    ->after('jam_mulai')
                    ->validationMessages([
                        'after' => 'Jam selesai harus setelah jam mulai.',
                    ]),
            ]);
    }
}
