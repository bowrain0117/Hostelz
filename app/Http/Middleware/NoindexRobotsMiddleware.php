<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Spatie\RobotsMiddleware\RobotsMiddleware;
use Symfony\Component\HttpFoundation\Response;

class NoindexRobotsMiddleware extends RobotsMiddleware
{
    /*
     * none - Do not show this page, media, or resource in search results; Do not follow the links on this page.
     * noindex - Do not show this page in search results, but do follow the links on this page.
     * nofollow - Show this page in search results, but do not follow the links on this page.
     *
     * */
    protected function shouldIndex(Request $request): string
    {
        return 'noindex';
    }
}
