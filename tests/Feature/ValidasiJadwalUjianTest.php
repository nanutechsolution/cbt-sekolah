<?php

use App\Filament\Guru\Resources\JadwalUjians\Pages\CreateJadwalUjian as GuruCreateJadwalUjian;
use App\Filament\Resources\JadwalUjians\Pages\CreateJadwalUjian;
use App\Filament\Resources\JadwalUjians\Pages\EditJadwalUjian;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\JadwalUjian;
use App\Models\Kelas;
use App\Models\PaketSoal;
use App\Models\Soal;
use App\Models\User;
use App\Services\JadwalUjianService;
use Carbon\CarbonInterface;
use Filament\Facades\Filament;
use Filament\Forms\Components\DateTimePicker;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{paket: PaketSoal, kelas: Kelas}
 */
function siapkanDataValidasiJadwal(): array
{
    $paket = PaketSoal::factory()->siap()->create(['tingkat' => 10]);

    Soal::factory()->create(['paket_soal_id' => $paket->id]);

    return [
        'paket' => $paket,
        'kelas' => Kelas::factory()->tingkat(10)->create(),
    ];
}

/**
 * @param  array{paket: PaketSoal, kelas: Kelas}  $data
 * @return array<string, mixed>
 */
function isianJadwal(array $data, CarbonInterface $mulai, CarbonInterface $selesai, int $durasi = 60): array
{
    return [
        'paket_soal_id' => $data['paket']->id,
        'nama' => 'UTS Matematika',
        'mulai' => $mulai->format('Y-m-d H:i:s'),
        'selesai' => $selesai->format('Y-m-d H:i:s'),
        'durasi_menit' => $durasi,
        'kelas' => [$data['kelas']->id],
    ];
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));

    $this->actingAs(User::factory()->admin()->create());
});

it('memakai DateTimePicker non-native dengan format dan langkah yang diminta', function () {
    $schema = Livewire::test(CreateJadwalUjian::class)->instance()->getSchema('form');

    foreach (['mulai', 'selesai'] as $name) {
        $field = $schema->getComponent($name);

        expect($field)
            ->toBeInstanceOf(DateTimePicker::class)
            ->and($field->isNative())->toBeFalse()
            ->and($field->getDisplayFormat())->toBe('d/m/Y H:i')
            ->and($field->hasSeconds())->toBeFalse()
            ->and($field->getMinutesStep())->toBe(5)
            ->and($field->getLocale())->toBe('id')
            ->and($field->getDefaultState())->toBeNull();
    }
});

it('menolak waktu selesai yang sama dengan waktu mulai', function () {
    $data = siapkanDataValidasiJadwal();
    $mulai = now()->addDay()->startOfMinute();

    Livewire::test(CreateJadwalUjian::class)
        ->fillForm(isianJadwal($data, $mulai, $mulai))
        ->call('create')
        ->assertHasFormErrors(['selesai']);

    expect(JadwalUjian::query()->count())->toBe(0);
});

it('menolak waktu selesai yang lebih awal daripada waktu mulai', function () {
    $data = siapkanDataValidasiJadwal();
    $selesai = now()->addDay()->startOfMinute();

    Livewire::test(CreateJadwalUjian::class)
        ->fillForm(isianJadwal($data, $selesai->copy()->addHour(), $selesai))
        ->call('create')
        ->assertHasFormErrors(['selesai']);

    expect(JadwalUjian::query()->count())->toBe(0);
});

it('menolak jendela ujian yang lebih pendek daripada durasi', function () {
    $data = siapkanDataValidasiJadwal();
    $mulai = now()->addDay()->startOfMinute();

    Livewire::test(CreateJadwalUjian::class)
        ->fillForm(isianJadwal($data, $mulai, $mulai->copy()->addMinutes(30), durasi: 60))
        ->call('create')
        ->assertHasFormErrors(['selesai']);

    expect(JadwalUjian::query()->count())->toBe(0);
});

it('menerima jendela ujian yang sama panjang dengan durasi', function () {
    $data = siapkanDataValidasiJadwal();
    $mulai = now()->addDay()->startOfMinute();

    Livewire::test(CreateJadwalUjian::class)
        ->fillForm(isianJadwal($data, $mulai, $mulai->copy()->addMinutes(60), durasi: 60))
        ->call('create')
        ->assertHasNoFormErrors();

    $jadwal = JadwalUjian::query()->sole();

    expect($jadwal->mulai->format('Y-m-d H:i:s'))->toBe($mulai->format('Y-m-d H:i:s'))
        ->and($jadwal->selesai->format('Y-m-d H:i:s'))->toBe($mulai->copy()->addMinutes(60)->format('Y-m-d H:i:s'));
});

it('menolak waktu mulai di masa lalu saat membuat jadwal baru', function () {
    $data = siapkanDataValidasiJadwal();
    $mulai = now()->subHour()->startOfMinute();

    Livewire::test(CreateJadwalUjian::class)
        ->fillForm(isianJadwal($data, $mulai, $mulai->copy()->addHours(2)))
        ->call('create')
        ->assertHasFormErrors(['mulai']);

    expect(JadwalUjian::query()->count())->toBe(0);
});

it('tetap mengizinkan penyuntingan jadwal yang sudah dimulai', function () {
    $data = siapkanDataValidasiJadwal();

    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $data['paket']->id,
        'mulai' => now()->subHour()->startOfMinute(),
        'selesai' => now()->addHour()->startOfMinute(),
        'durasi_menit' => 60,
    ]);
    $jadwal->kelas()->attach($data['kelas']);

    Livewire::test(EditJadwalUjian::class, ['record' => $jadwal->getKey()])
        ->fillForm([
            'nama' => 'UTS Matematika (revisi)',
            'mulai' => $jadwal->mulai->format('Y-m-d H:i:s'),
            'selesai' => $jadwal->selesai->format('Y-m-d H:i:s'),
        ])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($jadwal->refresh()->nama)->toBe('UTS Matematika (revisi)');
});

it('menerapkan validasi yang sama pada panel guru', function () {
    $data = siapkanDataValidasiJadwal();
    $guruUser = User::factory()->guru()->create();
    $guru = Guru::factory()->forUser($guruUser)->create();

    GuruMengajar::factory()->create([
        'guru_id' => $guru->id,
        'kelas_id' => $data['kelas']->id,
        'mapel_id' => $data['paket']->mapel_id,
        'tahun_ajaran_id' => $data['paket']->tahun_ajaran_id,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guruUser);

    $mulai = now()->addDay()->startOfMinute();

    Livewire::test(GuruCreateJadwalUjian::class)
        ->fillForm(isianJadwal($data, $mulai, $mulai->copy()->addMinutes(30), durasi: 60))
        ->call('create')
        ->assertHasFormErrors(['selesai']);

    expect(JadwalUjian::query()->count())->toBe(0);
});

it('menolak jendela ujian yang lebih pendek daripada durasi di service', function () {
    $data = siapkanDataValidasiJadwal();
    $admin = User::factory()->admin()->create();

    expect(fn () => app(JadwalUjianService::class)->simpan([
        'paket_soal_id' => $data['paket']->id,
        'nama' => 'UTS Matematika',
        'mulai' => now()->addDay()->startOfMinute()->format('Y-m-d H:i:s'),
        'selesai' => now()->addDay()->startOfMinute()->addMinutes(30)->format('Y-m-d H:i:s'),
        'durasi_menit' => 60,
        'kelas' => [$data['kelas']->id],
        'dibuat_oleh' => $admin->id,
    ]))->toThrow(ValidationException::class);

    expect(JadwalUjian::query()->count())->toBe(0);
});
