<?php

namespace Tests\Feature\Console;

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
