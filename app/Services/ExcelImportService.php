<?php

namespace App\Services;

use App\Models\Agama;
use App\Models\Guru;
use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Mapel;
use DateTimeInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use OpenSpout\Reader\XLSX\Reader;
use Throwable;

class ExcelImportService
{
    /**
     * Import an Excel file into one of the supported school data tables.
     */
    public function import(string $type, string $filePath): ImportResult
    {
        if (! is_file($filePath)) {
            throw new InvalidArgumentException('File import tidak ditemukan.');
        }

        $rows = $this->readRows($filePath);

        if (count($rows) < 2) {
            throw new InvalidArgumentException('File Excel tidak berisi data. Gunakan template yang disediakan.');
        }

        $headers = array_map($this->normalizeHeader(...), array_shift($rows));
        $requiredHeaders = $this->requiredHeaders($type);
        $missingHeaders = array_diff($requiredHeaders, $headers);

        if ($missingHeaders !== []) {
            throw new InvalidArgumentException(
                'Kolom wajib tidak ditemukan: '.implode(', ', $missingHeaders).'. Gunakan template yang disediakan.',
            );
        }

        $result = new ImportResult;

        foreach ($rows as $index => $rawRow) {
            $rowNumber = $index + 2;
            $row = [];

            foreach ($headers as $columnIndex => $header) {
                if ($header === '') {
                    continue;
                }

                $row[$header] = $rawRow[$columnIndex] ?? null;
            }

            if ($this->isEmptyRow($row)) {
                continue;
            }

            try {
                match ($type) {
                    'jurusan' => $this->importJurusan($row, $result),
                    'kelas' => $this->importKelas($row, $result),
                    'mapel' => $this->importMapel($row, $result),
                    'guru' => $this->importGuru($row, $result),
                    'siswa' => $this->importSiswa($row, $result),
                    default => throw new InvalidArgumentException('Jenis import tidak dikenal.'),
                };
            } catch (ValidationException $exception) {
                $result->addError($rowNumber, collect($exception->errors())->flatten()->all());
            } catch (Throwable $exception) {
                $result->addError($rowNumber, $exception->getMessage());
            }
        }

        return $result;
    }

    /**
     * @return array<int, array<int, mixed>>
     */
    private function readRows(string $filePath): array
    {
        $reader = new Reader;

        try {
            $reader->open($filePath);
            $rows = [];

            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    $rows[] = array_map($this->normalizeCell(...), $row->toArray());
                }

                break;
            }

