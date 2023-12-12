<?php

namespace Lib;

use Cache;
use Exception;

class GoogleAPI
{
    public static function makeApiCall($url, $cacheResultsForSeconds = 0)
    {
        if ($cacheResultsForSeconds) {
            $cachedResult = self::attemptGetFromCache($url);

            if ($cachedResult) {
                debugOutput('makeApiCall($url) was cached. ');
            } else {
                debugOutput('makeApiCall($url) not cached. ');
            }

            if ($cachedResult !== null) {
                return $cachedResult;
            }
        }

        $result = self::fetchPage($url);
        self::saveToCache($url, $result, $cacheResultsForSeconds);

        return $result;
    }

    private static function fetchPage($url, $postVars = null, $reportError = false)
    {
        static $curl;

        if (! $curl) {
            $curl = curl_init();
            curl_setopt_array($curl, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FAILONERROR => true,
                CURLOPT_CONNECTTIMEOUT => 35,
                CURLOPT_TIMEOUT => 40,
                CURLOPT_REFERER => routeURL('home', [], 'publicSite'), // Google requires a referer set to some page on our domain
                // Turn off the server and peer verification (TrustManager Concept).
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => false,
            ]);
        }

        curl_setopt($curl, CURLOPT_URL, $url);

        if ($postVars) {
            $paramString = '';
            foreach ($postVars as $name => $value) {
                $paramString .= '&' . $name . '=' . rawurlencode($value);
            }
            curl_setopt($curl, CURLOPT_POST, true);
            curl_setopt($curl, CURLOPT_POSTFIELDS, $paramString);
        } else {
            curl_setopt($curl, CURLOPT_POST, false);
        }

        $data = curl_exec($curl);

        if ($reportError && $data == '') {
            $errorText = curl_error($curl);
            if ($errorText != '') {
                throw new Exception($errorText);
            }
        }

        return $data;
    }

    private static function getCacheID($parameters)
    {
        return 'GoogleAPI:' . md5(serialize($parameters));
    }

    private static function attemptGetFromCache($parameters)
    {
        $result = Cache::get(self::getCacheID($parameters));

        return $result ? unserialize($result) : null;
    }

    private static function saveToCache($parameters, $result, $cacheResultsForSeconds)
    {
        if (! $cacheResultsForSeconds) {
            return null;
        }
        Cache::put(self::getCacheID($parameters), serialize($result), $cacheResultsForSeconds);
    }
}
