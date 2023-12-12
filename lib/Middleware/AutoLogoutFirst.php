<?php

namespace Lib\Middleware;

use Closure;

class AutoLogoutFirst
{
    public function handle($request, Closure $next)
    {
        if (auth()->check()) {
            auth()->logout();
        }

        return $next($request);
    }
}
