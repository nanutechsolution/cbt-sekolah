<?php

namespace App\Filament\Resources\TahunAjarans\Schemas;

use App\Enums\Semester;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class TahunAjaranForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('nama')
                    ->label('Tahun Ajaran')
                    ->placeholder('2026/2027')
                    ->required()
                    ->maxLength(20)
                    ->unique(
                        ignoreRecord: true,
                        modifyRuleUsing: function (Unique $rule, Get $get): Unique {
                            $semester = $get('semester');

                            return $rule->where(
                                'semester',
                                $semester instanceof Semester ? $semester->value : $semester,
                            );
                        },
                    )
                    ->validationMessages([
                        'unique' => 'Tahun ajaran dengan semester ini sudah ada.',
                    ]),
                Select::make('semester')
                    ->label('Semester')
                    ->options(Semester::class)
                    ->required()
                    ->native(false),
                Toggle::make('is_aktif')
                    ->label('Aktif')
                    ->helperText('Hanya satu tahun ajaran yang dapat aktif.')
                    ->default(false),
            ]);
    }
}
