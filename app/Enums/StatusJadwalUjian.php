<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StatusJadwalUjian: string implements HasLabel
{
    case Nonaktif = 'nonaktif';
    case Aktif = 'aktif';
    case Selesai = 'selesai';

    public function getLabel(): string
    {
        return match ($this) {
            self::Nonaktif => 'Nonaktif',
            self::Aktif => 'Aktif',
            self::Selesai => 'Selesai',
        };
    }
}
