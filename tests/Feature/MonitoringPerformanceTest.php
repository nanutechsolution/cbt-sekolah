<?php

use App\Filament\Resources\JadwalUjians\Pages\EditJadwalUjian;
use App\Filament\Resources\JadwalUjians\RelationManagers\PesertaUjianRelationManager;
use App\Models\Agama;
use App\Models\JadwalUjian;
use App\Models\Kelas;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\User;
use Filament\Facades\Filament;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('adds the monitoring indexes to peserta_ujian', function () {
    expect(Schema::hasIndex('peserta_ujian', ['terakhir_aktif']))->toBeTrue()
        ->and(Schema::hasIndex('peserta_ujian', ['jadwal_ujian_id', 'status']))->toBeTrue();
});

it('keeps the monitoring query count constant as participants grow', function () {
    $admin = User::factory()->admin()->create();
    $jadwal = JadwalUjian::factory()->create();
    $kelas = Kelas::factory()->create();
    $agama = Agama::factory()->create();
    $siswa = Siswa::factory()->count(10)->create([
        'kelas_id' => $kelas->id,
        'agama_id' => $agama->id,
    ]);

    $this->actingAs($admin);

    $tempatkanSiswa = function (Siswa $item) use ($jadwal): void {
        PesertaUjian::factory()->create([
            'jadwal_ujian_id' => $jadwal->id,
            'siswa_id' => $item->id,
        ]);
    };

    $siswa->take(2)->each($tempatkanSiswa);

    $render = function () use ($jadwal): array {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $component = Livewire::test(PesertaUjianRelationManager::class, [
            'ownerRecord' => $jadwal,
            'pageClass' => EditJadwalUjian::class,
        ])->assertSuccessful();

        $jumlahQuery = count(DB::getQueryLog());

        DB::disableQueryLog();

        return [$component, $jumlahQuery];
    };

    // Warm up Filament/Livewire before measuring the first participant set.
    $render();

    [, $queryKecil] = $render();

    $siswa->slice(2)->each($tempatkanSiswa);

    [$halamanBesar, $queryBesar] = $render();

    expect($queryBesar)->toBe($queryKecil);

    $records = $halamanBesar->instance()->getTableRecords();

    if ($records instanceof Paginator) {
        $records = $records->getCollection();
    }

    expect($records)->toHaveCount(10);

    foreach ($records as $record) {
        expect($record->relationLoaded('siswa'))->toBeTrue()
            ->and($record->siswa->relationLoaded('kelas'))->toBeTrue();
    }
});
