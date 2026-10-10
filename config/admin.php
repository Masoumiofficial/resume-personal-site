<?php

$path = trim((string) env('ADMIN_PATH', 'admin'), '/');

// Only a single safe URL segment is accepted; anything else falls back to "admin"
// so a typo in .env can never expose routes at the site root.
if (! preg_match('/^[a-z0-9][a-z0-9-]{1,40}$/', $path)) {
    $path = 'admin';
}

return [

    /*
    | URL prefix of the admin panel and of all authentication routes
    | (login, two-factor challenge, password reset). Obscurity only —
    | authentication, 2FA and rate limiting are the actual protection.
    */
    'path' => $path,

];
