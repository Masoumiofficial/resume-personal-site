<?php

namespace App\Support;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use PharData;
use RuntimeException;
use Throwable;

/**
 * Database (+ optional media) backups that work on shared hosting (D35):
 * pure PHP (no mysqldump/exec), engine-independent (data only, as JSON lines;
 * the schema comes from the migrations), so a backup can be restored on
 * MySQL, MariaDB or SQLite and the restore path is tested in CI.
 *
 * Files live in storage/app/backups (outside public/), mode 0600:
 *   db-YYYYmmdd-HHMMSS.jsonl.gz     header line + one {"t": table, "r": row} per line
 *   media-YYYYmmdd-HHMMSS.tar.gz    public/media (optional)
 *
 * Note: encrypted columns (2FA secrets) and sessions depend on APP_KEY; keep
 * a copy of the production .env in a safe place separately from backups.
 */
final class Backup
{
    public const FORMAT = 'sajadmasoumi-backup';

    /** Transient tables that are never backed up or restored. */
    public const SKIP = ['migrations', 'sessions', 'cache', 'cache_locks', 'jobs', 'job_batches', 'failed_jobs', 'password_reset_tokens'];

    public static function directory(): string
    {
        return (string) config('backup.path', storage_path('app/backups'));
    }

    private static function mediaRoot(): string
    {
        return (string) config('filesystems.disks.media.root');
    }

    /** @return list<string> backed-up table names (current database only) */
    public static function tables(): array
    {
        $connection = DB::connection();
        $schema = in_array($connection->getDriverName(), ['mysql', 'mariadb'], true) ? $connection->getDatabaseName() : null;
        $prefix = $connection->getTablePrefix();

        return collect(Schema::getTableListing($schema, false))
            ->map(fn (string $t) => $prefix !== '' && str_starts_with($t, $prefix) ? substr($t, strlen($prefix)) : $t)
            ->reject(fn (string $t) => in_array($t, self::SKIP, true) || str_starts_with($t, 'sqlite_'))
            ->sort()->values()->all();
    }

