<?php

use App\Enums\JenisAksiLog;
use App\Enums\JenisPelanggaran;
use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Filament\Resources\JadwalUjians\Pages\EditJadwalUjian;
use App\Filament\Resources\JadwalUjians\RelationManagers\PesertaUjianRelationManager;
use App\Filament\Resources\LogAktivitas\Pages\ListLogAktivitas;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\JadwalUjian;
use App\Models\Kelas;
use App\Models\LogAktivitas;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\PelanggaranUjian;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\User;
use App\Services\LogAktivitasService;
use App\Services\PesertaUjianService;
use Filament\Facades\Filament;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{paket: PaketSoal, soal: Soal, benar: OpsiSoal, kelas: Kelas, jadwal: JadwalUjian, siswa: Siswa, peserta: PesertaUjian}
 */
function siapkanPemulihanUjian(): array
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
        'pertanyaan' => 'Soal untuk pengujian pemulihan?',
    ]);

    $benar = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'A',
        'teks' => 'Jawaban benar',
        'urutan' => 1,
        'is_benar' => true,
    ]);

    OpsiSoal::factory()->create([
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

    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    $peserta = app(PesertaUjianService::class)->mulai($jadwal, $siswa);

    return compact('paket', 'soal', 'benar', 'kelas', 'jadwal', 'siswa', 'peserta');
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('menambah waktu peserta tanpa menghapus jawaban dan mencatat log', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $service->simpanJawaban(
        $data['peserta'],
        $data['siswa'],
        $data['peserta']->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    PelanggaranUjian::factory()->create([
        'peserta_ujian_id' => $data['peserta']->id,
        'jenis' => JenisPelanggaran::PindahTab,
    ]);
    $data['peserta']->update(['jumlah_pelanggaran' => 1]);
    $data['peserta']->refresh();
    $batasLama = $data['peserta']->waktu_batas->copy();
    $urutanLama = $data['peserta']->urutan_soal;
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $hasil = $service->tambahWaktu($data['peserta'], 15);

    expect($hasil->status)->toBe(StatusPesertaUjian::Mengerjakan)
        ->and($hasil->waktu_batas->timestamp)->toBe($batasLama->copy()->addMinutes(15)->timestamp)
        ->and($hasil->jawaban()->count())->toBe(1)
        ->and($hasil->pelanggaran()->count())->toBe(1)
        ->and($hasil->jumlah_pelanggaran)->toBe(1)
        ->and($hasil->urutan_soal)->toBe($urutanLama);

    $log = LogAktivitas::query()->sole();

    expect($log->aksi)->toBe(JenisAksiLog::TambahWaktu)
        ->and($log->pengguna_id)->toBe($admin->id)
        ->and($log->entitas_type)->toBe(PesertaUjian::class)
        ->and($log->entitas_id)->toBe($data['peserta']->id)
        ->and($log->data_sebelum['waktu_batas'])->toBe($batasLama->format('Y-m-d H:i:s'))
        ->and($log->data_sesudah['waktu_batas'])->toBe($batasLama->copy()->addMinutes(15)->format('Y-m-d H:i:s'))
        ->and($log->data_sesudah['tambahan_menit'])->toBe(15);
});

it('menolak tambah waktu yang melewati jam selesai jadwal', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $data['jadwal']->update(['selesai' => now()->addMinutes(70)]);
    $data['peserta']->refresh();
    $batasLama = $data['peserta']->waktu_batas->copy();

    expect(fn () => $service->tambahWaktu($data['peserta'], 15))
        ->toThrow(ValidationException::class);

    expect($data['peserta']->refresh()->waktu_batas->timestamp)->toBe($batasLama->timestamp)
        ->and(LogAktivitas::query()->count())->toBe(0);
});

it('menolak tambah waktu untuk peserta yang waktunya sudah habis', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $data['peserta']->update(['waktu_batas' => now()->subMinute()]);

    expect(fn () => $service->tambahWaktu($data['peserta'], 10))
        ->toThrow(ValidationException::class);

    expect(LogAktivitas::query()->count())->toBe(0);
});

