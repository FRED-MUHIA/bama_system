<?php

use Illuminate\Foundation\Application;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

// Some shared hosts expose this directory at /public instead of using it as
// the document root. Canonicalize those requests before Laravel determines
// its base URL, otherwise /public can be interpreted as the application's /.
$canonicalPublicUrl = require __DIR__.'/../bootstrap/canonical-public-url.php';
$canonicalLocation = $canonicalPublicUrl($_SERVER);

if ($canonicalLocation !== null) {
    header('Location: '.$canonicalLocation, true, 308);
    exit;
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
