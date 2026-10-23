<?php

use App\Http\Controllers\AnalisisButirController;
use App\Http\Controllers\CetakController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ImportTemplateController;
use App\Http\Controllers\SiswaAuthController;
use App\Http\Controllers\SiswaDashboardController;
use App\Http\Controllers\SiswaUjianController;
use App\Http\Controllers\WordTemplateController;
use App\Http\Middleware\EnsureSiswaAktif;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

Route::get('/', HomeController::class)
    ->name('home')
    ->withoutMiddleware([
        StartSession::class,
        ShareErrorsFromSession::class,
        PreventRequestForgery::class,
    ]);

Route::get('/login', fn () => redirect()->route('filament.admin.auth.login'))
    ->name('login');

Route::get('/admin/import-template/{type}', ImportTemplateController::class)
    ->whereIn('type', ['jurusan', 'kelas', 'mapel', 'guru', 'siswa'])
    ->name('admin.import-template');

Route::get('/admin/import-template-word', WordTemplateController::class)
    ->name('admin.import-template-word');

Route::get('/analisis/{jadwal}/export', [AnalisisButirController::class, 'export'])
    ->middleware('auth')
    ->name('analisis.export');

Route::middleware('auth')->prefix('cetak')->name('cetak.')->group(function (): void {
    Route::get('/jadwal/{jadwal}/kartu-peserta', [CetakController::class, 'kartu'])->name('kartu');
    Route::get('/jadwal/{jadwal}/daftar-hadir', [CetakController::class, 'daftarHadir'])->name('daftar-hadir');
    Route::get('/jadwal/{jadwal}/berita-acara', [CetakController::class, 'beritaAcara'])->name('berita-acara');
});

Route::prefix('ujian')->name('siswa.')->group(function (): void {
    Route::get('/login', [SiswaAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [SiswaAuthController::class, 'login'])->name('login.store');

    Route::middleware(EnsureSiswaAktif::class)->group(function (): void {
        Route::get('/', SiswaDashboardController::class)->name('dashboard');
        Route::post('/logout', [SiswaAuthController::class, 'logout'])->name('logout');

        Route::get('/{jadwal}/konfirmasi', [SiswaUjianController::class, 'konfirmasi'])
            ->name('ujian.konfirmasi');
        Route::post('/{jadwal}/mulai', [SiswaUjianController::class, 'mulai'])
            ->name('ujian.mulai');
        Route::get('/{peserta}/ruang', [SiswaUjianController::class, 'ruang'])
            ->name('ujian.ruang');
        Route::get('/{peserta}/hasil', [SiswaUjianController::class, 'hasil'])
            ->name('ujian.hasil');
    });
});
