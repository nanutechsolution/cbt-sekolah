<?php

use App\Enums\StatusJadwalUjian;
use App\Enums\StatusPaketSoal;
use App\Enums\StatusPesertaUjian;
use App\Enums\TipeSoal;
use App\Filament\Resources\PaketSoals\Pages\EditPaketSoal;
use App\Filament\Resources\PaketSoals\RelationManagers\SoalRelationManager;
use App\Http\Livewire\ExamRoom;
use App\Models\JadwalUjian;
use App\Models\JawabanSiswa;
use App\Models\Kelas;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\Siswa;
use App\Models\Soal;
use App\Models\User;
use App\Services\AnalisisButirService;
use App\Services\PaketSoalService;
use App\Services\PengaturanSekolahService;
use App\Services\PesertaUjianService;
use App\Services\SoalWordImportService;
use App\Services\WordTemplateService;
use Filament\Facades\Filament;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Livewire\Livewire;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;

uses(RefreshDatabase::class);

/**
 * @return array<string, mixed>
 */
function siapkanUjianTujuh(bool $jadwalAktif = true): array
{
    $paket = PaketSoal::factory()->create([
        'status' => StatusPaketSoal::Siap,
        'acak_soal' => false,
        'acak_opsi' => false,
        'tampilkan_nilai' => true,
    ]);

    $soal = [];

    $soal['pg'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 1,
        'tipe' => TipeSoal::Pg,
        'pertanyaan' => 'Soal pilihan ganda?',
        'audio' => 'soal/audio/contoh.mp3',
        'audio_maks_putar' => 2,
    ]);
    $soal['pg_benar'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['pg']->id,
        'label' => 'A',
        'teks' => 'Jawaban benar',
        'urutan' => 1,
        'is_benar' => true,
    ]);
    $soal['pg_salah'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['pg']->id,
        'label' => 'B',
        'teks' => 'Jawaban salah',
        'urutan' => 2,
    ]);

    $soal['kompleks'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 2,
        'tipe' => TipeSoal::PgKompleks,
        'pertanyaan' => 'Pilih jawaban yang benar lebih dari satu.',
    ]);
    $soal['kompleks_a'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['kompleks']->id,
        'label' => 'A',
        'teks' => 'Benar satu',
        'urutan' => 1,
        'is_benar' => true,
    ]);
    $soal['kompleks_b'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['kompleks']->id,
        'label' => 'B',
        'teks' => 'Benar dua',
        'urutan' => 2,
        'is_benar' => true,
    ]);
    $soal['kompleks_c'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['kompleks']->id,
        'label' => 'C',
        'teks' => 'Salah',
        'urutan' => 3,
    ]);

    $soal['bs'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 3,
        'tipe' => TipeSoal::BenarSalah,
        'pertanyaan' => 'Apakah air membeku pada 0 derajat?',
    ]);
    $soal['bs_benar'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['bs']->id,
        'label' => 'A',
        'teks' => 'Benar',
        'urutan' => 1,
        'is_benar' => true,
    ]);
    $soal['bs_salah'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['bs']->id,
        'label' => 'B',
        'teks' => 'Salah',
        'urutan' => 2,
    ]);

    $soal['isian'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 4,
        'tipe' => TipeSoal::IsianSingkat,
        'pertanyaan' => 'Ibu kota Indonesia adalah?',
    ]);
    OpsiSoal::factory()->create([
        'soal_id' => $soal['isian']->id,
        'label' => 'A',
        'teks' => 'Jakarta',
        'urutan' => 1,
        'is_benar' => true,
    ]);
    OpsiSoal::factory()->create([
        'soal_id' => $soal['isian']->id,
        'label' => 'B',
        'teks' => 'DKI Jakarta',
        'urutan' => 2,
        'is_benar' => true,
    ]);

    $soal['essay'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 5,
        'tipe' => TipeSoal::Essay,
        'pertanyaan' => 'Jelaskan proses fotosintesis.',
    ]);

    $soal['jodoh'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 6,
        'tipe' => TipeSoal::Menjodohkan,
        'pertanyaan' => 'Jodohkan istilah berikut.',
    ]);
    $soal['jodoh_a'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['jodoh']->id,
        'label' => 'A',
        'teks' => 'Satu',
        'pasangan' => 'Pertama',
        'urutan' => 1,
    ]);
    $soal['jodoh_b'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['jodoh']->id,
        'label' => 'B',
        'teks' => 'Dua',
        'pasangan' => 'Kedua',
        'urutan' => 2,
    ]);

    $soal['pernyataan'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 7,
        'tipe' => TipeSoal::BenarSalahPernyataan,
        'pertanyaan' => 'Tentukan benar atau salah.',
    ]);
    $soal['pernyataan_a'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['pernyataan']->id,
        'label' => 'A',
        'teks' => 'Air membeku pada 0 derajat.',
        'urutan' => 1,
        'is_benar' => true,
    ]);
    $soal['pernyataan_b'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['pernyataan']->id,
        'label' => 'B',
        'teks' => 'Matahari mengelilingi bumi.',
        'urutan' => 2,
        'is_benar' => false,
    ]);

    $soal['urutan'] = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'nomor' => 8,
        'tipe' => TipeSoal::Urutan,
        'pertanyaan' => 'Susun langkah berikut.',
    ]);
    $soal['urutan_a'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['urutan']->id,
        'label' => 'A',
        'teks' => 'Langkah pertama',
        'urutan' => 1,
    ]);
    $soal['urutan_b'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['urutan']->id,
        'label' => 'B',
        'teks' => 'Langkah kedua',
        'urutan' => 2,
    ]);
    $soal['urutan_c'] = OpsiSoal::factory()->create([
        'soal_id' => $soal['urutan']->id,
        'label' => 'C',
        'teks' => 'Langkah ketiga',
        'urutan' => 3,
    ]);

    $kelas = Kelas::factory()->tingkat($paket->tingkat)->create();
    $jadwal = JadwalUjian::factory()->create([
        'paket_soal_id' => $paket->id,
        'status' => $jadwalAktif ? StatusJadwalUjian::Aktif : StatusJadwalUjian::Nonaktif,
        'mulai' => $jadwalAktif ? now()->subMinute() : now()->addMinute(),
        'selesai' => now()->addHours(2),
        'durasi_menit' => 60,
    ]);
    $jadwal->kelas()->attach($kelas);

    $siswa = Siswa::factory()->create(['kelas_id' => $kelas->id]);

    return compact('paket', 'soal', 'kelas', 'jadwal', 'siswa');
}

