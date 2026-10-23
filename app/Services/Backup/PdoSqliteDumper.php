<?php

namespace App\Services\Backup;

use Spatie\DbDumper\Databases\Sqlite;

class PdoSqliteDumper extends Sqlite
{
    use DumpsDatabaseWithPdo;

    protected function pdoDriver(): string
    {
        return 'sqlite';
    }
}
