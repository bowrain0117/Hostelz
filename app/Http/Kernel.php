<?php

namespace App\Http;

use App\Http\Middleware\BlockRobotsMiddleware;
use App\Http\Middleware\NoindexRobotsMiddleware;
use Illuminate\Foundation\Http\Kernel as HttpKernel;

class Kernel extends HttpKernel
{
    /**
     * The application's global HTTP middleware stack.
     *
     * These middleware are run during every request to your application.
     *
     * @var array
     */
    protected $middleware = [
        \App\Http\Middleware\TrustProxies::class,
        \Illuminate\Http\Middleware\HandleCors::class,
        \App\Http\Middleware\PreventRequestsDuringMaintenance::class,
        \Illuminate\Foundation\Http\Middleware\ValidatePostSize::class,
        \App\Http\Middleware\TrimStrings::class,

        \App\Http\Middleware\Redirects::class,
        \App\Http\Middleware\RedirectToDefaultLanguage::class,

        //        \Illuminate\Foundation\Http\Middleware\ConvertEmptyStringsToNull::class,

        // 3rd Party middleware we've installed:
        //        \Clockwork\Support\Laravel\ClockworkMiddleware::class,

        // Our own:
        \Lib\Middleware\RemoveNoCacheHeader::class,
        \Lib\Middleware\AddContentLength::class,
        // 'Lib\Middleware\AddPageCacheCacheTagsHeader', (enable this if we start using a CDN)

        Middleware\BlockIPs::class,
    ];

    /**
     * The application's route middleware groups.
     *
     * @var array
     */
    protected $middlewareGroups = [
        'web' => [
            // Laravel's Middleware
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            // \Illuminate\Session\Middleware\StartSession::class, (we only use sessions on the dynamic sub-domain, so startSession is defined in $routeMiddleware instead)
            // \Illuminate\Session\Middleware\AuthenticateSession::class,
            // \Illuminate\View\Middleware\ShareErrorsFromSession::class,  (we currently handle passing errors to templates ourselves)
            // \App\Http\Middleware\VerifyCsrfToken::class, (rather than using it by default, we add it to the $routeMiddleware below)
            \Illuminate\Routing\Middleware\SubstituteBindings::class, // Route model binding
            \App\Http\Middleware\DevAccess::class,
            \App\Http\Middleware\CheckIncorrectUrl::class,
        ],

        'api' => [
            'throttle:api',
            //            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ];

    /**
     * The application's route middleware.
     *
     * These middleware may be assigned to groups or used individually.
     *
     * @var array
     */
    protected $routeMiddleware = [
        // Laravel
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'startSession' => \Illuminate\Session\Middleware\StartSession::class,
        // Lib
        'blockCountries' => \App\Http\Middleware\BlockCountries::class,
        'csrf' => \Lib\Middleware\VerifyCsrfIfPost::class,
        'pageCache' => \Lib\Middleware\PageCache::class,
        'browserCache' => \Lib\Middleware\BrowserCache::class,
        'preventBrowserCaching' => \Lib\Middleware\PreventBrowserCaching::class,
        'userHasPermission' => \Lib\Middleware\UserHasPermission::class,
        'userHasAnyPermissionOf' => \Lib\Middleware\UserHasAnyPermissionOf::class,
        'anyLoggedInUser' => \Lib\Middleware\AnyLoggedInUser::class,
        'autoLogoutFirst' => \Lib\Middleware\AutoLogoutFirst::class,
        'devAccess' => \App\Http\Middleware\DevAccess::class,
        'incorrectUrl' => \App\Http\Middleware\CheckIncorrectUrl::class,
        'blockRobots' => BlockRobotsMiddleware::class,
        'noindexRobots' => NoindexRobotsMiddleware::class,

        /* Laravel's Default Middleware as of Laravel 6
        'auth' => \App\Http\Middleware\Authenticate::class,
        'auth.basic' => \Illuminate\Auth\Middleware\AuthenticateWithBasicAuth::class,
        'cache.headers' => \Illuminate\Http\Middleware\SetCacheHeaders::class,
        'can' => \Illuminate\Auth\Middleware\Authorize::class,
        'guest' => \App\Http\Middleware\RedirectIfAuthenticated::class,
        'password.confirm' => \Illuminate\Auth\Middleware\RequirePassword::class,
        'signed' => \Illuminate\Routing\Middleware\ValidateSignature::class,
        'throttle' => \Illuminate\Routing\Middleware\ThrottleRequests::class,
        'verified' => \Illuminate\Auth\Middleware\EnsureEmailIsVerified::class,
        */
    ];
}
