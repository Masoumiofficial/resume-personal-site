<?php

namespace Tests\Feature\Admin;

use App\Models\Certification;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Skill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ResumeModulesTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create();
    }

    public function test_experience_with_jalali_range_stores_the_coarsest_precision(): void
    {
        $this->actingAs($this->admin())->post('/admin/experiences', [
            'organization' => 'کاکو موزیک', 'role' => 'مدیر مسئول و بنیان‌گذار', 'employment_type' => 'founder',
            'start_date' => '۱۳۹۲', 'end_date' => '1400/05', 'is_visible' => '1',
        ])->assertRedirect()->assertSessionHasNoErrors();

        $e = Experience::query()->sole();
        $this->assertSame('2013-03-21', $e->start_date->toDateString());
        $this->assertSame('year', $e->date_precision); // "۱۳۹۲" has no month, so the range shows years only
        $this->assertDatabaseHas('activity_logs', ['event' => 'content.created', 'subject_id' => $e->id]);

        $this->get("/admin/experiences/{$e->id}/edit")->assertOk()->assertSee('value="1392"', false);
    }

    public function test_experience_validation_messages_are_persian_and_dates_are_checked(): void
    {
        $this->actingAs($this->admin())->from('/admin/experiences/create')->post('/admin/experiences', [
            'organization' => '', 'role' => 'نقش', 'start_date' => '1400', 'end_date' => '1399',
        ])->assertSessionHasErrors(['organization' => 'مجموعه / شرکت الزامی است.', 'end_date']);

        $this->post('/admin/experiences', ['organization' => 'x', 'role' => 'y', 'start_date' => 'پارسال'])
            ->assertSessionHasErrors('start_date');
        $this->assertSame(0, Experience::query()->count());
    }

    public function test_skill_levels_cannot_be_percentages(): void
    {
        $admin = $this->admin();
        foreach (['95%', '۸۰٪', '70 درصد'] as $label) {
            $this->actingAs($admin)->post('/admin/skills', ['name' => 'PHP', 'group' => 'development', 'level_label' => $label])
                ->assertSessionHasErrors('level_label');
        }
        $this->post('/admin/skills', ['name' => 'English', 'group' => 'language', 'level_label' => 'B2'])->assertSessionHasNoErrors();
        $this->assertSame(['English'], Skill::query()->pluck('name')->all());
    }

    public function test_move_reorders_within_the_same_group_only(): void
    {
        $a = Skill::create(['name' => 'A', 'group' => 'development', 'sort_order' => 1]);
        $b = Skill::create(['name' => 'B', 'group' => 'development', 'sort_order' => 2]);
        $other = Skill::create(['name' => 'X', 'group' => 'design', 'sort_order' => 1]);

        $this->actingAs($this->admin())->post("/admin/skills/{$b->id}/move/up")->assertRedirect();

        $this->assertSame(['B', 'A'], Skill::query()->where('group', 'development')->ordered()->pluck('name')->all());
        $this->assertSame(1, $other->fresh()->sort_order);

        // Moving the first item up is a no-op, not an error.
        $this->post("/admin/skills/{$b->id}/move/up")->assertRedirect();
        $this->assertSame(['B', 'A'], Skill::query()->where('group', 'development')->ordered()->pluck('name')->all());
    }

    public function test_resume_preview_shows_only_visible_items_and_respects_privacy_flags(): void
    {
        Profile::create(['display_name' => 'سجاد معصومی', 'phone' => '09120000000', 'show_phone' => false, 'location' => 'شیراز', 'show_location' => true]);
        Experience::create(['organization' => 'Visible Org', 'role' => 'Founder', 'start_date' => '2013-03-21', 'date_precision' => 'year']);
        Experience::create(['organization' => 'Hidden Org', 'role' => 'X', 'is_visible' => false]);
        Certification::create(['title' => 'Course A', 'kind' => 'course', 'issued_at' => '2023-03-21', 'date_precision' => 'month']);
        Skill::create(['name' => 'فارسی', 'group' => 'language', 'level_label' => 'زبان مادری']);

        $this->actingAs($this->admin())->get('/admin/resume/print')->assertOk()
            ->assertSee('Visible Org')->assertSee('۱۳۹۲ – اکنون')->assertSee('فروردین ۱۴۰۲')->assertSee('فارسی (زبان مادری)')
            ->assertSee('شیراز')->assertDontSee('Hidden Org')->assertDontSee('09120000000');
    }

    public function test_profile_is_a_single_row_and_social_links_are_validated(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->put('/admin/profile', [
            'display_name' => 'سجاد معصومی', 'email' => 'info@sajadmasoumi.com', 'phone' => '۰۹۹۱۰۰۱۱۱۴۶',
            'social_links' => "لینکدین | https://www.linkedin.com/in/example\nگیت‌هاب | https://github.com/example",
        ])->assertRedirect('/admin/profile')->assertSessionHasNoErrors();
        $this->put('/admin/profile', ['display_name' => 'سجاد معصومی'])->assertSessionHasNoErrors();

        $this->assertSame(1, Profile::query()->count());

        $this->put('/admin/profile', ['display_name' => 'x', 'social_links' => 'بد | javascript:alert(1)'])
            ->assertSessionHasErrors(['social_links.0.url' => 'نشانی پیوند در «شبکه‌های اجتماعی» باید یک نشانی معتبر باشد.']);
        $this->get('/admin/profile')->assertOk();
        $this->put('/admin/profile', ['display_name' => 'x', 'email' => 'not-an-email'])->assertSessionHasErrors('email');
    }
}
