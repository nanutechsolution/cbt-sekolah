<?php

namespace App\Services;

use App\Models\JadwalUjian;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;

class AnalisisButirExportService
{
    /**
     * Create an XLSX analysis file and return its absolute path.
     *
     * @param  array<string, mixed>  $analisis
     */
    public function buat(JadwalUjian $jadwal, array $analisis): string
    {
        $direktori = storage_path('app/analisis');
        File::ensureDirectoryExists($direktori);

        $path = $direktori.'/analisis-butir-'.$jadwal->id.'-'.Str::uuid().'.xlsx';

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Ringkasan');

        $writer->addRow(Row::fromValues(['ANALISIS BUTIR SOAL']));
        $writer->addRow(Row::fromValues(['Jadwal', $jadwal->nama]));
        $writer->addRow(Row::fromValues(['Paket Soal', $jadwal->paketSoal?->nama]));
        $writer->addRow(Row::fromValues(['Mata Pelajaran', $jadwal->paketSoal?->mapel?->nama]));
        $writer->addRow(Row::fromValues(['Tanggal', $jadwal->mulai?->format('d/m/Y H:i')]));
        $writer->addRow(Row::fromValues(['Jumlah Peserta Selesai', $analisis['ringkasan']['jumlah_selesai']]));
        $writer->addRow(Row::fromValues(['Rata-rata Nilai', $analisis['ringkasan']['rata_rata']]));
        $writer->addRow(Row::fromValues(['Nilai Tertinggi', $analisis['ringkasan']['tertinggi']]));
        $writer->addRow(Row::fromValues(['Nilai Terendah', $analisis['ringkasan']['terendah']]));
        $writer->addRow(Row::fromValues([]));

        $writer->addRow(Row::fromValues([
            'No',
            'Tipe',
            'Pertanyaan',
            'Bobot',
            'Menjawab',
            'Benar',
            'Tingkat Kesukaran',
            'Kategori Kesukaran',
            'Daya Beda',
            'Kategori Daya Beda',
        ]));

        foreach ($analisis['butir'] as $butir) {
            $writer->addRow(Row::fromValues([
                $butir['nomor'],
                $butir['tipe_label'],
                html_entity_decode(strip_tags((string) $butir['pertanyaan'])),
                $butir['bobot'],
                $butir['jumlah_menjawab'],
                $butir['jumlah_benar'],
                $butir['tingkat_kesukaran'],
                $butir['kategori_kesukaran'],
                $butir['daya_beda'],
                $butir['kategori_daya_beda'],
            ]));
        }

        $writer->addNewSheetAndMakeItCurrent()->setName('Rincian Opsi');
        $writer->addRow(Row::fromValues([
            'No Soal',
            'Pertanyaan',
            'Opsi',
            'Teks Opsi',
            'Kunci',
            'Dipilih',
            'Persentase',
            'Kelompok Atas',
            'Kelompok Bawah',
            'Efektivitas Pengecoh',
        ]));

        foreach ($analisis['butir'] as $butir) {
            foreach ($butir['opsi'] as $opsi) {
                $writer->addRow(Row::fromValues([
                    $butir['nomor'],
                    html_entity_decode(strip_tags((string) $butir['pertanyaan'])),
                    $opsi['label'],
                    html_entity_decode(strip_tags((string) $opsi['teks'])),
                    $opsi['is_benar'] ? 'Ya' : 'Tidak',
                    $opsi['dipilih'],
                    $opsi['persentase'],
                    $opsi['atas'],
                    $opsi['bawah'],
                    $opsi['efektif'] === null ? '-' : ($opsi['efektif'] ? 'Efektif' : 'Kurang efektif'),
                ]));
            }
        }

        $writer->close();

        return $path;
    }
}
