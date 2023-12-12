<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CheckIncorrectUrl
{
    /**
     * This generates a 404 error when we get URLs that start with '//', which was happening for some reason
     * @param Request $request
     * @param Closure $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        $urlStart = Str::startsWith($request->getRequestUri(), '//');

        abort_if($urlStart, 404);

        return $next($request);
    }
}
