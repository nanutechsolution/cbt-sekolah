<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum Semester: string implements HasLabel
{
    case Ganjil = 'ganjil';
    case Genap = 'genap';

    public function getLabel(): string
    {
        return match ($this) {
            self::Ganjil => 'Ganjil',
            self::Genap => 'Genap',
        };
    }
}
