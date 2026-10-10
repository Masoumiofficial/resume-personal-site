<?php

namespace App\Models;

use App\Models\Concerns\Publishable;
use App\Models\Concerns\Sortable;
use App\Support\Jalali;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Service extends Model
{
    use Publishable, Sortable;

    public const UNITS = ['once' => 'یک‌باره', 'monthly' => 'ماهانه', 'hourly' => 'ساعتی'];

    public const CTA_TYPES = ['contact' => 'فرم تماس', 'external' => 'لینک بیرونی'];

    protected $fillable = ['service_group_id', 'title', 'slug', 'summary', 'body', 'price_from', 'price_original', 'price_unit', 'price_note', 'cta_type', 'cta_url', 'has_page', 'is_visible', 'sort_order', 'status', 'published_at', 'meta_title', 'meta_description', 'og_image_id', 'canonical_url', 'noindex'];

    protected $attributes = ['price_unit' => 'once', 'cta_type' => 'contact', 'has_page' => false, 'is_visible' => true, 'sort_order' => 0, 'status' => 'draft', 'noindex' => false];

    protected function casts(): array
    {
        return ['price_from' => 'integer', 'price_original' => 'integer', 'has_page' => 'boolean', 'is_visible' => 'boolean', 'noindex' => 'boolean', 'published_at' => 'datetime'];
    }

    public function sortScope(): array
    {
        return ['service_group_id'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(ServiceGroup::class, 'service_group_id');
    }

    /** «شروع از ۷٬۵۰۰٬۰۰۰ تومان» (D7) — «ماهانه»/«ساعتی» appended; null price = ask. */
    public function priceLabel(): string
    {
        if ($this->price_from === null) {
            return 'تماس بگیرید';
        }
        $unit = match ($this->price_unit) {
            'monthly' => ' (ماهانه)',
            'hourly' => ' (ساعتی)',
            default => '',
        };

        return 'شروع از '.Jalali::number($this->price_from).' تومان'.$unit;
    }
}
