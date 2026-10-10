<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $table = 'media';

    protected $fillable = ['disk', 'path', 'original_name', 'mime', 'size', 'width', 'height', 'sha256', 'alt', 'caption', 'variants', 'uploaded_by', 'legacy_url'];

    protected $attributes = ['disk' => 'media', 'alt' => null, 'caption' => null, 'variants' => null, 'width' => null, 'height' => null, 'legacy_url' => null];

    protected function casts(): array
    {
        return ['variants' => 'array', 'size' => 'integer', 'width' => 'integer', 'height' => 'integer'];
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public function isImage(): bool
    {
        return str_starts_with($this->mime, 'image/');
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    /** Smallest WebP rendition at least $width wide, else the original. */
    public function urlFor(int $width): string
    {
        $variants = collect($this->variants ?? [])->sortBy('width');
        $pick = $variants->first(fn ($v) => $v['width'] >= $width) ?? $variants->last();

        return $pick ? Storage::disk($this->disk)->url($pick['path']) : $this->url();
    }

    /** Label for pickers: «alt — original.jpg». */
    public function label(): string
    {
        return trim(($this->alt ? $this->alt.' — ' : '').$this->original_name);
    }

    /** Every column that may reference a media row, for the "in use" check. */
    public const REFERENCES = [
        ['profiles', 'avatar_id'], ['experiences', 'logo_id'], ['certifications', 'evidence_media_id'],
        ['projects', 'cover_id'], ['projects', 'og_image_id'], ['project_media', 'media_id'],
        ['articles', 'cover_id'], ['articles', 'og_image_id'], ['services', 'og_image_id'],
        ['testimonials', 'avatar_id'],
    ];
}
