<?php

namespace App\Console\Commands;

use App\Support\Backup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Throwable;

/**
 * Destructive: rebuilds the schema (migrate:fresh) and loads a backup.
 * A safety backup of the current state is written first, so a failed or
 * mistaken restore can itself be undone.
 */
class RestoreSite extends Command
{
    protected $signature = 'app:restore
        {file : db-*.jsonl.gz file (name inside storage/app/backups, or a path)}
        {--media= : media-*.tar to extract over public/media}
        {--force : Do not ask for confirmation (required in production / cron)}';

    protected $description = 'Restore the database (and optionally media) from an app:backup file — replaces ALL current data';

    public function handle(): int
    {
        $file = $this->resolve((string) $this->argument('file'));
        $media = $this->option('media') ? $this->resolve((string) $this->option('media')) : null;
        if ($file === null || ($this->option('media') && $media === null)) {
            $this->error('Backup file not found.');

            return self::FAILURE;
        }

        try {
            $header = Backup::header($file);
        } catch (Throwable $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }

        $this->line("Backup created {$header['created_at']} on {$header['driver']} — ".count($header['tables']).' tables.');

        if (! $this->option('force')) {
            if (app()->isProduction() || ! $this->input->isInteractive()) {
                $this->error('Restoring replaces all data. Re-run with --force to confirm.');

                return self::FAILURE;
            }
            if (! $this->confirm('This REPLACES all current data. Continue?')) {
                return self::FAILURE;
            }
        }

        $safety = Backup::dumpDatabase(now()->format('Ymd-His').'-pre-restore');
        $this->line('Safety backup of the current data: '.basename($safety['path']));

        try {
            $result = Backup::restoreDatabase($file);
        } catch (Throwable $e) {
            $this->error('Restore failed: '.$e->getMessage());
            $this->error('Undo with: php artisan app:restore '.basename($safety['path']).' --force');

            return self::FAILURE;
        }

        $this->info("Restored {$result['rows']} rows into {$result['tables']} tables.");
        if ($result['skipped_columns'] !== []) {
            $this->warn('Ignored (no longer in the schema): '.implode(', ', $result['skipped_columns']));
        }

        if ($media !== null) {
            $this->info('Media files restored: '.Backup::restoreMedia($media));
        }

        // Cached settings/menus must not survive a restore.
        Artisan::call('cache:clear');
        $this->line('Application cache cleared. Existing sessions were not restored: sign in again.');

        return self::SUCCESS;
    }

    private function resolve(string $file): ?string
    {
        foreach ([$file, Backup::directory().'/'.basename($file)] as $candidate) {
            if (is_file($candidate)) {
                return realpath($candidate);
            }
        }

        return null;
    }
}
