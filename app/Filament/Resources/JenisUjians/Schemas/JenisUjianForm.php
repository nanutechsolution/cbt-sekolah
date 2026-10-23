<?php

namespace App\Filament\Resources\JenisUjians\Schemas;

use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class JenisUjianForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Nama Jenis Ujian')
                    ->placeholder('Ujian Tengah Semester')
                    ->required()
                    ->maxLength(100)
                    ->trim(),
                TextInput::make('kode')
                    ->label('Kode')
                    ->placeholder('UTS')
                    ->helperText('Contoh: UTS, UAS, PAS. Kode disimpan dalam huruf besar.')
                    ->required()
                    ->maxLength(20)
                    ->trim()
                    ->rule('alpha_dash')
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'alpha_dash' => 'Kode hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
                        'unique' => 'Kode jenis ujian sudah ada.',
                    ]),
            ]);
    }
}
