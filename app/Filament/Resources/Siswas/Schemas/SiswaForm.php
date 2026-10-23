<?php

namespace App\Filament\Resources\Siswas\Schemas;

use App\Enums\JenisKelamin;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class SiswaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Identitas Siswa')
                    ->schema([
                        TextInput::make('nis')
                            ->label('NIS')
                            ->required()
                            ->maxLength(30)
                            ->trim()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'NIS sudah digunakan.',
                            ]),
                        TextInput::make('nisn')
                            ->label('NISN')
                            ->maxLength(30)
                            ->trim()
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'NISN sudah digunakan.',
                            ]),
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100)
                            ->trim(),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->required()
                            ->native(false),
                        TextInput::make('tempat_lahir')
                            ->label('Tempat Lahir')
                            ->maxLength(100)
                            ->trim()
                            ->nullable(),
                        DatePicker::make('tanggal_lahir')
                            ->label('Tanggal Lahir')
                            ->maxDate(now())
                            ->native(false)
                            ->nullable(),
                        Select::make('agama_id')
                            ->label('Agama')
                            ->relationship('agama', 'nama')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Select::make('kelas_id')
                            ->label('Kelas')
                            ->relationship('kelas', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Akun Ujian')
                    ->description('Username dan security key dipakai siswa untuk masuk ke ruang ujian.')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(50)
                            ->trim()
                            ->rule('alpha_dash')
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
                                'unique' => 'Username sudah digunakan.',
                            ]),
                        TextInput::make('security_key')
                            ->label('Security Key')
                            ->password()
                            ->revealable()
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('Kosongkan saat membuat siswa untuk generate otomatis. Kosongkan saat mengedit untuk mempertahankan key lama.'),
                        Toggle::make('is_aktif')
                            ->label('Akun Aktif')
                            ->default(true),
                        FileUpload::make('foto')
                            ->label('Foto Siswa')
                            ->image()
                            ->avatar()
                            ->disk('public')
                            ->directory('siswa')
                            ->visibility('public')
                            ->maxSize(2048)
                            ->acceptedFileTypes(['image/jpeg', 'image/png', 'image/webp'])
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
