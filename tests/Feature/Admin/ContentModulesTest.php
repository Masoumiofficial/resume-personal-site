<?php

namespace Tests\Feature\Admin;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Setting;
use App\Models\Tag;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentModulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    private function group(): ServiceGroup
    {
        return ServiceGroup::create(['title' => 'وب و وردپرس', 'slug' => 'web']);
    }

    public function test_service_price_accepts_persian_digits_and_separators(): void
    {
        $group = $this->group();
        $this->actingAs($this->admin())->post('/admin/services', [
            'title' => 'طراحی سایت شرکتی', 'service_group_id' => $group->id, 'price_from' => '۷٬۵۰۰٬۰۰۰',
            'price_unit' => 'once', 'cta_type' => 'contact', 'status' => 'published', 'is_visible' => '1',
        ])->assertSessionHasNoErrors();

        $service = Service::query()->sole();
        $this->assertSame(7500000, $service->price_from);
        $this->assertSame('شروع از ۷٬۵۰۰٬۰۰۰ تومان', $service->priceLabel());
        $this->assertNotNull($service->published_at, 'Publishing without a date publishes now.');
        $this->assertTrue($service->isLive());
    }

    public function test_service_cross_field_rules(): void
    {
        $group = $this->group();
        $base = ['title' => 'x', 'service_group_id' => $group->id, 'price_unit' => 'monthly', 'cta_type' => 'contact', 'status' => 'draft'];
        $this->actingAs($this->admin())->post('/admin/services', $base + ['has_page' => '1'])->assertSessionHasErrors('slug');
        $this->post('/admin/services', array_merge($base, ['cta_type' => 'external']))->assertSessionHasErrors('cta_url');
        $this->post('/admin/services', $base + ['price_from' => '1000000', 'price_original' => '900000'])->assertSessionHasErrors('price_original');
        $this->post('/admin/services', $base + ['price_from' => '12abc'])->assertSessionHasErrors('price_from');
        $this->assertSame(0, Service::query()->count());
    }

    public function test_a_group_with_services_cannot_be_deleted(): void
    {
        $group = $this->group();
        Service::create(['service_group_id' => $group->id, 'title' => 'x']);

        $this->actingAs($this->admin())->from('/admin/service-groups')->delete("/admin/service-groups/{$group->id}")
            ->assertRedirect('/admin/service-groups')->assertSessionHas('flash_error');
        $this->assertModelExists($group);
    }

    public function test_scheduled_service_is_not_live(): void
    {
        $group = $this->group();
        $this->actingAs($this->admin())->post('/admin/services', [
            'title' => 'x', 'service_group_id' => $group->id, 'price_unit' => 'once', 'cta_type' => 'contact',
            'status' => 'published', 'published_at' => '1410/01/01 10:00',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $service = Service::query()->sole();
        $this->assertSame('scheduled', $service->publicationState());
        $this->assertSame(0, Service::query()->published()->count());

        // Beyond the TIMESTAMP range of MySQL/MariaDB: a validation error, never a database error.
        $this->put("/admin/services/{$service->id}", [
            'title' => 'x', 'service_group_id' => $group->id, 'price_unit' => 'once', 'cta_type' => 'contact',
            'status' => 'published', 'published_at' => '1499/01/01',
        ])->assertRedirect()->assertSessionHasErrors(['published_at' => 'زمان انتشار: تاریخ باید پیش از سال ۱۴۱۷ (۲۰۳۸ میلادی) باشد.']);
    }

    public function test_article_slug_rules_body_sanitising_and_relations(): void
    {
        $admin = $this->admin();
        $category = ArticleCategory::create(['name' => 'توسعه نرم‌افزار', 'slug' => 'software-development']);
        $tag = Tag::create(['name' => 'آموزش برنامه‌نویسی', 'slug' => 'آموزش-برنامه‌نویسی']);

        foreach (['admin', 'blog', 'wp-login.php', 'Has Space', 'UPPER'] as $bad) {
            $this->actingAs($admin)->post('/admin/articles', ['title' => 't', 'slug' => $bad, 'status' => 'draft'])->assertSessionHasErrors('slug');
        }

        $this->post('/admin/articles', [
            'title' => 'آموزش برنامه‌نویسی', 'slug' => 'learn-programming-for-beginners', 'status' => 'draft',
            'body' => "## مقدمه\n\nمتن <script>alert(1)</script>",
            'categories' => [$category->id], 'tags' => [$tag->id],
        ])->assertSessionHasNoErrors();

        $article = Article::query()->sole();
        $this->assertStringContainsString('<h2>مقدمه</h2>', $article->body_html);
        $this->assertStringNotContainsString('<script', $article->body_html);
        $this->assertSame(1, $article->reading_minutes);
        $this->assertSame([$category->id], $article->categories()->pluck('article_categories.id')->all());
        $this->assertSame([$tag->id], $article->tags()->pluck('tags.id')->all());

        $this->post('/admin/articles', ['title' => 'dup', 'slug' => 'learn-programming-for-beginners', 'status' => 'draft'])
            ->assertSessionHasErrors(['slug' => 'این نامک قبلاً استفاده شده است.']);
    }

    public function test_persian_tag_slugs_are_accepted(): void
    {
        $this->actingAs($this->admin())->post('/admin/tags', ['name' => 'تست نرم‌افزار', 'slug' => 'تست-نرم‌افزار'])->assertSessionHasNoErrors();
        $this->assertDatabaseHas('tags', ['slug' => 'تست-نرم‌افزار']);
    }

    public function test_article_trash_restore_and_permanent_delete(): void
    {
        $admin = $this->admin();
        $article = Article::create(['title' => 'x', 'slug' => 'x-article']);

        $this->actingAs($admin)->delete("/admin/articles/{$article->id}")->assertRedirect('/admin/articles');
        $this->assertSoftDeleted($article);
        $this->get('/admin/articles?trashed=1')->assertOk()->assertSee('x');
        // A trashed slug stays reserved, so restoring can never collide.
        $this->post('/admin/articles', ['title' => 'y', 'slug' => 'x-article', 'status' => 'draft'])->assertSessionHasErrors('slug');

        $this->post("/admin/articles/{$article->id}/restore")->assertRedirect();
        $this->assertNotSoftDeleted($article);

        $article->delete();
        $this->delete("/admin/articles/{$article->id}/force")->assertRedirect();
        $this->assertModelMissing($article);
        $this->assertDatabaseHas('activity_logs', ['event' => 'content.force_deleted']);
    }

    public function test_testimonial_cannot_be_visible_without_consent(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post('/admin/testimonials', ['author_name' => 'نام', 'quote' => 'متن', 'is_visible' => '1'])
            ->assertSessionHasErrors('is_visible');

        $this->post('/admin/testimonials', ['author_name' => 'نام', 'quote' => 'متن', 'is_visible' => '1', 'consent_confirmed' => '1', 'received_at' => '1405/07/01'])
            ->assertSessionHasNoErrors();
        $this->assertSame(1, Testimonial::query()->public()->count());
    }

    public function test_settings_are_saved_and_the_cache_is_refreshed(): void
    {
        $this->assertTrue(Setting::get('contact_form_enabled'));

        $this->actingAs($this->admin())->put('/admin/settings', [
            'site_title' => 'سجاد معصومی', 'title_separator' => '|', 'contact_form_enabled' => '0', 'message_retention_days' => '۱۸۰',
        ])->assertRedirect('/admin/settings')->assertSessionHasNoErrors();

        $this->assertFalse(Setting::get('contact_form_enabled'));
        $this->assertSame(180, Setting::get('message_retention_days'));

        $this->put('/admin/settings', ['site_title' => 'x', 'title_separator' => '|', 'message_retention_days' => '5'])
            ->assertSessionHasErrors('message_retention_days');
    }

    public function test_unknown_ids_are_404_and_guests_cannot_write(): void
    {
        $this->actingAs($this->admin())->get('/admin/articles/999/edit')->assertNotFound();
        auth()->logout();
        $this->post('/admin/articles', ['title' => 'x', 'slug' => 'x', 'status' => 'draft'])->assertRedirect('/admin/login');
        $this->assertSame(0, Article::query()->count());
    }
}
