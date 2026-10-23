<?php

use App\Enums\UserRole;
use App\Filament\Resources\Gurus\Pages\CreateGuru;
use App\Filament\Resources\Gurus\Pages\EditGuru;
use App\Filament\Resources\Siswas\Pages\CreateSiswa;
use App\Filament\Resources\Siswas\Pages\EditSiswa;
use App\Models\Agama;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\Kelas;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use App\Services\ExcelImportService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

uses(RefreshDatabase::class);

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

function writeXlsxForTest(array $rows): string
{
    $path = tempnam(sys_get_temp_dir(), 'cbt_test_');

    $writer = new Writer;
    $writer->openToFile($path);

    foreach ($rows as $row) {
        $writer->addRow(Row::fromValues($row));
    }

    $writer->close();

    return $path;
}

it('shows the school administration pages to an active admin', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    foreach (['/admin/jurusan', '/admin/kelas', '/admin/mapel', '/admin/guru', '/admin/siswa'] as $path) {
        $this->get($path)->assertOk();
    }
});

it('forbids gurus from the school administration admin pages', function () {
    $guru = User::factory()->guru()->create(['is_aktif' => true]);

    $this->actingAs($guru)->get('/admin/siswa')->assertForbidden();
});

it('creates and updates a guru account together with its profile', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateGuru::class)
        ->fillForm([
            'username' => 'bu-ani',
            'email' => 'ani@example.test',
            'password' => 'password123',
            'is_aktif' => true,
            'nama' => 'Bu Ani',
            'nip' => '198001012006041099',
            'jenis_kelamin' => 'P',
            'no_hp' => '08123456789',
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $user = User::query()->where('username', 'bu-ani')->firstOrFail();
    $guru = Guru::query()->where('user_id', $user->id)->firstOrFail();

    expect($user->role)->toBe(UserRole::Guru)
        ->and($user->is_aktif)->toBeTrue()
        ->and($guru->nama)->toBe('Bu Ani')
        ->and($guru->jenis_kelamin)->toBe('P');

    Livewire::test(EditGuru::class, ['record' => $guru->getKey()])
        ->fillForm([
            'username' => 'bu-ani',
            'email' => 'ani@example.test',
            'password' => '',
            'is_aktif' => true,
            'nama' => 'Ibu Ani',
            'nip' => '198001012006041099',
            'jenis_kelamin' => 'P',
            'no_hp' => '08123456780',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($guru->fresh()->nama)->toBe('Ibu Ani')
        ->and($user->fresh()->name)->toBe('Ibu Ani')
        ->and($user->fresh()->email)->toBe('ani@example.test');
});

it('creates a student, encrypts the security key, and preserves it when editing', function () {
    $admin = User::factory()->admin()->create();
    $agama = Agama::factory()->create(['nama' => 'Islam']);
    $kelas = Kelas::factory()->create(['nama' => 'X TKJ 1', 'tingkat' => 10]);

    $this->actingAs($admin);

    Livewire::test(CreateSiswa::class)
        ->fillForm([
            'nis' => '20260001',
            'nisn' => '0099000001',
            'nama' => 'Siswa Satu',
            'jenis_kelamin' => 'L',
            'tempat_lahir' => 'Bandung',
            'tanggal_lahir' => '2008-01-01',
            'agama_id' => $agama->id,
            'kelas_id' => $kelas->id,
            'username' => 'siswasatu',
            'security_key' => 'RAHASIA123',
            'is_aktif' => true,
        ])
        ->call('create')
        ->assertHasNoFormErrors()
        ->assertNotified();

    $siswa = Siswa::query()->where('nis', '20260001')->firstOrFail();
    $rawKey = DB::table('siswa')->where('id', $siswa->id)->value('security_key');

    expect($siswa->security_key)->toBe('RAHASIA123')
        ->and($rawKey)->not->toBe('RAHASIA123')
        ->and($siswa->username)->toBe('siswasatu');

    Livewire::test(EditSiswa::class, ['record' => $siswa->getKey()])
        ->fillForm([
            'nis' => '20260001',
            'nisn' => '0099000001',
            'nama' => 'Siswa Satu Diperbarui',
            'jenis_kelamin' => 'L',
            'agama_id' => $agama->id,
            'kelas_id' => $kelas->id,
            'username' => 'siswasatu',
            'security_key' => '',
            'is_aktif' => true,
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($siswa->fresh()->security_key)->toBe('RAHASIA123')
        ->and($siswa->fresh()->nama)->toBe('Siswa Satu Diperbarui');
});

it('imports jurusan rows and reports invalid rows', function () {
    $path = writeXlsxForTest([
        ['kode', 'nama'],
        ['TKJ', 'Teknik Komputer dan Jaringan'],
        ['AKL', 'Akuntansi'],
        ['', 'Tanpa Kode'],
    ]);

    try {
        $result = app(ExcelImportService::class)->import('jurusan', $path);
    } finally {
        @unlink($path);
    }

    expect($result->created)->toBe(2)
        ->and($result->hasErrors())->toBeTrue()
        ->and($result->errors[0]['row'])->toBe(4);

    $this->assertDatabaseHas('jurusan', ['kode' => 'TKJ', 'nama' => 'Teknik Komputer dan Jaringan']);
    $this->assertDatabaseHas('jurusan', ['kode' => 'AKL']);
});

it('imports students with a generated security key and class reference', function () {
    Agama::factory()->create(['nama' => 'Islam']);
    Kelas::factory()->create(['nama' => 'X TKJ 1', 'tingkat' => 10]);

    $path = writeXlsxForTest([
        [
            'nis', 'nisn', 'nama', 'jenis_kelamin', 'tempat_lahir', 'tanggal_lahir',
            'agama_nama', 'kelas_nama', 'username', 'security_key', 'is_aktif',
        ],
        ['20260002', '0099000002', 'Siswa Import', 'P', 'Bandung', '2008-02-02', 'Islam', 'X TKJ 1', 'siswaimport', '', 1],
    ]);

    try {
        $result = app(ExcelImportService::class)->import('siswa', $path);
    } finally {
        @unlink($path);
    }

    $siswa = Siswa::query()->where('nis', '20260002')->firstOrFail();

    expect($result->created)->toBe(1)
        ->and($result->hasErrors())->toBeFalse()
        ->and($siswa->security_key)->not->toBeEmpty()
        ->and($siswa->kelas->nama)->toBe('X TKJ 1');
});

it('downloads an import template for an admin only', function () {
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->get(route('admin.import-template', 'jurusan'))
        ->assertOk()
        ->assertDownload('template-import-jurusan.xlsx');

    $guru = User::factory()->guru()->create();

    $this->actingAs($guru)
        ->get(route('admin.import-template', 'jurusan'))
        ->assertForbidden();
});

it('authorizes teachers to view only students in classes they teach', function () {
    $user = User::factory()->guru()->create();
    $guru = Guru::factory()->forUser($user)->create();
    $kelas = Kelas::factory()->create();
    $mapel = Mapel::factory()->create();
    $tahunAjaran = TahunAjaran::factory()->create();

    GuruMengajar::factory()->create([
        'guru_id' => $guru->id,
        'kelas_id' => $kelas->id,
        'mapel_id' => $mapel->id,
        'tahun_ajaran_id' => $tahunAjaran->id,
    ]);

    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);
    $other = Siswa::factory()->create();

    expect($user->can('view', $siswa))->toBeTrue()
        ->and($user->can('view', $other))->toBeFalse()
        ->and(config('auth.guards.siswa.provider'))->toBe('siswa')
        ->and(config('auth.providers.siswa.model'))->toBe(Siswa::class);
});
