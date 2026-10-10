<?php

namespace Tests\Feature\Site;

use App\Models\ActivityLog;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Profile;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\User;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;

/** Phase 5: old-URL redirects (D31) and the admin draft preview (D32). */
class RedirectsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Profile::query()->create(['display_name' => 'سجاد معصومی', 'headline' => 'توسعه‌دهندهٔ وب']);
    }

    private function raw(string $uri, string $method = 'GET'): TestResponse
    {
        return TestResponse::fromBaseResponse($this->app->make(HttpKernel::class)->handle(Request::create('http://localhost'.$uri, $method)));
    }

    private function rule(string $from, ?string $to, int $status = 301, bool $active = true): Redirect
    {
        return Redirect::query()->create(['from_path' => $from, 'to_url' => $to, 'status_code' => $status, 'is_active' => $active]);
    }

    private function article(array $attributes = []): Article
    {
        return Article::query()->create($attributes + ['title' => 'مقاله', 'slug' => 'learn-programming', 'body' => 'متن',
            'status' => 'published', 'published_at' => now()->subDay()]);
    }

    public function test_paths_are_normalised_for_storage_and_lookup(): void
    {
        $this->assertSame('/software-development/', Redirect::normalize('software-development'));
        $this->assertSame('/tag/تست-نرمافزار/', Redirect::normalize('/tag/%D8%AA%D8%B3%D8%AA-%D9%86%D8%B1%D9%85%D8%A7%D9%81%D8%B2%D8%A7%D8%B1'));
        $this->assertSame('/shop/', Redirect::normalize('https://sajadmasoumi.com//Shop?x=1#top'));
        $this->assertSame('/xmlrpc.php', Redirect::normalize('/xmlrpc.php'));
        $this->assertSame('/wp-json/*', Redirect::normalize('/wp-json/*'));
        $this->assertSame('/blog/tag/%D8%AA%D8%B3%D8%AA/', Redirect::encodePath('/blog/tag/تست/'));
    }

    public function test_old_category_url_gets_one_301_and_counts_the_hit(): void
    {
        $rule = $this->rule('/software-development/', '/blog/category/software-development/');

        $this->raw('/software-development/')->assertStatus(301)->assertHeader('Location', 'http://localhost/blog/category/software-development/');
        // Slashless and nested forms resolve directly (fallback route: no slash redirect first).
        $this->raw('/software-development')->assertStatus(301)->assertHeader('Location', 'http://localhost/blog/category/software-development/');

        $rule->refresh();
        $this->assertSame(2, $rule->hits);
        $this->assertNotNull($rule->last_hit_at);
    }

    public function test_encoded_persian_paths_match_and_query_string_is_kept(): void
    {
        $this->rule('/tag/تست-نرمافزار/', '/blog/category/software-testing-optimization/');

        $this->raw('/tag/%d8%aa%d8%b3%d8%aa-%d9%86%d8%b1%d9%85%d8%a7%d9%81%d8%b2%d8%a7%d8%b1/?utm_source=x')
            ->assertStatus(301)->assertHeader('Location', 'http://localhost/blog/category/software-testing-optimization/?utm_source=x');
    }

    public function test_410_rules_and_prefix_rules(): void
    {
        $this->rule('/cart/', null, 410);
        $this->rule('/wp-json/*', null, 410);

        $this->raw('/cart/')->assertStatus(410)->assertSee('این صفحه حذف شده است')->assertSee('noindex', false);
        $this->raw('/wp-json/wp/v2/posts?per_page=100')->assertStatus(410);
        $this->raw('/wp-json')->assertStatus(410);
    }

    public function test_redirects_never_shadow_live_pages_and_inactive_rules_are_ignored(): void
    {
        $this->article();
        $this->rule('/learn-programming/', '/blog/');
        $this->rule('/about/', '/contact/');
        $this->rule('/old/', '/blog/', 301, false);

        $this->raw('/learn-programming/')->assertOk();
        $this->raw('/about/')->assertOk();
        $this->raw('/old/')->assertNotFound();
        $this->assertSame(0, Redirect::query()->sum('hits'));
    }

    public function test_only_get_and_head_are_redirected_and_admin_paths_are_excluded(): void
    {
        $this->rule('/shop/', '/services/');
        $admin = config('admin.path');
        $this->rule("/{$admin}/nothing-here/", '/blog/');

        $this->raw('/shop/', 'POST')->assertNotFound();
        $this->raw('/shop/', 'HEAD')->assertStatus(301);
        $this->actingAs(User::factory()->create());
        $this->raw("/{$admin}/nothing-here/")->assertNotFound();
    }

    public function test_external_targets_and_self_loops(): void
    {
        $this->rule('/etehad/', 'https://etehadwp.com/');
        $loop = Redirect::query()->create(['from_path' => '/loop/', 'to_url' => '/loop', 'status_code' => 302]);

        $this->raw('/etehad/')->assertStatus(301)->assertHeader('Location', 'https://etehadwp.com/');
        $this->raw('/loop/')->assertNotFound();
        $this->assertSame(302, $loop->status_code);
    }

    public function test_changing_a_published_slug_leaves_a_301_and_renaming_back_does_not_loop(): void
    {
        $article = $this->article();

        $article->update(['slug' => 'programming-basics']);
        $this->raw('/learn-programming/')->assertStatus(301)->assertHeader('Location', 'http://localhost/programming-basics/');
        $this->raw('/programming-basics/')->assertOk();
        $this->assertTrue(ActivityLog::query()->where('event', 'redirect.auto_created')->exists());

        // Second rename: the first rule is repointed (no chain) and a rule for the new slug is created.
        $article->update(['slug' => 'programming-101']);
        $this->raw('/learn-programming/')->assertHeader('Location', 'http://localhost/programming-101/');
        $this->raw('/programming-basics/')->assertHeader('Location', 'http://localhost/programming-101/');

        // Back to the original: its old rule is removed, the page answers again.
        $article->update(['slug' => 'learn-programming']);
        $this->raw('/learn-programming/')->assertOk();
        $this->assertFalse(Redirect::query()->where('from_hash', Redirect::hash('/learn-programming/'))->exists());
        $this->raw('/programming-101/')->assertHeader('Location', 'http://localhost/learn-programming/');
    }

    public function test_slug_changes_of_drafts_and_unpublished_services_leave_no_redirect(): void
    {
        $this->article(['slug' => 'draft-post', 'status' => 'draft', 'published_at' => null])->update(['slug' => 'draft-post-2']);
        $group = ServiceGroup::query()->create(['title' => 'وب', 'slug' => 'web']);
        Service::query()->create(['service_group_id' => $group->id, 'title' => 'خدمت', 'slug' => 'svc', 'has_page' => false, 'status' => 'published', 'published_at' => now()])
            ->update(['slug' => 'svc-2']);
        ArticleCategory::query()->create(['name' => 'دسته', 'slug' => 'cat'])->update(['slug' => 'category']);

        $this->assertSame(['/blog/category/cat/'], Redirect::query()->pluck('from_path')->all());
    }

    public function test_admin_can_manage_redirects_with_validation(): void
    {
        $this->actingAs(User::factory()->create());
        $this->article();
        $base = '/'.config('admin.path').'/redirects';

        $this->get($base)->assertOk()->assertSee('تغییرمسیرها');
        $this->get($base.'/create')->assertOk();

        $this->post($base, ['from_path' => '/tag/%D8%AA%D8%B3%D8%AA/', 'status_code' => '301', 'to_url' => '/blog/tag/تست/', 'is_active' => '1'])
            ->assertSessionHasNoErrors()->assertRedirect();
        $rule = Redirect::query()->sole();
        $this->assertSame('/tag/تست/', $rule->from_path);
        $this->assertSame('/blog/tag/%D8%AA%D8%B3%D8%AA/', $rule->to_url);

        // Duplicate (different encoding), live pages, home/admin paths, bad or missing targets.
        $this->post($base, ['from_path' => '/tag/تست', 'status_code' => '301', 'to_url' => '/blog/'])->assertSessionHasErrors('from_path');
        $this->post($base, ['from_path' => '/learn-programming/', 'status_code' => '301', 'to_url' => '/blog/'])->assertSessionHasErrors('from_path');
        $this->post($base, ['from_path' => '/about/', 'status_code' => '301', 'to_url' => '/blog/'])->assertSessionHasErrors('from_path');
        $this->post($base, ['from_path' => '/', 'status_code' => '301', 'to_url' => '/blog/'])->assertSessionHasErrors('from_path');
        $this->post($base, ['from_path' => '/'.config('admin.path').'/x', 'status_code' => '410'])->assertSessionHasErrors('from_path');
        $this->post($base, ['from_path' => '/a/', 'status_code' => '301', 'to_url' => '//evil.example'])->assertSessionHasErrors('to_url');
        $this->post($base, ['from_path' => '/a/', 'status_code' => '301', 'to_url' => 'javascript:alert(1)'])->assertSessionHasErrors('to_url');
        $this->post($base, ['from_path' => '/a/', 'status_code' => '302'])->assertSessionHasErrors('to_url');
        $this->post($base, ['from_path' => '/a/', 'status_code' => '301', 'to_url' => '/a'])->assertSessionHasErrors('to_url');
        $this->post($base, ['from_path' => '/a/', 'status_code' => '307', 'to_url' => '/b/'])->assertSessionHasErrors('status_code');

        // 410 drops any target; editing keeps the record unique to itself.
        $this->post($base, ['from_path' => '/cart/', 'status_code' => '410', 'to_url' => '/ignored/', 'is_active' => '1'])->assertSessionHasNoErrors();
        $this->assertNull(Redirect::query()->where('from_path', '/cart/')->value('to_url'));
        $this->put($base.'/'.$rule->id, ['from_path' => '/tag/تست/', 'status_code' => '302', 'to_url' => '/blog/'])->assertSessionHasNoErrors();
        $this->assertSame(302, $rule->refresh()->status_code);
        $this->assertFalse($rule->is_active);

        $this->delete($base.'/'.$rule->id)->assertRedirect();
        $this->assertSame(1, Redirect::query()->count());
    }

    public function test_draft_preview_is_admin_only_noindex_and_not_cached(): void
    {
        $draft = $this->article(['slug' => 'what-is-domain', 'title' => 'دامنه چیست؟', 'status' => 'draft', 'published_at' => null]);
        $url = '/'.config('admin.path').'/articles/'.$draft->id.'/preview';

        $this->raw('/what-is-domain/')->assertNotFound();
        $this->get($url)->assertRedirect();   // guests go to the login page

        $this->actingAs(User::factory()->create());
        $this->get($url)->assertOk()->assertSee('دامنه چیست؟')->assertSee('پیش‌نمایش')
            ->assertSee('<meta name="robots" content="noindex', false)
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertNull($draft->refresh()->published_at);   // the preview date is never saved
        $this->get('/'.config('admin.path').'/articles/'.$draft->id.'/edit')->assertSee($url, false);
    }
}
