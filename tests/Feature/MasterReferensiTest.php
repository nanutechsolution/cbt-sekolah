<?php

use App\Filament\Resources\Agamas\Pages\CreateAgama;
use App\Filament\Resources\Agamas\Pages\EditAgama;
use App\Filament\Resources\Agamas\Pages\ListAgamas;
use App\Filament\Resources\JenisUjians\Pages\CreateJenisUjian;
use App\Filament\Resources\JenisUjians\Pages\EditJenisUjian;
use App\Filament\Resources\JenisUjians\Pages\ListJenisUjians;
use App\Filament\Resources\Ruangs\Pages\CreateRuang;
use App\Filament\Resources\Ruangs\Pages\EditRuang;
use App\Filament\Resources\Ruangs\Pages\ListRuangs;
use App\Filament\Resources\Sesis\Pages\CreateSesi;
use App\Filament\Resources\Sesis\Pages\EditSesi;
use App\Filament\Resources\Sesis\Pages\ListSesis;
use App\Models\Agama;
use App\Models\JenisUjian;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\User;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

dataset('master reference pages', [
    'agama' => ['/admin/agama', 'Agama'],
    'ruang' => ['/admin/ruang', 'Ruang'],
    'sesi' => ['/admin/sesi', 'Sesi'],
    'jenis ujian' => ['/admin/jenis-ujian', 'Jenis Ujian'],
]);

dataset('master reference models', [
    'agama' => [Agama::class],
    'ruang' => [Ruang::class],
    'sesi' => [Sesi::class],
    'jenis ujian' => [JenisUjian::class],
]);

dataset('master reference resource pages', [
    'agama' => [Agama::class, ListAgamas::class, EditAgama::class],
    'ruang' => [Ruang::class, ListRuangs::class, EditRuang::class],
    'sesi' => [Sesi::class, ListSesis::class, EditSesi::class],
    'jenis ujian' => [JenisUjian::class, ListJenisUjians::class, EditJenisUjian::class],
]);

it('soft deletes and restores master reference records', function (string $modelClass) {
    $record = $modelClass::factory()->create();

    $record->delete();

    expect($record->trashed())->toBeTrue();
    $this->assertSoftDeleted($record->getTable(), ['id' => $record->getKey()]);

    $record->restore();

    expect($record->fresh()->trashed())->toBeFalse();
})->with('master reference models');

it('normalizes jenis ujian codes and casts room capacity', function () {
    $jenisUjian = JenisUjian::factory()->create(['kode' => ' uts ']);
    $ruang = Ruang::factory()->create(['kapasitas' => '25']);

    expect($jenisUjian->kode)->toBe('UTS')
        ->and($ruang->kapasitas)->toBe(25);
});

it('shows a master reference page to an active admin', function (string $path, string $heading) {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get($path)
        ->assertOk()
        ->assertSee($heading);
})->with('master reference pages');

it('redirects guests away from master reference pages', function (string $path, string $heading) {
    $this->get($path)->assertRedirect('/admin/login');
})->with('master reference pages');

it('forbids gurus from master reference admin pages', function (string $path, string $heading) {
    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)
        ->get($path)
        ->assertForbidden();
})->with('master reference pages');

it('creates an agama and rejects a duplicate trimmed name', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateAgama::class)
        ->fillForm(['nama' => '  Islam  '])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas('agama', ['nama' => 'Islam']);

    Livewire::test(CreateAgama::class)
        ->fillForm(['nama' => 'Islam'])
        ->call('create')
        ->assertHasFormErrors(['nama' => 'unique']);

    $this->assertDatabaseCount('agama', 1);
});

it('updates an agama without a false unique error', function () {
    $admin = User::factory()->admin()->create();
    $agama = Agama::factory()->create(['nama' => 'Islam']);

    $this->actingAs($admin);

    Livewire::test(EditAgama::class, ['record' => $agama->getKey()])
        ->fillForm(['nama' => 'Islam'])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($agama->fresh()->nama)->toBe('Islam');
});

it('creates a ruang with capacity and validates its name and capacity', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateRuang::class)
        ->fillForm(['nama' => 'Ruang 01', 'kapasitas' => 30])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas('ruang', [
        'nama' => 'Ruang 01',
        'kapasitas' => 30,
    ]);

    Livewire::test(CreateRuang::class)
        ->fillForm(['nama' => ' Ruang 01 ', 'kapasitas' => 10])
        ->call('create')
        ->assertHasFormErrors(['nama' => 'unique']);

    Livewire::test(CreateRuang::class)
        ->fillForm(['nama' => 'Ruang 02', 'kapasitas' => 0])
        ->call('create')
        ->assertHasFormErrors(['kapasitas' => 'min']);

    $this->assertDatabaseCount('ruang', 1);
});

