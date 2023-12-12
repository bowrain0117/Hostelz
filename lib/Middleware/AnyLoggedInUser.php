<?php

namespace Lib\Middleware;

use Closure;

class AnyLoggedInUser
{
    public function handle($request, Closure $next)
    {
        if (! auth()->check()) {
            return accessDenied();
        }

        return $next($request);
    }
}
