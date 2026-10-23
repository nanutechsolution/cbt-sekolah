<?php

use App\Enums\JenjangSekolah;
use App\Models\PengaturanSekolah;
use App\Services\PengaturanSekolahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Mockery\MockInterface;

uses(RefreshDatabase::class);

it('menampilkan halaman error statis tanpa data pengaturan sekolah', function () {
    config(['app.debug' => false]);

    PengaturanSekolah::query()->create([
        'nama_sekolah' => 'Sekolah Rahasia',
        'jenjang' => JenjangSekolah::Sma,
    ]);

    $this->mock(PengaturanSekolahService::class, function (MockInterface $mock): void {
        $mock->shouldNotReceive('find');
        $mock->shouldNotReceive('get');
    });

    $halaman = [
        403 => 'Akses Ditolak',
        404 => 'Halaman Tidak Ditemukan',
        419 => 'Sesi Anda Sudah Berakhir',
        500 => 'Terjadi Kesalahan',
        503 => 'Layanan Sedang Pemeliharaan',
    ];

    foreach (array_keys($halaman) as $status) {
        Route::get("/tes-error-{$status}", fn () => abort($status));
    }

    foreach ($halaman as $status => $judul) {
        $this->get("/tes-error-{$status}")
            ->assertStatus($status)
            ->assertSee($judul)
            ->assertSee(config('app.name'))
            ->assertDontSee('Sekolah Rahasia')
            ->assertDontSee('<form', false)
            ->assertDontSee('_token', false);
    }

    $this->get('/tes-error-419')
        ->assertStatus(419)
        ->assertSee('Muat Ulang Halaman')
        ->assertSee('masuk lagi')
        ->assertSee('username dan security key')
        ->assertSee('jawaban yang sudah tersimpan tetap aman');
});
