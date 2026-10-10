<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/** Key-value site settings (JSON values), cached as one array. */
class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    public const DEFAULTS = [
        'site_title' => 'سجاد معصومی',
        'title_separator' => '|',
        'default_meta_description' => null,
        'footer_note' => null,
        'contact_form_enabled' => true,
        'message_retention_days' => 365,
        'home_show_services' => true,
        'home_show_projects' => true,
        'home_show_articles' => true,
        'home_show_testimonials' => true,
    ];

    public static function get(string $key, mixed $default = null): mixed
    {
        $all = Cache::rememberForever('settings.all', fn () => static::query()->pluck('value', 'key')->all());

        return array_key_exists($key, $all) ? $all[$key] : ($default ?? self::DEFAULTS[$key] ?? null);
    }

    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            static::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
        Cache::forget('settings.all');
    }
}
