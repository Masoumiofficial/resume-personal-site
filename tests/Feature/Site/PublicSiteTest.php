<?php

namespace Tests\Feature\Site;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Setting;
use App\Models\Tag;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/**
 * Public site (Phase 4). Requests go straight to the HTTP kernel because the
 * test client trims trailing slashes, and the slashed URL is the canonical one.
 */
class PublicSiteTest extends TestCase
{
    use RefreshDatabase;

    private function raw(string $uri, string $method = 'GET', array $parameters = []): TestResponse
    {
        $response = $this->app->make(HttpKernel::class)->handle(Request::create('http://localhost'.$uri, $method, $parameters));

        return TestResponse::fromBaseResponse($response);
    }

    private function article(array $attributes = []): Article
    {
        return Article::query()->create($attributes + [
            'title' => 'آموزش برنامه‌نویسی',
            'slug' => 'learn-programming',
            'excerpt' => 'خلاصهٔ مقاله',
            'body' => "## بخش اول\n\nمتن مقاله.",
            'status' => 'published',
            'published_at' => now()->subDay(),
        ]);
    }

    private function seedSite(): void
    {
        Profile::query()->create([
            'display_name' => 'سجاد معصومی',
            'headline' => 'توسعه‌دهندهٔ وب',
            'short_bio' => 'معرفی کوتاه',
            'long_bio' => 'معرفی **کامل**',
            'location' => 'شیراز',
            'email' => 'info@example.com',
            'phone' => '09120000000',
        ]);
        Experience::query()->create(['organization' => 'برند نمونه', 'role' => 'بنیان‌گذار', 'employment_type' => 'founder',
            'start_date' => '2013-03-21', 'date_precision' => 'year', 'is_visible' => true]);
        $group = ServiceGroup::query()->create(['title' => 'توسعه وب', 'slug' => 'web-development']);
        Service::query()->create(['service_group_id' => $group->id, 'title' => 'طراحی سایت', 'price_from' => 7500000,
            'status' => 'published', 'published_at' => now()->subDay()]);
        Service::query()->create(['service_group_id' => $group->id, 'title' => 'خدمت پیش‌نویس', 'price_from' => 1,
            'status' => 'draft']);
        $category = ArticleCategory::query()->create(['name' => 'برنامه‌نویسی', 'slug' => 'programming']);
        $this->article()->categories()->attach($category);
    }

    // ---- Pages -------------------------------------------------------------

    public function test_public_pages_render_with_canonical_urls_and_no_inline_styles(): void
    {
        $this->seedSite();

        foreach (['/', '/about/', '/resume/', '/works/', '/services/', '/blog/', '/contact/', '/learn-programming/', '/blog/category/programming/'] as $uri) {
            $response = $this->raw($uri)->assertOk();
            $html = $response->getContent();

            $this->assertStringContainsString('<link rel="canonical" href="http://localhost'.$uri.'">', $html, $uri);
            $this->assertDoesNotMatchRegularExpression('/\sstyle\s*=/i', $html, "inline style on {$uri} (blocked by the CSP)");
            // Every <script> must carry the per-request nonce.
            $this->assertSame(substr_count($html, '<script'), substr_count($html, '<script nonce=') + substr_count($html, '<script type="application/ld+json" nonce=') + substr_count($html, '<script src='), $uri);
        }
    }

    public function test_home_shows_panel_data_and_hides_drafts(): void
    {
        $this->seedSite();

        $this->raw('/')->assertOk()
            ->assertSee('سجاد معصومی')
            ->assertSee('فعال از ۱۳۹۲')                 // first experience year, Jalali
            ->assertSee('۷٬۵۰۰٬۰۰۰')
            ->assertDontSee('خدمت پیش‌نویس')
            ->assertSee('آموزش برنامه‌نویسی')
            ->assertSee('"@type":"Person"', false)
            ->assertDontSee('نمونه‌کارهای منتخب');       // no projects → no works section
    }

    public function test_home_renders_without_any_content(): void
    {
        $this->raw('/')->assertOk()->assertSee(config('app.name'));
    }

