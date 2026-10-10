<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Service;
use App\Models\Setting;
use App\Support\Link;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Response;

/** Machine-readable endpoints: RSS feed (same /feed/ URL as WordPress), XML sitemap, robots.txt. */
class FeedController extends Controller
{
    public function rss(): Response
    {
        $articles = Article::query()->published()->with('categories')->latest('published_at')->limit(20)->get();

        return response()->view('site.feed', [
            'articles' => $articles,
            'profile' => Profile::current(),
        ])->header('Content-Type', 'application/rss+xml; charset=UTF-8');
    }

    public function sitemap(): Response
    {
        $urls = collect([
            ['loc' => Link::route('home')],
            ['loc' => Link::route('about')],
            ['loc' => Link::route('resume')],
            ['loc' => Link::route('services.index')],
            ['loc' => Link::route('blog.index'), 'lastmod' => Article::query()->published()->max('published_at')],
            ['loc' => Link::route('contact')],
        ]);

        $projects = Project::query()->published()->where('noindex', false)->get(['slug', 'updated_at']);
        if ($projects->isNotEmpty()) {
            $urls->push(['loc' => Link::route('works.index')]);
        }
        $urls = $urls
            ->concat($projects->map(fn (Project $p) => ['loc' => $p->url(), 'lastmod' => $p->updated_at]))
            ->concat(Service::query()->published()->where('is_visible', true)->where('has_page', true)->where('noindex', false)
                ->get(['slug', 'has_page', 'updated_at'])->map(fn (Service $s) => ['loc' => $s->url(), 'lastmod' => $s->updated_at]))
            ->concat(ArticleCategory::query()->whereHas('articles', fn (Builder $q) => $q->published())->get(['id', 'slug'])
                ->map(fn (ArticleCategory $c) => ['loc' => $c->url()]))
            ->concat(Article::query()->published()->where('noindex', false)->whereNull('canonical_url')
                ->latest('published_at')->get(['slug', 'published_at', 'updated_content_at'])
                ->map(fn (Article $a) => ['loc' => $a->url(), 'lastmod' => $a->updated_content_at ?? $a->published_at]));

        return response()->view('site.sitemap', ['urls' => $urls])->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /** The admin path is deliberately not listed: robots.txt is public and would advertise it. */
    public function robots(): Response
    {
        $indexable = app()->isProduction() && Setting::get('search_indexing');
        $lines = $indexable
            ? ['User-agent: *', 'Disallow:', '', 'Sitemap: '.route('sitemap')]
            : ['User-agent: *', 'Disallow: /'];

        return response(implode("\n", $lines)."\n", 200, ['Content-Type' => 'text/plain; charset=UTF-8']);
    }
}
