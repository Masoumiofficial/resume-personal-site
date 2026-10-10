<?php

namespace App\Http\Controllers\Site;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Profile;
use App\Support\Link;
use App\Support\Seo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Fallback route: /{article-slug}/ at the site root, matching the old
 * WordPress permalinks. Only single-segment paths are considered; drafts,
 * scheduled posts and anything else are a plain 404.
 */
class ArticleController extends Controller
{
    public function show(Request $request): View|RedirectResponse
    {
        $slug = trim($request->decodedPath(), '/');
        abort_if(! $request->isMethod('GET') && ! $request->isMethod('HEAD'), 404);
        abort_if($slug === '' || str_contains($slug, '/'), 404);

        $article = Article::query()->published()->where('slug', $slug)->with('cover', 'categories', 'tags')->first();
        abort_if($article === null, 404);

        // Same canonical form as the rest of the site (D15): one 301 to the slashed URL.
        $path = (string) parse_url($request->getRequestUri(), PHP_URL_PATH);
        if (! str_ends_with($path, '/')) {
            $query = $request->getQueryString();

            return redirect()->to($article->url().($query ? '?'.$query : ''), 301);
        }

        return $this->render($article);
    }

    /**
     * Article page. $preview: admin-only draft preview (D32) — same template,
     * never indexed, with a banner; the in-memory date is not saved.
     */
    public function render(Article $article, bool $preview = false): View
    {
        if ($preview && $article->published_at === null) {
            $article->setAttribute('published_at', now());
        }

        $profile = Profile::current();
        $modified = $article->updated_content_at ?? $article->published_at;

        $seo = Seo::make()->fromModel($article, $article->title, $article->excerpt)->canonical($article->url())->image($article->cover);
        $seo->type = 'article';
        $seo->noindex = $seo->noindex || $preview;
        $seo->publishedTime = $article->published_at->toAtomString();
        $seo->modifiedTime = $modified->toAtomString();
        $seo->schema(array_filter([
            '@type' => 'BlogPosting',
            'headline' => $article->title,
            'description' => $seo->metaDescription(),
            'mainEntityOfPage' => $article->url(),
            'datePublished' => $seo->publishedTime,
            'dateModified' => $seo->modifiedTime,
            'image' => $seo->image,
            'inLanguage' => 'fa-IR',
            'author' => ['@type' => 'Person', 'name' => $profile->display_name, 'url' => Link::route('about')],
        ]));
        $category = $article->categories->first();
        $seo->schema(['@type' => 'BreadcrumbList', 'itemListElement' => array_values(array_filter([
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'مقالات', 'item' => Link::route('blog.index')],
            $category ? ['@type' => 'ListItem', 'position' => 2, 'name' => $category->name, 'item' => $category->url()] : null,
            ['@type' => 'ListItem', 'position' => $category ? 3 : 2, 'name' => $article->title],
        ]))]);

        $related = Article::query()->published()->whereKeyNot($article->id)
            ->when($category, fn ($q) => $q->whereHas('categories', fn ($c) => $c->whereIn('article_categories.id', $article->categories->modelKeys())))
            ->with('cover', 'categories')->latest('published_at')->limit(3)->get();

        return view('site.article', [
            'article' => $article,
            'seo' => $seo,
            'profile' => $profile,
            'modified' => $modified->gt($article->published_at->copy()->addDay()) ? $modified : null,
            'related' => $related,
            'preview' => $preview,
        ]);
    }
}
