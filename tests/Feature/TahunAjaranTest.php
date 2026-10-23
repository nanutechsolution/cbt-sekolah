<?php

use App\Enums\Semester;
use App\Filament\Resources\TahunAjarans\Pages\CreateTahunAjaran;
use App\Filament\Resources\TahunAjarans\Pages\EditTahunAjaran;
use App\Filament\Resources\TahunAjarans\Pages\ListTahunAjarans;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\TahunAjaranService;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('saves a tahun ajaran', function () {
    $tahunAjaran = app(TahunAjaranService::class)->save([
        'nama' => '2026/2027',
        'semester' => Semester::Ganjil,
        'is_aktif' => false,
    ]);

    expect($tahunAjaran->semester)->toBe(Semester::Ganjil)
        ->and($tahunAjaran->is_aktif)->toBeFalse();

    $this->assertDatabaseHas('tahun_ajaran', [
        'nama' => '2026/2027',
        'semester' => 'ganjil',
        'is_aktif' => false,
    ]);
});

it('keeps only one tahun ajaran active when saving', function () {
    $service = app(TahunAjaranService::class);

    $pertama = $service->save([
        'nama' => '2025/2026',
        'semester' => Semester::Ganjil,
        'is_aktif' => true,
    ]);

    $kedua = $service->save([
        'nama' => '2026/2027',
        'semester' => Semester::Ganjil,
        'is_aktif' => true,
    ]);

    expect(TahunAjaran::query()->where('is_aktif', true)->count())->toBe(1)
        ->and($service->active()?->is($kedua))->toBeTrue()
        ->and($pertama->refresh()->is_aktif)->toBeFalse();
});

it('activates a tahun ajaran and deactivates the previous one', function () {
    $pertama = TahunAjaran::factory()->create([
        'nama' => '2025/2026',
        'semester' => Semester::Ganjil,
        'is_aktif' => true,
    ]);

    $kedua = TahunAjaran::factory()->create([
        'nama' => '2026/2027',
        'semester' => Semester::Genap,
        'is_aktif' => false,
    ]);

    app(TahunAjaranService::class)->activate($kedua);

    expect($pertama->refresh()->is_aktif)->toBeFalse()
        ->and($kedua->refresh()->is_aktif)->toBeTrue()
        ->and(TahunAjaran::query()->where('is_aktif', true)->count())->toBe(1);
});

