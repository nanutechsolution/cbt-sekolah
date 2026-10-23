<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenjangSekolah: string implements HasLabel
{
    case Sd = 'sd';
    case Smp = 'smp';
    case Sma = 'sma';
    case Smk = 'smk';

    public function getLabel(): string
    {
        return match ($this) {
            self::Sd => 'SD',
            self::Smp => 'SMP',
            self::Sma => 'SMA',
            self::Smk => 'SMK',
        };
    }
}
