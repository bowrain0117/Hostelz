<?php

/* HTML / HTTP */

/*
    Similar to Laravel's route(), but defaults to not-absolute URLs unless the URL needs to be absolute.  Also allows $language to be specified.

    $urlType - 'absolute', 'relative', 'protocolRelative', 'publicSite', or 'auto' to determine automatically based on the route and the current route.
    $language - Language code, otherwise null for the current language.  Requires App\Languages;
*/

if (! function_exists('routeURL')) {
    function routeURL($name, $parameters = [], $urlType = 'auto', $language = null)
    {
        if (is_null($route = Route::getRoutes()->getByName($name))) {
            throw new InvalidArgumentException("Route [{$name}] not defined.", 404);
        }

        if ($urlType == 'auto') {
            // (http/https only, or no current route, such as for artisan commands, or the 404 page which has no route set on a page not found error)
            if ($route->httpsOnly() || $route->httpOnly() || ! Route::current()) {
                $urlType = 'absolute';
            } elseif ($route->domain() != '' && $route->domain() != Route::current()->domain()) {
                $urlType = 'protocolRelative';
            } else {
                $urlType = 'relative';
            }
        }
        if (! in_array($urlType, ['absolute', 'relative', 'protocolRelative', 'publicSite'])) {
            throw new Exception("Unknown urlType '$urlType'.");
        }

        $url = app('url')->route($name, $parameters, $urlType != 'relative', $route);

        if ($language !== null && $language != \App\Models\Languages::currentCode()) {
            $url = \App\Models\Languages::current()->changeUrlFromThisLanguageTo($url, $language);
        }

        if ($urlType == 'publicSite') {
            $url = convertOurUrlToPublicSiteUrl($url);
        } elseif ($urlType == 'protocolRelative') {
            $url = removeProtocolFromURL($url);
        }

        return $url;
    }
}

// Used to generate a URL for the public website from the dev server
if (! function_exists('convertOurUrlToPublicSiteUrl')) {
    function convertOurUrlToPublicSiteUrl($url)
    {
        if (config('custom.thisStaticDomain') === config('custom.publicStaticDomain')) {
            return $url;
        }
        $pos = strpos($url, config('custom.thisStaticDomain'));
        if ($pos == 7 || $pos == 8 || $pos == 2) { // (http:// or https:// or //)
            return substr_replace($url, config('custom.publicStaticDomain'), $pos, strlen(config('custom.thisStaticDomain')));
        }
        $pos = strpos($url, config('custom.thisDynamicDomain'));
        if ($pos == 7 || $pos == 8 || $pos == 2) { // (http:// or https:// or //)
            return substr_replace($url, config('custom.publicDynamicDomain'), $pos, strlen(config('custom.thisDynamicDomain')));
        }

        throw new Exception("Couldn't convert '$url' to a public URL.");
    }
}

if (! function_exists('removeProtocolFromURL')) {
    function removeProtocolFromURL($url)
    {
        if (stripos($url, 'http://') === 0) {
            return substr($url, 5);
        }
        if (stripos($url, 'https://') === 0) {
            return substr($url, 6);
        }

        throw new Exception("Can't remove protocol from '$url'.");
    }
}

if (! function_exists('urlIsOurSite')) {
    function urlIsOurSite($url, $specificSubdomain = null)
    {
        if (($specificSubdomain == 'static' || $specificSubdomain == null) &&
            stripos($url, 'http://' . config('custom.thisStaticDomain')) === 0 ||
            stripos($url, 'https://' . config('custom.thisStaticDomain')) === 0) {
            return true;
        }

        if (($specificSubdomain == 'dynamic' || $specificSubdomain == null) &&
            stripos($url, 'http://' . config('custom.thisDynamicDomain')) === 0 ||
            stripos($url, 'https://' . config('custom.thisDynamicDomain')) === 0) {
            return true;
        }

        return false;
    }
}

/*
    Set the necessary Cross-Origin Resource Sharing headers to allow AJAX requests from the static site to the dynamic site.
*/
if (! function_exists('setCorsHeadersToAllowOurSubdomains')) {
    function setCorsHeadersToAllowOurSubdomains($response, $acceptCookies)
    {
        // Check the origin
        $origin = Request::header('origin');
        if ($origin == '' && ! App::environment('production')) {
            return $response;
        } // allow it to be just loaded from the browser without using AJAX, for debugging.
        if (! urlIsOurSite($origin)) {
            /* logWarning("Not allowing access from origin '$origin'."); */
            App::abort(404);
        }

        if ($acceptCookies) {
            $response->header('Access-Control-Allow-Credentials', 'true');
        }

        return $response
            ->header('Access-Control-Allow-Origin', $origin)
            ->header('Access-Control-Allow-Headers', 'Content-Type, *')
            ->header('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, PUT, DELETE'); // (we could also allow delete, etc.)
    }
}

if (! function_exists('replaceNonUrlCharWithDash')) {
    function replaceNonUrlCharWithDash($string)
    {
        $string = str_replace(
            ['ñ', 'Ñ', 'é', 'è', 'É', 'ë', 'ü', 'ú', 'í', 'İ', 'ı', 'ï', 'á', 'à', 'â', 'ã', 'ä', 'Ã', 'ó', 'ø', 'ö', 'ť', 'Š', 'Ç', 'ç', 'č', 'ý'],
            ['n', 'N', 'e', 'e', 'E', 'e', 'u', 'u', 'i', 'I', 'i', 'i', 'a', 'a', 'a', 'a', 'a', 'A', 'o', 'o', 'o', 't', 'S', 'C', 'c', 'c', 'y'],
            $string
        );
        $result = preg_replace('/[^A-Za-z0-9\-\p{Katakana}\p{Hiragana}\p{Han}]/u', '-', $string);

        return preg_replace('/-+/', '-', $result);
    }
}

if (! function_exists('replaceUrlCharWith')) {
    function replaceUrlCharWith(string $string, string $separator = '-')
    {
        $unwantedUrlCharacters = [' ', "'", '@', '&', '!', '*', '(', ')', ',', '+', ':', '"', '<', '>', '%', '/'];

        return str_replace($unwantedUrlCharacters, $separator, $string);
    }
}
