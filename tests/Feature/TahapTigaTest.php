<?php

use App\Enums\JenisPelanggaran;
use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Http\Livewire\ExamRoom;
use App\Models\Guru;
use App\Models\JadwalUjian;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\User;
use App\Services\JadwalUjianService;
use App\Services\PengaturanSekolahService;
use App\Services\PesertaUjianService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{paket: PaketSoal, soal: Soal, benar: OpsiSoal, salah: OpsiSoal, kelas: Kelas, jadwal: JadwalUjian, siswa: Siswa}
 */
function siapkanUjianTiga(bool $acakSoal = false, bool $acakOpsi = false, ?int $durasi = 60): array
{
    $paket = PaketSoal::factory()->create([
        'status' => StatusPaketSoal::Siap,
        'acak_soal' => $acakSoal,
        'acak_opsi' => $acakOpsi,
        'tampilkan_nilai' => true,
    ]);

    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'tipe' => TipeSoal::Pg,
        'pertanyaan' => 'Berapa hasil 2 + 2?',
        'bobot' => 2,
    ]);

    $benar = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'A',
        'teks' => '4',
        'urutan' => 1,
        'is_benar' => true,
    ]);

    $salah = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'B',
        'teks' => '5',
        'urutan' => 2,
        'is_benar' => false,
    ]);

    $kelas = Kelas::factory()->tingkat($paket->tingkat)->create();
    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => StatusJadwalUjian::Aktif,
        'mulai' => now()->subMinute(),
        'selesai' => now()->addHours(2),
        'durasi_menit' => $durasi,
    ]);
    $jadwal->kelas()->attach($kelas);

    $siswa = Siswa::factory()->create([
        'kelas_id' => $kelas->id,
        'nama' => 'Siswa Ujian',
    ]);

    return compact('paket', 'soal', 'benar', 'salah', 'kelas', 'jadwal', 'siswa');
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('creates, validates, activates, and detects conflicting schedules', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Siap]);
    Soal::factory()->create(['paket_soal_id' => $paket->id]);

    $kelas = Kelas::factory()->tingkat($paket->tingkat)->create();
    $admin = User::factory()->admin()->create();
    $service = app(JadwalUjianService::class);

    $jadwal = $service->simpan([
        'paket_soal_id' => $paket->id,
        'nama' => 'Ujian Tengah Semester',
        'mulai' => now()->addHour()->format('Y-m-d H:i:s'),
        'selesai' => now()->addHours(3)->format('Y-m-d H:i:s'),
        'durasi_menit' => 90,
        'kelas' => [$kelas->id],
        'dibuat_oleh' => $admin->id,
    ]);

    expect($jadwal->status)->toBe(StatusJadwalUjian::Nonaktif)
        ->and($jadwal->kelas->pluck('id')->all())->toBe([$kelas->id])
        ->and($jadwal->durasi_menit)->toBe(90);

    $service->aktifkan($jadwal);

    expect($jadwal->refresh()->status)->toBe(StatusJadwalUjian::Aktif);

    expect(fn () => $service->simpan([
        'paket_soal_id' => $paket->id,
        'nama' => 'Jadwal Bentrok',
        'mulai' => now()->addHour()->format('Y-m-d H:i:s'),
        'selesai' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'durasi_menit' => 60,
        'kelas' => [$kelas->id],
        'dibuat_oleh' => $admin->id,
    ]))->toThrow(ValidationException::class);
});

it('rejects a draft package and a schedule for a different class level', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    Soal::factory()->create(['paket_soal_id' => $paket->id]);
    $kelas = Kelas::factory()->tingkat($paket->tingkat)->create();
    $admin = User::factory()->admin()->create();
    $service = app(JadwalUjianService::class);

    $base = [
        'paket_soal_id' => $paket->id,
        'nama' => 'Ujian',
        'mulai' => now()->addHour()->format('Y-m-d H:i:s'),
        'selesai' => now()->addHours(2)->format('Y-m-d H:i:s'),
        'durasi_menit' => 60,
        'kelas' => [$kelas->id],
        'dibuat_oleh' => $admin->id,
    ];

    expect(fn () => $service->simpan($base))->toThrow(ValidationException::class);

    $paket->update(['status' => StatusPaketSoal::Siap]);
    $base['kelas'] = [Kelas::factory()->tingkat($paket->tingkat + 1)->create()->id];

    expect(fn () => $service->simpan($base))->toThrow(ValidationException::class);
});

it('starts an exam once, persists shuffled order, and locks the device', function () {
    $data = siapkanUjianTiga();
    $service = app(PesertaUjianService::class);

    $peserta = $service->mulai($data['jadwal'], $data['siswa']);
    $token = $peserta->device_token;

    expect($peserta->status)->toBe(StatusPesertaUjian::Mengerjakan)
        ->and($token)->toHaveLength(80)
        ->and($peserta->urutan_soal)->toBe([$data['soal']->id])
        ->and($peserta->urutan_opsi[$data['soal']->id])->toBe([$data['benar']->id, $data['salah']->id])
        ->and($peserta->waktu_batas)->not->toBeNull();

    $resume = $service->mulai($data['jadwal'], $data['siswa'], $token);

    expect($resume->id)->toBe($peserta->id)
        ->and($resume->waktu_mulai->equalTo($peserta->waktu_mulai))->toBeTrue();

    expect(fn () => $service->mulai($data['jadwal'], $data['siswa'], 'token-perangkat-lain'))
        ->toThrow(ValidationException::class);
});

