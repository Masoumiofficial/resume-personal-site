<?php

namespace Tests\Feature\Console;

use App\Console\Commands\CreateAdmin;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class CreateAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_creates_the_admin_with_a_prompted_password(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'سجاد معصومی', '--email' => 'Admin@Example.com'])
            ->expectsQuestion('Password (min 12 characters)', 'correct-horse-battery')
            ->expectsQuestion('Repeat password', 'correct-horse-battery')
            ->assertSuccessful();

        $user = User::query()->sole();
        $this->assertSame('admin@example.com', $user->email);
        $this->assertTrue(Hash::check('correct-horse-battery', $user->password));
        $this->assertDatabaseHas('activity_logs', ['event' => 'auth.admin_created', 'user_id' => $user->id]);
    }

    public function test_rejects_short_or_mismatched_passwords(): void
    {
        $this->artisan('app:create-admin', ['--name' => 'A', '--email' => 'a@example.com'])
            ->expectsQuestion('Password (min 12 characters)', 'short')
            ->expectsQuestion('Repeat password', 'short')
            ->assertFailed();

        $this->artisan('app:create-admin', ['--name' => 'A', '--email' => 'a@example.com'])
            ->expectsQuestion('Password (min 12 characters)', 'correct-horse-battery')
            ->expectsQuestion('Repeat password', 'correct-horse-battery-2')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_generated_password_goes_to_a_private_file_not_the_output(): void
    {
        $file = storage_path(CreateAdmin::PASSWORD_FILE);
        @unlink($file);

        try {
            $this->artisan('app:create-admin', ['--name' => 'A', '--email' => 'a@example.com', '--generate-password' => true, '--no-interaction' => true])
                ->expectsOutputToContain('initial-admin-password.txt')
                ->assertSuccessful();

            $this->assertMatchesRegularExpression('/Password: ([A-Za-z0-9]{24})\n/', file_get_contents($file));
            preg_match('/Password: (\S+)/', file_get_contents($file), $m);
            $this->assertTrue(Hash::check($m[1], User::query()->sole()->password));
            $this->assertSame('600', substr(sprintf('%o', fileperms($file)), -3));
            $this->assertStringStartsWith(storage_path(), $file);
        } finally {
            @unlink($file);
        }
    }

    public function test_generate_password_requires_name_and_email(): void
    {
        $this->artisan('app:create-admin', ['--generate-password' => true, '--no-interaction' => true])->assertFailed();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_refuses_when_an_account_already_exists(): void
    {
        User::factory()->create();

        $this->artisan('app:create-admin', ['--name' => 'B', '--email' => 'b@example.com'])->assertFailed();
        $this->assertDatabaseCount('users', 1);
    }

    public function test_database_seeder_creates_no_accounts(): void
    {
        $this->seed();
        $this->assertDatabaseCount('users', 0);
    }
}
