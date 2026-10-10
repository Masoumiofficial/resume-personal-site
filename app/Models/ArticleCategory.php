<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOldSlug;
use App\Models\Concerns\Sortable;
use App\Support\Link;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ArticleCategory extends Model
{
    use RedirectsOldSlug, Sortable;

    protected $fillable = ['name', 'slug', 'parent_id', 'description', 'sort_order'];

    protected $attributes = ['sort_order' => 0];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_category');
    }

    public function url(): string
    {
        return Link::route('blog.category', $this->slug);
    }

    public static function slugPath(string $slug): string
    {
        return '/blog/category/'.$slug.'/';
    }
}
