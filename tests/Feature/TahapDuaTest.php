<?php

use App\Enums\StatusPaketSoal;
use App\Enums\TipeSoal;
use App\Filament\Guru\Resources\PaketSoals\PaketSoalResource as GuruPaketSoalResource;
use App\Filament\Resources\PaketSoals\Pages\CreatePaketSoal;
use App\Filament\Resources\PaketSoals\Pages\EditPaketSoal;
use App\Filament\Resources\PaketSoals\RelationManagers\SoalRelationManager;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\JenisUjian;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\PaketSoalService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('shows the bank soal pages to an admin and the scoped page to a guru', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->get('/admin/paket-soal')->assertOk();
    $this->get('/admin/paket-soal/create')->assertOk();

    $guruUser = User::factory()->guru()->create();
    Guru::factory()->forUser($guruUser)->create();

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guruUser)
        ->get('/guru/paket-soal')
        ->assertOk();
});

it('creates a paket soal through the admin resource', function () {
    $admin = User::factory()->admin()->create();
    $mapel = Mapel::factory()->create(['nama' => 'Matematika']);
    $jenis = JenisUjian::factory()->create(['nama' => 'UTS']);
    $tahun = TahunAjaran::factory()->create(['nama' => '2026/2027']);

    $this->actingAs($admin);

    Livewire::test(CreatePaketSoal::class)
        ->fillForm([
            'kode' => 'PS-MTK-UTS',
            'nama' => 'UTS Matematika',
            'mapel_id' => $mapel->id,
            'jenis_ujian_id' => $jenis->id,
            'tahun_ajaran_id' => $tahun->id,
            'tingkat' => 10,
            'acak_soal' => true,
            'acak_opsi' => true,
            'tampilkan_nilai' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $paket = PaketSoal::query()->where('kode', 'PS-MTK-UTS')->firstOrFail();

    expect($paket->dibuat_oleh)->toBe($admin->id)
        ->and($paket->status)->toBe(StatusPaketSoal::Draft)
        ->and($paket->mapel_id)->toBe($mapel->id);
});

it('scopes the guru package list to packages they own or teach', function () {
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::factory()->forUser($guruUser)->create();
    $mapel = Mapel::factory()->create();
    $kelas = Kelas::factory()->create(['tingkat' => 10]);
    $tahun = TahunAjaran::factory()->create();

    GuruMengajar::factory()->create([
        'guru_id' => $guru->id,
        'kelas_id' => $kelas->id,
        'mapel_id' => $mapel->id,
        'tahun_ajaran_id' => $tahun->id,
    ]);

    $terlihat = PaketSoal::factory()->create([
        'mapel_id' => $mapel->id,
        'tingkat' => 10,
        'tahun_ajaran_id' => $tahun->id,
        'dibuat_oleh' => User::factory()->admin()->create()->id,
    ]);

    $tidakTerlihat = PaketSoal::factory()->create([
        'tingkat' => 10,
        'tahun_ajaran_id' => $tahun->id,
        'dibuat_oleh' => User::factory()->admin()->create()->id,
    ]);

    Auth::login($guruUser);

    $ids = GuruPaketSoalResource::getEloquentQuery()->pluck('id');

    expect($ids)->toContain($terlihat->id)
        ->and($ids)->not->toContain($tidakTerlihat->id);
});

it('marks a package ready only when every PG question has exactly one correct option', function () {
    $paket = PaketSoal::factory()->create();
    $soal = Soal::factory()->create(['paket_soal_id' => $paket->id, 'tipe' => TipeSoal::Pg]);

    OpsiSoal::factory()->create(['soal_id' => $soal->id, 'label' => 'A', 'urutan' => 1, 'is_benar' => true]);
    OpsiSoal::factory()->create(['soal_id' => $soal->id, 'label' => 'B', 'urutan' => 2, 'is_benar' => false]);

    $service = app(PaketSoalService::class);

    expect($service->markReady($paket)->status)->toBe(StatusPaketSoal::Siap);

    $broken = PaketSoal::factory()->create();
    $brokenSoal = Soal::factory()->create(['paket_soal_id' => $broken->id, 'tipe' => TipeSoal::Pg]);
    OpsiSoal::factory()->create(['soal_id' => $brokenSoal->id, 'is_benar' => false]);
    OpsiSoal::factory()->create(['soal_id' => $brokenSoal->id, 'is_benar' => false]);

    expect(fn () => $service->markReady($broken))->toThrow(ValidationException::class);

    $empty = PaketSoal::factory()->create();

    expect(fn () => $service->markReady($empty))->toThrow(ValidationException::class);
});

it('assigns question numbers and option ordering automatically', function () {
    $paket = PaketSoal::factory()->create();

    $first = Soal::factory()->create(['paket_soal_id' => $paket->id]);
    $second = Soal::factory()->create(['paket_soal_id' => $paket->id]);

    expect($first->nomor)->toBe(1)
        ->and($second->nomor)->toBe(2);

    $opsiA = OpsiSoal::factory()->create(['soal_id' => $first->id, 'label' => '', 'urutan' => null]);
    $opsiB = OpsiSoal::factory()->create(['soal_id' => $first->id, 'label' => '', 'urutan' => null]);

    expect($opsiA->urutan)->toBe(1)
        ->and($opsiA->label)->toBe('A')
        ->and($opsiB->urutan)->toBe(2)
        ->and($opsiB->label)->toBe('B');
});

it('creates a PG question with options through the relation manager', function () {
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->create(['dibuat_oleh' => $admin->id]);

    $this->actingAs($admin);

    Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $paket,
        'pageClass' => EditPaketSoal::class,
    ])
        ->callTableAction('create', data: [
            'tipe' => TipeSoal::Pg->value,
            'pertanyaan' => 'Berapa hasil 2 + 2?',
            'bobot' => 1,
            'opsi' => [
                ['label' => 'A', 'teks' => '3', 'is_benar' => false, 'urutan' => 1],
                ['label' => 'B', 'teks' => '4', 'is_benar' => true, 'urutan' => 2],
                ['label' => 'C', 'teks' => '5', 'is_benar' => false, 'urutan' => 3],
                ['label' => 'D', 'teks' => '6', 'is_benar' => false, 'urutan' => 4],
            ],
        ])
        ->assertHasNoTableActionErrors();

    $soal = Soal::query()->where('paket_soal_id', $paket->id)->firstOrFail();

    expect($soal->nomor)->toBe(1)
        ->and($soal->opsi)->toHaveCount(4)
        ->and($soal->opsi->where('is_benar', true))->toHaveCount(1);
});
