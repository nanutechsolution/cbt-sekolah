<?php

namespace App\Enums;

use Filament\Support\Contracts\HasLabel;

enum TipeSoal: string implements HasLabel
{
    case Pg = 'pg';
    case PgKompleks = 'pg_kompleks';
    case BenarSalah = 'benar_salah';
    case IsianSingkat = 'isian_singkat';
    case Essay = 'essay';
    case Menjodohkan = 'menjodohkan';
    case BenarSalahPernyataan = 'benar_salah_pernyataan';
    case Urutan = 'urutan';

    public function getLabel(): string
    {
        return match ($this) {
            self::Pg => 'Pilihan Ganda',
            self::PgKompleks => 'Pilihan Ganda Kompleks',
            self::BenarSalah => 'Benar/Salah',
            self::IsianSingkat => 'Isian Singkat',
            self::Essay => 'Essay',
            self::Menjodohkan => 'Menjodohkan',
            self::BenarSalahPernyataan => 'Benar/Salah Pernyataan',
            self::Urutan => 'Urutan',
        };
    }

    public function isObjective(): bool
    {
        return $this !== self::Essay;
    }

    /**
     * Tipe yang boleh dipilih saat membuat soal baru.
     * Essay ditunda dan tiga tipe lain disembunyikan sampai implementasinya lengkap.
     */
    public function bolehDibuat(): bool
    {
        return in_array($this, [
            self::Pg,
            self::PgKompleks,
            self::BenarSalah,
            self::IsianSingkat,
        ], true);
    }

    public function usesOptions(): bool
    {
        return in_array($this, [
            self::Pg,
            self::PgKompleks,
            self::BenarSalah,
            self::IsianSingkat,
            self::Menjodohkan,
            self::BenarSalahPernyataan,
            self::Urutan,
        ], true);
    }

    public function usesTextAnswer(): bool
    {
        return in_array($this, [self::IsianSingkat, self::Essay], true);
    }

    /**
     * Pilihan tipe soal untuk pembuatan soal baru.
     *
     * @return array<string, string>
     */
    public static function initialOptions(): array
    {
        return collect(self::cases())
            ->filter(fn (self $tipe): bool => $tipe->bolehDibuat())
            ->mapWithKeys(fn (self $tipe): array => [$tipe->value => $tipe->getLabel()])
            ->all();
    }
}