beforeEach(function () {
    Filament::setCurrentPanel(Filament::getPanel('admin'));
});

it('only offers active question types when creating a question', function () {
    expect(array_keys(TipeSoal::initialOptions()))->toBe([
        TipeSoal::Pg->value,
        TipeSoal::PgKompleks->value,
        TipeSoal::BenarSalah->value,
        TipeSoal::IsianSingkat->value,
    ]);

    expect(TipeSoal::Essay->bolehDibuat())->toBeFalse()
        ->and(TipeSoal::Menjodohkan->bolehDibuat())->toBeFalse()
        ->and(TipeSoal::BenarSalahPernyataan->bolehDibuat())->toBeFalse()
        ->and(TipeSoal::Urutan->bolehDibuat())->toBeFalse();
});

it('does not expose short-answer keys or discussion in the Livewire snapshot', function () {
    Storage::fake('public');
    $data = siapkanUjianTujuh();
    Soal::query()
        ->whereKey($data['soal']['isian']->id)
        ->toBase()
        ->update(['pembahasan' => 'PEMBAHASAN RAHASIA']);

    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);
    session([PesertaUjianService::kunciSesi($peserta->id) => $peserta->device_token]);

    $this->actingAs($data['siswa'], 'siswa');

    $komponen = Livewire::test(ExamRoom::class, ['peserta' => $peserta]);
    $snapshot = json_encode($komponen->snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    expect($snapshot)->toBeString()
        ->and($snapshot)->not->toContain('Jakarta')
        ->and($snapshot)->not->toContain('DKI Jakarta')
        ->and($snapshot)->not->toContain('PEMBAHASAN RAHASIA')
        ->and($snapshot)->not->toContain('is_benar')
        ->and($snapshot)->not->toContain((string) $peserta->device_token);

    $komponen->assertDontSee('DKI Jakarta')
        ->assertDontSee('PEMBAHASAN RAHASIA');
});

