<?php

namespace Tests\Feature\Auth;

use App\Models\ActivityLog;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders(): void
    {
        $this->get('/admin/login')->assertOk()->assertSee('ورود')->assertSee('name="email"', false);
    }

    public function test_guest_is_redirected_from_admin_to_login(): void
    {
        $this->get('/admin')->assertRedirect('/admin/login');
        $this->get('/admin/security')->assertRedirect('/admin/login');
    }

    public function test_there_is_no_public_registration(): void
    {
        $this->get('/register')->assertNotFound();
        $this->get('/admin/register')->assertNotFound();
        $this->post('/admin/register', ['email' => 'x@example.com'])->assertNotFound();
    }

    public function test_valid_credentials_log_in_and_are_recorded(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertRedirect('/admin');

        $this->assertAuthenticatedAs($user);
        $this->assertNotNull($user->fresh()->last_login_at);
        $this->assertDatabaseHas('activity_logs', ['event' => 'auth.login', 'user_id' => $user->id]);
    }

    public function test_failed_login_is_logged_without_the_identifier_or_password(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->post('/admin/login', ['email' => 'attacker-typed@example.com', 'password' => 'Sup3r-Secret-Guess'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
        $log = ActivityLog::query()->where('event', 'auth.failed')->sole();
        $raw = json_encode($log->getAttributes(), JSON_UNESCAPED_UNICODE);

        $this->assertStringNotContainsString('attacker-typed', $raw);
        $this->assertStringNotContainsString('Sup3r-Secret-Guess', $raw);
        $this->assertFalse($log->properties['known_account']);
        $this->assertSame(16, strlen($log->properties['identifier_hash']));
        $this->assertNotSame('127.0.0.1', $log->ip_hash);
    }

    public function test_error_message_does_not_reveal_whether_the_account_exists(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong-password-1'])
            ->assertSessionHasErrors('email');
        $knownMessage = session('errors')->first('email');

        $this->flushSession();
        $this->post('/admin/login', ['email' => 'nobody@example.com', 'password' => 'wrong-password-1'])
            ->assertSessionHasErrors('email');

        $this->assertSame($knownMessage, session('errors')->first('email'));
        $this->assertSame('ایمیل یا رمز عبور درست نیست.', $knownMessage);
    }

    public function test_login_is_rate_limited_after_five_attempts(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'wrong-password-'.$i])
                ->assertStatus(302);
        }

        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password'])
            ->assertStatus(429);
        $this->assertGuest();
    }

    public function test_logout_ends_the_session(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/admin/logout')->assertRedirect();
        $this->assertGuest();
        $this->assertDatabaseHas('activity_logs', ['event' => 'auth.logout', 'user_id' => $user->id]);
    }

    public function test_forgot_password_response_is_identical_for_unknown_emails(): void
    {
        Notification::fake();
        User::factory()->create(['email' => 'admin@example.com']);

        $this->from('/admin/forgot-password')->post('/admin/forgot-password', ['email' => 'admin@example.com'])
            ->assertRedirect('/admin/forgot-password')->assertSessionHasNoErrors();
        $known = session('status');

        $this->flushSession();
        $this->from('/admin/forgot-password')->post('/admin/forgot-password', ['email' => 'nobody@example.com'])
            ->assertRedirect('/admin/forgot-password')->assertSessionHasNoErrors();

        $this->assertSame($known, session('status'));
        Notification::assertSentTimes(ResetPassword::class, 1);
    }
}
