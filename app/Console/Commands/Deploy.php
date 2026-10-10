<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * One idempotent command for first install AND updates (D40), so a host
 * without SSH needs a single cron line. Safe to run repeatedly — a cron entry
 * that was not removed in time does no harm:
 *
 *   - APP_KEY generated only when empty (never rotated);
 *   - migrations with --force (only pending ones run);
 *   - content import only on a brand-new database (never re-creates content
 *     deleted later in the panel); a new database also starts with search
 *     indexing OFF — going live is an explicit switch in Settings (D41);
 *   - admin account only when none exists (password to a private file);
 *   - caches rebuilt; then app:check-production.
 *
 * When the package carries a REVISION file, a revision that was already
 * deployed is skipped (use --again to force).
 */
class Deploy extends Command
{
    protected $signature = 'app:deploy
        {--admin-name= : Create the admin account if none exists (with --admin-email)}
        {--admin-email= : Login email for that account}
        {--again : Run even if this revision was already deployed}
        {--no-optimize : Do not rebuild the config/route/view caches}';

    protected $description = 'Install or update the site (idempotent; suitable for a one-off cron entry on hosts without SSH)';

    public const MARKER = 'app/private/deployed-revision';

    public function handle(): int
    {
        $lock = fopen(storage_path('framework/deploy.lock'), 'c');
        if ($lock === false || ! flock($lock, LOCK_EX | LOCK_NB)) {
            $this->warn('Another deploy is running; skipped.');

            return self::SUCCESS;
        }

        try {
            return $this->deploy();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }

    private function deploy(): int
    {
        $revision = is_file(base_path('REVISION')) ? trim((string) file_get_contents(base_path('REVISION'))) : null;
        $marker = storage_path(self::MARKER);
        if ($revision !== null && ! $this->option('again') && is_file($marker) && trim((string) file_get_contents($marker)) === $revision) {
            $this->line("Revision {$revision} is already deployed; nothing to do (remove the one-off cron entry). Use --again to force.");

            return self::SUCCESS;
        }

        $this->info('Deploying'.($revision ? " revision {$revision}" : '').' at '.now()->toDateTimeString());

        if (! is_file(base_path('.env'))) {
            $this->error('.env is missing: copy .env.production.example to .env and fill in the database settings first.');

            return self::FAILURE;
        }

        $this->step('Clear caches', fn () => $this->call('optimize:clear'));

        if (blank(config('app.key'))) {
            $this->step('Generate APP_KEY (first install)', fn () => $this->call('key:generate', ['--force' => true]));
        }

        try {
            $fresh = ! Schema::hasTable('migrations') || DB::table('migrations')->doesntExist();
        } catch (Throwable $e) {
            $this->error('Database connection failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! $this->step('Migrate', fn () => $this->call('migrate', ['--force' => true]))) {
            return self::FAILURE;
        }

        if ($fresh) {
            $this->step('Import the reviewed site content (new database)', fn () => $this->call('app:import-site-content'));
            Setting::put(['search_indexing' => false]);
            $this->warn('Search indexing is OFF (new install). Turn it on in the panel → Settings at go-live.');
        } else {
            $this->line('Existing database: content import skipped.');
        }

        if (User::query()->doesntExist()) {
            if ($this->option('admin-name') && $this->option('admin-email')) {
                $this->step('Create the admin account', fn () => $this->call('app:create-admin', [
                    '--name' => $this->option('admin-name'),
                    '--email' => $this->option('admin-email'),
                    '--generate-password' => true,
                    '--no-interaction' => true,
                ]));
            } else {
                $this->warn('No admin account yet: run again with --admin-name="…" --admin-email="…".');
            }
        }

        if (! $this->option('no-optimize')) {
            $this->step('Rebuild caches', fn () => $this->call('optimize'));
        }

        if ($revision !== null) {
            @mkdir(dirname($marker), 0700, true);
            file_put_contents($marker, $revision."\n");
        }

        $this->newLine();
        $this->call('app:check-production');
        $this->info('Deploy finished. Remove the one-off cron entry if you used one.');

        return self::SUCCESS;
    }

    /** @param callable(): int $run */
    private function step(string $title, callable $run): bool
    {
        $this->line("── {$title}");
        $code = $run();
        if ($code !== self::SUCCESS) {
            $this->error("Step failed: {$title}");
        }

        return $code === self::SUCCESS;
    }
}