    public function test_article_permalinks_at_the_root(): void
    {
        $this->seedSite();
        $this->article(['slug' => 'draft-post', 'status' => 'draft', 'published_at' => null]);
        $this->article(['slug' => 'future-post', 'published_at' => now()->addDay()]);

        $this->raw('/learn-programming/')->assertOk()
            ->assertSee('<h2>بخش اول</h2>', false)
            ->assertSee('"@type":"BlogPosting"', false)
            ->assertSee('"@type":"BreadcrumbList"', false)
            ->assertSee('<meta property="og:type" content="article">', false);

        $this->raw('/learn-programming?ref=x')->assertStatus(301)->assertHeader('Location', 'http://localhost/learn-programming/?ref=x');
        $this->raw('/draft-post/')->assertNotFound();
        $this->raw('/future-post/')->assertNotFound();
        $this->raw('/learn-programming/extra/')->assertNotFound();
        // Unknown paths are a direct 404, without a slash redirect first.
        $this->raw('/no-such-page')->assertNotFound();
    }

    public function test_persian_slug_article(): void
    {
        $this->article(['slug' => 'وردپرس-چیست', 'title' => 'وردپرس چیست']);
        $encoded = '/'.rawurlencode('وردپرس-چیست').'/';

        $this->raw($encoded)->assertOk()->assertSee('<link rel="canonical" href="http://localhost'.$encoded.'">', false);
    }

    public function test_fallback_does_not_capture_the_admin_panel(): void
    {
        $this->article(['slug' => 'admin-like']);

        $this->raw('/'.config('admin.path'))->assertRedirect();      // to the login page, not a 404
        $this->raw('/'.config('admin.path').'/login')->assertOk()->assertSee('ورود');
    }

