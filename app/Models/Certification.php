<?php

namespace App\Models;

use App\Models\Concerns\Sortable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certification extends Model
{
    use Sortable;

    public const KINDS = ['course' => 'دوره', 'certificate' => 'گواهی‌نامه', 'award' => 'جایزه'];

    protected $fillable = ['kind', 'title', 'issuer', 'issued_at', 'end_at', 'date_precision', 'credential_url', 'evidence_media_id', 'description', 'is_visible', 'sort_order'];

    protected $attributes = ['kind' => 'course', 'date_precision' => 'month', 'is_visible' => true, 'sort_order' => 0];

    protected function casts(): array
    {
        return ['issued_at' => 'date', 'end_at' => 'date', 'is_visible' => 'boolean'];
    }

    public function evidence(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'evidence_media_id');
    }
}
