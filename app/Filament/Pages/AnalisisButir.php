<?php

namespace App\Filament\Pages;

use App\Enums\UserRole;
use App\Models\JadwalUjian;
use App\Models\PaketSoal;
use App\Services\AnalisisButirService;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use UnitEnum;

class AnalisisButir extends Page
{
    protected static ?string $title = 'Analisis Butir Soal';

    protected static ?string $navigationLabel = 'Analisis Butir Soal';

    protected static string|UnitEnum|null $navigationGroup = 'Ujian';

    protected static ?int $navigationSort = 5;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBarSquare;

    protected string $view = 'filament.pages.analisis-butir';

    public int|string|null $jadwal_ujian_id = null;

    public function mount(): void
    {
        $this->jadwal_ujian_id = request()->integer('jadwal') ?: null;

        if ($this->jadwal_ujian_id !== null) {
            $this->pastikanAkses((int) $this->jadwal_ujian_id);
        }
    }

    public function updatedJadwalUjianId(): void
    {
        if ($this->jadwal_ujian_id !== null && $this->jadwal_ujian_id !== '') {
            $this->pastikanAkses((int) $this->jadwal_ujian_id);
        }
    }

    /**
     * @return array<int, string>
     */
    public function getJadwalOptions(): array
    {
        $user = Auth::user();

        if ($user === null) {
            return [];
        }

        $query = JadwalUjian::query()
            ->with(['paketSoal.mapel'])
            ->orderByDesc('mulai');

        if ($user->role === UserRole::Guru) {
            $query->whereIn(
                'paket_soal_id',
                PaketSoal::query()->tampilUntukUser($user)->select('id'),
            );
        }

        return $query->get()
            ->mapWithKeys(fn (JadwalUjian $jadwal): array => [
                $jadwal->id => $jadwal->nama.' — '.($jadwal->paketSoal?->mapel?->nama ?: 'Tanpa mapel'),
            ])
            ->all();
    }

    public function getJadwalTerpilih(): ?JadwalUjian
    {
        if ($this->jadwal_ujian_id === null) {
            return null;
        }

        $jadwal = JadwalUjian::query()
            ->with(['paketSoal.mapel', 'paketSoal.jenisUjian'])
            ->find($this->jadwal_ujian_id);

        if ($jadwal !== null) {
            $this->pastikanAkses($jadwal->id);
        }

        return $jadwal;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getHasilAnalisis(): ?array
    {
        $jadwal = $this->getJadwalTerpilih();

        return $jadwal !== null
            ? app(AnalisisButirService::class)->analisis($jadwal)
            : null;
    }

    private function pastikanAkses(int $jadwalId): void
    {
        $jadwal = JadwalUjian::query()->find($jadwalId);

        abort_unless(
            $jadwal !== null && Auth::user()?->can('view', $jadwal),
            403,
        );
    }
}
