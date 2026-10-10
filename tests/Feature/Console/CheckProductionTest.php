<?php

namespace Tests\Feature\Console;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckProductionTest extends TestCase
{
    use RefreshDatabase;

    public function test_unsafe_settings_fail_the_check(): void
    {
        config(['app.debug' => true, 'session.secure' => false]);

        $this->artisan('app:check-production')
            ->expectsOutputToContain('stack traces')
            ->expectsOutputToContain('SESSION_SECURE_COOKIE')
            ->expectsOutputToContain('check(s) failed')
            ->assertFailed();
    }

    public function test_reports_admin_two_factor_and_migration_state(): void
    {
        User::factory()->create();

        $this->artisan('app:check-production')
            ->expectsOutputToContain('1 account(s), 0 with two-factor authentication')
            ->expectsOutputToContain('applied')
            ->assertFailed();   // the testing environment is not production
    }
}