            return $rows;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException('File Excel tidak dapat dibaca. Pastikan file berformat .xlsx.', 0, $exception);
        } finally {
            try {
                $reader->close();
            } catch (Throwable) {
                // The reader may not have opened a valid archive.
            }
        }
    }

    private function normalizeHeader(mixed $value): string
    {
        $header = strtolower(trim((string) $value));
        $header = str_replace(["\xEF\xBB\xBF", ' ', '-'], ['', '_', '_'], $header);

        return trim($header, '_');
    }

    private function normalizeCell(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (filled($value)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return array<int, string>
     */
    private function requiredHeaders(string $type): array
    {
        return match ($type) {
            'jurusan' => ['kode', 'nama'],
            'kelas' => ['nama', 'tingkat'],
            'mapel' => ['kode', 'nama'],
            'guru' => ['nama', 'jenis_kelamin', 'username', 'password'],
            'siswa' => ['nis', 'nama', 'jenis_kelamin', 'kelas_nama', 'username'],
            default => throw new InvalidArgumentException('Jenis import tidak dikenal.'),
        };
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importJurusan(array $row, ImportResult $result): void
    {
        $data = [
            'kode' => strtoupper((string) ($row['kode'] ?? '')),
            'nama' => $this->string($row['nama'] ?? null),
        ];

        Validator::make($data, [
            'kode' => ['required', 'max:20', 'alpha_dash'],
            'nama' => ['required', 'max:100'],
        ], $this->validationMessages())->validate();

        $existing = Jurusan::withTrashed()->where('kode', $data['kode'])->first();

        if ($existing && ! $existing->trashed()) {
            throw ValidationException::withMessages(['kode' => "Kode jurusan {$data['kode']} sudah ada."]);
        }

        if ($existing) {
            $existing->restore();
            $existing->update(['nama' => $data['nama']]);
            $result->updated++;

            return;
        }

        Jurusan::query()->create($data);
        $result->created++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importKelas(array $row, ImportResult $result): void
    {
        $data = [
            'nama' => $this->string($row['nama'] ?? null),
            'tingkat' => $row['tingkat'] ?? null,
            'jurusan_kode' => $this->string($row['jurusan_kode'] ?? null),
            'wali_kelas_nip' => $this->string($row['wali_kelas_nip'] ?? null),
        ];

        Validator::make($data, [
            'nama' => ['required', 'max:50'],
            'tingkat' => ['required', 'integer', 'between:1,12'],
            'jurusan_kode' => ['nullable', 'string', 'max:20'],
            'wali_kelas_nip' => ['nullable', 'string', 'max:30'],
        ], $this->validationMessages())->validate();

        $jurusan = null;

        if (filled($data['jurusan_kode'])) {
            $jurusan = Jurusan::query()->where('kode', strtoupper((string) $data['jurusan_kode']))->first();

            if (! $jurusan) {
                throw ValidationException::withMessages(['jurusan_kode' => 'Kode jurusan tidak ditemukan.']);
            }
        }

        $waliKelas = null;

        if (filled($data['wali_kelas_nip'])) {
            $waliKelas = Guru::query()->where('nip', $data['wali_kelas_nip'])->first();

            if (! $waliKelas) {
                throw ValidationException::withMessages(['wali_kelas_nip' => 'NIP wali kelas tidak ditemukan.']);
            }
        }

        $existing = Kelas::withTrashed()->where('nama', $data['nama'])->first();

        if ($existing && ! $existing->trashed()) {
            throw ValidationException::withMessages(['nama' => "Nama kelas {$data['nama']} sudah ada."]);
        }

        $attributes = [
            'tingkat' => (int) $data['tingkat'],
            'jurusan_id' => $jurusan?->id,
            'wali_kelas_id' => $waliKelas?->id,
        ];

        if ($existing) {
            $existing->restore();
            $existing->update($attributes);
            $result->updated++;

            return;
        }

        Kelas::query()->create([
            'nama' => $data['nama'],
            ...$attributes,
        ]);
        $result->created++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importMapel(array $row, ImportResult $result): void
    {
        $data = [
            'kode' => strtoupper((string) ($row['kode'] ?? '')),
            'nama' => $this->string($row['nama'] ?? null),
            'kelompok' => $this->string($row['kelompok'] ?? null),
            'is_peminatan' => $this->toBoolean($row['is_peminatan'] ?? null, false),
        ];

        Validator::make($data, [
            'kode' => ['required', 'max:20', 'alpha_dash'],
            'nama' => ['required', 'max:100'],
            'kelompok' => ['nullable', 'max:50'],
            'is_peminatan' => ['required', 'boolean'],
        ], $this->validationMessages())->validate();

        $existing = Mapel::withTrashed()->where('kode', $data['kode'])->first();

        if ($existing && ! $existing->trashed()) {
            throw ValidationException::withMessages(['kode' => "Kode mata pelajaran {$data['kode']} sudah ada."]);
        }

        if ($existing) {
            $existing->restore();
            $existing->update([
                'nama' => $data['nama'],
                'kelompok' => $data['kelompok'],
                'is_peminatan' => $data['is_peminatan'],
            ]);
            $result->updated++;

            return;
        }

        Mapel::query()->create($data);
        $result->created++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importGuru(array $row, ImportResult $result): void
    {
        $data = [
            'nama' => $this->string($row['nama'] ?? null),
            'nip' => $this->string($row['nip'] ?? null),
            'jenis_kelamin' => $this->normalizeGender($row['jenis_kelamin'] ?? null),
            'no_hp' => $this->string($row['no_hp'] ?? null),
            'username' => strtolower($this->string($row['username'] ?? null) ?? ''),
            'email' => $this->string($row['email'] ?? null),
            'password' => (string) ($row['password'] ?? ''),
        ];

        Validator::make($data, [
            'nama' => ['required', 'max:100'],
            'nip' => ['nullable', 'max:30', 'unique:guru,nip'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'no_hp' => ['nullable', 'max:30'],
            'username' => ['required', 'max:50', 'alpha_dash', 'unique:users,username'],
            'email' => ['nullable', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ], $this->validationMessages())->validate();

        app(GuruService::class)->create($data);
        $result->created++;
    }

    /**
     * @param  array<string, mixed>  $row
     */
    private function importSiswa(array $row, ImportResult $result): void
    {
        $data = [
            'nis' => $this->string($row['nis'] ?? null),
            'nisn' => $this->string($row['nisn'] ?? null),
            'nama' => $this->string($row['nama'] ?? null),
            'jenis_kelamin' => $this->normalizeGender($row['jenis_kelamin'] ?? null),
            'tempat_lahir' => $this->string($row['tempat_lahir'] ?? null),
            'tanggal_lahir' => $this->string($row['tanggal_lahir'] ?? null),
            'kelas_nama' => $this->string($row['kelas_nama'] ?? null),
            'agama_nama' => $this->string($row['agama_nama'] ?? null),
            'username' => strtolower($this->string($row['username'] ?? null) ?? ''),
            'security_key' => $this->string($row['security_key'] ?? null),
            'is_aktif' => $this->toBoolean($row['is_aktif'] ?? null, true),
        ];

        Validator::make($data, [
            'nis' => ['required', 'max:30', 'unique:siswa,nis'],
            'nisn' => ['nullable', 'max:30', 'unique:siswa,nisn'],
            'nama' => ['required', 'max:100'],
            'jenis_kelamin' => ['required', 'in:L,P'],
            'tempat_lahir' => ['nullable', 'max:100'],
            'tanggal_lahir' => ['nullable', 'date'],
            'kelas_nama' => ['required', 'max:50'],
            'agama_nama' => ['nullable', 'max:50'],
            'username' => ['required', 'max:50', 'alpha_dash', 'unique:siswa,username'],
            'security_key' => ['nullable', 'max:50'],
            'is_aktif' => ['required', 'boolean'],
        ], $this->validationMessages())->validate();

        $kelas = Kelas::query()->where('nama', $data['kelas_nama'])->first();

        if (! $kelas) {
            throw ValidationException::withMessages(['kelas_nama' => 'Kelas tidak ditemukan.']);
        }

        $agama = null;

        if (filled($data['agama_nama'])) {
            $agama = Agama::query()
                ->whereRaw('LOWER(nama) = ?', [strtolower((string) $data['agama_nama'])])
                ->first();

            if (! $agama) {
                throw ValidationException::withMessages(['agama_nama' => 'Agama tidak ditemukan.']);
            }
        }

        $siswa = [
            'nis' => $data['nis'],
            'nisn' => $data['nisn'],
            'nama' => $data['nama'],
            'jenis_kelamin' => $data['jenis_kelamin'],
            'tempat_lahir' => $data['tempat_lahir'],
            'tanggal_lahir' => $data['tanggal_lahir'],
            'agama_id' => $agama?->id,
            'kelas_id' => $kelas->id,
            'username' => $data['username'],
            'security_key' => $data['security_key'],
            'is_aktif' => $data['is_aktif'],
        ];

        app(SiswaService::class)->create($siswa);
        $result->created++;
    }

    private function string(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function normalizeGender(mixed $value): ?string
    {
        $value = strtoupper(trim((string) $value));

        return match ($value) {
            'L', 'LAKI-LAKI', 'LAKI LAKI', 'MALE' => 'L',
            'P', 'PEREMPUAN', 'FEMALE' => 'P',
            default => $value === '' ? null : $value,
        };
    }

    private function toBoolean(mixed $value, bool $default): ?bool
    {
        if (blank($value)) {
            return $default;
        }

        if (is_bool($value)) {
            return $value;
        }

        return match (strtolower(trim((string) $value))) {
            '1', 'ya', 'y', 'true', 'aktif' => true,
            '0', 'tidak', 'n', 'false', 'nonaktif' => false,
            default => null,
        };
    }

    /**
     * @return array<string, string>
     */
    private function validationMessages(): array
    {
        return [
            'required' => ':attribute wajib diisi.',
            'unique' => ':attribute sudah digunakan.',
            'alpha_dash' => ':attribute hanya boleh berisi huruf, angka, tanda hubung, dan garis bawah.',
            'email' => 'Format :attribute tidak valid.',
            'date' => 'Format :attribute harus YYYY-MM-DD.',
            'between' => ':attribute harus bernilai antara :min dan :max.',
        ];
    }
}
