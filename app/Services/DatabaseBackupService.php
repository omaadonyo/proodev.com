<?php

namespace App\Services;

use App\Models\BackupRun;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PDO;

/**
 * Creates portable .sql dumps of the entire database and tracks every run.
 * Works with both SQLite and MySQL: the schema and data are introspected
 * through PDO so no external mysqldump binary is required.
 */
class DatabaseBackupService
{
    public function run(): BackupRun
    {
        $run = BackupRun::create([
            'file_name' => '',
            'file_path' => '',
            'file_size' => 0,
            'status' => 'running',
            'started_at' => now(),
        ]);

        try {
            $sql = $this->dumpSql();

            $fileName = 'proodev-backup-'.now()->format('Y-m-d_H-i-s').'.sql';

            Storage::disk('backups')->put($fileName, $sql);

            $size = Storage::disk('backups')->size($fileName);

            $run->update([
                'file_name' => $fileName,
                'file_path' => 'backups/'.$fileName,
                'file_size' => $size,
                'status' => 'success',
                'error' => null,
                'completed_at' => now(),
            ]);

            return $run->fresh();
        } catch (\Throwable $e) {
            $run->update([
                'status' => 'failed',
                'error' => $e->getMessage(),
                'completed_at' => now(),
            ]);

            return $run->fresh();
        }
    }

    /**
     * Total database size in bytes (sqlite file size, or summed MySQL tables).
     */
    public function databaseSize(): int
    {
        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            $path = DB::connection()->getDatabaseName();

            if ($path === ':memory:' || ! is_file($path)) {
                return 0;
            }

            return (int) filesize($path);
        }

        if ($driver === 'mysql') {
            $row = DB::selectOne('SELECT SUM(data_length + index_length) AS size FROM information_schema.TABLES WHERE table_schema = DATABASE()');

            return (int) ($row->size ?? 0);
        }

        return 0;
    }

    public function humanSize(int $bytes): string
    {
        foreach (['B', 'KB', 'MB', 'GB'] as $unit) {
            if ($bytes < 1024) {
                return round($bytes, $unit === 'B' ? 0 : 2).' '.$unit;
            }

            $bytes /= 1024;
        }

        return round($bytes, 2).' TB';
    }

    /**
     * Latest successful backup, or null when none exists yet.
     */
    public function latestSuccessful(): ?BackupRun
    {
        return BackupRun::where('status', 'success')->latest('completed_at')->first();
    }

    /**
     * Build a full, importable SQL dump of the connected database.
     */
    public function dumpSql(): string
    {
        $driver = DB::connection()->getDriverName();

        $lines = [];
        $lines[] = '-- ProoDev database backup';
        $lines[] = '-- Driver: '.$driver;
        $lines[] = '-- Generated: '.now()->toDateTimeString();
        $lines[] = '';

        if ($driver === 'sqlite') {
            $pdo = DB::connection()->getPdo();
            $tables = $pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'table' AND name NOT LIKE 'sqlite_%' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($tables as $table) {
                if (! empty($table['sql'])) {
                    $lines[] = rtrim((string) $table['sql'], ';').';';
                    $lines[] = '';
                }

                $this->appendInserts($pdo, $lines, $table['name']);
            }

            foreach ($pdo->query("SELECT name, sql FROM sqlite_master WHERE type = 'index' AND sql IS NOT NULL")->fetchAll(PDO::FETCH_ASSOC) as $index) {
                $lines[] = rtrim((string) $index['sql'], ';').';';
                $lines[] = '';
            }
        } elseif ($driver === 'mysql') {
            $pdo = DB::connection()->getPdo();
            $tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

            foreach ($tables as $table) {
                $create = $pdo->query('SHOW CREATE TABLE `'.$table.'`')->fetch(PDO::FETCH_ASSOC);

                $createSql = $create ? reset($create) : null;

                if ($createSql) {
                    $lines[] = rtrim((string) $createSql, ';').';';
                    $lines[] = '';
                }

                $this->appendInserts($pdo, $lines, $table);
            }
        }

        return implode("\n", $lines)."\n";
    }

    /**
     * Append INSERT statements for every row of a table.
     *
     * @param  list<string>  $lines
     */
    protected function appendInserts(PDO $pdo, array &$lines, string $table): void
    {
        $driver = DB::connection()->getDriverName();

        $quote = $driver === 'mysql' ? '`' : '"';

        $rows = $pdo->query('SELECT * FROM '.$quote.$table.$quote)->fetchAll(PDO::FETCH_ASSOC);

        if (empty($rows)) {
            $lines[] = '';

            return;
        }

        $columns = array_keys($rows[0]);
        $columnList = implode(', ', array_map(fn ($c) => $quote.str_replace($quote, $quote.$quote, $c).$quote, $columns));

        foreach ($rows as $row) {
            $values = array_map(fn ($value) => $value === null ? 'NULL' : $pdo->quote((string) $value, PDO::PARAM_STR), array_values($row));

            $lines[] = 'INSERT INTO '.$quote.$table.$quote.' ('.$columnList.') VALUES ('.implode(', ', $values).');';
        }

        $lines[] = '';
    }
}
