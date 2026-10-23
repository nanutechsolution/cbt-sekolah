<?php

namespace App\Services;

use App\Models\PaketSoal;

class DuplikatPaketResult
{
    public function __construct(
        public PaketSoal $paket,
        public int $fileHilang = 0,
    ) {}
}
