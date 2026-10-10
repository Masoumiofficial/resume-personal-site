<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Single row: the site owner's public identity. */
class Profile extends Model
{
    protected $fillable = ['display_name', 'headline', 'short_bio', 'long_bio', 'avatar_id', 'location', 'show_location', 'email', 'phone', 'show_phone', 'social_links', 'availability_note', 'resume_summary'];

    protected $attributes = ['show_location' => true, 'show_phone' => true, 'social_links' => null];

    protected function casts(): array
    {
        return ['show_location' => 'boolean', 'show_phone' => 'boolean', 'social_links' => 'array'];
    }

    public function avatar(): BelongsTo
    {
        return $this->belongsTo(Media::class, 'avatar_id');
    }

    public static function current(): self
    {
        return static::query()->with('avatar')->first() ?? new static(['display_name' => (string) config('app.name')]);
    }
}
