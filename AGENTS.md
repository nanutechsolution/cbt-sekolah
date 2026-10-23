# AGENTS.md — CBT Sekolah

Aplikasi ujian berbasis komputer (CBT) untuk SATU sekolah (SD/SMP/SMA/SMK). Bukan SaaS, tidak ada multi-tenancy, tidak ada kolom `sekolah_id`.

Baca file ini di awal setiap sesi. Spesifikasi fitur ada di `docs/spesifikasi.md`, skema database dan aturan data di `docs/erd.md`. Jika kode bertentangan dengan dokumen itu, tanyakan dulu, jangan menebak.

Dokumen tugas lain di `docs/` (misalnya `perbaikan-final.md`, `fitur-nilai.md`, `persiapan-produksi.md`) hanya dibaca dan dikerjakan jika saya menyebutnya di prompt.

## Stack dan versi

- PHP 8.4, Laravel 13, MySQL/MariaDB, Pest untuk test
- Filament 5 untuk panel Admin dan panel Guru (dua panel terpisah)
- Ruang ujian siswa: Blade + Livewire + Alpine.js, TANPA Filament (harus ringan)
- Cache, session, queue: Redis jika tersedia, jika tidak pakai driver `database`
- Import/export Excel: `maatwebsite/excel` atau `openspout`
- Cetak PDF: `barryvdh/laravel-dompdf` (Browsershot hanya jika benar-benar perlu)
- Import Word: `phpoffice/phpword` (tahap akhir)
- Backup: `spatie/laravel-backup`

PENTING: Filament 5 BERBEDA dari Filament 2/3. Jangan menulis API dari ingatan. Sebelum membuat Resource, Form, Table, Action, atau Page, gunakan tool `search-docs` dari Laravel Boost (dokumentasi sesuai versi terpasang), dan jika masih ragu baca langsung source di `vendor/filament`. Cek versi sebenarnya di `composer.json`. Hal yang sama berlaku untuk Livewire.

## Struktur aplikasi

- Panel Admin: `/admin`, guard `web`, role `admin`
- Panel Guru: `/guru`, guard `web`, role `guru`, hanya melihat data mapel/kelas yang diampu
- Ruang ujian siswa: `/ujian`, guard `siswa` (tabel `siswa`, terpisah dari `users`), login dengan username + security key
- Logika bisnis ditaruh di `app/Services/` (misalnya `UjianService`, `PenilaianService`, `AnalisisButirService`), bukan di Resource atau komponen Livewire
- Satu Policy per model, dipakai oleh kedua panel

## Laravel Boost

Proyek ini memakai Laravel Boost. Di bagian paling bawah file ini ada blok panduan yang dibuat otomatis oleh Boost. JANGAN diedit manual (akan tertimpa saat `php artisan boost:update`). Aturan proyek ini ada di atas blok itu.

JANGAN PERNAH mengetik tag pembuka atau penutup blok Boost secara manual di bagian atas file ini, termasuk di dalam teks penjelasan. Updater mengenali blok dari tag tersebut dan akan menimpa semua isi di antaranya.

- Pakai tool Boost: `search-docs` untuk dokumentasi, `database-schema` sebelum membuat migration/model, `database-query` untuk query baca saja.
- Jika aturan Boost dan aturan proyek ini bertentangan, aturan proyek ini yang berlaku (khususnya aturan keamanan dan aturan ujian).
- Perintah artisan selalu dengan `--no-interaction`.

## Aturan penamaan

- Nama tabel dan kolom: bahasa Indonesia, `snake_case`, tabel bentuk tunggal (contoh: `siswa`, `kelas`, `paket_soal`, `jawaban_siswa`)
- Nama model: bahasa Indonesia, `PascalCase` (contoh: `PaketSoal`, `JadwalUjian`)
- Foreign key: `<tabel>_id` (contoh: `kelas_id`, `paket_soal_id`)
- Enum disimpan sebagai string, didefinisikan sebagai PHP backed enum di `app/Enums/`
- Semua tabel punya `created_at` dan `updated_at`; data master memakai soft delete

## Aturan keamanan (tidak boleh dilanggar)

1. Semua validasi waktu, penilaian, dan akses dilakukan di SERVER. Jangan percaya data dari browser.
2. Siswa hanya boleh membaca dan menulis `peserta_ujian` dan `jawaban_siswa` miliknya sendiri. Cek kepemilikan di setiap request, jangan hanya mengandalkan ID dari URL.
3. Kunci jawaban (`opsi_soal.is_benar`, teks jawaban isian singkat, kolom `pasangan`, urutan benar, dan `pembahasan`) tidak pernah dikirim ke browser siswa. Penilaian hanya di server. Properti `public` komponen Livewire ikut terkirim ke browser (snapshot), jadi jangan menaruh data soal beserta kuncinya di properti `public`; test kebocoran harus memeriksa snapshot komponen, bukan hanya HTML yang dirender.
4. Sisa waktu dihitung dari `peserta_ujian.waktu_batas` (waktu server). Jawaban yang masuk setelah batas ditolak.
5. Setiap request ujian memvalidasi `device_token` sesi peserta (device lock).
6. Security key siswa disimpan terenkripsi (cast `encrypted`), bukan plain text, karena harus bisa dicetak di kartu peserta. Jangan di-log.
7. Guru tidak boleh melihat atau mengubah paket soal, jadwal, atau siswa di luar kelas/mapel yang diampu.
8. Upload file (gambar, audio, Excel, docx) divalidasi tipe MIME dan ukuran; simpan di disk `local`/`public` dengan nama acak.
9. Paket soal yang terkunci (lihat `docs/erd.md`, bagian Aturan data) tidak boleh diubah, ditegakkan di model/policy, bukan hanya di tampilan.
10. Setiap aksi yang mengubah nilai atau data ujian secara manual (skor essay, koreksi kunci, reset peserta) dicatat di log: siapa, kapan, dan perubahan apa.

