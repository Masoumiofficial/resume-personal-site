<?php

namespace App\Console\Commands;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * Production readiness check (Phase 5/6). Reads the real configuration of
 * the environment it runs in and prints OK / WARN / FAIL per item; exits
 * non-zero when anything FAILs. Run it on the host after every deploy:
 *
 *   php artisan app:check-production
 *
 * Note: this is the CLI PHP. On shared hosts the web PHP can differ — check
 * that side with tools/host-check (see docs/phase-0/hosting-checklist.md).
 */
class CheckProduction extends Command
{
    protected $signature = 'app:check-production';

    protected $description = 'Check configuration, PHP, database and permissions for a production deploy';

    /** @var list<array{0: string, 1: string, 2: string}> */
    private array $rows = [];

    public function handle(): int
    {
        $this->rows = [];
        $this->php();
        $this->app();
        $this->database();
        $this->filesystem();

        $this->table(['', 'Check', 'Details'], array_map(fn ($r) => [match ($r[0]) {
            'ok' => '<fg=green>OK</>', 'warn' => '<fg=yellow>WARN</>', default => '<fg=red>FAIL</>',
        }, $r[1], $r[2]], $this->rows));

        $failed = count(array_filter($this->rows, fn ($r) => $r[0] === 'fail'));
        $warned = count(array_filter($this->rows, fn ($r) => $r[0] === 'warn'));
        $failed ? $this->error("{$failed} check(s) failed, {$warned} warning(s).") : $this->info("No failures, {$warned} warning(s).");

        return $failed ? self::FAILURE : self::SUCCESS;
    }

    private function add(bool|string $state, string $check, string $details = ''): void
    {
        $this->rows[] = [is_bool($state) ? ($state ? 'ok' : 'fail') : $state, $check, $details];
    }

