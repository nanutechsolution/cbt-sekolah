<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum JenisPelanggaran: string implements HasLabel
{
    case PindahTab = 'pindah_tab';
    case AltTab = 'alt_tab';
    case CopyPaste = 'copy_paste';
    case KlikKanan = 'klik_kanan';
    case Lainnya = 'lainnya';

    public function getLabel(): string
    {
        return match ($this) {
            self::PindahTab => 'Pindah Tab',
            self::AltTab => 'Alt+Tab',
            self::CopyPaste => 'Copy/Paste',
            self::KlikKanan => 'Klik Kanan',
            self::Lainnya => 'Lainnya',
        };
    }
}
