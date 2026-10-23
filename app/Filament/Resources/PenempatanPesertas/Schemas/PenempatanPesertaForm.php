<?php

namespace App\Filament\Resources\PenempatanPesertas\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class PenempatanPesertaForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Penempatan Peserta')
                    ->schema([
                        Select::make('siswa_id')
                            ->label('Siswa')
                            ->relationship('siswa', 'nama')
                            ->searchable()
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('tahun_ajaran_id', $get('tahun_ajaran_id'))
                                    ->where('jenis_ujian_id', $get('jenis_ujian_id')),
                            )
                            ->validationMessages([
                                'unique' => 'Siswa ini sudah ditempatkan pada tahun ajaran dan jenis ujian yang dipilih.',
                            ]),
                        Select::make('tahun_ajaran_id')
                            ->label('Tahun Ajaran')
                            ->relationship('tahunAjaran', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('jenis_ujian_id')
                            ->label('Jenis Ujian')
                            ->relationship('jenisUjian', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('ruang_id')
                            ->label('Ruang')
                            ->relationship('ruang', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Select::make('sesi_id')
                            ->label('Sesi')
                            ->relationship('sesi', 'nama')
                            ->searchable()
                            ->preload()
                            ->required(),
                        TextInput::make('no_peserta')
                            ->label('Nomor Peserta')
                            ->required()
                            ->maxLength(30)
                            ->trim()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('tahun_ajaran_id', $get('tahun_ajaran_id'))
                                    ->where('jenis_ujian_id', $get('jenis_ujian_id')),
                            )
                            ->validationMessages([
                                'unique' => 'Nomor peserta sudah dipakai pada tahun ajaran dan jenis ujian ini.',
                            ]),
                    ])
                    ->columns(2),
            ]);
    }
}
