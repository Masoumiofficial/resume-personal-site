<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\ArticleCategory;
use App\Models\Tag;
use App\Support\Jalali;
use App\Support\Link;
use App\Support\Seo;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/** Blog index, category and tag archives with /page/{n}/ pagination (page 1 has no /page/ segment). */
class BlogController extends Controller
{
    public const PER_PAGE = 9;

    public function index(?int $page = null): View|RedirectResponse
    {
        return $this->archive(Article::query(), 'blog', [], $page, Seo::make('مقالات', 'مقاله‌ها و آموزش‌های وردپرس، برنامه‌نویسی، داده و رشد کسب‌وکار.'), 'مقالات', null);
    }

    public function category(string $slug, ?int $page = null): View|RedirectResponse
    {
        $category = ArticleCategory::query()->where('slug', $slug)->firstOrFail();
        // A parent category also lists its children's articles, as WordPress did.
        $ids = ArticleCategory::query()->where('parent_id', $category->id)->pluck('id')->push($category->id);
        $query = Article::query()->whereHas('categories', fn (Builder $q) => $q->whereIn('article_categories.id', $ids));

        return $this->archive($query, 'blog.category', ['slug' => $slug], $page,
            Seo::make('دسته‌بندی: '.$category->name, $category->description), $category->name, $category->description);
    }

    public function tag(string $slug, ?int $page = null): View|RedirectResponse
    {
        $tag = Tag::query()->where('slug', $slug)->firstOrFail();
        $query = Article::query()->whereHas('tags', fn (Builder $q) => $q->where('tags.id', $tag->id));

        $seo = Seo::make('برچسب: '.$tag->name);
        // Thin tag archives are not worth indexing; links are still followed.
        $seo->noindex = true;

        return $this->archive($query, 'blog.tag', ['slug' => $slug], $page, $seo, 'برچسب: '.$tag->name, null);
    }

    private function archive(Builder $query, string $route, array $params, ?int $page, Seo $seo, string $heading, ?string $intro): View|RedirectResponse
    {
        $firstPage = Link::route($route === 'blog' ? 'blog.index' : $route, $params);
        if ($page === 1) {
            return redirect()->to($firstPage, 301);
        }
        $page ??= 1;
        if ($page < 1) {
            abort(404);
        }

        $total = (clone $query)->published()->count();
        $lastPage = max(1, (int) ceil($total / self::PER_PAGE));
        // An empty first page is a valid (empty) archive; any page past the end is not.
        if ($page > $lastPage) {
            abort(404);
        }

        $items = $query->published()->with('cover', 'categories')->latest('published_at')->latest('id')
            ->forPage($page, self::PER_PAGE)->get();
        $pageRoute = ($route === 'blog' ? 'blog' : $route).'.page';
        $urlFor = fn (int $n): string => $n === 1 ? $firstPage : Link::route($pageRoute, $params + ['page' => $n]);

        if ($page > 1) {
            $seo->title .= ' — صفحهٔ '.Jalali::digits($page);
        }
        $seo->canonical($urlFor($page));
        if ($total === 0) {
            $seo->noindex = true;
        }

        return view('site.blog.index', [
            'seo' => $seo,
            'heading' => $heading,
            'intro' => $intro,
            'articles' => $items,
            'page' => $page,
            'prevUrl' => $page > 1 ? $urlFor($page - 1) : null,
            'nextUrl' => $page < $lastPage ? $urlFor($page + 1) : null,
            'pageUrls' => collect(range(1, $lastPage))->mapWithKeys(fn ($n) => [$n => $urlFor($n)])->all(),
            'categories' => ArticleCategory::query()->whereHas('articles', fn (Builder $q) => $q->published())->orderBy('sort_order')->orderBy('name')->get(),
            'currentUrl' => $firstPage,
        ]);
    }
}
