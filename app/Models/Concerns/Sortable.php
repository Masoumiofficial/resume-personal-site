<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Manual ordering through sort_order (admin up/down buttons). */
trait Sortable
{
    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('sort_order')->orderBy('id');
    }

    /** Group columns that scope the ordering (e.g. skills per group). */
    public function sortScope(): array
    {
        return [];
    }
}
