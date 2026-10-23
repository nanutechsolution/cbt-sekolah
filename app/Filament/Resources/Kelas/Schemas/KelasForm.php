<?php

namespace App\Filament\Resources\Kelas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class KelasForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Data Kelas')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Kelas')
                            ->placeholder('X TKJ 1 atau 7A')
                            ->required()
                            ->maxLength(50)
                            ->trim()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'Nama kelas sudah ada.',
                            ]),
                        TextInput::make('tingkat')
                            ->label('Tingkat')
                            ->helperText('Isi angka 1 sampai 12.')
                            ->required()
                            ->numeric()
                            ->integer()
                            ->minValue(1)
                            ->maxValue(12),
                        Select::make('jurusan_id')
                            ->label('Jurusan')
                            ->relationship('jurusan', 'nama')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->helperText('Kosongkan untuk SD/SMP.'),
                        Select::make('wali_kelas_id')
                            ->label('Wali Kelas')
                            ->relationship('waliKelas', 'nama')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }
}
