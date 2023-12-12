<?php

/*

This removes the "Cache-Control" header that Laravel automatically adds.

We prefer defaulting to no Cache-Control header at all because we like the default browser behavior of
using the cached version of pages when clicking a link or using the back button, but not when a new URL is entered by the user, etc.
Unfortunately the defult behavior for HTTPS pages is to not keep the cached page, so this works better for HTTP than HTTPS.

*/

namespace Lib\Middleware;

use Closure;

class RemoveNoCacheHeader
{
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        if ($response && $response->getMaxAge() === null) {
            $response->headers->remove('Cache-Control');
        }

        return $response;
    }
}
