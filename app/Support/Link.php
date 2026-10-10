<?php

namespace App\Support;

/**
 * Public page URLs with WordPress' trailing slash (decision D15).
 * route() and url() never add it, so every public link goes through here;
 * otherwise each internal click would cost a 301.
 */
final class Link
{
    public static function route(string $name, mixed $parameters = []): string
    {
        return self::slash(route($name, $parameters));
    }

    public static function to(string $path): string
    {
        return self::slash(url($path));
    }

    public static function slash(string $url): string
    {
        [$base, $query] = array_pad(explode('?', $url, 2), 2, null);
        $path = (string) parse_url($base, PHP_URL_PATH);

        if (! str_ends_with($base, '/') && ! str_contains(basename($path), '.')) {
            $base .= '/';
        }

        return $query === null ? $base : $base.'?'.$query;
    }
}
