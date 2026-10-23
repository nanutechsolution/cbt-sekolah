<?php

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Models\Guru;
use App\Models\GuruMengajar;
use App\Models\JadwalUjian;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\PesertaUjian;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\User;
use App\Services\AnalisisButirService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * @return array{jadwal: JadwalUjian, paket: PaketSoal, kelas: Kelas, soal: Soal, kunci: OpsiSoal, pengecoh: OpsiSoal}
 */
function siapkanAnalisisButir(): array
{
    $paket = PaketSoal::factory()->create([
        'status' => StatusPaketSoal::Siap,
        'acak_soal' => false,
        'acak_opsi' => false,
        'tampilkan_nilai' => true,
    ]);

    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 1,
        'tipe' => TipeSoal::Pg,
        'pertanyaan' => 'Ibu kota Indonesia adalah?',
        'bobot' => 1,
    ]);

    $kunci = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'A',
        'teks' => 'Jakarta',
        'urutan' => 1,
        'is_benar' => true,
    ]);

    $pengecoh = OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'B',
        'teks' => 'Bandung',
        'urutan' => 2,
        'is_benar' => false,
    ]);

    OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'C',
        'teks' => 'Surabaya',
        'urutan' => 3,
        'is_benar' => false,
    ]);

    $kelas = Kelas::factory()->tingkat($paket->tingkat)->create();
    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => StatusJadwalUjian::Selesai,
        'mulai' => now()->subHours(2),
        'selesai' => now()->subHour(),
    ]);
    $jadwal->kelas()->attach($kelas);

    return compact('jadwal', 'paket', 'kelas', 'soal', 'kunci', 'pengecoh');
}

function buatPesertaAnalisis(JadwalUjian $jadwal, Soal $soal, OpsiSoal $opsi, float $nilai): PesertaUjian
{
    $peserta = PesertaUjian::factory()->selesai()->create([
        'jadwal_ujian_id' => $jadwal->id,
        'siswa_id' => Siswa::factory()->create()->id,
        'nilai' => $nilai,
        'nilai_pg' => $nilai,
    ]);

    JawabanSiswa::factory()->create([
        'peserta_ujian_id' => $peserta->id,
        'soal_id' => $soal->id,
        'jawaban' => ['opsi_id' => $opsi->id],
        'is_benar' => $opsi->is_benar,
        'skor' => $opsi->is_benar ? 1 : 0,
    ]);

    return $peserta;
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('calculates difficulty, discrimination, and distractor effectiveness', function () {
    $data = siapkanAnalisisButir();

    buatPesertaAnalisis($data['jadwal'], $data['soal'], $data['kunci'], 90);
    buatPesertaAnalisis($data['jadwal'], $data['soal'], $data['kunci'], 80);
    buatPesertaAnalisis($data['jadwal'], $data['soal'], $data['kunci'], 40);
    buatPesertaAnalisis($data['jadwal'], $data['soal'], $data['pengecoh'], 30);

    $hasil = app(AnalisisButirService::class)->analisis($data['jadwal']);

    expect($hasil['ringkasan']['jumlah_selesai'])->toBe(4)
        ->and($hasil['ringkasan']['rata_rata'])->toBe(60.0)
        ->and($hasil['ringkasan']['tertinggi'])->toBe(90.0)
        ->and($hasil['ringkasan']['terendah'])->toBe(30.0)
        ->and($hasil['butir'])->toHaveCount(1);

    $butir = $hasil['butir'][0];

    expect($butir['tingkat_kesukaran'])->toBe(0.75)
        ->and($butir['kategori_kesukaran'])->toBe('Mudah')
        ->and($butir['daya_beda'])->toBe(0.5)
        ->and($butir['kategori_daya_beda'])->toBe('Baik')
        ->and($butir['jumlah_menjawab'])->toBe(4)
        ->and($butir['jumlah_benar'])->toBe(3);

    $opsiB = collect($butir['opsi'])->firstWhere('label', 'B');
    $opsiC = collect($butir['opsi'])->firstWhere('label', 'C');

    expect($opsiB['dipilih'])->toBe(1)
        ->and($opsiB['persentase'])->toBe(25.0)
        ->and($opsiB['efektif'])->toBeTrue()
        ->and($opsiC['dipilih'])->toBe(0)
        ->and($opsiC['efektif'])->toBeFalse();
});

it('handles a package without completed participants', function () {
    $data = siapkanAnalisisButir();

    $hasil = app(AnalisisButirService::class)->analisis($data['jadwal']);

    expect($hasil['ringkasan']['jumlah_selesai'])->toBe(0)
        ->and($hasil['ringkasan']['rata_rata'])->toBeNull()
        ->and($hasil['butir'][0]['tingkat_kesukaran'])->toBeNull()
        ->and($hasil['butir'][0]['daya_beda'])->toBeNull();
});

it('renders the analysis page and exports an Excel file for an admin', function () {
    $data = siapkanAnalisisButir();
    buatPesertaAnalisis($data['jadwal'], $data['soal'], $data['kunci'], 90);
    buatPesertaAnalisis($data['jadwal'], $data['soal'], $data['pengecoh'], 20);

    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);

    $this->get('/admin/analisis-butir?jadwal='.$data['jadwal']->id)
        ->assertOk()
        ->assertSee('Tingkat Kesukaran')
        ->assertSee('Ibu kota Indonesia');

    $this->get(route('analisis.export', $data['jadwal']))
        ->assertOk()
        ->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
});

it('allows a guru to analyze only their own package and blocks another package', function () {
    $data = siapkanAnalisisButir();
    buatPesertaAnalisis($data['jadwal'], $data['soal'], $data['kunci'], 90);

    $guruUser = User::factory()->guru()->create();
    $guru = Guru::factory()->forUser($guruUser)->create();
    GuruMengajar::factory()->create([
        'guru_id' => $guru->id,
        'mapel_id' => $data['paket']->mapel_id,
        'kelas_id' => $data['kelas']->id,
        'tahun_ajaran_id' => $data['paket']->tahun_ajaran_id,
    ]);

    Filament::setCurrentPanel(Filament::getPanel('guru'));

    $this->actingAs($guruUser)
        ->get('/guru/analisis-butir?jadwal='.$data['jadwal']->id)
        ->assertOk()
        ->assertSee('Tingkat Kesukaran');

    $lain = siapkanAnalisisButir();

    $this->get('/guru/analisis-butir?jadwal='.$lain['jadwal']->id)
        ->assertForbidden();
});
