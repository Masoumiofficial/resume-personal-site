<?php

namespace App\Support;

/**
 * Cache-busting for plain files in public/assets (decision D14: no Node/Vite).
 * Appends ?v=<mtime> so the server can send long cache lifetimes while every
 * deploy that changes a file changes its URL.
 */
final class Asset
{
    /** @var array<string, string> */
    private static array $cache = [];

    public static function url(string $path): string
    {
        $path = ltrim($path, '/');

        return self::$cache[$path] ??= (function () use ($path): string {
            $file = public_path('assets/'.$path);
            $version = is_file($file) ? (string) filemtime($file) : null;

            return asset('assets/'.$path).($version !== null ? '?v='.$version : '');
        })();
    }
}
