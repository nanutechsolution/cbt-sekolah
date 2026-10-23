<?php

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Filament\Guru\Resources\JadwalUjians\Pages\ListJadwalUjians as GuruListJadwalUjians;
use App\Filament\Resources\JadwalUjians\Pages\CreateJadwalUjian;
use App\Filament\Resources\JadwalUjians\Pages\ListJadwalUjians;
use App\Http\Livewire\ExamRoom;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\JadwalUjian;
use App\Models\Kelas;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\User;
use App\Services\PesertaUjianService;
use Filament\Facades\Filament;
use Filament\Support\Facades\FilamentTimezone;
use Filament\Tables\Table;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Session;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;

uses(RefreshDatabase::class);

/**
 * @return array{paket: PaketSoal, soal: Soal, kelas: Kelas, jadwal: JadwalUjian, siswa: Siswa, siswaDua: Siswa, siswaTiga: Siswa}
 */
function siapkanDataZonaWaktu(): array
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
        'pertanyaan' => 'Soal zona waktu?',
        'bobot' => 1,
    ]);

    OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'A',
        'teks' => 'Jawaban benar',
        'urutan' => 1,
        'is_benar' => true,
    ]);

    $kelas = Kelas::factory()->tingkat($paket->tingkat)->create();

    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => StatusJadwalUjian::Aktif,
        'mulai' => Carbon::parse('2026-10-22 08:00:00', 'Asia/Makassar'),
        'selesai' => Carbon::parse('2026-10-22 10:00:00', 'Asia/Makassar'),
        'durasi_menit' => 60,
    ]);
    $jadwal->kelas()->attach($kelas);

    $siswa = Siswa::factory()->create([
        'kelas_id' => $kelas->id,
        'nama' => 'Siswa Zona Waktu',
    ]);
    $siswaDua = Siswa::factory()->create([
        'kelas_id' => $kelas->id,
        'nama' => 'Siswa Zona Waktu Dua',
    ]);
    $siswaTiga = Siswa::factory()->create([
        'kelas_id' => $kelas->id,
        'nama' => 'Siswa Zona Waktu Tiga',
    ]);

    return compact('paket', 'soal', 'kelas', 'jadwal', 'siswa', 'siswaDua', 'siswaTiga');
}

function aturWaktuZonaWaktu(string $tanggal, string $jam): void
{
    Carbon::setTestNow(Carbon::parse("{$tanggal} {$jam}", 'Asia/Makassar'));
}

class ZonaWaktuAdminListJadwalUjians extends ListJadwalUjians
{
    protected function makeTable(): Table
    {
        return parent::makeTable()->paginated(false);
    }
}

class ZonaWaktuGuruListJadwalUjians extends GuruListJadwalUjians
{
    protected function makeTable(): Table
    {
        return parent::makeTable()->paginated(false);
    }
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
    aturWaktuZonaWaktu('2026-10-22', '08:00:00');
});

afterEach(function () {
    Carbon::setTestNow();
});

it('memakai Asia/Makassar sebagai zona waktu aplikasi dan Filament', function () {
    expect(config('app.timezone'))->toBe('Asia/Makassar')
        ->and(date_default_timezone_get())->toBe('Asia/Makassar')
        ->and(now()->timezoneName)->toBe('Asia/Makassar')
        ->and(FilamentTimezone::get())->toBe('Asia/Makassar')
        ->and(now()->format('Y-m-d H:i:s'))->toBe('2026-10-22 08:00:00');
});

it('menyimpan input form 08:00 WITA apa adanya di database', function () {
    $data = siapkanDataZonaWaktu();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(CreateJadwalUjian::class)
        ->fillForm([
            'paket_soal_id' => $data['paket']->id,
            'nama' => 'Ujian Form Zona Waktu',
            'mulai' => '2026-10-23 08:00:00',
            'selesai' => '2026-10-23 10:00:00',
            'durasi_menit' => 60,
            'kelas' => [$data['kelas']->id],
        ])
        ->call('create')
        ->assertHasNoFormErrors();

    $row = DB::table('jadwal_ujian')->where('nama', 'Ujian Form Zona Waktu')->first();

    expect($row)->not->toBeNull()
        ->and($row->mulai)->toBe('2026-10-23 08:00:00')
        ->and($row->selesai)->toBe('2026-10-23 10:00:00');

    $jadwal = JadwalUjian::query()->where('nama', 'Ujian Form Zona Waktu')->firstOrFail();

    expect($jadwal->mulai->timezoneName)->toBe('Asia/Makassar')
        ->and($jadwal->dalamJendela(Carbon::parse('2026-10-23 08:00:00', 'Asia/Makassar')))->toBeTrue();
});

