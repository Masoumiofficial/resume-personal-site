<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Experience extends Model
{
    use Sortable;

    public const EMPLOYMENT_TYPES = ['founder' => 'بنیان‌گذار', 'full_time' => 'تمام‌وقت', 'part_time' => 'پاره‌وقت', 'freelance' => 'فریلنس', 'contract' => 'قراردادی'];

    protected $fillable = ['organization', 'organization_url', 'logo_id', 'role', 'employment_type', 'start_date', 'end_date', 'date_precision', 'location', 'summary', 'highlights', 'is_visible', 'sort_order'];

    protected $attributes = ['date_precision' => 'year', 'is_visible' => true, 'sort_order' => 0, 'highlights' => null];

    protected function casts(): array
    {
        return ['start_date' => 'date', 'end_date' => 'date', 'highlights' => 'array', 'is_visible' => 'boolean'];
    }

    public function logo(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'logo_id');
    }
}
