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

];
