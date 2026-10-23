<?php

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Filament\Resources\PaketSoals\Pages\EditPaketSoal;
use App\Filament\Resources\PaketSoals\RelationManagers\SoalRelationManager;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\JadwalUjian;
use App\Models\JenisUjian;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\PesertaUjian;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PaketSoalLockService;
use App\Services\PaketSoalService;
use App\Services\SoalWordImportService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function buatPaketTerkunci(User $pemilik): PaketSoal
{
    $paket = PaketSoal::factory()->siap()->create([
        'dibuat_oleh' => $pemilik->id,
    ]);

    JadwalUjian::factory()->aktif()->create([
        'paket_soal_id' => $paket->id,
    ]);

    return $paket->refresh();
}

it('locks a package when it has an active schedule', function () {
    $paket = PaketSoal::factory()->create();
    JadwalUjian::factory()->aktif()->create([
        'paket_soal_id' => $paket->id,
    ]);

    expect($paket->fresh()->isTerkunci())->toBeTrue();
});

it('does not lock a package with only an inactive schedule and no participant', function () {
    $paket = PaketSoal::factory()->create();
    JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => StatusJadwalUjian::Nonaktif,
    ]);

    expect($paket->fresh()->isTerkunci())->toBeFalse();
});

it('locks a package when a participant has started, finished, or is suspended', function (StatusPesertaUjian $status) {
    $paket = PaketSoal::factory()->create();
    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => StatusJadwalUjian::Nonaktif,
    ]);

    PesertaUjian::factory()->create([
        'jadwal_ujian_id' => $jadwal->id,
        'status' => $status,
    ]);

    expect($paket->fresh()->isTerkunci())->toBeTrue();
})->with([
    'mengerjakan' => [StatusPesertaUjian::Mengerjakan],
    'selesai' => [StatusPesertaUjian::Selesai],
    'ditangguhkan' => [StatusPesertaUjian::Ditangguhkan],
]);

it('does not lock a package when a participant has not started', function () {
    $paket = PaketSoal::factory()->create();
    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => StatusJadwalUjian::Nonaktif,
    ]);

    PesertaUjian::factory()->create([
        'jadwal_ujian_id' => $jadwal->id,
        'status' => StatusPesertaUjian::Belum,
    ]);

    expect($paket->fresh()->isTerkunci())->toBeFalse();
});

it('memoizes the lock check for a package during a request', function () {
    $admin = User::factory()->admin()->create();
    $paket = buatPaketTerkunci($admin);
    $service = app(PaketSoalLockService::class);

    $service->lupakan($paket);
    DB::flushQueryLog();
    DB::enableQueryLog();

    $service->isTerkunci($paket);
    $service->isTerkunci($paket);

    expect(DB::getQueryLog())->toHaveCount(1);

    DB::disableQueryLog();
});

it('only allows package name and code changes when the package is locked', function () {
    $admin = User::factory()->admin()->create();
    $paket = buatPaketTerkunci($admin);

    $paket->update([
        'kode' => 'PS-SALINAN-EDIT',
        'nama' => 'Nama paket terkunci berubah',
    ]);

    expect($paket->fresh()->nama)->toBe('Nama paket terkunci berubah');

    $perubahan = [
        'mapel_id' => Mapel::factory()->create()->id,
        'jenis_ujian_id' => JenisUjian::factory()->create()->id,
        'tahun_ajaran_id' => TahunAjaran::factory()->create()->id,
        'tingkat' => $paket->tingkat >= 12 ? 11 : $paket->tingkat + 1,
        'acak_soal' => ! $paket->acak_soal,
        'acak_opsi' => ! $paket->acak_opsi,
        'tampilkan_nilai' => ! $paket->tampilkan_nilai,
        'status' => StatusPaketSoal::Draft,
    ];

    foreach ($perubahan as $field => $nilai) {
        $fresh = $paket->fresh();

        expect(fn () => $fresh->update([$field => $nilai]))
            ->toThrow(ValidationException::class);
    }
});

it('blocks deleting a locked package', function () {
    $admin = User::factory()->admin()->create();
    $paket = buatPaketTerkunci($admin);

    expect($admin->can('delete', $paket))->toBeFalse()
        ->and(fn () => $paket->delete())->toThrow(ValidationException::class)
        ->and($paket->fresh()->deleted_at)->toBeNull();
});

it('blocks question and option changes on a locked package', function () {
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->siap()->create([
        'dibuat_oleh' => $admin->id,
    ]);
    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
    ]);
    $opsi = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
    ]);

    JadwalUjian::factory()->aktif()->create([
        'paket_soal_id' => $paket->id,
    ]);

    expect(fn () => Soal::factory()->create(['paket_soal_id' => $paket->id]))
        ->toThrow(ValidationException::class)
        ->and(fn () => $soal->update(['pertanyaan' => 'Pertanyaan berubah']))
        ->toThrow(ValidationException::class)
        ->and(fn () => OpsiSoal::factory()->create(['soal_id' => $soal->id]))
        ->toThrow(ValidationException::class)
        ->and(fn () => $opsi->update(['teks' => 'Opsi berubah']))
        ->toThrow(ValidationException::class)
        ->and(fn () => $soal->delete())
        ->toThrow(ValidationException::class)
        ->and(fn () => $opsi->delete())
        ->toThrow(ValidationException::class);
});