it('saves all eight answer types and grades every objective question', function () {
    $data = siapkanUjianTujuh();
    $soal = $data['soal'];
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $jawaban = [
        'pg' => $soal['pg_benar']->id,
        'kompleks' => [$soal['kompleks_a']->id, $soal['kompleks_b']->id],
        'bs' => $soal['bs_benar']->id,
        'isian' => '  JAKARTA  ',
        'essay' => 'Fotosintesis mengubah cahaya menjadi energi.',
        'jodoh' => [
            'pasangan' => [
                $soal['jodoh_a']->id => 'Pertama',
                $soal['jodoh_b']->id => 'Kedua',
            ],
        ],
        'pernyataan' => [
            'pernyataan' => [
                $soal['pernyataan_a']->id => true,
                $soal['pernyataan_b']->id => false,
            ],
        ],
        'urutan' => [
            'urutan' => [
                $soal['urutan_a']->id,
                $soal['urutan_b']->id,
                $soal['urutan_c']->id,
            ],
        ],
    ];

    foreach ($jawaban as $kunci => $nilai) {
        $service->simpanJawaban(
            $peserta,
            $data['siswa'],
            $peserta->device_token,
            $soal[$kunci]->id,
            $nilai,
        );
    }

    $selesai = $service->kirim($peserta, $data['siswa'], $peserta->device_token);

    expect($selesai->status)->toBe(StatusPesertaUjian::Selesai)
        ->and((float) $selesai->nilai)->toBe(100.0)
        ->and((float) $selesai->nilai_pg)->toBe(100.0)
        ->and($selesai->nilai_essay)->toBeNull()
        ->and($selesai->jawaban()->count())->toBe(8);

    $essay = $selesai->jawaban()->where('soal_id', $soal['essay']->id)->firstOrFail();

    expect($essay->is_benar)->toBeNull()
        ->and($essay->skor)->toBeNull();

    $analisis = app(AnalisisButirService::class)->analisis($data['jadwal']);

    $kompleks = collect($analisis['butir'])->firstWhere('tipe', TipeSoal::PgKompleks->value);
    $isian = collect($analisis['butir'])->firstWhere('tipe', TipeSoal::IsianSingkat->value);

    expect($kompleks['jumlah_benar'])->toBe(1)
        ->and($kompleks['tingkat_kesukaran'])->toBe(1.0)
        ->and($isian['jumlah_benar'])->toBe(1)
        ->and($analisis['ringkasan']['jumlah_selesai'])->toBe(1);
});

it('rejects invalid payloads for the new objective question types', function () {
    $data = siapkanUjianTujuh();
    $soal = $data['soal'];
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    expect(fn () => $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $soal['kompleks']->id,
        [$soal['kompleks_a']->id, 999999],
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $soal['jodoh']->id,
        ['pasangan' => [$soal['jodoh_a']->id => 'Bukan pilihan']],
    ))->toThrow(ValidationException::class);

    expect(fn () => $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $soal['pernyataan']->id,
        ['pernyataan' => [$soal['pernyataan_a']->id => 'mungkin']],
    ))->toThrow(ValidationException::class);
});

it('renders controls for all question types in the student room', function () {
    Storage::fake('public');
    $data = siapkanUjianTujuh();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);
    session([PesertaUjianService::kunciSesi($peserta->id) => $peserta->device_token]);

    $this->actingAs($data['siswa'], 'siswa');

    $komponen = Livewire::test(ExamRoom::class, ['peserta' => $peserta])
        ->assertOk()
        ->assertSee('Audio soal', false);

    $komponen->call('pindahSoal', 2)
        ->assertSee('Pilih satu atau lebih jawaban yang benar.');

    $komponen->call('pindahSoal', 4)
        ->assertSee('Jawaban singkat');

    $komponen->call('pindahSoal', 6)
        ->assertSee('Pilih pasangan');

    $komponen->call('pindahSoal', 7)
        ->assertSee('Tentukan Benar atau Salah');

    $komponen->call('pindahSoal', 8)
        ->assertSee('Susun opsi berikut')
        ->assertSee('A++')
        ->call('pindahUrutan', $data['soal']['urutan']->id, 0, 1);

    $jawabanUrutan = JawabanSiswa::query()
        ->where('peserta_ujian_id', $peserta->id)
        ->where('soal_id', $data['soal']['urutan']->id)
        ->firstOrFail();

    expect($jawabanUrutan->jawaban['urutan'])->toBe([
        $data['soal']['urutan_b']->id,
        $data['soal']['urutan_a']->id,
        $data['soal']['urutan_c']->id,
    ]);
});

