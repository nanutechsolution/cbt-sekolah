<?php

namespace App\Filament\Resources\Ruangs\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class RuangForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama Ruang')
                    ->placeholder('Ruang 01')
                    ->required()
                    ->maxLength(100)
                    ->trim()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Nama ruang sudah ada.',
                    ]),
                TextInput::make('kapasitas')
                    ->label('Kapasitas')
                    ->placeholder('30')
                    ->numeric()
                    ->integer()
                    ->minValue(1)
                    ->maxValue(65535)
                    ->suffix('siswa')
                    ->nullable(),
            ]);
    }
}
