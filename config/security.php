<?php

return [

    /*
    | HTTP Strict Transport Security. Sent only for HTTPS requests in the
    | production environment. includeSubDomains is deliberately off until
    | every subdomain of the site is confirmed to serve HTTPS.
    */
    'hsts' => [
        'enabled' => (bool) env('SECURITY_HSTS', true),
        'max_age' => (int) env('SECURITY_HSTS_MAX_AGE', 31536000),
    ],

    /*
    | Content Security Policy. Inline <script>/<style> elements must carry the
    | per-request nonce (available in Blade as $cspNonce). Inline style="..."
    | attributes are NOT allowed — use classes.
    */
    'csp' => [
        'enabled' => (bool) env('SECURITY_CSP', true),
        'report_only' => (bool) env('SECURITY_CSP_REPORT_ONLY', false),
    ],

    /*
    | Reverse proxies / CDN in front of the site (e.g. a CDN that terminates
    | TLS). Only then are X-Forwarded-For/-Proto trusted, so rate limits and
    | logs see the visitor's IP instead of the proxy's. Empty = trust no one
    | (correct when the web server itself terminates TLS). Comma-separated
    | IPs/CIDRs, or "*" only if the origin is reachable through the proxy alone.
    */
    'trusted_proxies' => env('TRUSTED_PROXIES'),

];
