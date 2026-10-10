<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/**
 * Articles live at the site root (/{slug}/) to keep the old WordPress URLs,
 * so their slugs must never shadow a site section, a file or the admin path.
 * List: docs/phase-1/information-architecture.md §1.
 */
class NotReservedSlug implements ValidationRule
{
    public const RESERVED = [
        'admin', 'about', 'resume', 'works', 'services', 'blog', 'contact', 'feed',
        'sitemap.xml', 'robots.txt', 'media', 'build', 'storage', 'assets', 'login', 'logout',
        'password', 'two-factor', 'up', 'api', 'wp-admin', 'wp-content', 'wp-includes',
        'wp-login.php', 'xmlrpc.php', 'tag', 'category', 'author', 'page', 'search', 'user',
    ];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $reserved = array_merge(self::RESERVED, [config('admin.path')]);
        if (is_string($value) && in_array(strtolower($value), $reserved, true)) {
            $fail('این نامک برای بخش‌های سایت رزرو شده است.');
        }
    }
}
