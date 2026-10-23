<?php

use App\Filament\Resources\PenempatanPesertas\Pages\CreatePenempatanPeserta;
use App\Filament\Resources\PenempatanPesertas\Pages\ListPenempatanPesertas;
use App\Models\JenisUjian;
use App\Models\Kelas;
use App\Models\PenempatanPeserta;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PenempatanPesertaService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

/**
 * @return array{tahun_ajaran: TahunAjaran, jenis_ujian: JenisUjian, kelas: Kelas, ruang: Ruang, sesi: Sesi}
 */
function siapkanDataPenempatanPeserta(int $kapasitas = 30): array
{
    return [
        'tahun_ajaran' => TahunAjaran::factory()->create(),
        'jenis_ujian' => JenisUjian::factory()->create(),
        'kelas' => Kelas::factory()->create(),
        'ruang' => Ruang::factory()->create(['kapasitas' => $kapasitas]),
        'sesi' => Sesi::factory()->create(),
    ];
}

/**
 * @param  array{tahun_ajaran: TahunAjaran, jenis_ujian: JenisUjian, kelas: Kelas, ruang: Ruang, sesi: Sesi}  $data
 * @return array{tahun_ajaran_id: int, jenis_ujian_id: int, kelas_id: int, ruang_id: int, sesi_id: int, awalan_no_peserta: string}
 */
function dataAksiPenempatanPeserta(array $data, string $awalan = '2026-'): array
{
    return [
        'tahun_ajaran_id' => $data['tahun_ajaran']->id,
        'jenis_ujian_id' => $data['jenis_ujian']->id,
        'kelas_id' => $data['kelas']->id,
        'ruang_id' => $data['ruang']->id,
        'sesi_id' => $data['sesi']->id,
        'awalan_no_peserta' => $awalan,
    ];
}

it('formats participant numbers with the prefix and a three digit sequence', function () {
    $service = app(PenempatanPesertaService::class);

    expect($service->formatNomorPeserta('2026-', 1))->toBe('2026-001')
        ->and($service->formatNomorPeserta('2026-', 42))->toBe('2026-042');
});

it('places only active students, skips existing placements, and continues the number sequence', function () {
    $data = siapkanDataPenempatanPeserta();
    $siswaLama = Siswa::factory()->create([
        'kelas_id' => $data['kelas']->id,
        'nama' => 'Ahmad',
    ]);
    $siswaBaruPertama = Siswa::factory()->create([
        'kelas_id' => $data['kelas']->id,
        'nama' => 'Budi',
    ]);
    $siswaBaruKedua = Siswa::factory()->create([
        'kelas_id' => $data['kelas']->id,
        'nama' => 'Citra',
    ]);
    $siswaNonaktif = Siswa::factory()->inactive()->create([
        'kelas_id' => $data['kelas']->id,
        'nama' => 'Dewi',
    ]);

    PenempatanPeserta::factory()->create([
        'siswa_id' => $siswaLama->id,
        'tahun_ajaran_id' => $data['tahun_ajaran']->id,
        'jenis_ujian_id' => $data['jenis_ujian']->id,
        'ruang_id' => $data['ruang']->id,
        'sesi_id' => $data['sesi']->id,
        'no_peserta' => '2026-007',
    ]);

    $admin = User::factory()->admin()->create();

    $hasil = app(PenempatanPesertaService::class)->tempatkan(
        $admin,
        dataAksiPenempatanPeserta($data),
    );

    expect($hasil->dibuat)->toBe(2)
        ->and($hasil->dilewati)->toBe(1)
        ->and($hasil->nomorAwal)->toBe('2026-008')
        ->and($hasil->nomorAkhir)->toBe('2026-009')
        ->and($hasil->kapasitasTerlampaui())->toBeFalse();

    $this->assertDatabaseHas('penempatan_peserta', [
        'siswa_id' => $siswaBaruPertama->id,
        'no_peserta' => '2026-008',
    ]);
    $this->assertDatabaseHas('penempatan_peserta', [
        'siswa_id' => $siswaBaruKedua->id,
        'no_peserta' => '2026-009',
    ]);
    $this->assertDatabaseMissing('penempatan_peserta', [
        'siswa_id' => $siswaNonaktif->id,
    ]);
    $this->assertDatabaseCount('penempatan_peserta', 3);
});

