<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Route;

class DevAccess
{
    /**
     * Dev Site Access Control
     * @param Request $request
     * @param Closure $next
     * @return mixed|void
     */
    public function handle(Request $request, Closure $next)
    {
        if (! App::isProduction() && Route::currentRouteName() !== 'robots-txt' && ! App::runningInConsole()) {
            $devSiteAllowedIPs = Cache::get('devSiteAllowedIPs', []);

            if (! in_array($request->ip(), $devSiteAllowedIPs, true)) {
                Cookie::queue(Cookie::make('previous_url', $request->path(), 300));

                return redirect()->route('devAccess');
            }
        }

        return $next($request);
    }
}