it('membuka kunci device tanpa menghapus jawaban dan mencatat log', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $service->simpanJawaban(
        $data['peserta'],
        $data['siswa'],
        $data['peserta']->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    PelanggaranUjian::factory()->create([
        'peserta_ujian_id' => $data['peserta']->id,
        'jenis' => JenisPelanggaran::PindahTab,
    ]);
    $data['peserta']->update(['jumlah_pelanggaran' => 1]);
    $data['peserta']->refresh();

    $tokenLama = $data['peserta']->device_token;
    $urutanLama = $data['peserta']->urutan_soal;
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $hasil = $service->bukaKunciDevice($data['peserta']);

    expect($hasil->status)->toBe(StatusPesertaUjian::Mengerjakan)
        ->and($hasil->device_token)->toBeNull()
        ->and($hasil->jawaban()->count())->toBe(1)
        ->and($hasil->pelanggaran()->count())->toBe(1)
        ->and($hasil->jumlah_pelanggaran)->toBe(1)
        ->and($hasil->urutan_soal)->toBe($urutanLama);

    expect(fn () => $service->pastikanAkses($hasil, $data['siswa'], $tokenLama))
        ->toThrow(AuthorizationException::class);

    $log = LogAktivitas::query()->sole();

    expect($log->aksi)->toBe(JenisAksiLog::BukaKunciDevice)
        ->and($log->pengguna_id)->toBe($admin->id)
        ->and($log->data_sebelum['device_terkunci'])->toBeTrue()
        ->and($log->data_sesudah['device_terkunci'])->toBeFalse();
});

it('mengizinkan peserta yang dibuka kuncinya melanjutkan di perangkat baru', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $service->simpanJawaban(
        $data['peserta'],
        $data['siswa'],
        $data['peserta']->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    $data['peserta']->refresh();
    $tokenLama = $data['peserta']->device_token;
    $batasLama = $data['peserta']->waktu_batas->copy();
    $urutanLama = $data['peserta']->urutan_soal;

    $service->bukaKunciDevice($data['peserta']);
    $baru = $service->mulai(
        $data['jadwal'],
        $data['siswa'],
        null,
        '192.0.2.10',
        'Perangkat Baru',
    );

    expect($baru->device_token)->not->toBeNull()
        ->and($baru->device_token)->not->toBe($tokenLama)
        ->and($baru->waktu_batas->timestamp)->toBe($batasLama->timestamp)
        ->and($baru->jawaban()->count())->toBe(1)
        ->and($baru->urutan_soal)->toBe($urutanLama)
        ->and($baru->ip_address)->toBe('192.0.2.10')
        ->and($baru->user_agent)->toBe('Perangkat Baru');

    $service->pastikanAkses($baru, $data['siswa'], $baru->device_token);

    expect(fn () => $service->pastikanAkses($baru, $data['siswa'], $tokenLama))
        ->toThrow(AuthorizationException::class);
});

it('menyediakan aksi tambah waktu dan buka kunci untuk admin tanpa kehilangan jawaban', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $service->simpanJawaban(
        $data['peserta'],
        $data['siswa'],
        $data['peserta']->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(PesertaUjianRelationManager::class, [
        'ownerRecord' => $data['jadwal'],
        'pageClass' => EditJadwalUjian::class,
    ])
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$data['peserta']])
        ->assertTableActionVisible('tambah_waktu', $data['peserta'])
        ->assertTableActionVisible('buka_kunci_device', $data['peserta'])
        ->callTableAction('tambah_waktu', $data['peserta'], ['menit' => 10])
        ->assertHasNoTableActionErrors()
        ->callTableAction('buka_kunci_device', $data['peserta'])
        ->assertHasNoTableActionErrors();

    $data['peserta']->refresh();

    expect($data['peserta']->device_token)->toBeNull()
        ->and($data['peserta']->jawaban()->count())->toBe(1)
        ->and(LogAktivitas::query()->where('aksi', JenisAksiLog::TambahWaktu->value)->count())->toBe(1)
        ->and(LogAktivitas::query()->where('aksi', JenisAksiLog::BukaKunciDevice->value)->count())->toBe(1);
});

it('menyembunyikan aksi pemulihan dari guru', function () {
    $data = siapkanPemulihanUjian();

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
        'pageClass' => App\Filament\Guru\Resources\JadwalUjians\Pages\EditJadwalUjian::class,
    ])
        ->assertSuccessful()
        ->assertTableActionHidden('tambah_waktu', $data['peserta'])
        ->assertTableActionHidden('buka_kunci_device', $data['peserta']);

    expect(Gate::forUser($guruUser)->allows('tambahWaktu', $data['peserta']))->toBeFalse()
        ->and(Gate::forUser($guruUser)->allows('bukaKunciDevice', $data['peserta']))->toBeFalse();
});

