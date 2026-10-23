<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

it('creates a compressed database backup using the configured database', function () {
    if (DB::connection()->getDriverName() !== 'mysql') {
        $this->markTestSkipped('Backup PDO diuji dengan koneksi MySQL.');
    }

    $backupDirectory = storage_path('app/private/cbt-sekolah');
    File::ensureDirectoryExists($backupDirectory);

    $before = collect(File::files($backupDirectory))->map->getFilename()->all();

    $this->artisan('backup:run', [
        '--only-db' => true,
        '--disable-notifications' => true,
    ])->assertSuccessful();

    $newBackups = array_values(array_diff(
        collect(File::files($backupDirectory))->map->getFilename()->all(),
        $before,
    ));

    expect($newBackups)->toHaveCount(1);

    $backupPath = $backupDirectory.'/'.$newBackups[0];
    $zip = new ZipArchive;
    $opened = $zip->open($backupPath);

    expect($opened)->toBeTrue();

    try {
        expect($zip->numFiles)->toBeGreaterThan(0);

        $dump = $zip->getFromIndex(0);

        expect($dump)
            ->toBeString()
            ->toContain('CREATE TABLE');
    } finally {
        $zip->close();
        File::delete($backupPath);
    }
});
