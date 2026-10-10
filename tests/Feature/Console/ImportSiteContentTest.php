<?php

namespace Tests\Feature\Console;

use App\Models\ArticleCategory;
use App\Models\Certification;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Service;
use App\Models\ServiceGroup;
use App\Models\Skill;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'categories' => ArticleCategory::query()->count(),
        ];
    }
}
