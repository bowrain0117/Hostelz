<?php

namespace Lib\Middleware;

use Closure;

class PageCache
{
    private static $time;

    public function handle($request, Closure $next, $time)
    {
        self::$time = $time;

        return $next($request);
    }

    public function terminate($request, $response)
    {
        if (self::$time === 'indefinite') {
            $minutes = 0;
        } else {
            try {
                list($timeCount, $timeUnits) = explode(' ', self::$time);
            } catch (\Throwable $e) {
                logError('PageCache timeCount error. time: ' . self::$time . '; exeption: ' . json_encode($e));

                $timeUnits = '';
            }

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

        \Lib\PageCache::saveToPageCache($request, $response, $minutes);
    }
}
