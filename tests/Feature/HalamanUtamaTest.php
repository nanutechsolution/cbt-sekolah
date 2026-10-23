<?php

use App\Enums\JenjangSekolah;
use App\Models\PengaturanSekolah;
use App\Services\PengaturanSekolahService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use RuntimeException;

uses(RefreshDatabase::class);

/**
 * @param  array<string, mixed>  $attributes
 */
function buatPengaturanHalamanUtama(array $attributes = []): PengaturanSekolah
{
    return PengaturanSekolah::query()->create(array_merge([
        'nama_sekolah' => 'SMA Negeri 1 Contoh',
        'jenjang' => JenjangSekolah::Sma,
    ], $attributes));
}

it('menampilkan identitas sekolah dan tautan utama', function () {
    buatPengaturanHalamanUtama(['nama_sekolah' => 'SMA Negeri 1 Contoh']);

    $response = $this->get('/');

    $response->assertOk()
        ->assertSee('SMA Negeri 1 Contoh')
        ->assertSee('<title>SMA Negeri 1 Contoh</title>', false)
        ->assertSee('href="'.url('/ujian').'"', false)
        ->assertSee('href="'.url('/admin').'"', false)
        ->assertSee('href="'.url('/guru').'"', false)
        ->assertSee('Gunakan username dan security key dari kartu peserta.')
        ->assertDontSee('<form', false)
        ->assertDontSee('_token', false)
        ->assertDontSee('csrf-token', false)
        ->assertDontSee('$errors', false);
});

it('memakai nama aplikasi bila nama sekolah kosong', function () {
    buatPengaturanHalamanUtama(['nama_sekolah' => '']);

    $this->get('/')
        ->assertOk()
        ->assertSee('<title>'.e(config('app.name')).'</title>', false)
        ->assertSee(config('app.name'));
});

it('tetap tampil bila pengaturan sekolah belum ada', function () {
    $this->get('/')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('href="'.url('/ujian').'"', false);

    expect(PengaturanSekolah::query()->count())->toBe(0);
});

it('tetap tampil bila pembacaan pengaturan gagal', function () {
    $this->mock(PengaturanSekolahService::class, function (MockInterface $mock): void {
        $mock->shouldReceive('find')
            ->once()
            ->andThrow(new RuntimeException('Database tidak tersedia.'));
    });

    $this->get('/')
        ->assertOk()
        ->assertSee(config('app.name'))
        ->assertSee('href="'.url('/ujian').'"', false)
        ->assertSee('href="'.url('/admin').'"', false)
        ->assertSee('href="'.url('/guru').'"', false);
});

it('memakai penanda huruf awal bila logo tidak tersedia', function () {
    buatPengaturanHalamanUtama([
        'nama_sekolah' => 'SMA Satu',
        'logo' => 'sekolah/logo-yang-tidak-ada.png',
    ]);

    $this->get('/')
        ->assertOk()
        ->assertSee('SMA Satu')
        ->assertSee('school-mark-initial', false)
        ->assertDontSee('<img', false);
});

it('tidak menyetel cookie session pada halaman utama', function () {
    $this->get('/')
        ->assertOk()
        ->assertCookieMissing(config('session.cookie'))
        ->assertCookieMissing('XSRF-TOKEN');
});
