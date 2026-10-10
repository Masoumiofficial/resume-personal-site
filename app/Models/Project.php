<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use App\Models\Concerns\Sortable;
use App\Support\Link;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use Publishable, SoftDeletes, Sortable;

    protected $fillable = ['title', 'slug', 'summary', 'body', 'problem', 'solution', 'role', 'technologies', 'outcome', 'outcome_evidence_url', 'outcome_verified', 'project_url', 'client_name', 'year', 'cover_id', 'is_featured', 'sort_order', 'status', 'published_at', 'meta_title', 'meta_description', 'og_image_id', 'canonical_url', 'noindex'];

    protected $attributes = ['outcome_verified' => false, 'is_featured' => false, 'sort_order' => 0, 'status' => 'draft', 'noindex' => false, 'technologies' => null];

    protected function casts(): array
    {
        return ['technologies' => 'array', 'outcome_verified' => 'boolean', 'is_featured' => 'boolean', 'noindex' => 'boolean', 'published_at' => 'datetime', 'year' => 'integer'];
    }

    /** IA rule: an outcome is public only with evidence or an explicit verification tick. */
    public function showsOutcome(): bool
    {
        return filled($this->outcome) && (filled($this->outcome_evidence_url) || $this->outcome_verified);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(ProjectCategory::class, 'project_category');
    }

    public function gallery(): HasMany
    {
        return $this->hasMany(ProjectMedia::class)->orderBy('sort_order');
    }

    public function cover(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'cover_id');
    }

    public function url(): string
    {
        return Link::route('works.show', $this->slug);
    }
}
