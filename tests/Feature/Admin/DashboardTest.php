<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_counts_from_the_database(): void
    {
        $user = User::factory()->create();
        $now = now();
        $row = fn (string $slug, string $status, $publishedAt, $deletedAt = null) => [
            'title' => $slug, 'slug' => $slug, 'body' => '<p>x</p>', 'status' => $status,
            'published_at' => $publishedAt, 'deleted_at' => $deletedAt, 'created_at' => $now, 'updated_at' => $now,
        ];
        DB::table('articles')->insert([
            $row('a-1', 'published', $now->copy()->subDay()),
            $row('a-2', 'published', $now->copy()->subDays(2)),
            $row('a-future', 'published', $now->copy()->addDay()),      // scheduled: not counted as published
            $row('a-trashed', 'published', $now->copy()->subDay(), $now), // trashed: not counted
            $row('a-draft', 'draft', null),
        ]);

        $html = $this->actingAs($user)->get('/admin')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/data-stat="articles_published">۲</', $html);
        $this->assertMatchesRegularExpression('/data-stat="projects_published">۰</', $html);
        $this->assertMatchesRegularExpression('/data-stat="messages_new">۰</', $html);
        $this->assertStringContainsString('۱ پیش‌نویس', $html);
    }

    public function test_dashboard_warns_when_two_factor_is_off(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin')->assertSee('ورود دومرحله‌ای فعال نیست.');
    }

    public function test_dashboard_lists_recent_activity_in_jalali(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
        $this->post('/admin/login', ['email' => 'admin@example.com', 'password' => 'password']);

        $this->actingAs($user)->get('/admin')->assertSee('ورود موفق');
    }

    public function test_security_page_renders(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/admin/security')->assertOk()
            ->assertSee('ورود دومرحله‌ای (TOTP)')
            ->assertSee('تغییر رمز عبور');
    }

    public function test_password_change_requires_the_current_password(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->from('/admin/security')->put('/admin/user/password', [
            'current_password' => 'not-the-password',
            'password' => 'a-brand-new-long-password',
            'password_confirmation' => 'a-brand-new-long-password',
        ])->assertSessionHasErrorsIn('updatePassword', 'current_password');

        $this->actingAs($user)->from('/admin/security')->put('/admin/user/password', [
            'current_password' => 'password',
            'password' => 'short',
            'password_confirmation' => 'short',
        ])->assertSessionHasErrorsIn('updatePassword', 'password');

        $this->actingAs($user)->from('/admin/security')->put('/admin/user/password', [
            'current_password' => 'password',
            'password' => 'a-brand-new-long-password',
            'password_confirmation' => 'a-brand-new-long-password',
        ])->assertRedirect('/admin/security')->assertSessionHas('status', 'password-updated');

        $this->assertTrue(password_verify('a-brand-new-long-password', $user->fresh()->password));
        $this->assertDatabaseHas('activity_logs', ['event' => 'auth.password_changed', 'user_id' => $user->id]);
    }
}
