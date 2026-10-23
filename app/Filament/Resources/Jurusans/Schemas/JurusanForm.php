<?php

namespace App\Filament\Resources\Jurusans\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JurusanForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('kode')
                    ->label('Kode Jurusan')
                    ->placeholder('TKJ')
                    ->helperText('Kode disimpan dalam huruf besar.')
                    ->required()
                    ->maxLength(20)
                    ->trim()
                    ->rule('alpha_dash')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'alpha_dash' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
                        'unique' => 'Kode jurusan sudah ada.',
                    ]),
                TextInput::make('nama')
                    ->label('Nama Jurusan')
                    ->placeholder('Teknik Komputer dan Jaringan')
                    ->required()
                    ->maxLength(100)
                    ->trim(),
            ]);
    }
}