it('mencatat reset peserta beserta jumlah data yang dihapus', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $service->simpanJawaban(
        $data['peserta'],
        $data['siswa'],
        $data['peserta']->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $service->reset($data['peserta']);

    $log = LogAktivitas::query()
        ->where('aksi', JenisAksiLog::ResetPeserta->value)
        ->sole();

    expect($log->pengguna_id)->toBe($admin->id)
        ->and($log->data_sebelum['jumlah_jawaban'])->toBe(1)
        ->and($log->data_sebelum['device_terkunci'])->toBeTrue()
        ->and($log->data_sesudah['jumlah_jawaban'])->toBe(0)
        ->and($log->data_sesudah['device_terkunci'])->toBeFalse();
});

it('mencatat penyelesaian manual peserta', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $service->simpanJawaban(
        $data['peserta'],
        $data['siswa'],
        $data['peserta']->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $hasil = $service->selesaikanManual($data['peserta']);

    $log = LogAktivitas::query()
        ->where('aksi', JenisAksiLog::SelesaikanManual->value)
        ->sole();

    expect($hasil->status)->toBe(StatusPesertaUjian::Selesai)
        ->and($log->pengguna_id)->toBe($admin->id)
        ->and($log->data_sebelum['status'])->toBe(StatusPesertaUjian::Mengerjakan->value)
        ->and($log->data_sesudah['status'])->toBe(StatusPesertaUjian::Selesai->value)
        ->and($log->data_sesudah['nilai'])->not->toBeNull();
});

it('menampilkan log aktivitas di panel admin', function () {
    $data = siapkanPemulihanUjian();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    $log = app(LogAktivitasService::class)->catat(
        JenisAksiLog::TambahWaktu,
        $data['peserta'],
        $admin,
        'Uji tampilan log aktivitas.',
        ['sebelum' => true],
        ['sesudah' => true],
        '127.0.0.1',
    );

    Livewire::test(ListLogAktivitas::class)
        ->assertSuccessful()
        ->assertCanSeeTableRecords([$log])
        ->assertSee('Uji tampilan log aktivitas.');

    $this->get(route('filament.admin.resources.log-aktivitas.index'))
        ->assertOk()
        ->assertSee('Uji tampilan log aktivitas.');
});

it('mengizinkan perangkat baru melanjutkan lewat alur HTTP setelah kunci dibuka', function () {
    $data = siapkanPemulihanUjian();
    $service = app(PesertaUjianService::class);
    $service->simpanJawaban(
        $data['peserta'],
        $data['siswa'],
        $data['peserta']->device_token,
        $data['soal']->id,
        $data['benar']->id,
    );

    $data['peserta']->refresh();
    $tokenLama = $data['peserta']->device_token;
    $batasLama = $data['peserta']->waktu_batas->copy();
    $urutanLama = $data['peserta']->urutan_soal;
    $kunciSesi = PesertaUjianService::kunciSesi($data['peserta']->id);

    $this->actingAs($data['siswa'], 'siswa');
    $this->withSession([$kunciSesi => $tokenLama]);
    $this->get(route('siswa.ujian.ruang', $data['peserta']))->assertOk();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    $service->bukaKunciDevice($data['peserta']);

    $data['peserta']->refresh();

    expect($data['peserta']->device_token)->toBeNull();

    $this->actingAs($data['siswa'], 'siswa');
    $this->withSession([$kunciSesi => $tokenLama]);
    $this->get(route('siswa.ujian.ruang', $data['peserta']))->assertForbidden();

    $this->flushSession();
    $this->actingAs($data['siswa'], 'siswa');

    $this->post(route('siswa.ujian.mulai', $data['jadwal']))
        ->assertRedirect(route('siswa.ujian.ruang', $data['peserta']));

    $data['peserta']->refresh();

    expect($data['peserta']->device_token)->not->toBeNull()
        ->and($data['peserta']->device_token)->not->toBe($tokenLama)
        ->and($data['peserta']->jawaban()->count())->toBe(1)
        ->and($data['peserta']->waktu_batas->timestamp)->toBe($batasLama->timestamp)
        ->and($data['peserta']->urutan_soal)->toBe($urutanLama);

    $this->get(route('siswa.ujian.ruang', $data['peserta']))->assertOk();
});
