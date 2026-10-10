<?php

/*
| Post-deploy smoke test (Phase 6, D38) — checks a RUNNING site over HTTP.
|
|   php tools/smoke/smoke.php https://staging.example.com [options]
|
|   --expect-indexing     fail if the home page sends "noindex" (use after go-live)
|   --admin-path=admin    admin URL prefix (ADMIN_PATH)
|   --skip-redirects      do not test the rules in database/content/redirects.php
|   --insecure            accept an invalid TLS certificate (staging only)
|   --dev-server          target is `php -S` (CI package check): .htaccess rules
|                         are not applied there, so those checks become INFO
|
| Standalone: needs only PHP with ext-curl, no Laravel boot and no database.
| Runs from GitHub Actions (.github/workflows/smoke.yml) because GitHub's
| runners can reach the host. Exit code 1 when any check FAILs.
| Read-only: only GET/HEAD requests, nothing is submitted.
*/

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

$args = array_slice($argv, 1);
$base = null;
$opts = ['expect-indexing' => false, 'admin-path' => 'admin', 'skip-redirects' => false, 'insecure' => false, 'dev-server' => false];
foreach ($args as $arg) {
    if (preg_match('/^--([a-z-]+)(?:=(.*))?$/', $arg, $m)) {
        $opts[$m[1]] = $m[2] ?? true;
    } else {
        $base = rtrim($arg, '/');
    }
}
if (! $base || ! preg_match('#^https?://[^/]+$#', $base)) {
    fwrite(STDERR, "Usage: php tools/smoke/smoke.php https://example.com [--expect-indexing] [--admin-path=admin] [--skip-redirects] [--insecure]\n");
    exit(2);
}
if (! function_exists('curl_init')) {
    fwrite(STDERR, "ext-curl is required.\n");
    exit(2);
}

$https = str_starts_with($base, 'https://');
$host = parse_url($base, PHP_URL_HOST);
$results = [];

function check(string $state, string $name, string $details = ''): void
{
    global $results;
    $results[] = [$state, $name, $details];
    printf("%-4s  %s%s\n", $state, $name, $details === '' ? '' : "  — {$details}");
}

/** @return array{status: int, headers: array<string, string>, body: string, ttfb: float, error: string} */
function fetch(string $url, array $headers = []): array
{
    global $opts;
    $responseHeaders = [];
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 20,
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_USERAGENT => 'sajadmasoumi-smoke/1.0 (+deploy check)',
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_ENCODING => '',   // advertise and decode gzip/deflate/br
        CURLOPT_SSL_VERIFYPEER => ! $opts['insecure'],
        CURLOPT_SSL_VERIFYHOST => $opts['insecure'] ? 0 : 2,
        CURLOPT_HEADERFUNCTION => function ($ch, string $line) use (&$responseHeaders): int {
            if (str_contains($line, ':')) {
                [$name, $value] = explode(':', $line, 2);
                $responseHeaders[strtolower(trim($name))] = trim($value);
            } elseif (preg_match('#^HTTP/#', $line)) {
                $responseHeaders = [];   // keep only the final response's headers
            }

            return strlen($line);
        },
    ]);
    $body = curl_exec($ch);
    $result = [
        'status' => (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE),
        'headers' => $responseHeaders,
        'body' => is_string($body) ? $body : '',
        'ttfb' => (float) curl_getinfo($ch, CURLINFO_STARTTRANSFER_TIME),
        'error' => curl_error($ch),
    ];

    return $result;
}

/** Percent-encode each path segment (Persian slugs) but keep "/". */
function encodePath(string $path): string
{
    return implode('/', array_map(fn ($s) => rawurlencode(rawurldecode($s)), explode('/', $path)));
}

function locationPath(array $r): ?string
{
    $location = $r['headers']['location'] ?? null;

    return $location === null ? null : rawurldecode((string) parse_url($location, PHP_URL_PATH));
}

echo "Smoke test: {$base}\n\n";

// 1. HTTPS redirect -----------------------------------------------------------
if ($https) {
    $r = fetch("http://{$host}/");
    $location = $r['headers']['location'] ?? '';
    check(in_array($r['status'], [301, 308], true) && str_starts_with($location, "https://{$host}") ? 'OK' : 'FAIL',
        'http:// redirects permanently to https://', "{$r['status']} → ".($location ?: '-'));
} else {
    check('WARN', 'Not tested over HTTPS', 'HSTS and the http→https redirect are skipped');
}

