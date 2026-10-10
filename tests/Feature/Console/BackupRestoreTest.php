<?php

namespace Tests\Feature\Console;

use App\Models\Article;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use App\Support\Backup;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Restore testing (spec: "backups and restore testing"). Uses real
 * migrations (not a wrapping transaction) because restore runs
 * migrate:fresh; CI runs this on SQLite, MySQL 8.0 and MariaDB 10.3.
 */
class BackupRestoreTest extends TestCase
{
    use DatabaseMigrations;

    private string $dir;

    private string $media;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dir = sys_get_temp_dir().'/backup-test-'.bin2hex(random_bytes(4));
        $this->media = sys_get_temp_dir().'/media-test-'.bin2hex(random_bytes(4));
        File::ensureDirectoryExists($this->media.'/2026/10');
        config(['backup.path' => $this->dir, 'filesystems.disks.media.root' => $this->media]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->dir);
        File::deleteDirectory($this->media);
        parent::tearDown();
    }

    /** @return array<string, int> row count per backed-up table */
    private function snapshot(): array
    {
        return collect(Backup::tables())->mapWithKeys(fn ($t) => [$t => DB::table($t)->count()])->all();
    }

    public function test_round_trip_restores_every_row_including_relations(): void
    {
        $this->artisan('app:import-site-content')->assertSuccessful();
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        Setting::put(['site_name' => 'نام سایت ✓']);
        $before = $this->snapshot();
        $article = Article::query()->with('categories', 'tags')->firstOrFail();
        $passwordHash = $admin->password;

        $this->artisan('app:backup')->expectsOutputToContain('Database: db-')->assertSuccessful();
        $file = File::glob($this->dir.'/db-*.jsonl.gz')[0];
        $this->assertSame('600', substr(sprintf('%o', fileperms($file)), -3));

        // Damage: delete and change data.
        DB::table('articles')->delete();
        Redirect::query()->delete();
        Service::query()->update(['title' => 'changed']);
        $admin->forceFill(['email' => 'other@example.com'])->save();
        Setting::put(['site_name' => 'changed']);
        $this->assertSame('changed', Setting::get('site_name'));   // now cached

        $this->artisan('app:restore', ['file' => basename($file), '--force' => true])
            ->expectsOutputToContain('Safety backup')
            ->expectsOutputToContain('Restored')
            ->assertSuccessful();

        $this->assertSame($before, $this->snapshot());
        $restored = Article::query()->with('categories', 'tags')->findOrFail($article->id);
        $this->assertSame($article->title, $restored->title);
        $this->assertSame($article->body, $restored->body);
        $this->assertSame($article->categories->pluck('id')->all(), $restored->categories->pluck('id')->all());
        $this->assertSame($article->tags->pluck('id')->all(), $restored->tags->pluck('id')->all());
        $this->assertSame($article->published_at?->toIso8601String(), $restored->published_at?->toIso8601String());
        $this->assertSame('admin@example.com', User::query()->findOrFail($admin->id)->email);
        $this->assertSame($passwordHash, User::query()->findOrFail($admin->id)->password);
        $this->assertSame('نام سایت ✓', Setting::get('site_name'));   // cache was cleared
        $this->assertDatabaseMissing('services', ['title' => 'changed']);

        // Auto-increment continues after the restored ids.
        $this->assertGreaterThan($admin->id, User::factory()->create()->id);
    }

    public function test_media_archive_round_trip(): void
    {
        File::put($this->media.'/2026/10/photo.webp', 'image-bytes');
        File::put($this->media.'/.htaccess', 'deny');

        $this->artisan('app:backup', ['--media' => true])->expectsOutputToContain('1 files')->assertSuccessful();
        $archive = File::glob($this->dir.'/media-*.tar.gz')[0];
        $db = File::glob($this->dir.'/db-*.jsonl.gz')[0];

        File::delete($this->media.'/2026/10/photo.webp');
        $this->artisan('app:restore', ['file' => $db, '--media' => $archive, '--force' => true])
            ->expectsOutputToContain('Media files restored: 1')
            ->assertSuccessful();

        $this->assertSame('image-bytes', File::get($this->media.'/2026/10/photo.webp'));
    }

    public function test_restore_requires_force_when_not_interactive(): void
    {
        $this->artisan('app:backup')->assertSuccessful();
        $file = File::glob($this->dir.'/db-*.jsonl.gz')[0];

        $this->artisan('app:restore', ['file' => $file, '--no-interaction' => true])
            ->expectsOutputToContain('--force')
            ->assertFailed();
        $this->assertCount(1, File::glob($this->dir.'/db-*.jsonl.gz'));   // nothing touched, no safety backup
    }

    public function test_rejects_foreign_files_and_backups_from_newer_code(): void
    {
        File::ensureDirectoryExists($this->dir);
        File::put($this->dir.'/junk.jsonl.gz', gzencode("not json\n"));
        $this->artisan('app:restore', ['file' => 'junk.jsonl.gz', '--force' => true])
            ->expectsOutputToContain('unknown format')
            ->assertFailed();

        $header = ['format' => Backup::FORMAT, 'version' => 1, 'created_at' => now()->toIso8601String(), 'driver' => 'mysql',
            'migrations' => ['2099_01_01_000000_future_table'], 'tables' => []];
        File::put($this->dir.'/db-future.jsonl.gz', gzencode(json_encode($header)."\n"));
        User::factory()->create();

        $this->artisan('app:restore', ['file' => 'db-future.jsonl.gz', '--force' => true])
            ->expectsOutputToContain('newer version')
            ->expectsOutputToContain('Undo with')
            ->assertFailed();
        $this->assertSame(1, User::query()->count());   // migrate:fresh never ran
    }

    public function test_prune_keeps_the_newest_backups(): void
    {
        File::ensureDirectoryExists($this->dir);
        foreach (['20261001-030000', '20261002-030000', '20261003-030000'] as $stamp) {
            File::put($this->dir."/db-{$stamp}.jsonl.gz", '');
        }

        Backup::prune(2, 4);

        $this->assertSame(['db-20261002-030000.jsonl.gz', 'db-20261003-030000.jsonl.gz'],
            array_map('basename', File::glob($this->dir.'/db-*.jsonl.gz')));
    }

    public function test_transient_tables_are_not_backed_up(): void
    {
        $tables = Backup::tables();

        $this->assertContains('articles', $tables);
        $this->assertContains('users', $tables);
        foreach (['migrations', 'sessions', 'cache', 'password_reset_tokens'] as $transient) {
            $this->assertNotContains($transient, $tables);
        }
    }
}