it('does not allow changing a question type after it is created', function () {
    $paket = PaketSoal::factory()->create();
    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'tipe' => TipeSoal::Pg,
    ]);

    expect(fn () => $soal->update(['tipe' => TipeSoal::Essay]))
        ->toThrow(ValidationException::class);
});

it('still allows normal package and question edits when the package is unlocked', function () {
    $paket = PaketSoal::factory()->create();
    $soal = Soal::factory()->create(['paket_soal_id' => $paket->id]);
    $opsi = OpsiSoal::factory()->create(['soal_id' => $soal->id]);

    $paket->update([
        'tingkat' => $paket->tingkat >= 12 ? 11 : $paket->tingkat + 1,
        'acak_soal' => false,
        'status' => StatusPaketSoal::Siap,
    ]);
    $soal->update([
        'pertanyaan' => 'Pertanyaan yang boleh berubah.',
        'bobot' => 2,
    ]);
    $opsi->update(['teks' => 'Opsi yang boleh berubah.']);

    expect($paket->fresh()->acak_soal)->toBeFalse()
        ->and($soal->fresh()->pertanyaan)->toBe('Pertanyaan yang boleh berubah.')
        ->and($soal->fresh()->bobot)->toBe('2.00')
        ->and($opsi->fresh()->teks)->toBe('Opsi yang boleh berubah.');
});

it('rejects Word imports for a locked package', function () {
    $admin = User::factory()->admin()->create();
    $paket = buatPaketTerkunci($admin);

    expect(fn () => app(SoalWordImportService::class)->import($paket, '/tmp/file-tidak-ada.docx'))
        ->toThrow(ValidationException::class)
        ->and($paket->soal()->count())->toBe(0);
});

it('disables question creation and Word import on a locked package', function () {
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->siap()->create([
        'dibuat_oleh' => $admin->id,
    ]);
    Soal::factory()->create([
        'paket_soal_id' => $paket->id,
    ]);

    JadwalUjian::factory()->aktif()->create([
        'paket_soal_id' => $paket->id,
    ]);

    $this->actingAs($admin);

    $component = Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $paket->fresh(),
        'pageClass' => EditPaketSoal::class,
    ]);

    expect($component->instance()->isReadOnly())->toBeTrue()
        ->and($component->instance()->getTable()->getDescription())->toBe(PaketSoalLockService::PESAN_TERKUNCI);

    $component
        ->mountTableAction('create')
        ->assertTableActionNotMounted('create')
        ->mountTableAction('importWord')
        ->assertTableActionNotMounted('importWord');
});

it('rejects a duplicate question number in the same package', function () {
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->create([
        'dibuat_oleh' => $admin->id,
    ]);
    Soal::factory()->essay()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 1,
    ]);
    $kedua = Soal::factory()->essay()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 2,
    ]);

    $this->actingAs($admin);

    Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $paket,
        'pageClass' => EditPaketSoal::class,
    ])
        ->callTableAction('edit', $kedua, data: ['nomor' => 1])
        ->assertHasTableActionErrors(['nomor']);

    expect($kedua->fresh()->nomor)->toBe(2);
});

it('preserves option IDs when saving a question through the relation manager', function () {
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->create([
        'dibuat_oleh' => $admin->id,
    ]);
    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
    ]);
    OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'A',
        'teks' => 'Opsi A',
        'urutan' => 1,
        'is_benar' => false,
    ]);
    OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'B',
        'teks' => 'Opsi B',
        'urutan' => 2,
        'is_benar' => true,
    ]);

    $ids = $soal->opsi()->orderBy('id')->pluck('id')->all();

    $this->actingAs($admin);

    Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $paket,
        'pageClass' => EditPaketSoal::class,
    ])
        ->mountTableAction('edit', $soal)
        ->callMountedTableAction()
        ->assertHasNoTableActionErrors();

    expect($soal->fresh()->opsi()->orderBy('id')->pluck('id')->all())->toBe($ids);
});

