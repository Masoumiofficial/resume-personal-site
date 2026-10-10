<?php

namespace App\Models;

use App\Models\Concerns\RedirectsOldSlug;
use App\Support\Link;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use RedirectsOldSlug;

    protected $fillable = ['name', 'slug'];

    public function articles(): BelongsToMany
    {
        return $this->belongsToMany(Article::class, 'article_tag');
    }

    public function url(): string
    {
        return Link::route('blog.tag', $this->slug);
    }

    public static function slugPath(string $slug): string
    {
        return '/blog/tag/'.$slug.'/';
    }
}
