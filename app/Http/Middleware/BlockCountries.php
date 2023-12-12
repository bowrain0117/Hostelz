<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Cache;
use Stevebauman\Location\Facades\Location;

class BlockCountries
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
        if (! ($locatin = $this->getLocation())) {
            logWarning('BlockCountries no location for ip: ' . request()->ip());

            return $next($request);
        }

        if (in_array($locatin->countryCode, config('custom.blockedCountriesCode', []))) {
            logWarning('BlockCountries blocked location: ' . json_encode($locatin));

            return redirect()->route('onHold');
        }

        return $next($request);
    }

    private function getLocation()
    {
        return Cache::remember(
            'locationIp:' . request()->ip(),
            now()->addWeek(),
            fn () => Location::get()
        );
    }
}