    /** @return array{path: string, tables: int, rows: int, bytes: int} */
    public static function dumpDatabase(?string $stamp = null): array
    {
        File::ensureDirectoryExists(self::directory(), 0700);
        self::protectDirectory();
        $stamp ??= now()->format('Ymd-His');
        $path = self::directory()."/db-{$stamp}.jsonl.gz";
        $tmp = $path.'.part';

        $gz = gzopen($tmp, 'wb6');
        if ($gz === false) {
            throw new RuntimeException("Cannot write {$tmp}");
        }

        $tables = self::tables();
        $rows = 0;
        try {
            gzwrite($gz, json_encode([
                'format' => self::FORMAT,
                'version' => 1,
                'created_at' => now()->toIso8601String(),
                'driver' => DB::connection()->getDriverName(),
                'migrations' => Schema::hasTable('migrations') ? DB::table('migrations')->orderBy('id')->pluck('migration')->all() : [],
                'tables' => $tables,
            ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR)."\n");

            foreach ($tables as $table) {
                // Stable order without assuming an "id" column.
                $query = DB::table($table);
                $columns = Schema::getColumnListing($table);
                in_array('id', $columns, true) ? $query->orderBy('id') : $query->orderBy($columns[0]);

                foreach ($query->lazy(500) as $row) {
                    gzwrite($gz, json_encode(['t' => $table, 'r' => (array) $row], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR)."\n");
                    $rows++;
                }
            }
        } finally {
            gzclose($gz);
        }

        rename($tmp, $path);
        @chmod($path, 0600);

        return ['path' => $path, 'tables' => count($tables), 'rows' => $rows, 'bytes' => (int) filesize($path)];
    }

    /** @return array{path: string, files: int, bytes: int}|null null when there is nothing to archive */
    public static function dumpMedia(?string $stamp = null): ?array
    {
        $source = self::mediaRoot();
        $files = collect(is_dir($source) ? File::allFiles($source) : [])
            ->reject(fn ($f) => in_array($f->getFilename(), ['.htaccess', '.gitignore'], true));
        if ($files->isEmpty()) {
            return null;
        }

        File::ensureDirectoryExists(self::directory(), 0700);
        $stamp ??= now()->format('Ymd-His');
        $tar = self::directory()."/media-{$stamp}.tar";
        $archive = new PharData($tar);
        foreach ($files as $file) {
            $archive->addFile($file->getPathname(), $file->getRelativePathname());
        }
        $archive->compress(\Phar::GZ);
        unset($archive);
        @unlink($tar);
        @chmod($tar.'.gz', 0600);

        return ['path' => $tar.'.gz', 'files' => $files->count(), 'bytes' => (int) filesize($tar.'.gz')];
    }

    /** Header of a database backup, validated. */
    public static function header(string $path): array
    {
        $gz = @gzopen($path, 'rb');
        $line = $gz ? gzgets($gz, 10_000_000) : false;
        if ($gz) {
            gzclose($gz);
        }
        $header = is_string($line) ? json_decode($line, true) : null;
        if (! is_array($header) || ($header['format'] ?? null) !== self::FORMAT || ($header['version'] ?? null) !== 1) {
            throw new RuntimeException('Not a backup file of this site (unknown format).');
        }

        return $header;
    }

    /**
     * Rebuild the schema from the migrations and load the backed-up rows.
     * Refuses backups made by a newer version of the code (unknown migrations).
     *
     * @return array{tables: int, rows: int, skipped_columns: list<string>}
     */
    public static function restoreDatabase(string $path): array
    {
        $header = self::header($path);
        $known = array_map(fn (string $f) => pathinfo($f, PATHINFO_FILENAME), File::glob(database_path('migrations/*.php')));
        $unknown = array_values(array_diff($header['migrations'], $known));
        if ($unknown !== []) {
            throw new RuntimeException('The backup is from a newer version of the site (unknown migrations: '.implode(', ', $unknown).'). Deploy that version first.');
        }

        Artisan::call('migrate:fresh', ['--force' => true]);

        $columns = [];
        $skipped = [];
        $rows = 0;
        $batch = [];
        $flush = function () use (&$batch): void {
            foreach ($batch as $table => $list) {
                foreach (array_chunk($list, 200) as $chunk) {
                    DB::table($table)->insert($chunk);
                }
            }
            $batch = [];
        };

        Schema::disableForeignKeyConstraints();
        DB::beginTransaction();
        try {
            $gz = gzopen($path, 'rb');
            gzgets($gz, 10_000_000);   // header
            while (($line = gzgets($gz, 10_000_000)) !== false) {
                if (trim($line) === '') {
                    continue;
                }
                ['t' => $table, 'r' => $row] = json_decode($line, true, 512, JSON_THROW_ON_ERROR);
                if (in_array($table, self::SKIP, true)) {
                    continue;
                }
                $columns[$table] ??= Schema::hasTable($table) ? array_flip(Schema::getColumnListing($table)) : [];
                if ($columns[$table] === []) {
                    $skipped["{$table}.*"] = true;

                    continue;
                }
                foreach (array_keys(array_diff_key($row, $columns[$table])) as $gone) {
                    $skipped["{$table}.{$gone}"] = true;
                }
                $batch[$table][] = array_intersect_key($row, $columns[$table]);
                if (++$rows % 500 === 0) {
                    $flush();
                }
            }
            gzclose($gz);
            $flush();
            DB::commit();
        } catch (Throwable $e) {
            DB::rollBack();
            throw $e;
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        return ['tables' => count($columns), 'rows' => $rows, 'skipped_columns' => array_keys($skipped)];
    }

    /** Extract a media archive over public/media (existing files with the same name are replaced). */
    public static function restoreMedia(string $path): int
    {
        $archive = new PharData($path);
        $count = iterator_count(new \RecursiveIteratorIterator($archive));
        $archive->extractTo(self::mediaRoot(), null, true);

        return $count;
    }

    /** Keep the newest N files of each kind. @return list<string> deleted paths */
    public static function prune(int $keepDb, int $keepMedia): array
    {
        $deleted = [];
        foreach (['db-*.jsonl.gz' => $keepDb, 'media-*.tar.gz' => $keepMedia] as $pattern => $keep) {
            $files = File::glob(self::directory().'/'.$pattern);
            rsort($files);   // names sort by timestamp
            foreach (array_slice($files, max($keep, 1)) as $old) {
                @unlink($old);
                $deleted[] = $old;
            }
        }

        return $deleted;
    }

    /** storage/ is outside public/, but deny web access anyway in case of a misconfigured document root. */
    private static function protectDirectory(): void
    {
        $htaccess = self::directory().'/.htaccess';
        if (! is_file($htaccess)) {
            @file_put_contents($htaccess, "Require all denied\n");
        }
    }
}
