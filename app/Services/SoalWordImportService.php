<?php

namespace App\Services;

use App\Enums\TipeSoal;
use App\Models\OpsiSoal;
use App\Models\PaketSoal;
use App\Models\Soal;
use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpWord\Element\AbstractContainer;
use PhpOffice\PhpWord\Element\ListItem;
use PhpOffice\PhpWord\Element\ListItemRun;
use PhpOffice\PhpWord\Element\Table;
use PhpOffice\PhpWord\Element\Text;
use PhpOffice\PhpWord\Element\TextRun;
use PhpOffice\PhpWord\IOFactory;
use Throwable;
use ZipArchive;

class SoalWordImportService
{
    public function __construct(private PaketSoalLockService $lockService) {}

    /**
     * Import questions from a .docx file using a simple numbered format.
     *
     * Format:
     * 1. Question text
     * A. First option
     * B. Second option
     * Kunci: A
     *
     * Use "Kunci: A,C" for multiple answers. A line without options is rejected
     * because essay is not active yet.
     * Word automatic numbering is supported for question numbers and option labels.
     */
    public function import(PaketSoal $paketSoal, string $path): ImportResult
    {
        $this->lockService->pastikanTidakTerkunci($paketSoal);

        $hasil = new ImportResult;
        $pertanyaan = $this->urai($this->bacaBaris($path));

        if ($pertanyaan === []) {
            $hasil->addError(1, 'Tidak ada soal yang dapat dibaca dari dokumen.');

            return $hasil;
        }

        DB::transaction(function () use ($paketSoal, $pertanyaan, $hasil): void {
            $nomor = ((int) $paketSoal->soal()->max('nomor')) + 1;

            foreach ($pertanyaan as $indeks => $data) {
                $teks = trim($data['pertanyaan']);

                if ($teks === '') {
                    $hasil->addError($indeks + 1, 'Pertanyaan kosong.');

                    continue;
                }

                $opsi = $data['opsi'];
                $kunci = $data['kunci'];

                if ($data['tipe'] === null) {
                    if ($opsi === []) {
                        $hasil->addError($indeks + 1, 'Soal tanpa opsi tidak dapat diimpor karena tipe essay belum diaktifkan.');
                        $nomor++;

                        continue;
                    }

                    if (count($kunci) > 1) {
                        $tipe = TipeSoal::PgKompleks;
                    } else {
                        $tipe = TipeSoal::Pg;
                    }
                } else {
                    $tipe = $data['tipe'];
                }

                if (! $tipe->bolehDibuat()) {
                    $hasil->addError($indeks + 1, 'Tipe soal '.$tipe->getLabel().' belum diaktifkan sehingga tidak dapat diimpor.');
                    $nomor++;

                    continue;
                }

                if ($tipe->usesOptions() && $opsi === []) {
                    $hasil->addError($indeks + 1, 'Soal '.$tipe->getLabel().' tidak memiliki opsi.');
                    $nomor++;

                    continue;
                }

                if (in_array($tipe, [TipeSoal::Pg, TipeSoal::PgKompleks, TipeSoal::BenarSalah], true) && $kunci === []) {
                    $hasil->addError($indeks + 1, 'Kunci jawaban tidak ditemukan.');
                    $nomor++;

                    continue;
                }

                if ($tipe->usesOptions() && $kunci !== []) {
                    $kunciTidakDikenal = array_values(array_diff($kunci, array_keys($opsi)));

                    if ($kunciTidakDikenal !== []) {
                        $hasil->addError(
                            $indeks + 1,
                            'Kunci jawaban '.implode(', ', $kunciTidakDikenal).' tidak ada pada opsi ('.implode(', ', array_keys($opsi)).').',
                        );
                        $nomor++;

                        continue;
                    }
                }

                $soal = Soal::query()->create([
                    'paket_soal_id' => $paketSoal->id,
                    'nomor' => $nomor++,
                    'tipe' => $tipe,
                    'pertanyaan' => nl2br(e($teks)),
                    'bobot' => 1,
                    'pembahasan' => filled($data['pembahasan']) ? $data['pembahasan'] : null,
                ]);

                $urutan = 1;

                foreach ($opsi as $label => $teksOpsi) {
                    $pasangan = null;

                    if ($tipe === TipeSoal::Menjodohkan && str_contains($teksOpsi, '=')) {
                        [$teksOpsi, $pasangan] = array_map('trim', explode('=', $teksOpsi, 2));
                    }

                    $isBenar = in_array($label, $kunci, true);

                    if ($tipe === TipeSoal::BenarSalahPernyataan && preg_match('/\[(benar|salah|b|s)\]/i', $teksOpsi, $cocok)) {
                        $isBenar = in_array(mb_strtolower($cocok[1]), ['benar', 'b'], true);
                        $teksOpsi = trim(str_replace($cocok[0], '', $teksOpsi));
                    }

                    OpsiSoal::query()->create([
                        'soal_id' => $soal->id,
                        'label' => $label,
                        'teks' => trim($teksOpsi),
                        'is_benar' => $isBenar,
                        'urutan' => $urutan++,
                        'pasangan' => $pasangan,
                    ]);
                }

                $hasil->created++;
            }
        });

        return $hasil;
    }