it('autosaves multi-select and nested matching answers from the exam room', function () {
    Storage::fake('public');
    $data = siapkanUjianTujuh();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);
    session([PesertaUjianService::kunciSesi($peserta->id) => $peserta->device_token]);

    $this->actingAs($data['siswa'], 'siswa');

    $komponen = Livewire::test(ExamRoom::class, ['peserta' => $peserta]);

    $komponen->set('jawabanState.'.$data['soal']['kompleks']->id, [
        $data['soal']['kompleks_a']->id,
        $data['soal']['kompleks_b']->id,
    ]);

    $jawabanKompleks = JawabanSiswa::query()
        ->where('peserta_ujian_id', $peserta->id)
        ->where('soal_id', $data['soal']['kompleks']->id)
        ->firstOrFail();

    expect($jawabanKompleks->jawaban['opsi_id'])->toBe([
        $data['soal']['kompleks_a']->id,
        $data['soal']['kompleks_b']->id,
    ]);

    $komponen->set(
        'jawabanState.'.$data['soal']['jodoh']->id.'.'.$data['soal']['jodoh_a']->id,
        'Pertama',
    );

    $jawabanJodoh = JawabanSiswa::query()
        ->where('peserta_ujian_id', $peserta->id)
        ->where('soal_id', $data['soal']['jodoh']->id)
        ->firstOrFail();

    expect($jawabanJodoh->jawaban['pasangan'][$data['soal']['jodoh_a']->id])->toBe('Pertama');
});

it('hydrates saved statement answers as selected radio values', function () {
    Storage::fake('public');
    $data = siapkanUjianTujuh();
    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    $service->simpanJawaban(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $data['soal']['pernyataan']->id,
        [
            'pernyataan' => [
                $data['soal']['pernyataan_a']->id => true,
                $data['soal']['pernyataan_b']->id => false,
            ],
        ],
    );

    session([PesertaUjianService::kunciSesi($peserta->id) => $peserta->device_token]);
    $this->actingAs($data['siswa'], 'siswa');

    Livewire::test(ExamRoom::class, ['peserta' => $peserta])
        ->assertSet('jawabanState.'.$data['soal']['pernyataan']->id.'.'.$data['soal']['pernyataan_a']->id, '1')
        ->assertSet('jawabanState.'.$data['soal']['pernyataan']->id.'.'.$data['soal']['pernyataan_b']->id, '0');
});

it('enforces the audio play limit on the server', function () {
    Storage::fake('public');
    $data = siapkanUjianTujuh();
    $soalPg = $data['soal']['pg'];

    $service = app(PesertaUjianService::class);
    $peserta = $service->mulai($data['jadwal'], $data['siswa']);

    expect($service->catatPutarAudio($peserta, $data['siswa'], $peserta->device_token, $soalPg->id))->toBe(1)
        ->and($service->catatPutarAudio($peserta, $data['siswa'], $peserta->device_token, $soalPg->id))->toBe(2);

    expect(fn () => $service->catatPutarAudio(
        $peserta,
        $data['siswa'],
        $peserta->device_token,
        $soalPg->id,
    ))->toThrow(ValidationException::class);

    expect(JawabanSiswa::query()
        ->where('peserta_ujian_id', $peserta->id)
        ->where('soal_id', $soalPg->id)
        ->value('jumlah_putar_audio'))->toBe(2);
});

