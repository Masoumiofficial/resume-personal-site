<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Symfony\Component\HttpFoundation\Response;

/**
 * Baseline security headers for every web response.
 *
 * A fresh CSP nonce is generated per request and shared with all views as
 * $cspNonce; the only inline script we use (theme bootstrap in <head>) carries it.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = base64_encode(random_bytes(18));
        $request->attributes->set('csp_nonce', $nonce);
        View::share('cspNonce', $nonce);

        /** @var Response $response */
        $response = $next($request);

        $headers = $response->headers;
        $headers->set('X-Content-Type-Options', 'nosniff');
        $headers->set('X-Frame-Options', 'DENY');
        $headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=(), payment=(), usb=(), interest-cohort=()');
        $headers->set('Cross-Origin-Opener-Policy', 'same-origin');

        if (config('security.csp.enabled')) {
            $policy = implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'nonce-{$nonce}'",
                "style-src 'self' 'nonce-{$nonce}'",
                "img-src 'self' data:",
                "font-src 'self'",
                "connect-src 'self'",
                "form-action 'self'",
                "base-uri 'self'",
                "frame-ancestors 'none'",
                "object-src 'none'",
            ]);
            $headers->set(config('security.csp.report_only') ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy', $policy);
        }

        if (config('security.hsts.enabled') && $request->isSecure() && app()->isProduction()) {
            $headers->set('Strict-Transport-Security', 'max-age='.(int) config('security.hsts.max_age'));
        }

        // Staging/local copies must never end up in search results (covers feeds and files too, not just HTML).
        if (! app()->isProduction()) {
            $headers->set('X-Robots-Tag', 'noindex, nofollow');
        }

        // Authenticated/admin pages must never be stored by shared caches.
        // hasUser(): only an already-resolved user — never triggers a session lookup here.
        if (auth()->guard()->hasUser() || $request->is(config('admin.path'), config('admin.path').'/*')) {
            $headers->set('Cache-Control', 'no-store, private');
        }

        return $response;
    }
}
