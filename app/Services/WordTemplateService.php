<?php

namespace App\Services;

use InvalidArgumentException;
use PhpOffice\PhpWord\IOFactory;
use PhpOffice\PhpWord\PhpWord;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Throwable;

class WordTemplateService
{
    /**
     * Build and download an example Word question import template.
     */
    public function download(): BinaryFileResponse
    {
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cbt_template_soal_');

        if ($temporaryPath === false) {
            throw new InvalidArgumentException('Tidak dapat membuat file template.');
        }

        $path = $temporaryPath.'.docx';
        @unlink($temporaryPath);

        try {
            $phpWord = new PhpWord;
            $section = $phpWord->addSection();

            $section->addText('Template Import Soal Word', ['bold' => true, 'size' => 16]);
            $section->addText('Setiap soal diawali nomor. Opsi ditulis A sampai E.');
            $section->addText('Kunci jawaban ditulis pada baris "Kunci: A" atau "Kunci: A,C".');
            $section->addText('Tipe isian singkat diawali baris "Tipe Soal: Isian Singkat".');
            $section->addText('Soal tanpa opsi akan dilewati karena tipe essay belum diaktifkan. Nomor otomatis Word juga didukung.');
            $section->addText('Hapus contoh di bawah ini sebelum mengisi soal Anda.', ['italic' => true]);
            $section->addTextBreak();

            $section->addText('1. Ibu kota Indonesia adalah?');
            $section->addText('A. Bandung');
            $section->addText('B. Jakarta');
            $section->addText('C. Surabaya');
            $section->addText('D. Medan');
            $section->addText('E. Makassar');
            $section->addText('Kunci: B');
            $section->addTextBreak();

            $section->addText('2. Pilih bilangan genap.');
            $section->addText('A. 2');
            $section->addText('B. 3');
            $section->addText('C. 4');
            $section->addText('D. 5');
            $section->addText('E. 6');
            $section->addText('Kunci: A,C');
            $section->addTextBreak();

            $section->addText('Tipe Soal: Isian Singkat');
            $section->addText('3. Siapa presiden pertama Indonesia?');
            $section->addText('A. Soekarno');
            $section->addText('B. Mohammad Hatta');
            $section->addText('Kunci: A');

            IOFactory::createWriter($phpWord, 'Word2007')->save($path);
        } catch (Throwable $exception) {
            @unlink($path);

            throw $exception;
        }

        return response()
            ->download($path, 'template-import-soal.docx', [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            ])
            ->deleteFileAfterSend(true);
    }
}