it('imports Word questions with options and answer keys', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    $path = storage_path('app/import-soal-test.docx');

    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('1. Ibu kota Indonesia adalah?');
    $section->addText('A. Bandung');
    $section->addText('B. Jakarta');
    $section->addText('C. Surabaya');
    $section->addText('Kunci: B');
    $section->addText('2. Pilih bilangan genap.');
    $section->addText('A. 2');
    $section->addText('B. 3');
    $section->addText('C. 4');
    $section->addText('Kunci: A,C');
    $section->addText('Tipe Soal: Isian Singkat');
    $section->addText('3. Siapa presiden pertama Indonesia?');
    $section->addText('A. Soekarno');
    $section->addText('B. Mohammad Hatta');
    $section->addText('Kunci Jawaban: A');
    $section->addText('4. Planet terdekat dari Matahari?');
    $section->addText('A. Merkurius');
    $section->addText('B. Venus');
    $section->addText('Kunci: A');
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    try {
        $hasil = app(SoalWordImportService::class)->import($paket, $path);
    } finally {
        @unlink($path);
    }

    expect($hasil->created)->toBe(4)
        ->and($hasil->hasErrors())->toBeFalse()
        ->and($paket->soal()->count())->toBe(4);

    $pertama = $paket->soal()->where('nomor', 1)->firstOrFail();
    $kedua = $paket->soal()->where('nomor', 2)->firstOrFail();
    $ketiga = $paket->soal()->where('nomor', 3)->firstOrFail();
    $keempat = $paket->soal()->where('nomor', 4)->firstOrFail();

    expect($pertama->tipe)->toBe(TipeSoal::Pg)
        ->and($pertama->opsi()->where('is_benar', true)->firstOrFail()->label)->toBe('B')
        ->and($kedua->tipe)->toBe(TipeSoal::PgKompleks)
        ->and($kedua->opsi()->where('is_benar', true)->count())->toBe(2)
        ->and($ketiga->tipe)->toBe(TipeSoal::IsianSingkat)
        ->and($ketiga->opsi()->where('is_benar', true)->firstOrFail()->teks)->toBe('Soekarno')
        ->and($keempat->tipe)->toBe(TipeSoal::Pg)
        ->and($keempat->opsi()->where('is_benar', true)->firstOrFail()->label)->toBe('A');
});

it('skips essay questions from Word import while essay is delayed', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    $path = storage_path('app/import-soal-essay-ditolak-test.docx');

    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('1. Jelaskan proses fotosintesis.');
    $section->addText('2. Ibu kota Indonesia adalah?');
    $section->addText('A. Bandung');
    $section->addText('B. Jakarta');
    $section->addText('Kunci: B');
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    try {
        $hasil = app(SoalWordImportService::class)->import($paket, $path);
    } finally {
        @unlink($path);
    }

    expect($hasil->created)->toBe(1)
        ->and($hasil->hasErrors())->toBeTrue()
        ->and($hasil->errors)->toHaveCount(1)
        ->and($hasil->errors[0]['row'])->toBe(1)
        ->and($paket->soal()->where('tipe', TipeSoal::Essay)->exists())->toBeFalse()
        ->and($paket->soal()->count())->toBe(1);
});

it('imports Word questions from an automatically numbered list', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    $path = storage_path('app/import-soal-numbered-test.docx');

    $phpWord = new PhpWord;
    $phpWord->addNumberingStyle('questions', [
        'type' => 'multilevel',
        'levels' => [
            ['format' => 'decimal', 'text' => '%1.', 'left' => 720, 'hanging' => 360, 'tabPos' => 720],
            ['format' => 'upperLetter', 'text' => '%2.', 'left' => 1440, 'hanging' => 360, 'tabPos' => 1440],
        ],
    ]);
    $section = $phpWord->addSection();
    $section->addListItem('Ibu kota Indonesia adalah?', 0, null, 'questions');
    $section->addListItem('Bandung', 1, null, 'questions');
    $section->addListItem('Jakarta', 1, null, 'questions');
    $section->addText('Kunci: B');
    $section->addListItem('Pilih bilangan genap.', 0, null, 'questions');
    $section->addListItem('2', 1, null, 'questions');
    $section->addListItem('3', 1, null, 'questions');
    $section->addListItem('4', 1, null, 'questions');
    $section->addText('Kunci: A,C');
    $section->addListItem('Jelaskan pentingnya air.', 0, null, 'questions');
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    try {
        $hasil = app(SoalWordImportService::class)->import($paket, $path);
    } finally {
        @unlink($path);
    }

    expect($hasil->created)->toBe(2)
        ->and($hasil->hasErrors())->toBeTrue()
        ->and($hasil->errors)->toHaveCount(1)
        ->and($hasil->errors[0]['row'])->toBe(3)
        ->and($paket->soal()->count())->toBe(2)
        ->and($paket->soal()->where('tipe', TipeSoal::Essay)->exists())->toBeFalse();

    $pertama = $paket->soal()->where('nomor', 1)->firstOrFail();
    $kedua = $paket->soal()->where('nomor', 2)->firstOrFail();

    expect($pertama->tipe)->toBe(TipeSoal::Pg)
        ->and($pertama->opsi()->where('is_benar', true)->firstOrFail()->label)->toBe('B')
        ->and($kedua->tipe)->toBe(TipeSoal::PgKompleks)
        ->and($kedua->opsi()->where('is_benar', true)->count())->toBe(2);
});