    public function test_404_page_uses_the_site_layout_and_is_noindex(): void
    {
        $this->raw('/missing/')->assertNotFound()
            ->assertSee('صفحه پیدا نشد')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false)
            ->assertSee('class="site-header"', false);
    }

    // ---- Blog archives -----------------------------------------------------

    public function test_blog_pagination(): void
    {
        foreach (range(1, 10) as $i) {
            $this->article(['slug' => "post-{$i}", 'title' => "مقاله {$i}", 'published_at' => now()->subDays($i)]);
        }

        $this->raw('/blog/')->assertOk()->assertSee('مقاله 1')->assertDontSee('مقاله 10<')
            ->assertSee('<link rel="next" href="http://localhost/blog/page/2/">', false);
        $this->raw('/blog/page/2/')->assertOk()->assertSee('مقاله 10')
            ->assertSee('<link rel="canonical" href="http://localhost/blog/page/2/">', false)
            ->assertSee('<link rel="prev" href="http://localhost/blog/">', false);
        $this->raw('/blog/page/1/')->assertStatus(301)->assertHeader('Location', 'http://localhost/blog/');
        $this->raw('/blog/page/3/')->assertNotFound();
    }

    public function test_parent_category_lists_child_articles_and_tags_are_noindex(): void
    {
        $parent = ArticleCategory::query()->create(['name' => 'توسعه نرم‌افزار', 'slug' => 'software-development']);
        $child = ArticleCategory::query()->create(['name' => 'تست', 'slug' => 'testing', 'parent_id' => $parent->id]);
        $article = $this->article();
        $article->categories()->attach($child);
        $tag = Tag::query()->create(['name' => 'PHP', 'slug' => 'php']);
        $article->tags()->attach($tag);

        $this->raw('/blog/category/software-development/')->assertOk()->assertSee('آموزش برنامه‌نویسی');
        $this->raw('/blog/category/nope/')->assertNotFound();
        $this->raw('/blog/tag/php/')->assertOk()->assertSee('آموزش برنامه‌نویسی')
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    // ---- Portfolio and services ---------------------------------------------

    public function test_projects_only_show_when_published_and_outcomes_need_evidence(): void
    {
        Project::query()->create(['title' => 'پروژهٔ منتشرشده', 'slug' => 'live', 'status' => 'published', 'published_at' => now()->subDay(),
            'outcome' => 'رشد ادعایی بدون مدرک']);
        Project::query()->create(['title' => 'پروژهٔ پیش‌نویس', 'slug' => 'draft', 'status' => 'draft']);

        $this->raw('/works/')->assertOk()->assertSee('پروژهٔ منتشرشده')->assertDontSee('پروژهٔ پیش‌نویس');
        $this->raw('/works/live/')->assertOk()->assertDontSee('رشد ادعایی بدون مدرک');
        $this->raw('/works/draft/')->assertNotFound();

        Project::query()->where('slug', 'live')->update(['outcome_verified' => true]);
        $this->raw('/works/live/')->assertSee('رشد ادعایی بدون مدرک');
    }

    public function test_empty_portfolio_is_noindex(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->raw('/works/')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_service_pages_exist_only_for_services_with_a_page(): void
    {
        $group = ServiceGroup::query()->create(['title' => 'گروه', 'slug' => 'group']);
        Service::query()->create(['service_group_id' => $group->id, 'title' => 'مجوز ساماندهی', 'slug' => 'samandehi', 'has_page' => true,
            'price_from' => 1500000, 'status' => 'published', 'published_at' => now()->subDay(), 'body' => 'شرح خدمت']);
        Service::query()->create(['service_group_id' => $group->id, 'title' => 'بدون صفحه', 'slug' => 'no-page',
            'status' => 'published', 'published_at' => now()->subDay()]);

        $this->raw('/services/samandehi/')->assertOk()->assertSee('شرح خدمت')
            ->assertSee('"price":15000000,"priceCurrency":"IRR"', false);   // toman × 10
        $this->raw('/services/no-page/')->assertNotFound();
        $this->raw('/services/')->assertOk()->assertSee('href="http://localhost/services/samandehi/"', false);
    }

    // ---- Contact ------------------------------------------------------------

    public function test_contact_page_has_the_protected_form(): void
    {
        $this->seedSite();

        $this->raw('/contact/?subject='.rawurlencode('مجوز ساماندهی'))->assertOk()
            ->assertSee('name="website"', false)
            ->assertSee('name="_ts"', false)
            ->assertSee('value="مجوز ساماندهی"', false)
            ->assertSee('href="tel:+989120000000"', false);

        Setting::put(['contact_form_enabled' => '0']);
        $this->raw('/contact/')->assertOk()->assertDontSee('name="website"', false)->assertSee('موقتاً غیرفعال');
    }

    // ---- Machine-readable endpoints ----------------------------------------------

    public function test_sitemap_lists_only_public_indexable_urls(): void
    {
        $this->seedSite();
        $this->article(['slug' => 'draft-post', 'status' => 'draft', 'published_at' => null]);
        $this->article(['slug' => 'hidden-post', 'noindex' => true]);

        $response = $this->raw('/sitemap.xml')->assertOk()->assertHeader('Content-Type', 'application/xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $locs = array_map('strval', $xml->xpath('//*[local-name()="loc"]'));

        $this->assertContains('http://localhost/', $locs);
        $this->assertContains('http://localhost/learn-programming/', $locs);
        $this->assertContains('http://localhost/blog/category/programming/', $locs);
        $this->assertNotContains('http://localhost/draft-post/', $locs);
        $this->assertNotContains('http://localhost/hidden-post/', $locs);
        $this->assertNotContains('http://localhost/works/', $locs);   // empty portfolio
    }

    public function test_feed_is_valid_rss(): void
    {
        $this->seedSite();

        $response = $this->raw('/feed/')->assertOk()->assertHeader('Content-Type', 'application/rss+xml; charset=UTF-8');
        $xml = simplexml_load_string($response->getContent());
        $this->assertNotFalse($xml);
        $this->assertSame('آموزش برنامه‌نویسی', (string) $xml->channel->item[0]->title);
        $this->assertSame('http://localhost/learn-programming/', (string) $xml->channel->item[0]->link);
    }

    public function test_robots_txt_and_meta_follow_environment_and_setting(): void
    {
        // Non-production: everything closed, including an X-Robots-Tag header.
        $this->raw('/robots.txt')->assertOk()->assertSee('Disallow: /')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
        $this->raw('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);

        $this->app->detectEnvironment(fn () => 'production');
        $robots = $this->raw('/robots.txt')->assertOk()->assertHeaderMissing('X-Robots-Tag')->getContent();
        $this->assertStringContainsString("Disallow:\n", $robots);
        $this->assertStringContainsString('Sitemap: http://localhost/sitemap.xml', $robots);
        $this->assertStringNotContainsString(config('admin.path'), $robots);
        $this->raw('/')->assertSee('<meta name="robots" content="index, follow, max-image-preview:large">', false);

        Setting::put(['search_indexing' => '0']);
        $this->raw('/robots.txt')->assertSee('Disallow: /');
        $this->raw('/')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_json_ld_cannot_break_out_of_its_script_tag(): void
    {
        $this->article(['slug' => 'xss', 'title' => 'عنوان </script><script>alert(1)</script>']);

        $html = $this->raw('/xss/')->assertOk()->getContent();
        $this->assertStringNotContainsString('</script><script>alert(1)', $html);
        $this->assertStringContainsString('\u003C/script\u003E', $html);
    }
}
