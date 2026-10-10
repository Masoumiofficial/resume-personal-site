<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;

/** Pure-PHP backup (no mysqldump / exec needed) — see App\Support\Backup and D35. */
class BackupSite extends Command
{
    protected $signature = 'app:backup {--media : Also archive public/media} {--no-prune : Keep all old backups}';

    protected $description = 'Back up the database (and optionally uploaded media) to storage/app/backups';

    public function handle(): int
    {
        $stamp = now()->format('Ymd-His');

        $db = Backup::dumpDatabase($stamp);
        $this->info(sprintf('Database: %s (%d tables, %d rows, %s)', basename($db['path']), $db['tables'], $db['rows'], self::size($db['bytes'])));

        if ($this->option('media')) {
            $media = Backup::dumpMedia($stamp);
            $this->info($media
                ? sprintf('Media: %s (%d files, %s)', basename($media['path']), $media['files'], self::size($media['bytes']))
                : 'Media: nothing to archive.');
        }

        if (! $this->option('no-prune')) {
            $deleted = Backup::prune((int) config('backup.keep'), (int) config('backup.keep_media'));
            if ($deleted !== []) {
                $this->line('Pruned '.count($deleted).' old backup file(s).');
            }
        }

        $this->line('Location: '.Backup::directory().' — download a copy off the server regularly.');

        return self::SUCCESS;
    }

    /** Human-readable size without ext-intl (which Number::fileSize needs). */
    private static function size(int $bytes): string
    {
        return $bytes >= 1048576 ? round($bytes / 1048576, 1).' MB' : round($bytes / 1024, 1).' KB';
    }
}
