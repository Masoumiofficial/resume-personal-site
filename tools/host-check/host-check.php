<?php
/**
 * Host capability check — Phase 0 Discovery (sajadmasoumi.com)
 * --------------------------------------------------------------
 * A one-time, read-only diagnostic. It reports the PHP version,
 * extensions, limits and filesystem layout needed to choose the
 * Laravel version and the deployment layout.
 *
 * It deliberately does NOT:
 *   - call phpinfo() or print environment variables
 *   - read any file contents
 *   - connect to the database or accept credentials
 *   - write anything except (optionally) deleting itself
 *
 * USAGE (see docs/phase-0/hosting-checklist.md):
 *   1. Replace ACCESS_TOKEN below with a long random string.
 *   2. Upload to public_html/ with a non-obvious filename.
 *   3. Open  https://sajadmasoumi.com/<file>.php?token=<ACCESS_TOKEN>
 *   4. Copy the output, then open  ...?token=<ACCESS_TOKEN>&delete=1
 *      (or delete the file via File Manager). Never leave it on the server.
 */

declare(strict_types=1);

const ACCESS_TOKEN = 'CHANGE_ME';

header('Content-Type: text/plain; charset=utf-8');
header('Cache-Control: no-store, max-age=0');
header('X-Robots-Tag: noindex, nofollow');
header('X-Content-Type-Options: nosniff');

$given = isset($_GET['token']) && is_string($_GET['token']) ? $_GET['token'] : '';
if (ACCESS_TOKEN === 'CHANGE_ME' || strlen(ACCESS_TOKEN) < 24 || !hash_equals(ACCESS_TOKEN, $given)) {
    http_response_code(404);
    echo "Not Found\n";
    exit;
}

if (isset($_GET['delete']) && $_GET['delete'] === '1') {
    $ok = @unlink(__FILE__);
    echo $ok ? "Deleted. Verify the URL now returns 404.\n" : "Could not delete. Remove the file manually via File Manager.\n";
    exit;
}

/** @return string */
function yn(bool $v): string { return $v ? 'yes' : 'NO'; }

function line(string $k, string $v): void { printf("%-34s %s\n", $k, $v); }

function section(string $t): void { echo "\n== {$t} ==\n"; }

function fnAvailable(string $fn): bool
{
    if (!function_exists($fn)) { return false; }
    $disabled = array_map('trim', explode(',', (string) ini_get('disable_functions')));
    return !in_array($fn, $disabled, true);
}

echo "HOST CHECK REPORT — generated " . gmdate('Y-m-d H:i:s') . " UTC\n";
echo "(Contains server paths. Share only with the project team; do not post publicly.)\n";

