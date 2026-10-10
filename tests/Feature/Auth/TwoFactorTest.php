<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PragmaRX\Google2FA\Google2FA;
use Tests\TestCase;

class TwoFactorTest extends TestCase
{
    use RefreshDatabase;

    private function userWithTwoFactor(string $secret): User
    {
        return User::factory()->create([
            'email' => 'admin@example.com',
            'two_factor_secret' => encrypt($secret),
            'two_factor_recovery_codes' => encrypt(json_encode(['recovery-code-one', 'recovery-code-two'])),
            'two_factor_confirmed_at' => now(),
        ]);
    }

    public function test_password_alone_is_not_enough_when_two_factor_is_enabled(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        $this->userWithTwoFactor($secret);

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect('/admin/two-factor-challenge');
        $this->assertGuest();

        $this->get('/admin/two-factor-challenge')->assertOk()->assertSee('name="code"', false);
    }

    public function test_valid_totp_code_completes_login(): void
    {
        $google2fa = new Google2FA;
        $secret = $google2fa->generateSecretKey();
        $user = $this->userWithTwoFactor($secret);

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password']);
        $this->post('/admin/two-factor-challenge', ['code' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_code_is_rejected_and_logged(): void
    {
        $secret = (new Google2FA)->generateSecretKey();
        $user = $this->userWithTwoFactor($secret);

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password']);
        $this->post('/admin/two-factor-challenge', ['code' => '000000'])->assertRedirect('/admin/two-factor-challenge');

        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['event' => 'auth.two_factor_failed', 'user_id' => $user->id]);
    }

    public function test_recovery_code_works_once_and_its_value_is_not_logged(): void
    {
        $user = $this->userWithTwoFactor((new Google2FA)->generateSecretKey());

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password']);
        $this->post('/admin/two-factor-challenge', ['recovery_code' => 'recovery-code-one'])->assertRedirect('/admin');
        $this->assertAuthenticatedAs($user);

        $this->assertNotContains('recovery-code-one', $user->fresh()->recoveryCodes());
        $this->assertDatabaseHas('activity_logs', ['event' => 'auth.recovery_code_used']);
        $this->assertStringNotContainsString('recovery-code-one', (string) DB::table('activity_logs')->pluck('properties')->implode(' '));
    }

    public function test_enabling_two_factor_requires_password_confirmation_and_shows_qr(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/user/two-factor-authentication')
            ->assertRedirect('/admin/user/confirm-password');

        $this->actingAs($user)->withSession(['auth.password_confirmed_at' => time()])
            ->from('/admin/security')
            ->post('/admin/user/two-factor-authentication')
            ->assertRedirect('/admin/security');

        $this->assertNotNull($user->fresh()->two_factor_secret);
        $this->assertNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseMissing('activity_logs', ['event' => 'auth.two_factor_enabled']); // only after confirming a code

        $this->actingAs($user->fresh())->withSession(['auth.password_confirmed_at' => time()])->get('/admin/security')->assertOk()->assertSee('<svg', false)->assertSee('تأیید و فعال‌سازی');
    }

    public function test_confirming_a_valid_code_enables_two_factor_and_shows_recovery_codes(): void
    {
        $google2fa = new Google2FA;
        $user = User::factory()->create();
        $session = ['auth.password_confirmed_at' => time()];

        $this->actingAs($user)->withSession($session)->post('/admin/user/two-factor-authentication');
        $secret = decrypt($user->fresh()->two_factor_secret);

        $this->actingAs($user)->withSession($session)->from('/admin/security')
            ->post('/admin/user/confirmed-two-factor-authentication', ['code' => $google2fa->getCurrentOtp($secret)])
            ->assertRedirect('/admin/security');

        $this->assertNotNull($user->fresh()->two_factor_confirmed_at);
        $this->assertDatabaseHas('activity_logs', ['event' => 'auth.two_factor_enabled', 'user_id' => $user->id]);

        $codes = $user->fresh()->recoveryCodes();
        $this->actingAs($user->fresh())->withSession($session + ['status' => 'two-factor-authentication-confirmed'])
            ->get('/admin/security')->assertOk()->assertSee($codes[0])->assertSee('فعال');
    }
}