    private function php(): void
    {
        $this->add(version_compare(PHP_VERSION, '8.3.0', '>=') ? (version_compare(PHP_VERSION, '8.4.0', '>=') ? 'ok' : 'warn') : 'fail',
            'PHP version (CLI)', PHP_VERSION.' — required ≥ 8.3, recommended 8.4 (D1)');

        $required = ['ctype', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pdo', 'session', 'tokenizer', 'xml', 'dom', 'gd'];
        $required[] = config('database.default') === 'sqlite' ? 'pdo_sqlite' : 'pdo_mysql';
        $missing = array_values(array_filter($required, fn ($e) => ! extension_loaded($e)));
        $this->add($missing === [], 'PHP extensions', $missing ? 'missing: '.implode(', ', $missing) : implode(', ', $required));

        if (function_exists('gd_info')) {
            $gd = gd_info();
            $this->add(! empty($gd['WebP Support']) ? 'ok' : 'warn', 'GD WebP support', ! empty($gd['WebP Support']) ? 'yes' : 'no — uploads are kept as JPEG/PNG only (D18)');
        }

        $opcache = function_exists('opcache_get_status') && (bool) ini_get('opcache.enable');
        $this->add($opcache ? 'ok' : 'warn', 'OPcache', $opcache ? 'enabled' : 'not enabled for CLI (the web SAPI may differ: check tools/host-check)');
        $this->add(ini_get('expose_php') ? 'warn' : 'ok', 'expose_php', ini_get('expose_php') ? 'on — PHP version is advertised (header also removed in public/.htaccess)' : 'off');
    }

    private function app(): void
    {
        $this->add(app()->isProduction(), 'APP_ENV', (string) config('app.env'));
        $this->add(! config('app.debug'), 'APP_DEBUG', config('app.debug') ? 'true — stack traces and secrets can leak' : 'false');
        $this->add(filled(config('app.key')), 'APP_KEY', filled(config('app.key')) ? 'set' : 'missing — run php artisan key:generate');
        $this->add(str_starts_with((string) config('app.url'), 'https://'), 'APP_URL uses https', (string) config('app.url'));
        $this->add((bool) config('session.secure'), 'SESSION_SECURE_COOKIE', config('session.secure') ? 'true' : 'false — the session cookie can travel over plain http');
        $this->add((bool) config('session.encrypt') && config('session.http_only'), 'Session encrypt + HttpOnly', 'driver: '.config('session.driver'));
        $this->add((bool) config('security.csp.enabled') && ! config('security.csp.report_only'), 'Content-Security-Policy enforced');
        $this->add((bool) config('security.hsts.enabled') ? 'ok' : 'warn', 'HSTS', config('security.hsts.enabled') ? 'max-age='.config('security.hsts.max_age') : 'disabled');
        $this->add(in_array(config('logging.channels.'.config('logging.default').'.level', config('logging.channels.daily.level')), ['debug'], true) ? 'warn' : 'ok',
            'LOG_LEVEL', 'debug logs grow quickly on shared hosting; warning or error is enough');
        $this->add(config('admin.path') === 'admin' ? 'warn' : 'ok', 'ADMIN_PATH', config('admin.path') === 'admin' ? 'default "admin" — a less guessable path reduces login noise' : 'custom');
        $this->add(app()->configurationIsCached() ? 'ok' : 'warn', 'Config cache', app()->configurationIsCached() ? 'cached' : 'run php artisan optimize after each deploy');
        $this->add(app()->routesAreCached() ? 'ok' : 'warn', 'Route cache', app()->routesAreCached() ? 'cached' : 'run php artisan optimize after each deploy');
    }

    private function database(): void
    {
        try {
            $pdo = DB::connection()->getPdo();
        } catch (Throwable $e) {
            $this->add(false, 'Database connection', $e->getMessage());

            return;
        }
        $driver = DB::connection()->getDriverName();
        $version = (string) $pdo->getAttribute(\PDO::ATTR_SERVER_VERSION);
        $ok = match (true) {
            $driver === 'sqlite' => 'warn',
            str_contains(strtolower($version), 'mariadb') => version_compare(preg_replace('/^(5\.5\.5-)?/', '', $version), '10.3', '>='),
            default => version_compare($version, '5.7', '>='),
        };
        $this->add($ok, 'Database server', "{$driver} {$version}".($driver === 'sqlite' ? ' — production should use MySQL/MariaDB' : ''));

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $charset = DB::selectOne('SELECT @@character_set_database AS c')->c ?? '';
            $this->add($charset === 'utf8mb4', 'Database charset', (string) $charset);
        }

        try {
            $migrator = app('migrator');
            $files = array_keys($migrator->getMigrationFiles([database_path('migrations')]));
            $ran = $migrator->repositoryExists() ? $migrator->getRepository()->getRan() : [];
            $pending = array_values(array_diff($files, $ran));
            $this->add($pending === [], 'Migrations', $pending ? count($pending).' pending — run php artisan migrate --force' : count($ran).' applied');
            if ($pending !== []) {
                return;
            }

            $admins = User::query()->count();
            $with2fa = User::query()->whereNotNull('two_factor_confirmed_at')->count();
            $this->add($admins === 0 ? false : ($with2fa === $admins ? 'ok' : 'warn'), 'Admin accounts', "{$admins} account(s), {$with2fa} with two-factor authentication");
            $this->add(Schema::hasTable('sessions'), 'Sessions table');
            $indexing = Setting::get('search_indexing', '1');
            $this->add($indexing ? 'ok' : 'warn', 'Search indexing (setting)', $indexing ? 'on' : 'off — robots.txt disallows everything (D25)');
        } catch (Throwable $e) {
            $this->add(false, 'Database schema', $e->getMessage());
        }
    }

    private function filesystem(): void
    {
        foreach (['storage/framework/cache', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs', 'bootstrap/cache', 'public/media'] as $dir) {
            $path = base_path($dir);
            $this->add(is_dir($path) && is_writable($path), "Writable: {$dir}");
        }
        foreach (['.env', 'storage', 'vendor'] as $private) {
            $this->add(! file_exists(public_path($private)), "Not inside public/: {$private}");
        }
        $this->add(is_file(public_path('media/.htaccess')), 'public/media/.htaccess (no script execution)');
    }
}
