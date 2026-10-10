<?php

namespace App\Support;

use App\Models\Redirect;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Runs only when a GET/HEAD request is about to become a 404 (registered in
 * bootstrap/app.php), so redirect rules never shadow a live page and normal
 * page views cost no extra query. Returns null to keep the regular 404.
 */
final class RedirectResolver
{
    public static function respond(Request $request): ?Response
    {
        if (! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
            return null;
        }

        $admin = trim((string) config('admin.path'), '/');
        $path = trim($request->path(), '/');
        if ($path === $admin || str_starts_with($path, $admin.'/')) {
            return null;
        }

        try {
            $rule = Redirect::match($request->getPathInfo());
            $rule?->recordHit();
        } catch (Throwable $e) {
            // A missing/locked table must never turn a 404 into a 500.
            report($e);

            return null;
        }

        if ($rule === null) {
            return null;
        }

        if ($rule->status_code === 410) {
            return response()->view('errors.410', [], 410);
        }

        $target = self::target($rule, $request);

        return $target === null ? null : redirect()->to($target, $rule->status_code);
    }

    private static function target(Redirect $rule, Request $request): ?string
    {
        $to = (string) $rule->to_url;
        if ($to === '') {
            return null;
        }

        if (str_starts_with($to, '/')) {
            // Same path as the request → never loop.
            if (Redirect::normalize($to) === Redirect::normalize($request->getPathInfo())) {
                return null;
            }
            $to = rtrim(url('/'), '/').$to;
        }

        $query = $request->getQueryString();
        if ($query !== null && $query !== '' && ! str_contains($to, '?')) {
            $to .= '?'.$query;
        }

        return $to;
    }
}
