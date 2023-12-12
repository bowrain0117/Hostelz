<?php

namespace Lib\Middleware;

use Closure;
use Exception;

/*

Debugging note:  On https sites with a broken certificate (such as our current dev site), Chrome will never actually cache the page, even if the cache headers are set.

*/

class BrowserCache
{
    public static $disabled = false;

    public function handle($request, Closure $next, $time)
    {
        $response = $next($request);
        if (! $response) {
            return null;
        }

        // we don't want to browser cache posted pages, etc.
        if (self::$disabled || $response->getMaxAge() !== null || $request->getMethod() != 'GET' || config('custom.browserCacheHeadersDisabled')) {
            return $response;
        }

        if ($time == 'indefinite') {
            $minutes = 0;
        } else {
            $timeParameterParts = explode(' ', $time);
            list($timeCount, $timeUnits, $privateOrPublic) = $timeParameterParts;

            switch ($timeUnits) {
                case 'minute':
                case 'minutes':
                    $minutes = $timeCount;
                    break;

                case 'hour':
                case 'hours':
                    $minutes = $timeCount * 60;
                    break;

                case 'day':
                case 'days':
                    $minutes = $timeCount * 60 * 24;
                    break;

                default:
                    logError("Unknown cache units of '" . $timeUnits . "'.");

                    return;
            }
        }

        switch ($privateOrPublic) {
            case 'public':
                $response->setPublic();
                break;

            case 'private':
                $response->setPrivate();
                break;

            default:
                throw new Exception("Unknown value '$privateOrPublic' for privateOrPublic.");
        }

        return $response->setMaxAge($minutes * 60)
            ->header('Access-Control-Max-Age', $minutes * 60); // only used by the browser if it's a cross-domain AJAX request
    }
}