it('creates a sesi and rejects an end time before the start time', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateSesi::class)
        ->fillForm([
            'nama' => 'Pagi',
            'jam_mulai' => '07:30',
            'jam_selesai' => '09:30',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $sesi = Sesi::query()->where('nama', 'Pagi')->firstOrFail();

    expect(substr($sesi->jam_mulai, 0, 5))->toBe('07:30')
        ->and(substr($sesi->jam_selesai, 0, 5))->toBe('09:30');

    Livewire::test(CreateSesi::class)
        ->fillForm([
            'nama' => 'Siang',
            'jam_mulai' => '13:00',
            'jam_selesai' => '12:00',
        ])
        ->call('create')
        ->assertHasFormErrors(['jam_selesai' => 'after']);

    $this->assertDatabaseMissing('sesi', ['nama' => 'Siang']);
});

it('creates a jenis ujian, normalizes its code, and rejects duplicates', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateJenisUjian::class)
        ->fillForm([
            'nama' => 'Ujian Tengah Semester',
            'kode' => 'uts',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $this->assertDatabaseHas('jenis_ujian', [
        'nama' => 'Ujian Tengah Semester',
        'kode' => 'UTS',
    ]);

    Livewire::test(CreateJenisUjian::class)
        ->fillForm([
            'nama' => 'Ujian Lain',
            'kode' => 'UTS',
        ])
        ->call('create')
        ->assertHasFormErrors(['kode' => 'unique']);

    Livewire::test(CreateJenisUjian::class)
        ->fillForm([
            'nama' => 'Ujian Salah',
            'kode' => 'UTS/1',
        ])
        ->call('create')
        ->assertHasFormErrors(['kode' => 'alpha_dash']);

    $this->assertDatabaseCount('jenis_ujian', 1);
});

it('updates a jenis ujian without a false unique error', function () {
    $admin = User::factory()->admin()->create();
    $jenisUjian = JenisUjian::factory()->create([
        'nama' => 'Ujian Tengah Semester',
        'kode' => 'UTS',
    ]);

    $this->actingAs($admin);

    Livewire::test(EditJenisUjian::class, ['record' => $jenisUjian->getKey()])
        ->fillForm([
            'nama' => 'Ujian Tengah Semester',
            'kode' => 'UTS',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($jenisUjian->fresh()->kode)->toBe('UTS');
});

it('soft deletes and restores a master reference record from Filament', function (
    string $modelClass,
    string $listPage,
    string $editPage,
) {
    $admin = User::factory()->admin()->create();
    $record = $modelClass::factory()->create();

    $this->actingAs($admin);

    Livewire::test($listPage)
        ->assertActionVisible(TestAction::make('delete')->table($record))
        ->callAction(TestAction::make('delete')->table($record));

    expect($record->fresh()->trashed())->toBeTrue();

    Livewire::test($editPage, ['record' => $record->getKey()])
        ->assertActionVisible('restore')
        ->callAction('restore');

    expect($record->fresh()->trashed())->toBeFalse();
})->with('master reference resource pages');

it('authorizes only active admins to manage master reference data', function (string $modelClass) {
    $record = $modelClass::factory()->create();
    $admin = User::factory()->admin()->create();
    $guru = User::factory()->guru()->create();
    $inactiveAdmin = User::factory()->admin()->inactive()->create();
    $inactiveGuru = User::factory()->guru()->inactive()->create();

    expect($admin->can('viewAny', $modelClass))->toBeTrue()
        ->and($admin->can('view', $record))->toBeTrue()
        ->and($admin->can('create', $modelClass))->toBeTrue()
        ->and($admin->can('update', $record))->toBeTrue()
        ->and($admin->can('delete', $record))->toBeTrue()
        ->and($admin->can('deleteAny', $modelClass))->toBeTrue()
        ->and($admin->can('restore', $record))->toBeTrue()
        ->and($admin->can('restoreAny', $modelClass))->toBeTrue()
        ->and($admin->can('forceDelete', $record))->toBeFalse()
        ->and($admin->can('forceDeleteAny', $modelClass))->toBeFalse()
        ->and($guru->can('viewAny', $modelClass))->toBeTrue()
        ->and($guru->can('view', $record))->toBeTrue()
        ->and($guru->can('create', $modelClass))->toBeFalse()
        ->and($guru->can('update', $record))->toBeFalse()
        ->and($guru->can('delete', $record))->toBeFalse()
        ->and($guru->can('deleteAny', $modelClass))->toBeFalse()
        ->and($guru->can('restore', $record))->toBeFalse()
        ->and($guru->can('restoreAny', $modelClass))->toBeFalse()
        ->and($guru->can('forceDelete', $record))->toBeFalse()
        ->and($guru->can('forceDeleteAny', $modelClass))->toBeFalse()
        ->and($inactiveAdmin->can('viewAny', $modelClass))->toBeFalse()
        ->and($inactiveAdmin->can('create', $modelClass))->toBeFalse()
        ->and($inactiveGuru->can('viewAny', $modelClass))->toBeFalse();
})->with('master reference models');
