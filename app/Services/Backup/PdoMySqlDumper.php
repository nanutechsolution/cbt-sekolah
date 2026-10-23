<?php

namespace App\Services\Backup;

use Spatie\DbDumper\Databases\MySql;

class PdoMySqlDumper extends MySql
{
    use DumpsDatabaseWithPdo;

    protected function pdoDriver(): string
    {
        return 'mysql';
    }
}