it('imports automatic Word option labels after a manually numbered question', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    $path = storage_path('app/import-soal-manual-question-lettered-options-test.docx');

    $phpWord = new PhpWord;
    $phpWord->addNumberingStyle('options', [
        'type' => 'multilevel',
        'levels' => [
            ['format' => 'decimal', 'text' => '%1.', 'left' => 720, 'hanging' => 360, 'tabPos' => 720],
            ['format' => 'upperLetter', 'text' => '%2.', 'left' => 1440, 'hanging' => 360, 'tabPos' => 1440],
        ],
    ]);
    $section = $phpWord->addSection();
    $section->addText('1. Ibu kota Indonesia adalah?');
    $section->addListItem('Bandung', 1, null, 'options');
    $section->addListItem('Jakarta', 1, null, 'options');
    $section->addText('Kunci: B');
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    try {
        $hasil = app(SoalWordImportService::class)->import($paket, $path);
    } finally {
        @unlink($path);
    }

    expect($hasil->created)->toBe(1)
        ->and($hasil->hasErrors())->toBeFalse()
        ->and($paket->soal()->count())->toBe(1);

    $soal = $paket->soal()->firstOrFail();

    expect($soal->tipe)->toBe(TipeSoal::Pg)
        ->and($soal->opsi()->count())->toBe(2)
        ->and($soal->opsi()->where('is_benar', true)->firstOrFail()->label)->toBe('B');
});

it('rejects a Word answer key that is not present in the options', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    $path = storage_path('app/import-soal-invalid-key-test.docx');

    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('1. Ibu kota Indonesia adalah?');
    $section->addText('A. Bandung');
    $section->addText('B. Jakarta');
    $section->addText('C. Surabaya');
    $section->addText('Kunci: E');
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    try {
        $hasil = app(SoalWordImportService::class)->import($paket, $path);
    } finally {
        @unlink($path);
    }

    expect($hasil->created)->toBe(0)
        ->and($hasil->hasErrors())->toBeTrue()
        ->and($hasil->errors)->toHaveCount(1)
        ->and($hasil->errors[0]['row'])->toBe(1)
        ->and($hasil->errors[0]['messages'])->toContain('Kunci jawaban E tidak ada pada opsi (A, B, C).')
        ->and($paket->soal()->count())->toBe(0);
});

it('downloads the Word import template for admins and teachers', function () {
    $this->actingAs(User::factory()->admin()->create())
        ->get(route('admin.import-template-word'))
        ->assertOk()
        ->assertDownload('template-import-soal.docx');

    $this->actingAs(User::factory()->guru()->create())
        ->get(route('admin.import-template-word'))
        ->assertOk()
        ->assertDownload('template-import-soal.docx');
});

it('builds a Word template that imports as PG, PG kompleks, and isian singkat', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    $response = app(WordTemplateService::class)->download();
    $path = $response->getFile()->getPathname();

    try {
        $hasil = app(SoalWordImportService::class)->import($paket, $path);
    } finally {
        @unlink($path);
    }

    expect($hasil->created)->toBe(3)
        ->and($hasil->hasErrors())->toBeFalse()
        ->and($paket->soal()->count())->toBe(3);

    $pertama = $paket->soal()->where('nomor', 1)->firstOrFail();
    $kedua = $paket->soal()->where('nomor', 2)->firstOrFail();
    $ketiga = $paket->soal()->where('nomor', 3)->firstOrFail();

    expect($pertama->tipe)->toBe(TipeSoal::Pg)
        ->and($pertama->opsi()->count())->toBe(5)
        ->and($pertama->opsi()->where('label', 'E')->firstOrFail()->teks)->toBe('Makassar')
        ->and($kedua->tipe)->toBe(TipeSoal::PgKompleks)
        ->and($kedua->opsi()->count())->toBe(5)
        ->and($kedua->opsi()->where('label', 'E')->firstOrFail()->teks)->toBe('6')
        ->and($ketiga->tipe)->toBe(TipeSoal::IsianSingkat)
        ->and($ketiga->opsi()->where('is_benar', true)->firstOrFail()->teks)->toBe('Soekarno');
});

