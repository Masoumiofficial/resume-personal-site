<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

/**
 * URL rules for the WordPress migration and later URL changes (decision D31).
 *
 * Rules are only consulted when a request would otherwise be a 404 (see
 * App\Support\RedirectResolver), so a redirect can never hide a live page.
 *
 *  - from_path is stored normalised (see normalize()); a trailing "*" makes
 *    it a prefix rule, e.g. /wp-json/* .
 *  - to_url is a site path (/blog/) or an absolute http(s) URL; NULL for 410.
 */
class Redirect extends Model
{
    public const STATUSES = [301 => '۳۰۱ — انتقال دائمی', 302 => '۳۰۲ — انتقال موقت', 410 => '۴۱۰ — حذف‌شده (Gone)'];

    protected $fillable = ['from_path', 'to_url', 'status_code', 'note', 'is_active'];

    protected $attributes = ['to_url' => null, 'status_code' => 301, 'hits' => 0, 'last_hit_at' => null, 'note' => null, 'is_active' => true];

    protected function casts(): array
    {
        return ['status_code' => 'integer', 'hits' => 'integer', 'last_hit_at' => 'datetime', 'is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        static::saving(function (Redirect $redirect): void {
            $redirect->from_path = self::normalize((string) $redirect->from_path);
            $redirect->from_hash = self::hash($redirect->from_path);
            if ((int) $redirect->status_code === 410) {
                $redirect->to_url = null;
            } elseif ($redirect->to_url !== null && str_starts_with($redirect->to_url, '/')) {
                $redirect->to_url = self::encodePath($redirect->to_url);
            }
        });
    }

    /**
     * Canonical form used for storage and lookup: decoded UTF-8, leading slash,
     * no query/fragment, single slashes, lower case, and WordPress' trailing
     * slash unless the last segment looks like a file (xmlrpc.php) or the rule
     * is a prefix ("/wp-json/*").
     */
    public static function normalize(string $path): string
    {
        $path = trim($path);
        if (preg_match('~^https?://~i', $path)) {
            $path = (string) parse_url($path, PHP_URL_PATH);
        }
        $path = (string) preg_replace('~[?#].*$~s', '', $path);
        $path = rawurldecode($path);
        if (! mb_check_encoding($path, 'UTF-8')) {
            $path = mb_convert_encoding($path, 'UTF-8', 'UTF-8');
        }
        $path = '/'.ltrim((string) preg_replace('~/{2,}~', '/', $path), '/');
        $path = mb_strtolower($path, 'UTF-8');

        if (str_ends_with($path, '*')) {
            return rtrim($path, '*').'*';
        }
        if (! str_ends_with($path, '/') && ! str_contains(basename($path), '.')) {
            $path .= '/';
        }

        return $path;
    }

    public static function hash(string $normalizedPath): string
    {
        return hash('sha256', $normalizedPath);
    }

    public function isPrefix(): bool
    {
        return str_ends_with($this->from_path, '*');
    }

    /** Active rule for a request path: exact match first, then the longest prefix rule. */
    public static function match(string $requestPath): ?self
    {
        $path = self::normalize($requestPath);

        $exact = self::query()->where('from_hash', self::hash($path))->where('is_active', true)->first();
        if ($exact) {
            return $exact;
        }

        return self::query()->where('is_active', true)->where('from_path', 'like', '%*')->get()
            ->filter(fn (Redirect $r) => str_starts_with($path, rtrim($r->from_path, '*')))
            ->sortByDesc(fn (Redirect $r) => mb_strlen($r->from_path))
            ->first();
    }

    public function recordHit(): void
    {
        // Query builder: no model events and no updated_at bump for a counter.
        DB::table($this->getTable())->where('id', $this->getKey())->update([
            'hits' => DB::raw('hits + 1'),
            'last_hit_at' => now(),
        ]);
    }

    /** Percent-encode each path segment (Persian slugs) so the Location header stays ASCII. */
    public static function encodePath(string $url): string
    {
        [$path, $rest] = array_pad(preg_split('~(?=[?#])~', $url, 2), 2, '');
        $segments = array_map(fn (string $s) => rawurlencode(rawurldecode($s)), explode('/', $path));

        return implode('/', $segments).$rest;
    }

    /**
     * Create (or repoint) the 301 left behind when a public item's URL changes.
     * Rules that pointed at the old URL are repointed to avoid chains, and a
     * rule *from* the new URL is removed (renaming back must not loop).
     */
    public static function rememberMove(string $oldPath, string $newPath, string $note): ?self
    {
        $from = self::normalize($oldPath);
        $to = '/'.ltrim(rawurldecode($newPath), '/');   // target keeps its exact form
        if ($from === self::normalize($to)) {
            return null;
        }

        self::query()->where('from_hash', self::hash(self::normalize($to)))->delete();
        self::query()->where('status_code', '!=', 410)->get()
            ->filter(fn (Redirect $r) => $r->to_url !== null && str_starts_with($r->to_url, '/') && self::normalize($r->to_url) === $from)
            ->each(fn (Redirect $r) => $r->forceFill(['to_url' => $to])->save());

        $redirect = self::query()->where('from_hash', self::hash($from))->first() ?? new self;
        $redirect->forceFill(['from_path' => $from, 'to_url' => $to, 'status_code' => 301, 'is_active' => true, 'note' => $note])->save();

        return $redirect;
    }
}