// 2. Home page and security headers --------------------------------------------
$home = fetch("{$base}/");
if ($home['status'] !== 200) {
    check('FAIL', 'Home page responds 200', $home['status'].' '.$home['error']);
} else {
    check('OK', 'Home page responds 200', sprintf('%.0f KB', strlen($home['body']) / 1024));
    $h = $home['headers'];
    check(isset($h['content-security-policy']) ? 'OK' : 'FAIL', 'Content-Security-Policy header');
    check(strtolower($h['x-content-type-options'] ?? '') === 'nosniff' ? 'OK' : 'FAIL', 'X-Content-Type-Options: nosniff');
    check(strtoupper($h['x-frame-options'] ?? '') === 'DENY' ? 'OK' : 'FAIL', 'X-Frame-Options: DENY');
    check(isset($h['referrer-policy']) ? 'OK' : 'FAIL', 'Referrer-Policy header', $h['referrer-policy'] ?? '');
    if ($https) {
        check(str_contains($h['strict-transport-security'] ?? '', 'max-age=') ? 'OK' : 'FAIL', 'Strict-Transport-Security (HSTS)', $h['strict-transport-security'] ?? 'missing — APP_ENV must be production');
    }
    check(isset($h['x-powered-by']) ? 'WARN' : 'OK', 'No X-Powered-By header', $h['x-powered-by'] ?? '');
    check(preg_match('/<html[^>]+dir="rtl"/', $home['body']) && ! str_contains($home['body'], '/wp-content/themes/') ? 'OK' : 'FAIL',
        'The new site is being served (not WordPress)');
    $noindex = str_contains(strtolower($h['x-robots-tag'] ?? ''), 'noindex') || preg_match('/<meta[^>]+name="robots"[^>]+noindex/i', $home['body']);
    if ($opts['expect-indexing']) {
        check($noindex ? 'FAIL' : 'OK', 'Indexing allowed (go-live)', $noindex ? 'noindex is sent — check APP_ENV and the indexing setting (D25)' : '');
    } else {
        check('INFO', 'Indexing', $noindex ? 'noindex (expected on staging, D25)' : 'allowed');
    }
    $encoding = $h['content-encoding'] ?? '';
    check($encoding !== '' ? 'OK' : 'WARN', 'HTML compression', $encoding ?: 'none — enable gzip/brotli on the host');

    if (preg_match('#(?:href|src)="([^"]*/assets/[^"]+\?v=\d+)"#', $home['body'], $m)) {
        $asset = fetch(str_starts_with($m[1], 'http') ? $m[1] : $base.$m[1]);
        $cache = $asset['headers']['cache-control'] ?? '';
        preg_match('/max-age=(\d+)/', $cache, $age);
        check($asset['status'] === 200 ? 'OK' : 'FAIL', 'Static asset loads', $asset['status'].' '.basename(parse_url($m[1], PHP_URL_PATH)));
        check(($age[1] ?? 0) >= 2592000 ? 'OK' : 'WARN', 'Static asset long cache (≥ 30 days)', $cache ?: 'no Cache-Control (public/.htaccess mod_expires/mod_headers?)');
    }
}

// 3. Server response time (lab measurement from the GitHub runner, not real users)
$times = [];
for ($i = 0; $i < 5; $i++) {
    $times[] = fetch("{$base}/")['ttfb'];
}
sort($times);
$median = $times[2];
check($median <= 0.6 ? 'OK' : 'WARN', 'TTFB median of 5 (from the runner, not real-user data)', sprintf('%.0f ms (min %.0f, max %.0f; D33 threshold 600 ms)', $median * 1000, $times[0] * 1000, $times[4] * 1000));

// 4. Core URLs ------------------------------------------------------------------
foreach (['/about/', '/resume/', '/services/', '/blog/', '/contact/', '/up'] as $path) {
    $r = fetch($base.$path);
    check($r['status'] === 200 ? 'OK' : 'FAIL', "GET {$path} → 200", (string) $r['status']);
}
$r = fetch("{$base}/robots.txt");
if ($opts['expect-indexing']) {
    check($r['status'] === 200 && str_contains($r['body'], 'Sitemap:') && ! preg_match('#^Disallow:\s*/\s*$#m', $r['body']) ? 'OK' : 'FAIL', 'robots.txt allows crawling and lists the sitemap', (string) $r['status']);
} else {
    check($r['status'] === 200 ? 'OK' : 'FAIL', 'robots.txt', $r['status'].(preg_match('#^Disallow:\s*/\s*$#m', $r['body']) ? ' (disallows all — staging)' : ''));
}
$r = fetch("{$base}/sitemap.xml");
check($r['status'] === 200 && str_contains($r['body'], '<urlset') ? 'OK' : 'FAIL', 'sitemap.xml', $r['status'].' '.substr_count($r['body'], '<loc>').' URLs');
if ($r['status'] === 200 && preg_match('#<loc>(https?://[^/<]+)#', $r['body'], $m)) {
    check($m[1] === $base ? 'OK' : 'FAIL', 'Sitemap URLs use this origin (APP_URL)', $m[1]);
}
$r = fetch("{$base}/feed/");
check($r['status'] === 200 && str_contains($r['body'], '<rss') ? 'OK' : 'FAIL', 'RSS feed', (string) $r['status']);

