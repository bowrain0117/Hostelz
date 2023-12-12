<?php

/*

Add the Cache-Tag header (can be used with CDNs to intelligently clear their cache)

*/

namespace Lib\Middleware;

use Closure;
use Lib\PageCache;

class AddPageCacheCacheTagsHeader
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $response = $next($request);

        $cacheTags = PageCache::getCacheTags();
        if ($cacheTags) {
            $response->header('Cache-Tag', implode(' ', $cacheTags));
        }

        return $response;
    }
}
