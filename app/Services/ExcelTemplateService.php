<?php

namespace App\Services;

use InvalidArgumentException;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Writer\XLSX\Writer;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExcelTemplateService
{
    /**
     * Build and download an empty import template for a supported data type.
     */
    public function download(string $type): BinaryFileResponse
    {
        $definition = $this->definition($type);
        $path = tempnam(sys_get_temp_dir(), 'cbt_template_');

        if ($path === false) {
            throw new InvalidArgumentException('Tidak dapat membuat file template.');
        }

        $writer = new Writer;
        $writer->openToFile($path);
        $writer->addRow(Row::fromValues($definition['headers']));
        $writer->addRow(Row::fromValues($definition['sample']));
        $writer->close();

        return response()
            ->download($path, "template-import-{$type}.xlsx", [
                'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ])
            ->deleteFileAfterSend(true);
    }

    /**
     * @return array{headers: array<int, string>, sample: array<int, mixed>}
     */
    private function definition(string $type): array
    {
        return match ($type) {
            'jurusan' => [
                'headers' => ['kode', 'nama'],
                'sample' => ['TKJ', 'Teknik Komputer dan Jaringan'],
            ],
            'kelas' => [
                'headers' => ['nama', 'tingkat', 'jurusan_kode', 'wali_kelas_nip'],
                'sample' => ['X TKJ 1', 10, 'TKJ', '198001012006041001'],
            ],
            'mapel' => [
                'headers' => ['kode', 'nama', 'kelompok', 'is_peminatan'],
                'sample' => ['MTK', 'Matematika', 'Umum', 0],
            ],
            'guru' => [
                'headers' => ['nama', 'nip', 'jenis_kelamin', 'no_hp', 'username', 'email', 'password'],
                'sample' => ['Guru Contoh', '198001012006041002', 'L', '081234567890', 'gurucontoh', 'guru@example.test', 'password123'],
            ],
            'siswa' => [
                'headers' => [
                    'nis',
                    'nisn',
                    'nama',
                    'jenis_kelamin',
                    'tempat_lahir',
                    'tanggal_lahir',
                    'agama_nama',
                    'kelas_nama',
                    'username',
                    'security_key',
                    'is_aktif',
                ],
                'sample' => [
                    '20260001',
                    '0099000001',
                    'Siswa Contoh',
                    'L',
                    'Bandung',
                    '2008-01-01',
                    'Islam',
                    'X TKJ 1',
                    'siswacontoh',
                    '',
                    1,
                ],
            ],
            default => throw new InvalidArgumentException('Jenis template tidak dikenal.'),
        };
    }
}
