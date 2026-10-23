<?php

namespace App\Services\Backup;

use PDO;
use PDOException;
use RuntimeException;

trait DumpsDatabaseWithPdo
{
    abstract protected function pdoDriver(): string;

    public function dumpToFile(string $dumpFile): void
    {
        $handle = fopen($dumpFile, 'wb');

        if ($handle === false) {
            throw new RuntimeException("Tidak dapat membuat file backup [{$dumpFile}].");
        }

        try {
            $pdo = $this->createPdoConnection();

            if ($this->pdoDriver() === 'sqlite') {
                $this->dumpSqlite($pdo, $handle);
            } else {
                $this->dumpMysql($pdo, $handle);
            }
        } finally {
            fclose($handle);
        }

        if (! is_file($dumpFile) || filesize($dumpFile) === 0) {
            throw new RuntimeException("File backup [{$dumpFile}] kosong.");
        }
    }

    private function createPdoConnection(): PDO
    {
        try {
            if ($this->pdoDriver() === 'sqlite') {
                if ($this->dbName === '' || $this->dbName === ':memory:') {
                    throw new RuntimeException('Database SQLite tidak memiliki file yang dapat dibackup.');
                }

                return new PDO(
                    'sqlite:'.$this->dbName,
                    options: [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
                );
            }

            $dsn = "mysql:dbname={$this->dbName};charset=utf8mb4";

            if ($this->socket !== '') {
                $dsn .= ";unix_socket={$this->socket}";
            } else {
                $dsn .= ";host={$this->host};port={$this->port}";
            }

            return new PDO(
                $dsn,
                $this->userName,
                $this->password,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
            );
        } catch (PDOException $exception) {
            throw new RuntimeException('Tidak dapat terhubung ke database untuk backup.', previous: $exception);
        }
    }

    /**
     * @param  resource  $handle
     */
    private function dumpMysql(PDO $pdo, $handle): void
    {
        $this->write($handle, "SET NAMES utf8mb4;\nSET FOREIGN_KEY_CHECKS=0;\n\n");

        $tables = $pdo->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")
            ->fetchAll(PDO::FETCH_NUM);

        $tableNames = $this->filterTables(array_map(
            fn (array $row): string => (string) $row[0],
            $tables,
        ));

        foreach ($tableNames as $tableName) {
            $quotedTable = $this->quoteMysqlIdentifier($tableName);

            if ($this->createTables) {
                $createTable = $pdo->query("SHOW CREATE TABLE {$quotedTable}")->fetch(PDO::FETCH_NUM);

                if (! is_array($createTable) || ! isset($createTable[1])) {
                    throw new RuntimeException("Tidak dapat membaca struktur tabel [{$tableName}].");
                }

                $this->write(
                    $handle,
                    "DROP TABLE IF EXISTS {$quotedTable};\n{$createTable[1]};\n\n",
                );
            }

            if ($this->includeData) {
                $this->dumpMysqlRows($pdo, $handle, $tableName, $quotedTable);
            }
        }

        $this->write($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
    }

    /**
     * @param  resource  $handle
     */
    private function dumpMysqlRows(PDO $pdo, $handle, string $tableName, string $quotedTable): void
    {
        $statement = $pdo->query("SELECT * FROM {$quotedTable}");
        $columns = null;
        $rows = [];

        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            if ($columns === null) {
                $columns = array_keys($row);
            }

            $rows[] = '('.implode(', ', array_map(
                fn (mixed $value): string => $this->quoteValue($pdo, $value),
                array_values($row),
            )).')';

            if (count($rows) >= 100) {
                $this->writeMysqlInsert($handle, $quotedTable, $columns, $rows);
                $rows = [];
            }
        }

        if ($rows !== [] && $columns !== null) {
            $this->writeMysqlInsert($handle, $quotedTable, $columns, $rows);
        }
    }

    /**
     * @param  resource  $handle
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $rows
     */
    private function writeMysqlInsert($handle, string $quotedTable, array $columns, array $rows): void
    {
        $quotedColumns = implode(', ', array_map(
            fn (string $column): string => $this->quoteMysqlIdentifier($column),
            $columns,
        ));

        $this->write(
            $handle,
            "INSERT INTO {$quotedTable} ({$quotedColumns}) VALUES\n".implode(",\n", $rows).";\n",
        );
    }

    /**
     * @param  resource  $handle
     */
    private function dumpSqlite(PDO $pdo, $handle): void
    {
        $this->write($handle, "PRAGMA foreign_keys=OFF;\nBEGIN TRANSACTION;\n\n");

        $tables = $pdo->query(
            "SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name",
        )->fetchAll(PDO::FETCH_ASSOC);

        $tableNames = $this->filterTables(array_map(
            fn (array $table): string => (string) $table['name'],
            $tables,
        ));

        foreach ($tables as $table) {
            $tableName = (string) $table['name'];

            if (! in_array($tableName, $tableNames, true)) {
                continue;
            }

            $quotedTable = $this->quoteSqliteIdentifier($tableName);

            if ($this->createTables) {
                if (! is_string($table['sql']) || $table['sql'] === '') {
                    throw new RuntimeException("Tidak dapat membaca struktur tabel [{$tableName}].");
                }

                $this->write(
                    $handle,
                    "DROP TABLE IF EXISTS {$quotedTable};\n".rtrim($table['sql']).";\n\n",
                );
            }

            if ($this->includeData) {
                $this->dumpSqliteRows($pdo, $handle, $quotedTable);
            }
        }

        $this->write($handle, "COMMIT;\nPRAGMA foreign_keys=ON;\n");
    }

    /**
     * @param  resource  $handle
     */
    private function dumpSqliteRows(PDO $pdo, $handle, string $quotedTable): void
    {
        $statement = $pdo->query("SELECT * FROM {$quotedTable}");
        $columns = null;
        $rows = [];

        while (($row = $statement->fetch(PDO::FETCH_ASSOC)) !== false) {
            if ($columns === null) {
                $columns = array_keys($row);
            }

            $rows[] = '('.implode(', ', array_map(
                fn (mixed $value): string => $this->quoteValue($pdo, $value),
                array_values($row),
            )).')';

            if (count($rows) >= 100) {
                $this->writeSqliteInsert($handle, $quotedTable, $columns, $rows);
                $rows = [];
            }
        }

        if ($rows !== [] && $columns !== null) {
            $this->writeSqliteInsert($handle, $quotedTable, $columns, $rows);
        }
    }

    /**
     * @param  resource  $handle
     * @param  array<int, string>  $columns
     * @param  array<int, string>  $rows
     */
    private function writeSqliteInsert($handle, string $quotedTable, array $columns, array $rows): void
    {
        $quotedColumns = implode(', ', array_map(
            fn (string $column): string => $this->quoteSqliteIdentifier($column),
            $columns,
        ));

        $this->write(
            $handle,
            "INSERT INTO {$quotedTable} ({$quotedColumns}) VALUES\n".implode(",\n", $rows).";\n",
        );
    }

    private function quoteValue(PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value)) {
            return (string) $value;
        }

        if (is_float($value)) {
            return var_export($value, true);
        }

        $quotedValue = $pdo->quote((string) $value);

        if ($quotedValue === false) {
            throw new RuntimeException('Tidak dapat meng-escape nilai database.');
        }

        return $quotedValue;
    }

    /**
     * @param  array<int, string>  $tables
     * @return array<int, string>
     */
    private function filterTables(array $tables): array
    {
        if ($this->includeTables !== []) {
            return array_values(array_intersect($tables, $this->includeTables));
        }

        if ($this->excludeTables !== []) {
            return array_values(array_diff($tables, $this->excludeTables));
        }

        return $tables;
    }

    private function quoteMysqlIdentifier(string $identifier): string
    {
        return '`'.str_replace('`', '``', $identifier).'`';
    }

    private function quoteSqliteIdentifier(string $identifier): string
    {
        return '"'.str_replace('"', '""', $identifier).'"';
    }

    /**
     * @param  resource  $handle
     */
    private function write($handle, string $contents): void
    {
        if (fwrite($handle, $contents) === false) {
            throw new RuntimeException('Tidak dapat menulis file backup database.');
        }
    }
}
