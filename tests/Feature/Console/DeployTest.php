<?php

namespace Tests\Feature\Console;

use App\Console\Commands\CreateAdmin;
use App\Console\Commands\Deploy;
use App\Models\Profile;
use App\Models\Service;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/** app:deploy must be safe to run again and again (one-off cron on hosts without SSH, D40). */
class DeployTest extends TestCase
{
    use DatabaseMigrations;

    protected function tearDown(): void
    {
        @unlink(storage_path(CreateAdmin::PASSWORD_FILE));
        @unlink(storage_path(Deploy::MARKER));
        @unlink(base_path('REVISION'));
        parent::tearDown();
    }

    public function test_first_install_migrates_imports_and_creates_the_admin(): void
    {
        Schema::dropAllTables();   // a brand-new, empty database

        $this->artisan('app:deploy', ['--admin-name' => 'سجاد', '--admin-email' => 'admin@example.com', '--no-optimize' => true])
            ->expectsOutputToContain('Import the reviewed site content')
            ->expectsOutputToContain('Deploy finished')
            ->assertSuccessful();

        $this->assertSame('info@sajadmasoumi.com', Profile::current()->email);
        $this->assertSame('admin@example.com', User::query()->sole()->email);
        $this->assertFileExists(storage_path(CreateAdmin::PASSWORD_FILE));
        $this->assertFalse((bool) Setting::get('search_indexing'));   // staging-safe until go-live (D41)
    }

    public function test_update_never_reimports_deleted_content_or_adds_accounts(): void
    {
        Schema::dropAllTables();
        $this->artisan('app:deploy', ['--admin-name' => 'A', '--admin-email' => 'a@example.com', '--no-optimize' => true])->assertSuccessful();
        $deleted = Service::query()->firstOrFail();
        $deleted->delete();
        $services = Service::query()->count();

        $this->artisan('app:deploy', ['--admin-name' => 'B', '--admin-email' => 'b@example.com', '--no-optimize' => true])
            ->expectsOutputToContain('content import skipped')
            ->assertSuccessful();

        $this->assertSame($services, Service::query()->count());
        $this->assertSame(1, User::query()->count());

        // An update keeps the owner's choice.
        Setting::put(['search_indexing' => true]);
        $this->artisan('app:deploy', ['--no-optimize' => true])->assertSuccessful();
        $this->assertTrue((bool) Setting::get('search_indexing'));
    }

    public function test_an_already_deployed_revision_is_skipped(): void
    {
        file_put_contents(base_path('REVISION'), "abc123\n");

        $this->artisan('app:deploy', ['--no-optimize' => true])->expectsOutputToContain('revision abc123')->assertSuccessful();
        $this->assertSame("abc123\n", file_get_contents(storage_path(Deploy::MARKER)));

        $this->artisan('app:deploy', ['--no-optimize' => true])->expectsOutputToContain('already deployed')->assertSuccessful();
        $this->artisan('app:deploy', ['--no-optimize' => true, '--again' => true])->expectsOutputToContain('Deploy finished')->assertSuccessful();
    }

    public function test_warns_when_no_admin_exists_and_none_was_requested(): void
    {
        $this->artisan('app:deploy', ['--no-optimize' => true])
            ->expectsOutputToContain('No admin account yet')
            ->assertSuccessful();
    }
}
