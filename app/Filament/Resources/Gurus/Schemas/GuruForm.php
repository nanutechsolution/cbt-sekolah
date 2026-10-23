<?php

namespace App\Filament\Resources\Gurus\Schemas;

use App\Enums\JenisKelamin;
use App\Filament\Resources\Gurus\Pages\CreateGuru;
use App\Models\Guru;
use App\Models\User;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class GuruForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Akun Login')
                    ->description('Akun ini dipakai guru untuk masuk ke panel guru.')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->required()
                            ->maxLength(50)
                            ->trim()
                            ->rule('alpha_dash')
                            ->unique(
                                table: User::class,
                                column: 'username',
                                ignorable: fn (?Guru $record): ?User => $record?->user,
                                ignoreRecord: false,
                            )
                            ->validationMessages([
                                'alpha_dash' => 'Username hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
                                'unique' => 'Username sudah digunakan.',
                            ]),
                        TextInput::make('email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255)
                            ->trim()
                            ->nullable()
                            ->unique(
                                table: User::class,
                                column: 'email',
                                ignorable: fn (?Guru $record): ?User => $record?->user,
                                ignoreRecord: false,
                            )
                            ->validationMessages([
                                'unique' => 'Email sudah digunakan.',
                            ]),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->nullable()
                            ->minLength(8)
                            ->required(fn ($livewire): bool => $livewire instanceof CreateGuru)
                            ->helperText(fn ($livewire): string => $livewire instanceof CreateGuru
                                ? 'Minimal 8 karakter.'
                                : 'Kosongkan jika tidak ingin mengubah password.'),
                        Toggle::make('is_aktif')
                            ->label('Akun Aktif')
                            ->default(true),
                    ])
                    ->columns(2),
                Section::make('Profil Guru')
                    ->schema([
                        TextInput::make('nama')
                            ->label('Nama Lengkap')
                            ->required()
                            ->maxLength(100)
                            ->trim(),
                        TextInput::make('nip')
                            ->label('NIP')
                            ->maxLength(30)
                            ->trim()
                            ->nullable()
                            ->unique(ignoreRecord: true)
                            ->validationMessages([
                                'unique' => 'NIP sudah digunakan.',
                            ]),
                        Select::make('jenis_kelamin')
                            ->label('Jenis Kelamin')
                            ->options(JenisKelamin::class)
                            ->required()
                            ->native(false),
                        TextInput::make('no_hp')
                            ->label('Nomor HP')
                            ->tel()
                            ->maxLength(30)
                            ->trim()
                            ->nullable(),
                    ])
                    ->columns(2),
            ]);
    }
}
