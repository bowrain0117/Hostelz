<?php

namespace Lib\Middleware;

use Closure;

class PreventBrowserCaching
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        return preventBrowserCaching($response);
    }
}
