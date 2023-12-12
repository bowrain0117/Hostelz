<?php

// Special purpose - Use https://secure.hostelz.com?opcache_reset=1 if we need to force an opcache reset.
if (@$_GET['opcache_reset']) {
    opcache_reset();
    echo "opcache reset";
    exit();
}


/* Try page cache */

require __DIR__.'/../lib/PageCache.php';
// so the dev site uses the same cache as the production site so both get cleared when something is updated on the dev site.
Lib\PageCache::$pageCacheStorageOverride = '/home/hostelz/production/storage/pageCache/'; 
// (this is clunkly. would prefer to read values from .env perhaps, but that would be slow)
if (in_array($_SERVER['HTTP_HOST'], [ 'www.hostelz.com', 'secure.hostelz.com' /*, 'production.hostelz.com', 'production-secure.hostelz.com'*/ ])) {
    Lib\PageCache::quickTryCache();
}

/* Boot Laravel */

/**
 * Laravel - A PHP Framework For Web Artisans
 *
 * @package  Laravel
 * @author   Taylor Otwell <taylor@laravel.com>
 */

use Illuminate\Contracts\Http\Kernel;
use Illuminate\Http\Request;

define('LARAVEL_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check If The Application Is Under Maintenance
|--------------------------------------------------------------------------
|
| If the application is in maintenance / demo mode via the "down" command
| we will load this file so that any pre-rendered content can be shown
| instead of starting the framework, which could cause an exception.
|
*/

if (file_exists(__DIR__.'/../storage/framework/maintenance.php')) {
    require __DIR__.'/../storage/framework/maintenance.php';
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
|
| Composer provides a convenient, automatically generated class loader for
| this application. We just need to utilize it! We'll simply require it
| into the script here so we don't need to manually load our classes.
|
*/

require __DIR__.'/../vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Run The Application
|--------------------------------------------------------------------------
|
| Once we have the application, we can handle the incoming request using
| the application's HTTP kernel. Then, we will send the response back
| to this client's browser, allowing them to enjoy our application.
|
*/

$app = require_once __DIR__.'/../bootstrap/app.php';

$kernel = $app->make(Kernel::class);

$response = tap($kernel->handle(
    $request = Request::capture()
))->send();

$kernel->terminate($request, $response);
