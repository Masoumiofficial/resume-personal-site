<?php

namespace App\Support;

/**
 * Article body images (Markdown output) at render time: lazy loading, async
 * decoding, and the real width/height of local files so the browser reserves
 * the space before the image arrives (no layout shift, CLS). Remote images and
 * files that do not exist (yet) are left as they are.
 */
final class BodyImages
{
    public static function prepare(string $html): string
    {
        $public = realpath(public_path()) ?: public_path();

        return (string) preg_replace_callback('/<img\b([^>]*)>/i', function (array $m) use ($public): string {
            $attributes = $m[1];
            $extra = '';
            if (! preg_match('/\sloading=/i', $attributes)) {
                $extra .= ' loading="lazy"';
            }
            if (! preg_match('/\sdecoding=/i', $attributes)) {
                $extra .= ' decoding="async"';
            }
            if (! preg_match('/\swidth=/i', $attributes) && preg_match('/\ssrc="(\/(?!\/)[^"?#]+)/i', $attributes, $src)) {
                $file = realpath($public.rawurldecode(html_entity_decode($src[1], ENT_QUOTES)));
                if ($file !== false && str_starts_with($file, $public.DIRECTORY_SEPARATOR) && is_file($file)) {
                    $size = @getimagesize($file);
                    if ($size !== false && $size[0] > 0 && $size[1] > 0) {
                        $extra .= ' width="'.$size[0].'" height="'.$size[1].'"';
                    }
                }
            }

            return '<img'.$extra.$attributes.'>';
        }, $html);
    }
}
