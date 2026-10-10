<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Experience;
use App\Models\Profile;
use App\Models\Project;
use App\Models\ServiceGroup;
use App\Models\Setting;
use App\Models\Testimonial;
use App\Support\Jalali;
use App\Support\Link;
use App\Support\Seo;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function __invoke(): View
    {
        $profile = Profile::current();
        $founded = Experience::query()->where('is_visible', true)->where('employment_type', 'founder')->ordered()->get();

        $seo = Seo::make(null, $profile->short_bio)->canonical(Link::route('home'))->image($profile->avatar);
        $seo->schema(array_filter([
            '@type' => 'Person',
            'name' => $profile->display_name,
            'jobTitle' => $profile->headline,
            'url' => Link::route('home'),
            'email' => $profile->email ? 'mailto:'.$profile->email : null,
            'image' => $seo->image,
            'sameAs' => collect($profile->social_links ?? [])->pluck('url')->filter()->values()->all() ?: null,
        ]));
        $seo->schema(['@type' => 'WebSite', 'name' => Setting::get('site_title'), 'url' => Link::route('home'), 'inLanguage' => 'fa-IR']);

        return view('site.home', [
            'seo' => $seo,
            'profile' => $profile,
            'facts' => $this->facts($profile),
            'ventures' => $founded,
            'groups' => Setting::get('home_show_services') ? ServiceGroup::query()->ordered()
                ->with(['services' => fn ($q) => $q->published()->where('is_visible', true)->ordered()])
                ->get()->filter(fn ($g) => $g->services->isNotEmpty()) : collect(),
            'projects' => Setting::get('home_show_projects')
                ? Project::query()->published()->with('cover', 'categories')->orderByDesc('is_featured')->ordered()->limit(3)->get() : collect(),
            'articles' => Setting::get('home_show_articles')
                ? Article::query()->published()->with('cover', 'categories')->latest('published_at')->limit(3)->get() : collect(),
            'testimonials' => Setting::get('home_show_testimonials') ? Testimonial::query()->public()->ordered()->limit(3)->get() : collect(),
        ]);
    }

    /**
     * Facts strip, computed from panel data only (no invented numbers):
     * first year of activity, founded brands, location.
     *
     * @return list<array{label: string, value: string}>
     */
    private function facts(Profile $profile): array
    {
        $experiences = Experience::query()->where('is_visible', true)->get(['start_date', 'employment_type']);
        $first = $experiences->pluck('start_date')->filter()->min();
        $founded = $experiences->where('employment_type', 'founder')->count();

        return array_values(array_filter([
            $first ? ['label' => 'شروع فعالیت حرفه‌ای', 'value' => Jalali::format($first, 'Y')] : null,
            $founded > 0 ? ['label' => 'برندهای بنیان‌گذاری‌شده', 'value' => Jalali::digits((string) $founded).' برند'] : null,
            $profile->location && $profile->show_location ? ['label' => 'محل فعالیت', 'value' => $profile->location] : null,
        ]));
    }
}
