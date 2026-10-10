<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use App\Support\Link;
use App\Support\Markdown;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use Publishable, SoftDeletes;

    protected $fillable = ['title', 'slug', 'excerpt', 'body', 'cover_id', 'status', 'published_at', 'updated_content_at', 'legacy_wp_id', 'meta_title', 'meta_description', 'og_image_id', 'canonical_url', 'noindex'];

    protected $attributes = ['status' => 'draft', 'noindex' => false, 'body_html' => null, 'legacy_html' => null];

    protected function casts(): array
    {
        return ['published_at' => 'datetime', 'updated_content_at' => 'datetime', 'noindex' => 'boolean', 'legacy_wp_id' => 'integer', 'reading_minutes' => 'integer'];
    }

    protected static function booted(): void
    {
        // body (Markdown) is the source of truth; body_html is a safe render cache.
        static::saving(function (Article $article): void {
            if ($article->isDirty('body') || $article->body_html === null) {
                $article->body_html = Markdown::toHtml($article->body);
                $article->reading_minutes = $article->body_html !== '' ? Markdown::readingMinutes($article->body_html) : null;
            }
        });
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ArticleCategory::class, 'article_category');
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'article_tag');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }

    /** Articles live at the site root, like the WordPress permalinks they replace: /{slug}/ */
    public function url(): string
    {
        return Link::to(rawurlencode($this->slug));
    }
}
