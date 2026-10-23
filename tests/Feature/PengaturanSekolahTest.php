<?php

use App\Enums\JenjangSekolah;
use App\Filament\Pages\PengaturanSekolah as PengaturanSekolahPage;
use App\Models\PengaturanSekolah;
use App\Models\User;
use App\Services\PengaturanSekolahService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('creates a singleton settings row with safe defaults', function () {
    $service = app(PengaturanSekolahService::class);

    $settings = $service->get();
    $sameSettings = $service->get();

    expect($settings->is($sameSettings))->toBeTrue()
        ->and(PengaturanSekolah::query()->count())->toBe(1)
        ->and($settings->jenjang)->toBe(JenjangSekolah::Sd)
        ->and($settings->batas_pelanggaran)->toBe(5)
        ->and($settings->auto_tangguhkan)->toBeTrue()
        ->and($settings->idle_timeout_menit)->toBe(10);
});

it('updates the singleton settings row', function () {
    $settings = app(PengaturanSekolahService::class)->update([
        'nama_sekolah' => 'SMK Negeri 1 Contoh',
        'jenjang' => JenjangSekolah::Smk,
        'npsn' => '12345678',
        'alamat' => 'Jl. Pendidikan No. 1',
        'kota' => 'Bandung',
        'nama_kepala_sekolah' => 'Drs. Contoh',
        'nip_kepala_sekolah' => '19800101 200501 1 001',
        'batas_pelanggaran' => 4,
        'auto_tangguhkan' => false,
        'idle_timeout_menit' => 15,
    ]);

    expect($settings->nama_sekolah)->toBe('SMK Negeri 1 Contoh')
        ->and($settings->jenjang)->toBe(JenjangSekolah::Smk)
        ->and($settings->batas_pelanggaran)->toBe(4)
        ->and($settings->auto_tangguhkan)->toBeFalse()
        ->and($settings->idle_timeout_menit)->toBe(15);

    $this->assertDatabaseHas('pengaturan_sekolah', [
        'nama_sekolah' => 'SMK Negeri 1 Contoh',
        'jenjang' => 'smk',
        'batas_pelanggaran' => 4,
        'auto_tangguhkan' => false,
        'idle_timeout_menit' => 15,
    ]);
});

it('shows the settings page to an active admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/pengaturan-sekolah')
        ->assertOk()
        ->assertSee('Pengaturan Sekolah')
        ->assertSee('Batas Pelanggaran');
});

it('redirects guests away from the settings page', function () {
    $this->get('/admin/pengaturan-sekolah')
        ->assertRedirect('/admin/login');
});

it('forbids gurus from accessing the settings page', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)
        ->get('/admin/pengaturan-sekolah')
        ->assertForbidden();
});

it('authorizes only active admins to manage school settings', function () {
    $settings = app(PengaturanSekolahService::class)->get();
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $inactiveAdmin = User::factory()->admin()->inactive()->create();

    expect($admin->can('viewAny', PengaturanSekolah::class))->toBeTrue()
        ->and($admin->can('update', $settings))->toBeTrue()
        ->and($guru->can('viewAny', PengaturanSekolah::class))->toBeFalse()
        ->and($guru->can('update', $settings))->toBeFalse()
        ->and($inactiveAdmin->can('viewAny', PengaturanSekolah::class))->toBeFalse();
});

it('saves settings submitted from the admin page', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(PengaturanSekolahPage::class)
        ->fillForm([
            'nama_sekolah' => 'SMA Negeri 2',
            'jenjang' => JenjangSekolah::Sma->value,
            'npsn' => '87654321',
            'alamat' => 'Jl. Pendidikan No. 2',
            'kota' => 'Jakarta',
            'nama_kepala_sekolah' => 'Drs. Kepala Sekolah',
            'nip_kepala_sekolah' => '19700101 199501 1 001',
            'batas_pelanggaran' => 4,
            'auto_tangguhkan' => false,
            'idle_timeout_menit' => 15,
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('pengaturan_sekolah', [
        'nama_sekolah' => 'SMA Negeri 2',
        'jenjang' => 'sma',
        'batas_pelanggaran' => 4,
        'auto_tangguhkan' => false,
        'idle_timeout_menit' => 15,
    ]);
});

it('rejects invalid school settings', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(PengaturanSekolahPage::class)
        ->fillForm([
            'nama_sekolah' => '',
            'jenjang' => 'tidak-valid',
            'batas_pelanggaran' => 0,
            'idle_timeout_menit' => 0,
        ])
        ->call('save')
        ->assertHasFormErrors([
            'nama_sekolah',
            'jenjang',
            'batas_pelanggaran',
            'idle_timeout_menit',
        ]);
});