section('PHP runtime');
line('PHP version', PHP_VERSION);
line('Meets Laravel 13 (>= 8.3.0)', yn(version_compare(PHP_VERSION, '8.3.0', '>=')));
line('SAPI', PHP_SAPI);
line('OS family', PHP_OS_FAMILY);
line('Architecture (int size)', (string) (PHP_INT_SIZE * 8) . '-bit');
line('Server software', (string) ($_SERVER['SERVER_SOFTWARE'] ?? 'unknown'));
line('HTTPS on this request', yn((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https')));

section('Extensions required by Laravel 13');
$required = ['ctype', 'curl', 'dom', 'fileinfo', 'filter', 'hash', 'mbstring', 'openssl', 'pcre', 'pdo', 'session', 'tokenizer', 'xml'];
$missing = [];
foreach ($required as $ext) {
    $ok = extension_loaded($ext);
    if (!$ok) { $missing[] = $ext; }
    line($ext, yn($ok));
}
line('>> Missing required', $missing ? implode(', ', $missing) : 'none');

section('Extensions needed by this project');
$project = [
    'pdo_mysql' => 'MySQL/MariaDB driver',
    'gd'        => 'image resize/convert (one of gd/imagick)',
    'imagick'   => 'image resize/convert (alternative)',
    'exif'      => 'image orientation',
    'intl'      => 'Jalali dates / number formatting',
    'zip'       => 'release/backup archives',
    'sodium'    => 'modern crypto (2FA, tokens)',
    'bcmath'    => 'optional',
    'opcache'   => 'performance',
    'Zend OPcache' => 'performance (same as above)',
];
foreach ($project as $ext => $why) {
    line($ext, yn(extension_loaded($ext)) . "  ({$why})");
}
if (extension_loaded('gd') && function_exists('gd_info')) {
    $gd = gd_info();
    line('gd WebP support', yn(!empty($gd['WebP Support'])));
    line('gd AVIF support', yn(!empty($gd['AVIF Support'])));
    line('gd JPEG support', yn(!empty($gd['JPEG Support'])));
}
if (class_exists('PDO')) {
    line('PDO drivers', implode(', ', PDO::getAvailableDrivers()) ?: 'none');
}

section('Limits (php.ini)');
foreach (['memory_limit', 'max_execution_time', 'max_input_time', 'max_input_vars', 'upload_max_filesize', 'post_max_size', 'max_file_uploads', 'default_charset', 'date.timezone', 'session.save_handler', 'session.cookie_secure', 'session.use_strict_mode', 'expose_php', 'display_errors', 'allow_url_fopen', 'file_uploads', 'opcache.enable', 'opcache.validate_timestamps'] as $k) {
    $v = ini_get($k);
    line($k, $v === false ? '(not set)' : ($v === '' ? '(empty)' : (string) $v));
}
line('open_basedir active', yn(ini_get('open_basedir') !== '' && ini_get('open_basedir') !== false));

section('Functions relevant to deployment');
foreach (['proc_open' => 'Composer/Artisan via web', 'exec' => 'shell helpers', 'shell_exec' => 'shell helpers', 'symlink' => 'storage:link / release switching', 'mail' => 'PHP mail()', 'fsockopen' => 'SMTP sockets', 'curl_exec' => 'outbound HTTP', 'putenv' => 'env handling', 'set_time_limit' => 'long tasks', 'ignore_user_abort' => 'long tasks', 'escapeshellarg' => 'Artisan internals'] as $fn => $why) {
    line($fn, yn(fnAvailable($fn)) . "  ({$why})");
}

section('Filesystem layout');
$docRoot = (string) ($_SERVER['DOCUMENT_ROOT'] ?? '');
$scriptDir = __DIR__;
$parent = dirname($scriptDir);
line('Document root', $docRoot !== '' ? $docRoot : 'unknown');
line('This script dir', $scriptDir);
line('Script dir writable', yn(is_writable($scriptDir)));
line('Parent of script dir', $parent);
line('Parent readable', yn(@is_readable($parent)));
line('Parent writable', yn(@is_writable($parent)) . '  (needed to keep app code outside public_html)');
$grand = dirname($parent);
line('Grandparent', $grand);
line('Grandparent writable', yn(@is_writable($grand)));
line('Temp dir', sys_get_temp_dir());
line('Temp dir writable', yn(@is_writable(sys_get_temp_dir())));
$free = @disk_free_space($scriptDir);
line('Disk free (PHP view)', $free === false ? 'unknown' : sprintf('%.2f GB (may not reflect account quota)', $free / 1073741824));

section('Process identity');
if (function_exists('posix_geteuid') && function_exists('posix_getpwuid')) {
    $u = @posix_getpwuid(posix_geteuid());
    line('PHP runs as user', is_array($u) ? (string) $u['name'] : 'unknown');
} else {
    line('PHP runs as user', 'unknown (posix not available)');
}
$owner = @fileowner(__FILE__);
line('Script file owner uid', $owner === false ? 'unknown' : (string) $owner);
if (function_exists('posix_geteuid')) {
    line('PHP uid == file owner uid', yn($owner !== false && posix_geteuid() === $owner) . '  (yes = suPHP/LSAPI per-user; 0755/0644 perms are enough)');
}

section('Web server modules (Apache only)');
if (function_exists('apache_get_modules')) {
    $mods = apache_get_modules();
    foreach (['mod_rewrite', 'mod_headers', 'mod_deflate', 'mod_expires', 'mod_brotli'] as $m) {
        line($m, yn(in_array($m, $mods, true)));
    }
} else {
    line('apache_get_modules()', 'not available (LiteSpeed/FPM): verify .htaccess rewrite manually');
}

echo "\nDONE. Now delete this file: add &delete=1 to the URL, or remove it via File Manager.\n";
