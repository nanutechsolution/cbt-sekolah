<?php

namespace App\Services;

class PenempatanPesertaResult
{
    public function __construct(
        public int $dibuat,
        public int $dilewati,
        public ?string $nomorAwal,
        public ?string $nomorAkhir,
        public int $jumlahPesertaRuangSesi,
        public ?int $kapasitasRuang,
        public string $namaRuang,
        public string $namaSesi,
    ) {}

    public function kapasitasTerlampaui(): bool
    {
        return $this->kapasitasRuang !== null
            && $this->jumlahPesertaRuangSesi > $this->kapasitasRuang;
    }
}