it('menyimpan waktu_mulai dan waktu_batas dalam jam WITA', function () {
    $data = siapkanDataZonaWaktu();
    $service = app(PesertaUjianService::class);

    aturWaktuZonaWaktu('2026-10-22', '08:00:00');
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $row = DB::table('peserta_ujian')->where('id', $peserta->id)->first();

    expect($row->waktu_mulai)->toBe('2026-10-22 08:00:00')
        ->and($row->waktu_batas)->toBe('2026-10-22 09:00:00');

    aturWaktuZonaWaktu('2026-10-22', '09:30:00');
    $pesertaDua = $service->mulai($data['jadwal'], $data['siswaDua']);

    $rowDua = DB::table('peserta_ujian')->where('id', $pesertaDua->id)->first();

    expect($rowDua->waktu_mulai)->toBe('2026-10-22 09:30:00')
        ->and($rowDua->waktu_batas)->toBe('2026-10-22 10:00:00');
});

it('hanya dapat memulai jadwal 08:00-10:00 di dalam jendela WITA', function () {
    $data = siapkanDataZonaWaktu();
    $service = app(PesertaUjianService::class);

    aturWaktuZonaWaktu('2026-10-22', '07:59:00');
    expect($data['jadwal']->bisaDimulai())->toBeFalse();
    expect(fn () => $service->mulai($data['jadwal'], $data['siswa']))
        ->toThrow(ValidationException::class);

    aturWaktuZonaWaktu('2026-10-22', '08:00:00');
    expect($data['jadwal']->bisaDimulai())->toBeTrue();

    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    expect($peserta->status)->toBe(StatusPesertaUjian::Mengerjakan);

    aturWaktuZonaWaktu('2026-10-22', '09:59:00');
    expect($data['jadwal']->bisaDimulai())->toBeTrue();

    $pesertaDua = $service->mulai($data['jadwal'], $data['siswaDua']);

    expect($pesertaDua->waktu_batas->format('H:i'))->toBe('10:00');

    aturWaktuZonaWaktu('2026-10-22', '10:01:00');
    expect($data['jadwal']->bisaDimulai())->toBeFalse();
    expect(fn () => $service->mulai($data['jadwal'], $data['siswaTiga']))
        ->toThrow(ValidationException::class);
});

it('menutup peserta setelah waktu_batas WITA terlewati', function () {
    $data = siapkanDataZonaWaktu();
    $service = app(PesertaUjianService::class);

    aturWaktuZonaWaktu('2026-10-22', '08:00:00');
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    aturWaktuZonaWaktu('2026-10-22', '09:01:00');

    expect($service->tutupYangKedaluwarsa())->toBe(1)
        ->and($peserta->refresh()->status)->toBe(StatusPesertaUjian::Selesai);
});

it('menampilkan 08:00 WITA secara konsisten di admin, guru, dan ruang ujian siswa', function () {
    $data = siapkanDataZonaWaktu();

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    Livewire::test(ZonaWaktuAdminListJadwalUjians::class)
        ->assertSee('08:00');

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

    Livewire::test(ZonaWaktuGuruListJadwalUjians::class)
        ->assertSee('08:00');

    $this->actingAs($data['siswa'], 'siswa')
        ->get('/ujian')
        ->assertOk()
        ->assertSee('08:00')
        ->assertSee('10:00');

    $this->get(route('siswa.ujian.konfirmasi', $data['jadwal']))
        ->assertOk()
        ->assertSee('08:00')
        ->assertSee('10:00');

    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    Session::put(PesertaUjianService::kunciSesi($peserta->id), $peserta->device_token);

    Livewire::test(ExamRoom::class, ['peserta' => $peserta])
        ->assertSet('sisaDetik', 3600);
});
