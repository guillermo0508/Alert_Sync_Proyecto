<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// On serverless platforms (e.g. Vercel) the app filesystem is read-only except /tmp.
// LARAVEL_STORAGE_PATH / VIEW_COMPILED_PATH / APP_*_CACHE are pointed at /tmp in that
// environment (see .env.vercel.example); /tmp is wiped on every cold start, so the
// directories Laravel expects to already exist must be created here before the app boots.
if ($storagePath = getenv('LARAVEL_STORAGE_PATH')) {
    foreach (['framework/cache/data', 'framework/sessions', 'framework/testing', 'framework/views', 'app/public', 'logs'] as $dir) {
        @mkdir($storagePath.'/'.$dir, 0775, true);
    }
}
foreach (['VIEW_COMPILED_PATH'] as $envVar) {
    if ($path = getenv($envVar)) {
        @mkdir($path, 0775, true);
    }
}
foreach (['APP_CONFIG_CACHE', 'APP_EVENTS_CACHE', 'APP_PACKAGES_CACHE', 'APP_ROUTES_CACHE', 'APP_SERVICES_CACHE'] as $envVar) {
    if ($path = getenv($envVar)) {
        @mkdir(dirname($path), 0775, true);
    }
}

// Determine if the application is in maintenance mode...
if (file_exists($maintenance = __DIR__.'/../storage/framework/maintenance.php')) {
    require $maintenance;
}

// Register the Composer autoloader...
require __DIR__.'/../vendor/autoload.php';

// Bootstrap Laravel and handle the request...
/** @var Application $app */
$app = require_once __DIR__.'/../bootstrap/app.php';

$app->handleRequest(Request::capture());