it('rejects a duplicate nama and semester combination', function () {
    $admin = User::factory()->admin()->create();

    TahunAjaran::factory()->create([
        'nama' => '2026/2027',
        'semester' => Semester::Ganjil,
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateTahunAjaran::class)
        ->fillForm([
            'nama' => '2026/2027',
            'semester' => Semester::Ganjil->value,
            'is_aktif' => false,
        ])
        ->call('create')
        ->assertHasFormErrors(['nama' => 'unique']);

    $this->assertDatabaseCount('tahun_ajaran', 1);
});

it('allows the same nama with a different semester', function () {
    $admin = User::factory()->admin()->create();

    TahunAjaran::factory()->create([
        'nama' => '2026/2027',
        'semester' => Semester::Ganjil,
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateTahunAjaran::class)
        ->fillForm([
            'nama' => '2026/2027',
            'semester' => Semester::Genap->value,
            'is_aktif' => false,
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $this->assertDatabaseHas('tahun_ajaran', [
        'nama' => '2026/2027',
        'semester' => 'genap',
    ]);
});

it('creates a tahun ajaran from the admin page and activates it', function () {
    $admin = User::factory()->admin()->create();
    $aktif = TahunAjaran::factory()->create([
        'nama' => '2025/2026',
        'semester' => Semester::Ganjil,
        'is_aktif' => true,
    ]);

    $this->actingAs($admin);

    Livewire::test(CreateTahunAjaran::class)
        ->fillForm([
            'nama' => '2026/2027',
            'semester' => Semester::Ganjil->value,
            'is_aktif' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas('tahun_ajaran', [
        'nama' => '2026/2027',
        'semester' => 'ganjil',
        'is_aktif' => true,
    ]);

    expect($aktif->refresh()->is_aktif)->toBeFalse();
});

it('updates a tahun ajaran from the admin page without a false unique error', function () {
    $admin = User::factory()->admin()->create();
    $tahunAjaran = TahunAjaran::factory()->create([
        'nama' => '2026/2027',
        'semester' => Semester::Ganjil,
        'is_aktif' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test(EditTahunAjaran::class, [
        'record' => $tahunAjaran->getKey(),
    ])
        ->fillForm([
            'nama' => '2026/2027',
            'semester' => Semester::Ganjil->value,
            'is_aktif' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($tahunAjaran->refresh()->is_aktif)->toBeTrue();
});

it('activates a tahun ajaran from the table action', function () {
    $admin = User::factory()->admin()->create();
    $aktif = TahunAjaran::factory()->create([
        'nama' => '2025/2026',
        'semester' => Semester::Ganjil,
        'is_aktif' => true,
    ]);
    $nonaktif = TahunAjaran::factory()->create([
        'nama' => '2026/2027',
        'semester' => Semester::Ganjil,
        'is_aktif' => false,
    ]);

    $this->actingAs($admin);

    Livewire::test(ListTahunAjarans::class)
        ->assertActionVisible(TestAction::make('aktifkan')->table($nonaktif))
        ->assertActionHidden(TestAction::make('aktifkan')->table($aktif))
        ->callAction(TestAction::make('aktifkan')->table($nonaktif));

    expect($aktif->refresh()->is_aktif)->toBeFalse()
        ->and($nonaktif->refresh()->is_aktif)->toBeTrue();
});

it('authorizes only active admins to manage tahun ajaran', function () {
    $tahunAjaran = TahunAjaran::factory()->create();
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $inactiveAdmin = User::factory()->admin()->inactive()->create();
    $inactiveGuru = User::factory()->guru()->inactive()->create();

    expect($admin->can('viewAny', TahunAjaran::class))->toBeTrue()
        ->and($admin->can('create', TahunAjaran::class))->toBeTrue()
        ->and($admin->can('update', $tahunAjaran))->toBeTrue()
        ->and($admin->can('delete', $tahunAjaran))->toBeTrue()
        ->and($admin->can('deleteAny', TahunAjaran::class))->toBeTrue()
        ->and($admin->can('restore', $tahunAjaran))->toBeFalse()
        ->and($admin->can('restoreAny', TahunAjaran::class))->toBeFalse()
        ->and($admin->can('forceDelete', $tahunAjaran))->toBeFalse()
        ->and($admin->can('forceDeleteAny', TahunAjaran::class))->toBeFalse()
        ->and($guru->can('viewAny', TahunAjaran::class))->toBeTrue()
        ->and($guru->can('view', $tahunAjaran))->toBeTrue()
        ->and($guru->can('create', TahunAjaran::class))->toBeFalse()
        ->and($guru->can('update', $tahunAjaran))->toBeFalse()
        ->and($guru->can('delete', $tahunAjaran))->toBeFalse()
        ->and($guru->can('deleteAny', TahunAjaran::class))->toBeFalse()
        ->and($inactiveAdmin->can('viewAny', TahunAjaran::class))->toBeFalse()
        ->and($inactiveGuru->can('viewAny', TahunAjaran::class))->toBeFalse();
});

it('shows the tahun ajaran page to an active admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get('/admin/tahun-ajaran')
        ->assertOk()
        ->assertSee('Tahun Ajaran');
});

it('redirects guests away from the tahun ajaran page', function () {
    $this->get('/admin/tahun-ajaran')
        ->assertRedirect('/admin/login');
});

it('forbids gurus from accessing the admin tahun ajaran page', function () {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)
        ->get('/admin/tahun-ajaran')
        ->assertForbidden();
});
