<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;

class BlockIPs
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
        if ($this->isInBlockList($request->ip())) {
            logWarning('BlockIPs for ip: ' . request()->ip());
            abort('404');
        }

        return $next($request);
    }

    private function isInBlockList($ip)
    {
        return Cache::remember(
            'isInBlockList:' . $ip,
            now()->addMonth(),
            fn () => in_array($ip, config('custom.blockIpList', []))
        );
    }
}