it('disables package fields except name and code while locked', function () {
    $admin = User::factory()->admin()->create();
    $paket = buatPaketTerkunci($admin);

    $this->actingAs($admin);

    Livewire::test(EditPaketSoal::class, ['record' => $paket->getKey()])
        ->assertFormFieldDisabled('mapel_id')
        ->assertFormFieldDisabled('jenis_ujian_id')
        ->assertFormFieldDisabled('tahun_ajaran_id')
        ->assertFormFieldDisabled('tingkat')
        ->assertFormFieldDisabled('acak_soal')
        ->assertFormFieldDisabled('acak_opsi')
        ->assertFormFieldDisabled('tampilkan_nilai')
        ->assertFormFieldEnabled('kode')
        ->assertFormFieldEnabled('nama')
        ->fillForm([
            'kode' => 'PS-TERKUNCI-EDIT',
            'nama' => 'Nama dari form terkunci',
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($paket->fresh()->kode)->toBe('PS-TERKUNCI-EDIT')
        ->and($paket->fresh()->nama)->toBe('Nama dari form terkunci');
});

it('duplicates a package with questions, options, and copied media', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->siap()->create([
        'dibuat_oleh' => $admin->id,
    ]);
    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'gambar' => 'soal/gambar-asli.jpg',
        'audio' => 'soal/audio/audio-asli.mp3',
    ]);
    $opsi = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'gambar' => 'opsi-soal/gambar-opsi-asli.jpg',
    ]);

    Storage::disk('public')->put('soal/gambar-asli.jpg', 'gambar soal');
    Storage::disk('public')->put('soal/audio/audio-asli.mp3', 'audio soal');
    Storage::disk('public')->put('opsi-soal/gambar-opsi-asli.jpg', 'gambar opsi');

    JadwalUjian::factory()->aktif()->create([
        'paket_soal_id' => $paket->id,
    ]);

    $hasil = app(PaketSoalService::class)->duplikat($paket->fresh(), $admin);
    $hasil->paket->load('soal.opsi');
    $salinanSoal = $hasil->paket->soal->firstOrFail();
    $salinanOpsi = $salinanSoal->opsi->firstOrFail();

    expect($hasil->fileHilang)->toBe(0)
        ->and($hasil->paket->getKey())->not->toBe($paket->getKey())
        ->and($hasil->paket->kode)->not->toBe($paket->kode)
        ->and($hasil->paket->kode)->toContain('-COPY')
        ->and($hasil->paket->nama)->toContain('Salinan')
        ->and($hasil->paket->status)->toBe(StatusPaketSoal::Draft)
        ->and($hasil->paket->dibuat_oleh)->toBe($admin->id)
        ->and($hasil->paket->soal)->toHaveCount(1)
        ->and($salinanSoal->opsi)->toHaveCount(1)
        ->and($salinanSoal->gambar)->not->toBe($soal->gambar)
        ->and($salinanSoal->audio)->not->toBe($soal->audio)
        ->and($salinanOpsi->gambar)->not->toBe($opsi->gambar)
        ->and(Storage::disk('public')->exists($salinanSoal->gambar))->toBeTrue()
        ->and(Storage::disk('public')->exists($salinanSoal->audio))->toBeTrue()
        ->and(Storage::disk('public')->exists($salinanOpsi->gambar))->toBeTrue();
});

it('still duplicates a package and reports media files that cannot be copied', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->siap()->create([
        'dibuat_oleh' => $admin->id,
    ]);
    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'gambar' => 'soal/hilang.jpg',
        'audio' => 'soal/audio/hilang.mp3',
    ]);
    OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'gambar' => 'opsi-soal/hilang.jpg',
    ]);

    JadwalUjian::factory()->aktif()->create([
        'paket_soal_id' => $paket->id,
    ]);

    $hasil = app(PaketSoalService::class)->duplikat($paket->fresh(), $admin);
    $hasil->paket->load('soal.opsi');
    $salinanSoal = $hasil->paket->soal->firstOrFail();

    expect($hasil->fileHilang)->toBe(3)
        ->and($hasil->paket->exists)->toBeTrue()
        ->and($hasil->paket->soal)->toHaveCount(1)
        ->and($salinanSoal->gambar)->toBeNull()
        ->and($salinanSoal->audio)->toBeNull()
        ->and($salinanSoal->opsi->firstOrFail()->gambar)->toBeNull();
});

it('allows duplication only for users who can view the package', function () {
    $admin = User::factory()->admin()->create();
    $pemilik = User::factory()->guru()->create();
    Guru::factory()->forUser($pemilik)->create();
    $guruLain = User::factory()->guru()->create();
    Guru::factory()->forUser($guruLain)->create();

    $paket = PaketSoal::factory()->create([
        'dibuat_oleh' => $pemilik->id,
    ]);

    $mapel = Mapel::factory()->create();
    $kelas = Kelas::factory()->create(['tingkat' => 10]);
    $tahun = TahunAjaran::factory()->create();

    GuruMengajar::factory()->create([
        'guru_id' => $guruLain->guru->id,
        'kelas_id' => $kelas->id,
        'mapel_id' => $mapel->id,
        'tahun_ajaran_id' => $tahun->id,
    ]);

    expect($pemilik->can('duplicate', $paket))->toBeTrue()
        ->and($admin->can('duplicate', $paket))->toBeTrue()
        ->and($guruLain->can('duplicate', $paket))->toBeFalse();
});

it('duplicates a locked package from the edit page action', function () {
    Storage::fake('public');

    $admin = User::factory()->admin()->create();
    $paket = buatPaketTerkunci($admin);

    $this->actingAs($admin);

    Livewire::test(EditPaketSoal::class, ['record' => $paket->getKey()])
        ->callAction('duplikat')
        ->assertNotified();

    expect(PaketSoal::query()->where('dibuat_oleh', $admin->id)->count())->toBe(2)
        ->and(PaketSoal::query()->where('kode', 'LIKE', '%-COPY')->exists())->toBeTrue();
});
