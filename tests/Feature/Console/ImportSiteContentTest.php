<?php

namespace Tests\Feature\Console;

use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Certification;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Redirect;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Skill;
use Illuminate\Contracts\Http\Kernel as HttpKernel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class ImportSiteContentTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_the_reviewed_content(): void
    {
        $this->artisan('app:import-site-content')->assertSuccessful();

        $profile = Profile::current();
        $this->assertSame('info@sajadmasoumi.com', $profile->email);
        $this->assertStringNotContainsString('چند برابر', $profile->short_bio.$profile->long_bio); // unverifiable claim
        $this->assertStringNotContainsString('۱۳۷۹', $profile->short_bio.$profile->long_bio);     // birth date stays private
        $this->assertNull($profile->availability_note);

        $kako = Experience::query()->where('organization', 'کاکو موزیک')->sole();
        $this->assertSame(['2013-03-21', 'year', null], [$kako->start_date->toDateString(), $kako->date_precision, $kako->end_date]);
        $this->assertNull(Experience::query()->where('organization', 'اتحاد وردپرس')->value('start_date'));
        $this->assertSame(4, Experience::query()->where('is_visible', true)->count());

        $this->assertSame(1, Certification::query()->where('is_visible', true)->count());
        $this->assertSame(0, Certification::query()->where('title', 'like', '%مسترکلاس%')->count());

        $this->assertSame(0, Skill::query()->whereNotNull('level_label')->count()); // no levels / percentages
        $this->assertEqualsCanonicalizing(
            ['WordPress', 'PHP', 'HTML', 'CSS', 'React', 'Java', 'Photoshop', 'Illustrator', 'هوش مصنوعی (AI)', 'فارسی', 'انگلیسی'],
            Skill::query()->where('is_visible', true)->pluck('name')->all()
        );

        $this->assertSame(3, ServiceGroup::query()->count());
        $this->assertSame(14, Service::query()->published()->count());
        $corporate = Service::query()->where('title', 'طراحی سایت شرکتی / ساده')->sole();
        $this->assertSame('شروع از ۷٬۵۰۰٬۰۰۰ تومان', $corporate->priceLabel());
        $samandehi = Service::query()->where('slug', 'samandehi-license')->sole();
        $this->assertSame(['draft', null], [$samandehi->status, $samandehi->price_original]);

        $parent = ArticleCategory::query()->where('slug', 'software-development')->sole();
        $this->assertSame($parent->id, ArticleCategory::query()->where('slug', 'programming-tutorials')->value('parent_id'));
        $this->assertDatabaseHas('activity_logs', ['event' => 'content.imported']);
    }

    public function test_second_run_creates_nothing_and_keeps_panel_edits(): void
    {
        $this->artisan('app:import-site-content')->assertSuccessful();
        $counts = $this->counts();

        Profile::current()->update(['headline' => 'عنوان تأییدشده']);
        Service::query()->where('title', 'طراحی سایت شرکتی / ساده')->update(['price_from' => 9000000]);

        $this->artisan('app:import-site-content')->expectsOutputToContain('Created 0 row(s)')->assertSuccessful();

        $this->assertSame($counts, $this->counts());
        $this->assertSame('عنوان تأییدشده', Profile::current()->headline);
        $this->assertSame(9000000, Service::query()->where('title', 'طراحی سایت شرکتی / ساده')->value('price_from'));
        $this->assertDatabaseCount('activity_logs', 1);
    }

    public function test_dry_run_writes_nothing(): void
    {
        $this->artisan('app:import-site-content', ['--dry-run' => true])->expectsOutputToContain('[dry run] would create')->assertSuccessful();

        $this->assertSame(array_fill_keys(array_keys($this->counts()), 0), $this->counts());
    }

    private function counts(): array
    {
        return [
            'profiles' => Profile::query()->count(), 'experiences' => Experience::query()->count(),
            'certifications' => Certification::query()->count(), 'skills' => Skill::query()->count(),
            'groups' => ServiceGroup::query()->count(), 'services' => Service::query()->count(),
            'categories' => ArticleCategory::query()->count(), 'articles' => Article::query()->count(),
        ];
    }

    public function test_migrated_articles_are_imported_with_categories_and_local_dates(): void
    {
        $this->artisan('app:import-site-content')->assertSuccessful();

        $article = Article::query()->where('slug', 'learn-programming-for-beginners')->sole();
        $this->assertTrue($article->isLive());
        // 09:00 in Tehran (UTC+03:30) is stored as UTC.
        $this->assertSame('2024-11-03 05:30:00', $article->published_at->utc()->format('Y-m-d H:i:s'));
        $this->assertEqualsCanonicalizing(['programming-tutorials', 'software-development'], $article->categories->pluck('slug')->all());
        $this->assertStringContainsString('<h2>', $article->body_html);
        $this->assertNotNull($article->reading_minutes);

        // The two posts copied from etehadwp.com were rewritten (D5) and wait for the owner's review.
        $this->assertSame(3, Article::query()->published()->count());
        $this->assertSame(['draft', 'draft'], Article::query()->whereIn('slug', ['what-is-wordpress', 'what-is-domain'])->pluck('status')->all());

        // No post links to the old .ir domain any more; images use site-relative /wp-content paths.
        foreach (Article::query()->get() as $a) {
            $this->assertStringNotContainsString('sajadmasoumi.ir', $a->body, $a->slug);
        }
    }

    public function test_article_files_with_unknown_categories_roll_back_the_whole_import(): void
    {
        $dir = sys_get_temp_dir().'/articles-'.uniqid();
        mkdir($dir);
        file_put_contents($dir.'/bad.md', "---\ntitle: T\nslug: bad-post\npublished_at: 2024-01-01 10:00\ncategories: nope\nstatus: published\n---\nBody\n");

        try {
            $this->artisan('app:import-site-content', ['--articles' => $dir])->assertFailed();
        } finally {
            unlink($dir.'/bad.md');
            rmdir($dir);
        }

        $this->assertSame(0, Article::query()->count());
        $this->assertSame(0, Profile::query()->count());
    }

    public function test_old_url_redirects_are_imported_and_every_301_lands_on_a_live_page(): void
    {
        $this->artisan('app:import-site-content')->assertSuccessful();
        $this->artisan('app:import-site-content')->assertSuccessful();   // idempotent

        $this->assertSame(59, Redirect::query()->count());
        $this->assertSame(33, Redirect::query()->where('status_code', 410)->count());
        $this->assertSame(0, Redirect::query()->whereIn('to_url', ['/', ''])->count());   // nothing to the home page

        $kernel = $this->app->make(HttpKernel::class);
        foreach (Redirect::query()->where('status_code', 301)->get() as $rule) {
            $old = $kernel->handle(Request::create('http://localhost'.Redirect::encodePath(rtrim($rule->from_path, '*'))));
            $this->assertSame(301, $old->getStatusCode(), $rule->from_path);

            $target = $kernel->handle(Request::create($old->headers->get('Location')));
            $this->assertSame(200, $target->getStatusCode(), $rule->from_path.' → '.$old->headers->get('Location'));
        }
        foreach (['/cart/', '/xmlrpc.php', '/wp-login.php', '/wp-admin/admin-ajax.php', '/wp-json/wp/v2/users'] as $gone) {
            $this->assertSame(410, $kernel->handle(Request::create('http://localhost'.$gone))->getStatusCode(), $gone);
        }
        // URLs the new site still serves are not in the redirect list.
        foreach (['/learn-programming-for-beginners/', '/blog/', '/feed/'] as $kept) {
            $this->assertSame(200, $kernel->handle(Request::create('http://localhost'.$kept))->getStatusCode(), $kept);
        }
    }
}
