<?php

/*

This is a simplified sub-set of the CSRF checking methods offered by Laravel.
See also: Illuminate\Foundation\Http\Middleware\VerifyCsrfToken

*/

namespace Lib\Middleware;

use Closure;
use Request;
use Response;
use Session;

class VerifyCsrfIfPost
{
    public function handle($request, Closure $next)
    {
        if (in_array($request->getMethod(), ['POST', 'DELETE', 'PUT', 'PATCH'])) {
            if (Session::token() !== Request::input('_token')) {
                return Response::view('error');
            }
        }

        return $next($request);
    }
}