    /**
     * @return array<int, string>
     */
    private function bacaBaris(string $path): array
    {
        try {
            $dokumen = IOFactory::load($path);
        } catch (Throwable $exception) {
            throw new \RuntimeException('File Word tidak dapat dibaca: '.$exception->getMessage(), 0, $exception);
        }

        $baris = [];
        $state = [
            'penomoran' => $this->bacaFormatPenomoran($path),
            'nomor_soal' => 0,
            'nomor_opsi' => 0,
            'ada_soal' => false,
        ];

        foreach ($dokumen->getSections() as $bagian) {
            $this->kumpulkanTeks($bagian->getElements(), $baris, $state);
        }

        return $baris;
    }

    /**
     * PhpWord membaca teks list item tanpa nomor yang terlihat di Word.
     * Definisi numbering dipakai untuk merekonstruksi nomor soal dan label opsi.
     *
     * @return array<int, array<int, string>>
     */
    private function bacaFormatPenomoran(string $path): array
    {
        $zip = new ZipArchive;

        if (@$zip->open($path) !== true) {
            return [];
        }

        $xml = $zip->getFromName('word/numbering.xml');
        $zip->close();

        if (! is_string($xml) || $xml === '') {
            return [];
        }

        $dokumen = new DOMDocument;

        if (! @$dokumen->loadXML($xml, LIBXML_NONET)) {
            return [];
        }

        $xpath = new DOMXPath($dokumen);
        $namespace = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';
        $xpath->registerNamespace('w', $namespace);

        $abstractNodes = $xpath->query('//w:abstractNum');

        if ($abstractNodes === false) {
            return [];
        }

        $abstract = [];

        foreach ($abstractNodes as $abstractNode) {
            if (! $abstractNode instanceof DOMElement) {
                continue;
            }

            $abstractId = $abstractNode->getAttributeNS($namespace, 'abstractNumId');
            $levelNodes = $xpath->query('w:lvl', $abstractNode);

            if ($levelNodes === false) {
                continue;
            }

            $level = [];

            foreach ($levelNodes as $levelNode) {
                if (! $levelNode instanceof DOMElement) {
                    continue;
                }

                $levelId = (int) ($levelNode->getAttributeNS($namespace, 'ilvl') ?: 0);
                $formatNodes = $xpath->query('w:numFmt', $levelNode);
                $formatNode = $formatNodes === false ? null : $formatNodes->item(0);

                if (! $formatNode instanceof DOMElement) {
                    continue;
                }

                $format = $formatNode->getAttributeNS($namespace, 'val');

                if ($format !== '') {
                    $level[$levelId] = $format;
                }
            }

            $abstract[$abstractId] = $level;
        }

        $numNodes = $xpath->query('//w:num');

        if ($numNodes === false) {
            return [];
        }

        $penomoran = [];

        foreach ($numNodes as $numNode) {
            if (! $numNode instanceof DOMElement) {
                continue;
            }

            $numId = $numNode->getAttributeNS($namespace, 'numId');
            $abstractNodes = $xpath->query('w:abstractNumId', $numNode);
            $abstractNode = $abstractNodes === false ? null : $abstractNodes->item(0);

            if (! $abstractNode instanceof DOMElement) {
                continue;
            }

            $abstractId = $abstractNode->getAttributeNS($namespace, 'val');

            if ($numId !== '' && isset($abstract[$abstractId])) {
                $penomoran[(int) $numId] = $abstract[$abstractId];
            }
        }

        return $penomoran;
    }

