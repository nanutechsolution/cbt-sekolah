<?php

use App\Enums\JenisPelanggaran;
use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Filament\Resources\JadwalUjians\Pages\EditJadwalUjian;
use App\Filament\Resources\JadwalUjians\RelationManagers\PesertaUjianRelationManager;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\JadwalUjian;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\PenempatanPeserta;
use App\Models\PesertaUjian;
use App\Models\Ruang;
use App\Models\Sesi;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\User;
use App\Services\JadwalUjianService;
use App\Services\PesertaUjianService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{paket: PaketSoal, soal: Soal, benar: OpsiSoal, salah: OpsiSoal, kelas: Kelas, jadwal: JadwalUjian, siswa: Siswa}
 */
function siapkanUjianEmpat(): array
{
    $paket = PaketSoal::factory()->create([
        'status' => StatusPaketSoal::Siap,
        'acak_soal' => false,
        'acak_opsi' => false,
        'tampilkan_nilai' => true,
    ]);

    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'tipe' => TipeSoal::Pg,
        'pertanyaan' => 'Soal untuk pengujian cetak?',
    ]);

    $benar = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'A',
        'teks' => 'Jawaban benar',
        'urutan' => 1,
        'is_benar' => true,
    ]);

    $salah = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'B',
        'teks' => 'Jawaban salah',
        'urutan' => 2,
        'is_benar' => false,
    ]);

    $kelas = Kelas::factory()->tingkat($paket->tingkat)->create();
    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => StatusJadwalUjian::Aktif,
        'mulai' => now()->subMinute(),
        'selesai' => now()->addHours(2),
        'durasi_menit' => 60,
    ]);
    $jadwal->kelas()->attach($kelas);

    $siswa = Siswa::factory()->create([
        'kelas_id' => $kelas->id,
        'nama' => 'Siswa Cetak',
    ]);

    return compact('paket', 'soal', 'benar', 'salah', 'kelas', 'jadwal', 'siswa');
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('resets a participant, their answers, violations, and device token', function () {
    $data = siapkanUjianEmpat();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );
    $service->catatPelanggaran(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        JenisPelanggaran::PindahTab,
    );
    $service->kirim($peserta, $data['siswa'], $peserta->device_token);

    $peserta = $service->reset($peserta->refresh());

    expect($peserta->status)->toBe(StatusPesertaUjian::Belum)
        ->and($peserta->device_token)->toBeNull()
        ->and($peserta->waktu_mulai)->toBeNull()
        ->and($peserta->waktu_batas)->toBeNull()
        ->and($peserta->waktu_selesai)->toBeNull()
        ->and($peserta->urutan_soal)->toBeNull()
        ->and($peserta->urutan_opsi)->toBeNull()
        ->and($peserta->nilai)->toBeNull()
        ->and($peserta->nilai_pg)->toBeNull()
        ->and($peserta->jumlah_pelanggaran)->toBe(0)
        ->and($peserta->jawaban()->count())->toBe(0)
        ->and($peserta->pelanggaran()->count())->toBe(0);
});

it('finalises expired participants and does not grade them twice', function () {
    $data = siapkanUjianEmpat();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    $this->travel(61)->minutes();

    expect($service->tutupYangKedaluwarsa())->toBe(1);

    $peserta->refresh();

    expect($peserta->status)->toBe(StatusPesertaUjian::Selesai)
        ->and((float) $peserta->nilai)->toBe(100.0)
        ->and($service->tutupYangKedaluwarsa())->toBe(0);

    $this->travelBack();
});

it('shows the monitoring table to an admin and allows the reset action', function () {
    $data = siapkanUjianEmpat();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(PesertaUjianRelationManager::class, [
        'ownerRecord' => $data['jadwal'],
        'pageClass' => EditJadwalUjian::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$peserta])
        ->callTableAction('reset', $peserta)
        ->assertHasNoTableActionErrors();

    expect($peserta->refresh()->status)->toBe(StatusPesertaUjian::Belum)
        ->and(JawabanSiswa::query()->count())->toBe(0);
});

it('hides the reset action from a guru in the monitoring table', function () {
    $data = siapkanUjianEmpat();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $guruUser = User::factory()->guru()->create();
    $guru = Guru::factory()->forUser($guruUser)->create();
    GuruMengajar::factory()->create([
        'guru_id' => $guru->id,
        'mapel_id' => $data['paket']->mapel_id,
        'kelas_id' => $data['kelas']->id,
        'tahun_ajaran_id' => $data['paket']->tahun_ajaran_id,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('guru'));
    $this->actingAs($guruUser);

    Livewire::test(PesertaUjianRelationManager::class, [
        'ownerRecord' => $data['jadwal'],
        'pageClass' => \App\Filament\Guru\Resources\JadwalUjians\Pages\EditJadwalUjian::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$peserta])
        ->assertTableActionHidden('reset', $peserta);
});

it('prints kartu peserta, daftar hadir, and berita acara as PDF', function () {
    $data = siapkanUjianEmpat();

    PenempatanPeserta::factory()->create([
        'siswa_id' => $data['siswa']->id,
        'tahun_ajaran_id' => $data['paket']->tahun_ajaran_id,
        'jenis_ujian_id' => $data['paket']->jenis_ujian_id,
        'ruang_id' => Ruang::factory()->create()->id,
        'sesi_id' => Sesi::factory()->create()->id,
        'no_peserta' => '2026001',
    ]);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    foreach (['cetak.kartu', 'cetak.daftar-hadir', 'cetak.berita-acara'] as $route) {
        $response = $this->get(route($route, $data['jadwal']));

        $response->assertOk();
        expect($response->headers->get('content-type'))->toContain('application/pdf');
    }
});

it('authorises PDF access by schedule ownership and authentication', function () {
    $data = siapkanUjianEmpat();

    $this->get(route('cetak.kartu', $data['jadwal']))->assertRedirect('/login');

    $guruUser = User::factory()->guru()->create();
    Guru::factory()->forUser($guruUser)->create();

    $this->actingAs($guruUser)
        ->get(route('cetak.kartu', $data['jadwal']))
        ->assertForbidden();

    $guruUserDua = User::factory()->guru()->create();
    $guruDua = Guru::factory()->forUser($guruUserDua)->create();
    GuruMengajar::factory()->create([
        'guru_id' => $guruDua->id,
        'mapel_id' => $data['paket']->mapel_id,
        'kelas_id' => $data['kelas']->id,
        'tahun_ajaran_id' => $data['paket']->tahun_ajaran_id,
    ]);

    $this->actingAs($guruUserDua)
        ->get(route('cetak.kartu', $data['jadwal']))
        ->assertOk();
});

it('rejects a schedule with an invalid class level and detects an overlap through the service', function () {
    $data = siapkanUjianEmpat();
    $admin = User::factory()->admin()->create();
    $service = app(JadwalUjianService::class);

    expect(fn () => $service->simpan([
        'paket_soal_id' => $data['paket']->id,
        'nama' => 'Jadwal lain',
        'mulai' => now()->addMinutes(10)->format('Y-m-d H:i:s'),
        'selesai' => now()->addMinutes(70)->format('Y-m-d H:i:s'),
        'durasi_menit' => 60,
        'kelas' => [$data['kelas']->id],
        'dibuat_oleh' => $admin->id,
    ]))->toThrow(ValidationException::class);

    expect($data['jadwal']->refresh()->status)->toBe(StatusJadwalUjian::Aktif);
});
