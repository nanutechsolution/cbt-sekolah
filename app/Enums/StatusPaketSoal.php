<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StatusPaketSoal: string implements HasLabel
{
    case Draft = 'draft';
    case Siap = 'siap';

    public function getLabel(): string
    {
        return match ($this) {
            self::Draft => 'Draft',
            self::Siap => 'Siap',
        };
    }
}
