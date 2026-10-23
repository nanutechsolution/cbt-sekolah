<?php

namespace App\Filament\Resources\Agamas\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class AgamaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama Agama')
                    ->placeholder('Islam')
                    ->required()
                    ->maxLength(100)
                    ->trim()
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'Nama agama sudah ada.',
                    ]),
            ]);
    }
}