it('persists a unique answer row through autosave and validates ownership and options', function () {
    $data = siapkanUjianTiga();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $jawaban = $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    expect($jawaban->jawaban)->toBe(['opsi_id' => $data['benar']->id])
        ->and(JawabanSiswa::query()->count())->toBe(1);

    $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        $data['salah']->id,
    );

    expect(JawabanSiswa::query()->count())->toBe(1)
        ->and(JawabanSiswa::query()->firstOrFail()->jawaban)->toBe(['opsi_id' => $data['salah']->id]);

    expect(fn () => $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        999999,
    ))->toThrow(ValidationException::class);

    $siswaLain = Siswa::factory()->create();

    expect(fn () => $service->simpanJawaban(
        $peserta,
        $siswaLain,
        $peserta->device_token,
        $data['soal']->id,
        $data['benar']->id,
    ))->toThrow(AuthorizationException::class);

    $this->travel(61)->minutes();

    expect(fn () => $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        $data['benar']->id,
    ))->toThrow(ValidationException::class);

    $this->travelBack();
});

it('grades PG answers and submits idempotently', function () {
    $data = siapkanUjianTiga(durasi: 60);
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    $selesai = $service->kirim($peserta, $data['siswa'], $peserta->device_token);
    $waktuSelesai = $selesai->waktu_selesai;

    expect($selesai->status)->toBe(StatusPesertaUjian::Selesai)
        ->and((float) $selesai->nilai_pg)->toBe(100.0)
        ->and((float) $selesai->nilai)->toBe(100.0)
        ->and($selesai->nilai_essay)->toBeNull()
        ->and($selesai->waktu_selesai)->not->toBeNull();

    $lagi = $service->kirim($selesai, $data['siswa'], $peserta->device_token);

    expect($lagi->id)->toBe($selesai->id)
        ->and($lagi->waktu_selesai->equalTo($waktuSelesai))->toBeTrue()
        ->and(JawabanSiswa::query()->count())->toBe(1);

    expect(fn () => $service->simpanJawaban(
        $peserta->refresh(),
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        $data['salah']->id,
    ))->toThrow(ValidationException::class);
});

it('suspends a participant after the configured violation limit', function () {
    $data = siapkanUjianTiga();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    app(PengaturanSekolahService::class)->get()->update([
        'batas_pelanggaran' => 2,
        'auto_tangguhkan' => true,
    ]);

    expect($service->catatPelanggaran(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        JenisPelanggaran::PindahTab,
    ))->toBe(1);

    expect($peserta->refresh()->status)->toBe(StatusPesertaUjian::Mengerjakan);

    expect($service->catatPelanggaran(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        JenisPelanggaran::CopyPaste,
    ))->toBe(2);

    expect($peserta->refresh()->status)->toBe(StatusPesertaUjian::Ditangguhkan)
        ->and($peserta->pelanggaran()->count())->toBe(2);
});

it('authenticates a student and protects the student area', function () {
    $siswa = Siswa::factory()->create([
        'username' => 'siswa-login',
        'security_key' => 'KEY-123',
    ]);

    $this->post('/ujian/login', [
        'username' => 'SISWA-LOGIN',
        'security_key' => 'KEY-123',
    ])->assertRedirect('/ujian');

    $this->assertAuthenticatedAs($siswa, 'siswa');

    $this->get('/ujian')->assertOk()->assertSee($siswa->nama, false);

    $this->post('/ujian/logout')->assertRedirect('/ujian/login');
    $this->assertGuest('siswa');
});

it('rejects an invalid student security key', function () {
    Siswa::factory()->create([
        'username' => 'siswa-gagal',
        'security_key' => 'BENAR',
    ]);

    $this->post('/ujian/login', [
        'username' => 'siswa-gagal',
        'security_key' => 'SALAH',
    ])->assertSessionHasErrors('username');
});

it('runs the student start and exam room flow without exposing the answer key', function () {
    $data = siapkanUjianTiga();
    $this->actingAs($data['siswa'], 'siswa');

    $response = $this->post(route('siswa.ujian.mulai', $data['jadwal']));

    $peserta = PesertaUjian::query()->firstOrFail();

    $response->assertRedirect(route('siswa.ujian.ruang', $peserta));
    $this->assertSame($peserta->device_token, session(PesertaUjianService::kunciSesi($peserta->id)));

    $response = $this->get(route('siswa.ujian.ruang', $peserta))
        ->assertOk()
        ->assertSee('Berapa hasil 2 + 2?')
        ->assertDontSee('is_benar');

    $snapshot = htmlspecialchars_decode(
        (string) str($response->getContent())->betweenFirst('wire:snapshot="', '"'),
        ENT_QUOTES,
    );

    expect($snapshot)->not->toBe('')
        ->and($snapshot)->not->toContain('is_benar');
});

it('renders the schedule resource pages for admin and guru', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->get('/admin/jadwal-ujian')->assertOk();
    $this->get('/admin/jadwal-ujian/create')->assertOk();
    $this->get('/admin/penempatan-peserta')->assertOk();

    $guruUser = User::factory()->guru()->create();
    Guru::factory()->forUser($guruUser)->create();

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guruUser)
        ->get('/guru/jadwal-ujian')
        ->assertOk();
});

it('allows the student exam component to autosave and submit', function () {
    $data = siapkanUjianTiga();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $this->actingAs($data['siswa'], 'siswa');
    Session::put(PesertaUjianService::kunciSesi($peserta->id), $peserta->device_token);

    Livewire::test(ExamRoom::class, ['peserta' => $peserta])
        ->assertSee('Berapa hasil 2 + 2?')
        ->set('jawabanState.'.$data['soal']->id, $data['benar']->id)
        ->call('pindahSoal', 1)
        ->call('submitExam')
        ->assertRedirect(route('siswa.ujian.hasil', $peserta));

    expect($peserta->refresh()->status)->toBe(StatusPesertaUjian::Selesai)
        ->and((float) $peserta->nilai)->toBe(100.0);
});
