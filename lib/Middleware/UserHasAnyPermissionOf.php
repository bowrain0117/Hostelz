<?php

/*

$permissions - '|' is used as a separator for multiple permissions (because a comma can't be used because that's used for separating multiple route filters).

*/

namespace Lib\Middleware;

use App\Models\User;
use Closure;

class UserHasAnyPermissionOf
{
    public function handle($request, Closure $next, $permissions)
    {
        if (! auth()->check() || ! auth()->user()->hasAnyPermissionOf(explode('|', $permissions))) {
            return accessDenied();
        }

        return $next($request);
    }
}
