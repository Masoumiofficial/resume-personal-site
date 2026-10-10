<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Decision D15: public page URLs keep WordPress' trailing slash
 * (/what-is-wordpress/), so every existing URL keeps answering 200 without a
 * redirect. Requests without the slash get ONE 301 to the slashed form.
 *
 * Not applied to: the fallback route (see ArticleController), the home page,
 * the admin panel and auth routes, files with
 * an extension (sitemap.xml, robots.txt, images), the /up health check, and
 * non-GET/HEAD requests (forms must never be redirected).
 */
class CanonicalTrailingSlash
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->applies($request)) {
            return $next($request);
        }

        $uri = $request->getRequestUri();          // raw path + query, still percent-encoded
        $path = parse_url($uri, PHP_URL_PATH) ?? '/';

        if (! str_ends_with($path, '/')) {
            $query = parse_url($uri, PHP_URL_QUERY);
            $target = $request->getSchemeAndHttpHost().$request->getBaseUrl().$this->stripBase($request, $path).'/'.($query !== null ? '?'.$query : '');

            return redirect()->to($target, 301);
        }

        return $next($request);
    }

    private function applies(Request $request): bool
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return false;
        }

        // Fallback route (root article permalinks): unknown paths must 404 directly,
        // not 301 first; ArticleController adds the slash only for real articles.
        if ($request->route()?->isFallback) {
            return false;
        }

        $path = trim($request->path(), '/');       // decoded, without slashes

        if ($path === '' || $path === 'up') {
            return false;
        }

        $admin = config('admin.path');
        if ($path === $admin || str_starts_with($path, $admin.'/')) {
            return false;
        }

        // Last segment contains a dot → treat as a file (sitemap.xml, robots.txt, feed.xml, *.webp…)
        $last = basename($path);

        return ! str_contains($last, '.');
    }

    private function stripBase(Request $request, string $path): string
    {
        $base = $request->getBaseUrl();

        return $base !== '' && str_starts_with($path, $base) ? substr($path, strlen($base)) : $path;
    }
}