$r = fetch("{$base}/about");
check(in_array($r['status'], [301, 308], true) && locationPath($r) === '/about/' ? 'OK' : 'FAIL', 'Trailing slash added with 301', $r['status'].' → '.(locationPath($r) ?? '-'));

$missing = '/smoke-no-such-page-'.bin2hex(random_bytes(3)).'/';
$r = fetch($base.$missing);
check($r['status'] === 404 ? 'OK' : 'FAIL', 'Unknown URL → 404 (not redirected to home)', (string) $r['status']);

$admin = '/'.trim((string) $opts['admin-path'], '/').'/login';
$r = fetch($base.$admin);
check($r['status'] === 200 && str_contains(strtolower($r['headers']['x-robots-tag'] ?? ''), 'noindex') ? 'OK' : 'FAIL', "Admin login {$admin} (200 + noindex)", (string) $r['status']);

// 5. Files that must never be served ------------------------------------------
foreach (['/.env', '/.env.production.example', '/composer.json', '/composer.lock', '/artisan', '/.git/config', '/vendor/autoload.php',
    '/storage/logs/laravel.log', '/bootstrap/public-path.php', '/media/.htaccess', '/database/preview.sqlite',
    '/wp-content/uploads/2024/10/log_file_2024-10-16__09-06-50.txt'] as $path) {
    $r = fetch($base.$path);
    $leak = $r['status'] === 200 || str_contains($r['body'], 'APP_KEY=');
    $htaccessOnly = $opts['dev-server'] && str_ends_with($path, '.htaccess');
    check($leak ? ($htaccessOnly ? 'INFO' : 'FAIL') : 'OK', "Not served: {$path}", $r['status'].($leak && $htaccessOnly ? ' (php -S ignores .htaccess; real host must refuse it)' : ''));
}

// 6. Legacy uploads (old image URLs) -------------------------------------------
$r = fetch("{$base}/wp-content/uploads/2026/02/sajad.m1-560x560.jpeg");
check($r['status'] === 200 ? 'OK' : 'WARN', 'Legacy uploads copied (sample image)', $r['status'].($r['status'] === 200 ? '' : ' — copy /wp-content/uploads (images only, D37)'));

// 7. Old WordPress URLs (D31) --------------------------------------------------
if (! $opts['skip-redirects']) {
    $rules = require __DIR__.'/../../database/content/redirects.php';
    $bad = [];
    foreach ($rules as [$from, $to, $status]) {
        $path = rtrim($from, '*');
        $r = fetch($base.encodePath($path));
        $ok = $to === null
            ? $r['status'] === 410
            : $r['status'] === $status && locationPath($r) === $to;
        if (! $ok) {
            $bad[] = "{$from} → {$r['status']} ".(locationPath($r) ?? '');
        }
    }
    check($bad === [] ? 'OK' : 'FAIL', 'Old WordPress URLs ('.count($rules).' rules: 301/410 as listed)', $bad === [] ? '' : count($bad).' wrong: '.implode('; ', array_slice($bad, 0, 5)));
}

// Summary ---------------------------------------------------------------------
$fail = count(array_filter($results, fn ($r) => $r[0] === 'FAIL'));
$warn = count(array_filter($results, fn ($r) => $r[0] === 'WARN'));
echo "\n".($fail ? "{$fail} FAILED" : 'No failures').", {$warn} warning(s), ".count($results)." checks.\n";

if ($summary = getenv('GITHUB_STEP_SUMMARY')) {
    $md = "## Smoke test: {$base}\n\n| | Check | Details |\n|---|---|---|\n";
    foreach ($results as [$state, $name, $details]) {
        $md .= "| {$state} | ".str_replace('|', '\|', $name).' | '.str_replace('|', '\|', $details)." |\n";
    }
    file_put_contents($summary, $md."\n**".($fail ? "{$fail} failed" : 'No failures').", {$warn} warning(s).**\n", FILE_APPEND);
}

exit($fail ? 1 : 0);