## Aturan ujian (bagian paling kritis)

- Urutan soal dan urutan opsi diacak SEKALI saat peserta mulai ujian, disimpan di `peserta_ujian.urutan_soal` dan `urutan_opsi` (JSON). Refresh tidak boleh mengubah urutan.
- Autosave jawaban per soal (debounce), operasi upsert pada `jawaban_siswa` dengan unique `(peserta_ujian_id, soal_id)`.
- Timer: `waktu_batas = waktu_mulai + durasi`, dibatasi juga oleh `jadwal_ujian.selesai`. Browser hanya menampilkan hitung mundur dari sisa waktu yang dikirim server.
- Submit ujian harus idempoten (klik dua kali tidak menghasilkan dua penilaian).
- Pelanggaran (pindah tab, dll.) dicatat ke `pelanggaran_ujian` dan menaikkan `peserta_ujian.jumlah_pelanggaran`. Batas pelanggaran dibaca dari `pengaturan_sekolah`, bukan hardcode.

## Cara kerja

- Kerjakan SATU modul per sesi. Mulai dengan rencana singkat (mode Plan), tunggu persetujuan, baru eksekusi.
- Setiap migration harus punya `down()` yang benar. Jangan mengubah migration yang sudah dijalankan; buat migration baru.
- Logika kritis (timer, penilaian, acak soal, analisis butir soal) wajib punya test Pest (`php artisan make:test --pest NamaTest`). Jalankan test yang paling sempit dulu (`php artisan test --compact --filter=...`), lalu minta saya menjalankan seluruh suite sebelum commit.
- Pakai Eloquent eager loading (`with`) untuk mencegah N+1, terutama di halaman monitoring dan analisis. Tambahkan index pada kolom yang sering difilter.
- Commit kecil setelah satu modul selesai dan test lolos.
- Jika ragu soal API Filament/Livewire, baca source di `vendor/`, jangan menebak.

## Larangan

- JANGAN menjalankan `migrate:fresh`, `migrate:refresh`, `db:wipe`, atau perintah yang menghapus data pada database yang berisi data. Gunakan database terpisah untuk test (`.env.testing`).
- JANGAN menambah package baru tanpa konfirmasi. Package yang SUDAH DISETUJUI: Filament 5, `maatwebsite/excel` (atau `openspout`), `barryvdh/laravel-dompdf`, `spatie/laravel-backup`, `phpoffice/phpword` (tahap 7). Selain itu, tanya dulu.
- Folder yang SUDAH DISETUJUI di dalam `app/`: `Services`, `Enums`, `Policies`, `Jobs`, `Exports`, `Imports`. Jangan membuat folder dasar baru selain itu tanpa izin.
- JANGAN membuat file dokumentasi baru. File di `docs/` hanya diubah atas permintaan saya.
- JANGAN mengubah file `.env`, kredensial, atau konfigurasi server.
- JANGAN mengerjakan apa pun di luar tugas yang saya sebut di prompt dan dokumen tugas yang saya tunjuk.

## Perintah

```bash
composer install
php artisan migrate
php artisan db:seed
php artisan test --compact
php artisan make:filament-user      # buat akun admin
vendor/bin/pint --dirty --format agent   # format kode yang diubah
```

## Status pengerjaan

Tahap 1 sampai 7 sudah selesai dibangun dan sedang diaudit. Jangan mengerjakan ulang atau menulis ulang modul yang sudah ada tanpa diminta.

- [x] Tahap 1 — Fondasi
- [x] Tahap 2 — Bank soal
- [x] Tahap 3 — Ruang ujian
- [x] Tahap 4 — Manajemen ujian
- [x] Tahap 5 — Cetak dokumen
- [x] Tahap 6 — Analisis butir soal
- [x] Tahap 7 — Pelengkap

## Urutan pekerjaan berikutnya

Tandai `[x]` jika selesai. Kerjakan hanya yang saya minta di prompt.

- [ ] Perbaikan final (urutan 2, 1, 3): `docs/perbaikan-final.md`
- [ ] Audit keamanan (hanya membaca dan melapor sampai saya setujui)
- [ ] Fitur nilai: `docs/fitur-nilai.md`
- [ ] Pemolesan UI/UX per alur kerja
- [ ] Persiapan produksi: `docs/persiapan-produksi.md`