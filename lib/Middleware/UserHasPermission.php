<?php

namespace Lib\Middleware;

use App\Models\User;
use Closure;

class UserHasPermission
{
    public function handle($request, Closure $next, $permission)
    {
        if (! auth()->check() || ! auth()->user()->hasPermission($permission)) {
            return accessDenied();
        }

        return $next($request);
    }
}
