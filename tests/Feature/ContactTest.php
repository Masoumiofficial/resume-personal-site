<?php

namespace Tests\Feature;

use App\Models\ContactMessage;
use App\Models\Setting;
use App\Models\User;
use App\Support\ContactForm;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class ContactTest extends TestCase
{
    use RefreshDatabase;

    private function payload(array $override = []): array
    {
        return array_merge([
            'name' => 'مریم', 'email' => 'Maryam@Example.com', 'subject' => 'پروژه',
            'message' => 'سلام، برای طراحی سایت شرکتی سؤال داشتم.',
            ContactForm::HONEYPOT => '',
            ContactForm::TOKEN => Crypt::encryptString((string) (time() - 30)),
        ], $override);
    }

    public function test_valid_message_is_stored_with_a_hashed_ip_only(): void
    {
        $this->from('/contact/')->post('/contact/', $this->payload(), ['REMOTE_ADDR' => '203.0.113.9'])
            ->assertRedirect('/contact/')->assertSessionHas('contact_status', 'sent');

        $m = ContactMessage::query()->sole();
        $this->assertSame(['new', 'maryam@example.com'], [$m->status, $m->email]);
        $this->assertNotSame('203.0.113.9', $m->ip_hash);
        $this->assertSame(64, strlen($m->ip_hash));
    }

    public function test_bots_get_the_same_response_but_nothing_is_stored(): void
    {
        $this->post('/contact/', $this->payload([ContactForm::HONEYPOT => 'http://spam.example']))->assertSessionHas('contact_status', 'sent');
        $this->post('/contact/', $this->payload([ContactForm::TOKEN => 'forged']))->assertSessionHas('contact_status', 'sent');
        $this->post('/contact/', $this->payload([ContactForm::TOKEN => null]))->assertSessionHas('contact_status', 'sent');

        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_suspicious_messages_go_to_spam(): void
    {
        $this->post('/contact/', $this->payload([ContactForm::TOKEN => ContactForm::token()]), ['REMOTE_ADDR' => '198.51.100.1']); // too fast
        $this->post('/contact/', $this->payload(['message' => 'http://a.example http://b.example www.c.example https://d.example']), ['REMOTE_ADDR' => '198.51.100.2']);

        $this->assertSame(['spam', 'spam'], ContactMessage::query()->pluck('status')->all());
    }

    public function test_validation_and_rate_limit(): void
    {
        $this->post('/contact/', $this->payload(['email' => 'nope', 'message' => 'کوتاه']))->assertSessionHasErrors(['email', 'message']);

        for ($i = 0; $i < 2; $i++) {
            $this->post('/contact/', $this->payload())->assertRedirect();
        }
        $this->post('/contact/', $this->payload())->assertStatus(429); // 4th request from the same IP within 10 minutes
        $this->assertSame(2, ContactMessage::query()->count());
    }

    public function test_disabled_form_stores_nothing(): void
    {
        Setting::put(['contact_form_enabled' => false]);
        $this->post('/contact/', $this->payload())->assertSessionHas('contact_error');
        $this->assertSame(0, ContactMessage::query()->count());
    }

    public function test_admin_inbox_marks_read_changes_status_and_shows_safely(): void
    {
        $m = ContactMessage::create(['name' => '<b>x</b>', 'email' => 'a@example.com', 'message' => "خط ۱\n<script>alert(1)</script>"]);
        $admin = User::factory()->create();

        $this->actingAs($admin)->get('/admin/messages')->assertOk()->assertSee('is-unread', false);
        $this->get("/admin/messages/{$m->id}")->assertOk()->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;script&gt;', false);
        $this->assertSame('read', $m->fresh()->status);

        $this->patch("/admin/messages/{$m->id}", ['status' => 'replied'])->assertRedirect();
        $this->assertSame('replied', $m->fresh()->status);
        $this->assertNotNull($m->fresh()->handled_at);
        $this->patch("/admin/messages/{$m->id}", ['status' => 'deleted'])->assertSessionHasErrors('status');
    }

    public function test_old_messages_and_spam_are_pruned(): void
    {
        Setting::put(['message_retention_days' => 365]);
        $keep = ContactMessage::create(['name' => 'a', 'email' => 'a@example.com', 'message' => 'recent message']);
        $old = ContactMessage::create(['name' => 'b', 'email' => 'b@example.com', 'message' => 'old message']);
        $spam = ContactMessage::create(['name' => 'c', 'email' => 'c@example.com', 'message' => 'spam message', 'status' => 'spam']);
        ContactMessage::query()->whereKey($old->id)->update(['created_at' => now()->subDays(400)]);
        ContactMessage::query()->whereKey($spam->id)->update(['created_at' => now()->subDays(31)]);

        Artisan::call('model:prune', ['--model' => [ContactMessage::class]]);

        $this->assertSame([$keep->id], ContactMessage::query()->pluck('id')->all());
    }
}