it('places a class from the list action without creating duplicates on a second run', function () {
    $admin = User::factory()->admin()->create();
    $data = siapkanDataPenempatanPeserta();

    Siswa::factory()->count(2)->create([
        'kelas_id' => $data['kelas']->id,
    ]);
    $siswaNonaktif = Siswa::factory()->inactive()->create([
        'kelas_id' => $data['kelas']->id,
    ]);

    $payload = dataAksiPenempatanPeserta($data);

    $this->actingAs($admin);

    Livewire::test(ListPenempatanPesertas::class)
        ->callAction('tempatkanPeserta', data: $payload)
        ->assertHasNoFormErrors()
        ->assertNotified('Peserta berhasil ditempatkan');

    expect(PenempatanPeserta::query()->orderBy('no_peserta')->pluck('no_peserta')->all())
        ->toBe(['2026-001', '2026-002']);
    $this->assertDatabaseMissing('penempatan_peserta', [
        'siswa_id' => $siswaNonaktif->id,
    ]);

    Livewire::test(ListPenempatanPesertas::class)
        ->callAction('tempatkanPeserta', data: $payload)
        ->assertHasNoFormErrors()
        ->assertNotified('Tidak ada peserta baru ditempatkan');

    $this->assertDatabaseCount('penempatan_peserta', 2);
});

