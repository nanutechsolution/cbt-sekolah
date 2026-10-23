<?php

namespace App\Providers;

use App\Http\Livewire\ExamRoom;
use App\Services\Backup\PdoMySqlDumper;
use App\Services\Backup\PdoSqliteDumper;
use App\Services\PaketSoalLockService;
use Illuminate\Support\ServiceProvider;
use Livewire\Livewire;
use Spatie\Backup\Tasks\Backup\DbDumperFactory;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(PaketSoalLockService::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        DbDumperFactory::extend('mysql', fn (): PdoMySqlDumper => new PdoMySqlDumper);
        DbDumperFactory::extend('mariadb', fn (): PdoMySqlDumper => new PdoMySqlDumper);
        DbDumperFactory::extend('sqlite', fn (): PdoSqliteDumper => new PdoSqliteDumper);

        Livewire::component('exam-room', ExamRoom::class);
    }
}