    /**
     * @param  array<int, mixed>  $elements
     * @param  array<int, string>  $baris
     * @param  array{penomoran: array<int, array<int, string>>, nomor_soal: int, nomor_opsi: int, ada_soal: bool}  $state
     */
    private function kumpulkanTeks(array $elements, array &$baris, array &$state): void
    {
        foreach ($elements as $element) {
            if ($element instanceof Text) {
                $this->tambahkanBarisBiasa((string) $element->getText(), $baris, $state);

                continue;
            }

            if ($element instanceof ListItem || $element instanceof ListItemRun) {
                $this->tambahkanListItem($element, $baris, $state);

                continue;
            }

            if ($element instanceof TextRun) {
                $this->tambahkanBarisBiasa((string) $element->getText(), $baris, $state);

                continue;
            }

            if ($element instanceof Table) {
                foreach ($element->getRows() as $row) {
                    $sel = [];

                    foreach ($row->getCells() as $cell) {
                        $teksSel = [];

                        foreach ($cell->getElements() as $isi) {
                            $this->kumpulkanTeks([$isi], $teksSel, $state);
                        }

                        $sel[] = trim(implode(' ', $teksSel));
                    }

                    $baris[] = implode("\t", $sel);
                }

                continue;
            }

            if ($element instanceof AbstractContainer) {
                $this->kumpulkanTeks($element->getElements(), $baris, $state);
            }
        }
    }

    /**
     * @param  array<int, string>  $baris
     * @param  array{penomoran: array<int, array<int, string>>, nomor_soal: int, nomor_opsi: int, ada_soal: bool}  $state
     */
    private function tambahkanBarisBiasa(string $teks, array &$baris, array &$state): void
    {
        $teks = trim($teks);

        if ($teks === '') {
            return;
        }

        if ($this->barisKontrol($teks)) {
            $state['ada_soal'] = false;
        } elseif ($this->sudahBernomor($teks)) {
            $state['ada_soal'] = true;
            $state['nomor_opsi'] = 0;
        }

        $baris[] = $teks;
    }

    /**
     * @param  array<int, string>  $baris
     * @param  array{penomoran: array<int, array<int, string>>, nomor_soal: int, nomor_opsi: int, ada_soal: bool}  $state
     */
    private function tambahkanListItem(ListItem|ListItemRun $element, array &$baris, array &$state): void
    {
        $teks = trim((string) $element->getText());

        if ($teks === '') {
            return;
        }

        if ($this->barisKontrol($teks)) {
            $state['ada_soal'] = false;
            $baris[] = $teks;

            return;
        }

        $format = $this->formatDaftar($element, $state['penomoran']);
        $kedalaman = (int) $element->getDepth();

        if ($kedalaman === 0
            && $format !== null
            && $this->formatSoal($format)
            && ! $this->sudahBernomor($teks)) {
            $state['nomor_soal']++;
            $state['nomor_opsi'] = 0;
            $state['ada_soal'] = true;
            $baris[] = $state['nomor_soal'].'. '.$teks;

            return;
        }

        if ($format !== null
            && $this->formatOpsi($format)
            && $state['ada_soal']
            && ! $this->sudahBerlabelOpsi($teks)) {
            $label = $this->labelOpsi($state['nomor_opsi']);

            if ($label !== null) {
                $state['nomor_opsi']++;
                $baris[] = $label.'. '.$teks;

                return;
            }
        }

        if ($this->sudahBernomor($teks)) {
            $state['ada_soal'] = true;
            $state['nomor_opsi'] = 0;
        }

        $baris[] = $teks;
    }

    /**
     * @param  array<int, array<int, string>>  $penomoran
     */
    private function formatDaftar(ListItem|ListItemRun $element, array $penomoran): ?string
    {
        $style = $element->getStyle();
        $numStyle = $style?->getNumStyle();

        if (! is_string($numStyle) || ! preg_match('/^PHPWordList(\d+)$/', $numStyle, $cocok)) {
            return null;
        }

        return $penomoran[(int) $cocok[1]][(int) $element->getDepth()] ?? null;
    }

    private function formatSoal(string $format): bool
    {
        return in_array($format, ['decimal', 'decimalZero', 'decimalFullWidth', 'decimalHalfWidth'], true);
    }

    private function formatOpsi(string $format): bool
    {
        return in_array($format, ['upperLetter', 'lowerLetter'], true);
    }

