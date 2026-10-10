<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * status = draft | published. A published item with a future published_at is
 * "scheduled" and stays hidden until that moment.
 */
trait Publishable
{
    public const STATUSES = ['draft' => 'پیش‌نویس', 'published' => 'منتشرشده'];

    public function scopePublished(Builder $query): Builder
    {
        return $query->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    public function isLive(): bool
    {
        return $this->status === 'published' && $this->published_at !== null && $this->published_at->lte(now());
    }

    /** draft | scheduled | published — for admin badges. */
    public function publicationState(): string
    {
        if ($this->status !== 'published') {
            return 'draft';
        }

        return $this->published_at && $this->published_at->isFuture() ? 'scheduled' : 'published';
    }
}
