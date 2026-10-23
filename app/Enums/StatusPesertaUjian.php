<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum StatusPesertaUjian: string implements HasLabel
{
    case Belum = 'belum';
    case Mengerjakan = 'mengerjakan';
    case Selesai = 'selesai';
    case Ditangguhkan = 'ditangguhkan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Belum => 'Belum Mengerjakan',
            self::Mengerjakan => 'Mengerjakan',
            self::Selesai => 'Selesai',
            self::Ditangguhkan => 'Ditangguhkan',
        };
    }
}
