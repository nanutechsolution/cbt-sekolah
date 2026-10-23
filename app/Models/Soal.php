<?php

namespace App\Models;

use App\Enums\TipeSoal;
use App\Services\PaketSoalLockService;
use Database\Factories\SoalFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Validation\ValidationException;

#[Table('soal')]
#[Fillable([
    'paket_soal_id',
    'nomor',
    'tipe',
    'pertanyaan',
    'gambar',
    'audio',
    'audio_maks_putar',
    'bobot',
    'pembahasan',
])]
class Soal extends Model
{
    /** @use HasFactory<SoalFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        static::creating(function (Soal $soal): void {
            app(PaketSoalLockService::class)->pastikanTidakTerkunci((int) $soal->paket_soal_id);

            if (blank($soal->nomor)) {
                $soal->nomor = ((int) static::query()
                    ->where('paket_soal_id', $soal->paket_soal_id)
                    ->max('nomor')) + 1;
            }
        });

        static::updating(function (Soal $soal): void {
            if ($soal->isDirty('tipe')) {
                throw ValidationException::withMessages([
                    'tipe' => 'Tipe soal tidak dapat diubah setelah soal dibuat.',
                ]);
            }

            $lockService = app(PaketSoalLockService::class);

            foreach ($soal->paketSoalIdsUntukGuard() as $paketId) {
                $lockService->pastikanTidakTerkunci($paketId);
            }
        });

        static::deleting(function (Soal $soal): void {
            $lockService = app(PaketSoalLockService::class);

            foreach ($soal->paketSoalIdsUntukGuard() as $paketId) {
                $lockService->pastikanTidakTerkunci($paketId);
            }
        });

        static::saved(function (Soal $soal): void {
            app(PaketSoalLockService::class)->lupakanSoal($soal->getKey());
        });

        static::deleted(function (Soal $soal): void {
            app(PaketSoalLockService::class)->lupakanSoal($soal->getKey());
        });
    }

    /**
     * @return array<int, int>
     */
    private function paketSoalIdsUntukGuard(): array
    {
        return collect([
            $this->getOriginal('paket_soal_id'),
            $this->paket_soal_id,
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
            'nomor' => 'integer',
            'audio_maks_putar' => 'integer',
            'bobot' => 'decimal:2',
            'tipe' => TipeSoal::class,
        ];
    }

    /**
     * @return BelongsTo<PaketSoal, $this>
     */
    public function paketSoal(): BelongsTo
    {
        return $this->belongsTo(PaketSoal::class);
    }

    /**
     * @return HasMany<OpsiSoal, $this>
     */
    public function opsi(): HasMany
    {
        return $this->hasMany(OpsiSoal::class)->orderBy('urutan');
    }
}
