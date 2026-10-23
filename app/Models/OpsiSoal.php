<?php

namespace App\Models;

use App\Services\PaketSoalLockService;
use Database\Factories\OpsiSoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Table('opsi_soal')]
#[Fillable([
    'soal_id',
    'label',
    'teks',
    'gambar',
    'is_benar',
    'urutan',
    'pasangan',
])]
class OpsiSoal extends Model
{
    /** @use HasFactory<OpsiSoalFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::saving(function (OpsiSoal $opsi): void {
            $lockService = app(PaketSoalLockService::class);

            foreach ($opsi->soalIdsUntukGuard() as $soalId) {
                $lockService->pastikanSoalTidakTerkunci($soalId);
            }

            if (blank($opsi->urutan)) {
                $opsi->urutan = ((int) static::query()
                    ->where('soal_id', $opsi->soal_id)
                    ->max('urutan')) + 1;
            }

            if (blank($opsi->label)) {
                $opsi->label = chr(64 + min(26, max(1, (int) $opsi->urutan)));
            }
        });

        static::deleting(function (OpsiSoal $opsi): void {
            $lockService = app(PaketSoalLockService::class);

            foreach ($opsi->soalIdsUntukGuard() as $soalId) {
                $lockService->pastikanSoalTidakTerkunci($soalId);
            }
        });
    }

    /**
     * @return array<int, int>
     */
    private function soalIdsUntukGuard(): array
    {
        return collect([
            $this->getOriginal('soal_id'),
            $this->soal_id,
        ])
            ->filter()
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'is_benar' => 'boolean',
            'urutan' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Soal, $this>
     */
    public function soal(): BelongsTo
    {
        return $this->belongsTo(Soal::class);
    }
}
