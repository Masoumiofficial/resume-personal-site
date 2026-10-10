<?php

namespace App\Support;

use Illuminate\Support\Str;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;

/**
 * Markdown is the source of truth for long text. Rendering is safe by
 * construction: raw HTML is escaped (shown as text, never executed) and
 * javascript:/data:/vbscript:/file: links are dropped, so the output can be
 * printed unescaped without a separate HTML sanitizer dependency.
 */
final class Markdown
{
    public static function toHtml(?string $markdown): string
    {
        if ($markdown === null || trim($markdown) === '') {
            return '';
        }

        $host = parse_url((string) config('app.url'), PHP_URL_HOST) ?: 'localhost';

        return Str::markdown($markdown, [
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => [$host, 'www.'.$host],
                'open_in_new_window' => false,
                'nofollow' => '',
                'noopener' => 'external',
                'noreferrer' => '',
            ],
        ], [new ExternalLinkExtension]);
    }

    /** Estimated reading time (Persian prose, ~200 words per minute), at least 1. */
    public static function readingMinutes(string $html): int
    {
        $words = preg_split('/\s+/u', trim(strip_tags($html)), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        return max(1, (int) round(count($words) / 200));
    }
}
