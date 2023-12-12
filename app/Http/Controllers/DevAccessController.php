<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Redirect;

class DevAccessController extends Controller
{
    public function checkDevAccess(Request $request)
    {
        if ($request->input('pw') !== config('custom.devAccess')) {
            return redirect()->refresh();
        }

        $devSiteAllowedIPs = Cache::get('devSiteAllowedIPs', []);
        $devSiteAllowedIPs[] = $request->ip();

        if (count($devSiteAllowedIPs) > 20) {
            array_shift($devSiteAllowedIPs);
        }

        Cache::put('devSiteAllowedIPs', $devSiteAllowedIPs, 12 * 60 * 60 * 60);

        $url = Cookie::get('previous_url');
        $cookie = Cookie::forget('previous_url');

        return Redirect::to($url)->withCookie($cookie);
    }
}
