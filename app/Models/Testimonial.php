<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Shown publicly only when the author's consent is confirmed AND it is visible (D6). */
class Testimonial extends Model
{
    use Sortable;

    protected $fillable = ['author_name', 'author_role', 'company', 'company_url', 'avatar_id', 'quote', 'related_project_id', 'consent_confirmed', 'received_at', 'is_visible', 'sort_order'];

    protected $attributes = ['consent_confirmed' => false, 'is_visible' => false, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['consent_confirmed' => 'boolean', 'is_visible' => 'boolean', 'received_at' => 'date'];
    }

    public function scopePublic(Builder $query): Builder
    {
        return $query->where('consent_confirmed', true)->where('is_visible', true);
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_id');
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class, 'related_project_id');
    }
}
