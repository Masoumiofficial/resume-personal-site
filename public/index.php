<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Where the rest of the project lives (D36). Normally this file sits in
// <project>/public and the project is one level up. On a host whose document
// root cannot be changed, the contents of public/ are uploaded to public_html
// and the project to a sibling folder named below (never inside public_html);
// see docs/phase-6/deployment-guide.md.
$base = is_file(__DIR__.'/../bootstrap/app.php') ? __DIR__.'/..' : __DIR__.'/../site-app';

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = $base.'/storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require $base.'/vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once $base.'/bootstrap/app.php';

$app->handleRequest(Request::capture());