    private function barisKontrol(string $teks): bool
    {
        return (bool) preg_match('/^(?:kunci(?:\s+jawaban)?|jawaban|pembahasan|(?:tipe|jenis)(?:\s+soal)?)\s*[:=]/i', $teks);
    }

    private function sudahBernomor(string $teks): bool
    {
        return (bool) preg_match('/^\d+\s*[\.\)]\s*\S/u', $teks);
    }

    private function sudahBerlabelOpsi(string $teks): bool
    {
        return (bool) preg_match('/^\*?\s*[A-H]\s*[\.\)]\s*\S/i', $teks);
    }

    private function labelOpsi(int $indeks): ?string
    {
        return $indeks <= 7 ? chr(65 + $indeks) : null;
    }

    /**
     * @param  array<int, string>  $baris
     * @return array<int, array{pertanyaan: string, opsi: array<string, string>, kunci: array<int, string>, tipe: TipeSoal|null, pembahasan: string|null}>
     */
    private function urai(array $baris): array
    {
        $hasil = [];
        $sekarang = null;
        $opsiTerakhir = null;
        $tipeBerikutnya = null;

        foreach ($baris as $barisMentah) {
            $teks = trim(preg_replace('/\s+/', ' ', $barisMentah) ?? '');

            if ($teks === '') {
                continue;
            }

            if (preg_match('/^(?:tipe|jenis)(?:\s+soal)?\s*[:=]\s*(.+)$/i', $teks, $cocok)) {
                $tipeBerikutnya = $this->tipeDari($cocok[1]);

                continue;
            }

            if (preg_match('/^(\d+)\s*[\.\)]\s*(.+)$/u', $teks, $cocok)) {
                if ($sekarang !== null) {
                    $hasil[] = $sekarang;
                }

                $sekarang = [
                    'pertanyaan' => trim($cocok[2]),
                    'opsi' => [],
                    'kunci' => [],
                    'tipe' => $tipeBerikutnya,
                    'pembahasan' => null,
                ];
                $opsiTerakhir = null;
                $tipeBerikutnya = null;

                continue;
            }

            if ($sekarang === null) {
                continue;
            }

            if (preg_match('/^(?:kunci(?:\s+jawaban)?|jawaban)\s*[:=]\s*(.+)$/i', $teks, $cocok)) {
                preg_match_all('/[A-H]/i', $cocok[1], $kunci);
                $sekarang['kunci'] = array_values(array_unique(array_map('strtoupper', $kunci[0])));

                continue;
            }

            if (preg_match('/^pembahasan\s*[:=]\s*(.+)$/i', $teks, $cocok)) {
                $sekarang['pembahasan'] = trim($cocok[1]);

                continue;
            }

            if (preg_match('/^\*?\s*([A-H])\s*[\.\)]\s*(.+)$/i', $teks, $cocok)) {
                $label = strtoupper($cocok[1]);
                $sekarang['opsi'][$label] = trim($cocok[2]);
                $opsiTerakhir = $label;

                if (str_starts_with($teks, '*')) {
                    $sekarang['kunci'][] = $label;
                    $sekarang['kunci'] = array_values(array_unique($sekarang['kunci']));
                }

                continue;
            }

            if ($opsiTerakhir !== null) {
                $sekarang['opsi'][$opsiTerakhir] .= ' '.$teks;

                continue;
            }

            $sekarang['pertanyaan'] .= "\n".$teks;
        }

        if ($sekarang !== null) {
            $hasil[] = $sekarang;
        }

        return $hasil;
    }

    private function tipeDari(string $nilai): ?TipeSoal
    {
        $nilai = mb_strtolower(trim($nilai));

        $langsung = TipeSoal::tryFrom($nilai);

        if ($langsung !== null) {
            return $langsung;
        }

        return match ($nilai) {
            'pg', 'pilihan ganda' => TipeSoal::Pg,
            'pg kompleks', 'pilihan ganda kompleks' => TipeSoal::PgKompleks,
            'benar salah', 'benar/salah' => TipeSoal::BenarSalah,
            'isian', 'isian singkat' => TipeSoal::IsianSingkat,
            'uraian', 'esai' => TipeSoal::Essay,
            'menjodohkan' => TipeSoal::Menjodohkan,
            'benar salah pernyataan', 'benar/salah pernyataan' => TipeSoal::BenarSalahPernyataan,
            'urutan' => TipeSoal::Urutan,
            default => null,
        };
    }
}
