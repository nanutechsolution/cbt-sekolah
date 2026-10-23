<?php

use App\Services\PesertaUjianService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(fn () => app(PesertaUjianService::class)->tutupYangKedaluwarsa())
    ->name('tutup-peserta-ujian-kedaluwarsa')
    ->everyMinute()
    ->withoutOverlapping();

Schedule::command('backup:run --only-db --disable-notifications')
    ->name('backup-database-harian')
    ->dailyAt('01:00')
    ->withoutOverlapping(120);

Schedule::command('backup:clean --disable-notifications')
    ->name('bersihkan-backup-lama')
    ->dailyAt('01:30')
    ->withoutOverlapping(120);