it('warns without blocking when the room and session capacity is exceeded', function () {
    $admin = User::factory()->admin()->create();
    $data = siapkanDataPenempatanPeserta(kapasitas: 1);

    Siswa::factory()->count(2)->create([
        'kelas_id' => $data['kelas']->id,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListPenempatanPesertas::class)
        ->callAction('tempatkanPeserta', data: dataAksiPenempatanPeserta($data))
        ->assertHasNoFormErrors()
        ->assertNotified('Kapasitas ruang terlampaui');

    $this->assertDatabaseCount('penempatan_peserta', 2);
});

it('rejects a duplicate student for the same year and exam type with a validation message', function () {
    $admin = User::factory()->admin()->create();
    $data = siapkanDataPenempatanPeserta();
    $siswa = Siswa::factory()->create([
        'kelas_id' => $data['kelas']->id,
    ]);

    PenempatanPeserta::factory()->create([
        'siswa_id' => $siswa->id,
        'tahun_ajaran_id' => $data['tahun_ajaran']->id,
        'jenis_ujian_id' => $data['jenis_ujian']->id,
        'ruang_id' => $data['ruang']->id,
        'sesi_id' => $data['sesi']->id,
        'no_peserta' => '2026-001',
    ]);

    $this->actingAs($admin);

    Livewire::test(CreatePenempatanPeserta::class)
        ->fillForm([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $data['tahun_ajaran']->id,
            'jenis_ujian_id' => $data['jenis_ujian']->id,
            'ruang_id' => $data['ruang']->id,
            'sesi_id' => $data['sesi']->id,
            'no_peserta' => '2026-002',
        ])
        ->call('create')
        ->assertHasFormErrors(['siswa_id' => 'unique'])
        ->assertSee('Siswa ini sudah ditempatkan pada tahun ajaran dan jenis ujian yang dipilih.');

    $this->assertDatabaseCount('penempatan_peserta', 1);
});

it('allows the same student to be placed for a different exam type', function () {
    $admin = User::factory()->admin()->create();
    $data = siapkanDataPenempatanPeserta();
    $jenisUjianLain = JenisUjian::factory()->create();
    $siswa = Siswa::factory()->create([
        'kelas_id' => $data['kelas']->id,
    ]);

    PenempatanPeserta::factory()->create([
        'siswa_id' => $siswa->id,
        'tahun_ajaran_id' => $data['tahun_ajaran']->id,
        'jenis_ujian_id' => $data['jenis_ujian']->id,
        'ruang_id' => $data['ruang']->id,
        'sesi_id' => $data['sesi']->id,
        'no_peserta' => '2026-001',
    ]);

    $this->actingAs($admin);

    Livewire::test(CreatePenempatanPeserta::class)
        ->fillForm([
            'siswa_id' => $siswa->id,
            'tahun_ajaran_id' => $data['tahun_ajaran']->id,
            'jenis_ujian_id' => $jenisUjianLain->id,
            'ruang_id' => $data['ruang']->id,
            'sesi_id' => $data['sesi']->id,
            'no_peserta' => '2026-001',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseCount('penempatan_peserta', 2);
});

it('allows only active admins to manage placements', function () {
    $record = PenempatanPeserta::factory()->create();
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $inactiveAdmin = User::factory()->admin()->inactive()->create();
    $inactiveGuru = User::factory()->guru()->inactive()->create();

    expect($admin->can('viewAny', PenempatanPeserta::class))->toBeTrue()
        ->and($admin->can('view', $record))->toBeTrue()
        ->and($admin->can('create', PenempatanPeserta::class))->toBeTrue()
        ->and($admin->can('update', $record))->toBeTrue()
        ->and($admin->can('delete', $record))->toBeTrue()
        ->and($admin->can('deleteAny', PenempatanPeserta::class))->toBeTrue()
        ->and($admin->can('tempatkan', PenempatanPeserta::class))->toBeTrue()
        ->and($guru->can('viewAny', PenempatanPeserta::class))->toBeFalse()
        ->and($guru->can('view', $record))->toBeFalse()
        ->and($guru->can('create', PenempatanPeserta::class))->toBeFalse()
        ->and($guru->can('update', $record))->toBeFalse()
        ->and($guru->can('delete', $record))->toBeFalse()
        ->and($guru->can('deleteAny', PenempatanPeserta::class))->toBeFalse()
        ->and($guru->can('tempatkan', PenempatanPeserta::class))->toBeFalse()
        ->and($inactiveAdmin->can('viewAny', PenempatanPeserta::class))->toBeFalse()
        ->and($inactiveAdmin->can('tempatkan', PenempatanPeserta::class))->toBeFalse()
        ->and($inactiveGuru->can('viewAny', PenempatanPeserta::class))->toBeFalse()
        ->and($inactiveGuru->can('tempatkan', PenempatanPeserta::class))->toBeFalse();
});

it('forbids a guru from opening the placement page', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)
        ->get('/admin/penempatan-peserta')
        ->assertForbidden();
});

it('rejects a guru who executes a mounted placement action', function () {
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $data = siapkanDataPenempatanPeserta();

    Siswa::factory()->create([
        'kelas_id' => $data['kelas']->id,
    ]);

    $payload = dataAksiPenempatanPeserta($data);

    $this->actingAs($admin);

    $page = Livewire::test(ListPenempatanPesertas::class)
        ->assertActionVisible('tempatkanPeserta')
        ->mountAction('tempatkanPeserta')
        ->fillForm($payload);

    $this->actingAs($guru);

    $page->callMountedAction()
        ->assertStatus(403);

    $this->assertDatabaseCount('penempatan_peserta', 0);
});

it('rejects a guru inside the placement service before writing data', function () {
    $guru = User::factory()->guru()->create();
    $data = siapkanDataPenempatanPeserta();

    Siswa::factory()->create([
        'kelas_id' => $data['kelas']->id,
    ]);

    expect(fn () => app(PenempatanPesertaService::class)->tempatkan(
        $guru,
        dataAksiPenempatanPeserta($data),
    ))->toThrow(AuthorizationException::class);

    $this->assertDatabaseCount('penempatan_peserta', 0);
});