it('imports a Word file through the relation manager action', function () {
    Storage::fake('local');
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->create(['dibuat_oleh' => $admin->id]);
    $relativePath = 'import-word/import-action.docx';
    $path = Storage::disk('local')->path($relativePath);
    Storage::disk('local')->makeDirectory('import-word');

    $phpWord = new PhpWord;
    $section = $phpWord->addSection();
    $section->addText('1. Ibu kota Indonesia adalah?');
    $section->addText('A. Bandung');
    $section->addText('B. Jakarta');
    $section->addText('Kunci: B');
    IOFactory::createWriter($phpWord, 'Word2007')->save($path);

    $this->actingAs($admin);

    Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $paket,
        'pageClass' => EditPaketSoal::class,
    ])
        ->callTableAction('importWord', data: ['file' => [$relativePath]])
        ->assertHasNoTableActionErrors();

    expect($paket->soal()->count())->toBe(1)
        ->and($paket->soal()->firstOrFail()->tipe)->toBe(TipeSoal::Pg)
        ->and(Storage::disk('local')->exists($relativePath))->toBeFalse();
});

it('rejects marking a package containing essay as ready', function () {
    $data = siapkanUjianTujuh(false);
    $data['paket']->update(['status' => StatusPaketSoal::Draft]);

    expect(fn () => app(PaketSoalService::class)->markReady($data['paket']))
        ->toThrow(ValidationException::class);

    expect($data['paket']->refresh()->status)->toBe(StatusPaketSoal::Draft);
});

it('requires an accepted answer for a short-answer question', function () {
    $paket = PaketSoal::factory()->create(['status' => StatusPaketSoal::Draft]);
    $soal = Soal::factory()->create([
        'paket_soal_id' => $paket->id,
        'tipe' => TipeSoal::IsianSingkat,
    ]);
    OpsiSoal::factory()->create([
        'soal_id' => $soal->id,
        'label' => 'A',
        'teks' => 'Jakarta',
        'is_benar' => false,
    ]);

    expect(fn () => app(PaketSoalService::class)->markReady($paket))
        ->toThrow(ValidationException::class);
});

it('logs out an admin after the configured idle timeout', function () {
    app(PengaturanSekolahService::class)->update(['idle_timeout_menit' => 10]);

    $admin = User::factory()->admin()->create();

    $this->actingAs($admin)
        ->withSession(['panel_last_activity' => now()->subMinutes(11)->timestamp])
        ->get('/admin')
        ->assertRedirect('/admin/login');

    $this->assertGuest('web');
});

it('creates non-PG questions with conditional options through the relation manager', function () {
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->create(['dibuat_oleh' => $admin->id]);

    $this->actingAs($admin);

    Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $paket,
        'pageClass' => EditPaketSoal::class,
    ])
        ->callTableAction('create', data: [
            'tipe' => TipeSoal::PgKompleks->value,
            'pertanyaan' => 'Pilih bilangan genap.',
            'bobot' => 1,
            'opsi' => [
                ['label' => 'A', 'teks' => '2', 'is_benar' => true, 'urutan' => 1],
                ['label' => 'B', 'teks' => '3', 'is_benar' => false, 'urutan' => 2],
                ['label' => 'C', 'teks' => '4', 'is_benar' => true, 'urutan' => 3],
            ],
        ])
        ->assertHasNoTableActionErrors();

    $soal = Soal::query()->where('paket_soal_id', $paket->id)->firstOrFail();

    expect($soal->tipe)->toBe(TipeSoal::PgKompleks)
        ->and($soal->opsi()->where('is_benar', true)->count())->toBe(2);
});

it('rejects creating an essay question through the relation manager while delayed', function () {
    $admin = User::factory()->admin()->create();
    $paket = PaketSoal::factory()->create(['dibuat_oleh' => $admin->id]);

    $this->actingAs($admin);

    Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $paket,
        'pageClass' => EditPaketSoal::class,
    ])
        ->callTableAction('create', data: [
            'tipe' => TipeSoal::Essay->value,
            'pertanyaan' => 'Jelaskan proses fotosintesis.',
            'bobot' => 1,
        ])
        ->assertHasTableActionErrors(['tipe']);

    expect(Soal::query()->where('paket_soal_id', $paket->id)->exists())->toBeFalse();
});

it('hydrates the edit form for non-PG question types', function () {
    $data = siapkanUjianTujuh();
    $admin = User::factory()->admin()->create();

    $this->actingAs($admin);

    Livewire::test(SoalRelationManager::class, [
        'ownerRecord' => $data['paket'],
        'pageClass' => EditPaketSoal::class,
    ])
        ->mountTableAction('edit', $data['soal']['kompleks'])
        ->assertOk();
});
