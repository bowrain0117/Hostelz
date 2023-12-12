<?php

namespace Lib;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;

/*

Laravel config settings used:

    custom.pageCacheDisableSaving
    custom.pageCacheDisableClearing

*/

class PageCache
{
    private static $tags = [];

    private static $disabled = false;

    // to change the default storage location. should end in a trailing '/'.
    public static $pageCacheStorageOverride = null;

    // shouldn't usually be needed (routes that specify a domain include the domain, or HTTP only or HTTPS only routes automatically use the full URL)
    public static $alwaysUseFullUrlForCacheKey = false;

    // can only be used if Apache's mod_deflate is not used (or else our content length would be wrong)
    public static $outputContentLengthHeader = true;

    const MAX_TAG_LENGTH = 20;

    const KEY_LENGTH = 32; // the length of an MD5 hash

    public static function maintenanceTasks()
    {
        $output = '';

        $deletedKeysArray = self::deleteExpired();
        $output .= 'Delete expired: ' . implode(', ', $deletedKeysArray) . "\n";

        $output .= "optimizeCacheTable\n";
        self::optimizeCacheTable();

        return $output;
    }

    public static function dontCacheThisPage()
    {
        self::$disabled = true;
    }

    /*
        A very fast attempt to use the page cache before loading Laravel.
        So this can't use any Laravel code!
    */

    public static function quickTryCache()
    {
        if (self::$disabled || $_SERVER['REQUEST_METHOD'] !== 'GET') {
            return;
        } // used for GET requests only

        if (! self::$alwaysUseFullUrlForCacheKey) {
            // Try to find the page with just the path and not the domain or protocol
            $cacheKey = self::makeCacheKeyForCurrentPage(false, false);
            $cache = self::cacheGet($cacheKey);

            if (! $cache) {
                // Try to find the page with just the path and domain, but not protocol
                $cacheKey = self::makeCacheKeyForCurrentPage(false, true);
                $cache = self::cacheGet($cacheKey);
            }
        }

        if (self::$alwaysUseFullUrlForCacheKey || ! $cache) {
            // Try to find the page with the full URL
            $cacheKey = self::makeCacheKeyForCurrentPage(true, true);
            $cache = self::cacheGet($cacheKey);
        }

        if (! $cache) {
            return;
        }

        $cache = json_decode($cache, true);

        if ($cache['cacheExpirationTime'] && $cache['cacheExpirationTime'] < time()) {
            // The cache file existed, but it's expired. So this is a good time to delete it.
            // (We don't delete the PageCacheIndex record, but that's because we haven't booted Laravel and don't have DB, but that's ok.)
            self::deleteFromCache($cacheKey);

            return;
        }
        if ($cache['statusCode']) {
            http_response_code($cache['statusCode']);
        }
        if ($cache['cacheControl'] != '') {
            header('Cache-Control: ' . $cache['cacheControl']);
        }
        if ($cache['contentType'] != '') {
            header('Content-Type: ' . $cache['contentType']);
        }
        // Add the Cache-Tag header (can be used with CDNs to intelligently clear their cache)
        if ($cache['tags']) {
            header('Cache-Tag: ' . implode(' ', $cache['tags']));
        }
        header('X-PageCache: isCached'); // our own special so we can detect if our cache is working by looking at the response headers (for debugging)
        self::outputContent($cache['content']);
        exit();
    }

    public static function getCacheTagHeaderValue()
    {
    }

    private static function outputContent($content)
    {
        if (! self::$outputContentLengthHeader) {
            echo $content;

            return;
        }

        $contentLength = strlen($content);
        $usingCompressedOutput = ($contentLength && ini_get('zlib.output_compression') &&
            isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false);

        if ($usingCompressedOutput) {
            // In order to accurately set Content-Length, we have to compress the data ourselves rather than letting PHP do it automatically.
            $compressedContent = gzencode($content, 9, FORCE_GZIP);
            $compressedContentLength = strlen($compressedContent);
            if ($compressedContentLength / $contentLength < 0.9) {
                ini_set('zlib.output_compression', false);
                header('Content-Encoding: gzip');
                $content = $compressedContent;
                $contentLength = $compressedContentLength;
            }
        }

        // compressed or not, sets the Content-Length
        header("Content-Length: $contentLength");
        echo $content;
    }

    // $expirationMinutes = 0 for indefinte.

    public static function isProtocolOrDomainNeededForCacheKey($request)
    {
        if (self::$alwaysUseFullUrlForCacheKey) {
            return ['protocol' => true, 'domain' => true];
        }
        $return = [];
        $route = $request->route();
        $return['protocol'] = ($route->httpOnly() || $route->httpsOnly());
        $return['domain'] = ($return['protocol'] || $route->domain() != ''); // we always include the domain if the route is protocol-specific

        return $return;
    }

    public static function isProtocolNeededForCacheKey($request)
    {
        if (self::$alwaysUseFullUrlForCacheKey) {
            return true;
        }
        $route = $request->route();

        return $route->domain() != '' || $route->httpOnly() || $route->httpsOnly();
    }

    public static function saveToPageCache($request, $response, $expirationMinutes)
    {
        // (checks isOk() because other response types may have redirect headers and things that we don't cache, so just cache regular responses.)
        if (self::$disabled || config('custom.pageCacheDisableSaving') || $request->getMethod() !== 'GET' || ! $response->isOk()) {
            return false;
        }

        $partsNeeded = self::isProtocolOrDomainNeededForCacheKey($request);
        $pageKey = self::makeCacheKeyForCurrentPage($partsNeeded['protocol'], $partsNeeded['domain']);

        //  todo: test cache
//        debugOutputCustom("request:[" . $request->fullUrl() . "]; file:[" . self::cachePathForKey($pageKey) . "] key:[$pageKey]; tag:[" . var_export(self::$tags, 1) . "]", 'testCache');

        self::savePageToCache($pageKey, self::$tags, $response, $expirationMinutes);
        self::saveToIndex($pageKey, self::$tags, $expirationMinutes); // do this *after* savePageToCache() to avoid a race condition when clearing cache pages
    }

    public static function clearAll()
    {
        if (config('custom.pageCacheDisableClearing')) {
            return;
        }

        self::clearByTag(null);

        // We also delete cache files manually just in case we missed anything that was out of sync with the database.
        self::deleteAllFilesFromCacheStorage();
    }

    public static function clearByTag($tag)
    {
        self::debugOutput("PageCache::clearByTag($tag)");
        $keys = self::deleteFromIndex($tag);
        // Allow for extra execution time if there are a lot of cache items to delete
        if ($keys && count($keys) > 100) {
            $secondsWanted = round(count($keys) / 5);
            if (ini_get('max_execution_time') < $secondsWanted) {
                set_time_limit($secondsWanted);
            }
        }
        foreach ($keys as $key) {
            self::deleteFromCache($key);
        }

        return $keys; // just in case they want to know what was deleted.
    }

    // This should be called automatically, maybe hourly or daily
    public static function deleteExpired()
    {
        if (config('custom.pageCacheDisableClearing')) {
            return;
        }
        self::debugOutput('PageCache::deleteExpired()');
        $keys = self::deleteExpiredFromIndex();
        // Allow for extra execution time if there are a lot of cache items to delete
        if ($keys && count($keys) > 100) {
            set_time_limit(10 * 60 + round(count($keys) / 10));
        }
        foreach ($keys as $key) {
            self::deleteFromCache($key);
        }

        return $keys; // just in case they want to know what was deleted.
    }

    // Note: Tags may not contain spaces (so they work with the Cache-Tag header)
    public static function addCacheTags($tags)
    {
        if (is_array($tags)) {
            self::$tags = array_merge(self::$tags, $tags);
        } else {
            self::$tags[] = $tags;
        }
    }

    public static function getCacheTags()
    {
        return self::$tags;
    }

    /*
    ** Misc Private Functions
    */

    private static function makeCacheKey($method, $url)
    {
        // (string length must match the KEY_LENGTH constant)
        return md5($method . ':' . trim($url, '/'));
    }

    private static function makeCacheKeyForCurrentPage($includeProtocol, $includeDomain)
    {
        $url = $_SERVER['REQUEST_URI'];
        if ($includeDomain) {
            $url = '//' . $_SERVER['HTTP_HOST'] . $url;
        }
        if ($includeProtocol) {
            $url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . ':' . $url;
        }

        return self::makeCacheKey($_SERVER['REQUEST_METHOD'], $url);
    }

    /*
    ** Cache Get/Set
    */

    /*
    This would be the Laravel way to do it, but now we use the non-Laravel quickTryCache() method

    private function getPageFromCache($key)
    {
        self::debugOutput("PageCache::getPageFromCache($key)");

        $cache = self::cacheGet($key);
        if ($cache == null) {
            self::debugOutput("-> not cached.");
            return null;
        }

        $cache = json_decode($cache, true);
        $response = Response::make($cache['content'] . '<!-- cached '.$key.' -->');
        if ($cache['statusCode']) $response->setStatusCode($cache['statusCode']);
        if ($cache['maxAge']) $response->setMaxAge($cache['maxAge']);
        if ($cache['contentType'] != '') $response->header('Content-Type', $cache['contentType']);
        self::debugOutput("-> returning cached page.");
        return $response;
    }
    */

    private static function savePageToCache($key, $tags, $response, $expirationMinutes)
    {
        $content = $response->getContent();

        if ($response->headers->get('Content-Encoding') == 'gzip') {
            // Our AddContentLength middleware (or other software) may have already compressed the output,
            // but we want to store it uncompressed in case the next user's browser doesn't support compression.
            $content = gzdecode($content);
        }

        self::debugOutput("PageCache::savePageToCache($key)");
        self::cacheSet($key, json_encode([
            'cacheExpirationTime' => $expirationMinutes ? time() + $expirationMinutes * 60 : 0,
            'statusCode' => $response->getStatusCode(),
            'cacheControl' => $response->headers->get('Cache-Control'),
            'contentType' => $response->headers->get('Content-Type'),
            'tags' => $tags,
            'content' => $content,
        ]),
            $expirationMinutes // also pass $expirationMinutes to cacheSet() (but cacheSet() implementation may ignore it, so we also need 'expirationTime')
        );
    }

    private static function deleteFromCache($key)
    {
        self::debugOutput("PageCache::deleteFromCache($key)");
        self::cacheDelete($key);
    }

    /*
    ** PageCache Index
    */

    // The only purpose of having a PageCache Index is so we can clear cache by tag (or clear all or remove expired cache).

    // Cached List (note: items listed in the list might have expired, so it lists pages that may exist, but not guarantteed.)

    /* Table Definitions */

    public static function optimizeCacheTable()
    {
        DB::statement('OPTIMIZE TABLE pageCacheIndex');
    }

    private static function saveToIndex($key, $tags, $expirationMinutes)
    {
        // may already exist in db
        if (! $tags) {
            $tags = [''];
        } // at least save one row with an empty tag so we know the page cache for the key exists.

        self::debugOutput("PageCache::saveToIndex($key, '" . implode("','", $tags) . "')");

        $expirationTime = $expirationMinutes ? (string) Carbon::now()->addMinutes($expirationMinutes) : null;

        foreach ($tags as $tag) {
            if (strlen($tag) > self::MAX_TAG_LENGTH) {
                throw new Exception("Tag '$tag' is over the max length.");
            }
            // We do this in a try block because it may fail if the key/tag combo already existed (which happens when a cached page was expired)
            try {
                DB::table('pageCacheIndex')->insert([
                    'cacheKey' => $key,
                    'tag' => $tag,
                    'expirationTime' => $expirationTime,
                ]);
            } catch (Exception $e) {
                self::debugOutput('-> ' . $e->getMessage());
                if ($expirationTime != null) {
                    self::debugOutput("-> Updating expiration time to $expirationTime.");
                    // Generally if the insert failed, it means there was a previously cached page that expired. So we just try updating the expirationTime.
                    DB::table('pageCacheIndex')->where('cacheKey', $key)->where('tag', $tag)->update(['expirationTime' => $expirationTime]);
                }
            }
        }
    }

    public static function debugOutput($output)
    {
        if (function_exists('debugOutput')) {
            debugOutput($output);
        }
    }

    // If $tag is null, this deletes all.

    private static function deleteFromIndex($tag = null)
    {
        self::debugOutput("PageCache::deleteFromIndex($tag)");

        // Prep query
        $query = DB::table('pageCacheIndex');
        if ($tag !== null) {
            $query->where('tag', $tag);
        }

        // Get keys
        $keys = $query->groupBy('cacheKey')->pluck('cacheKey')->all();
        /* self::debugOutput("-> '".implode(',', $keys)."'"); */

        if ($keys) {
            if ($tag !== null) {
                // Delete all rows from index belonging to those keys
                DB::table('pageCacheIndex')->whereIn('cacheKey', $keys)->delete();
            } else {
                // Just delete *all* rows from the index
                DB::table('pageCacheIndex')->delete();
            }
        }

        return $keys;
    }

    // If $tag is null, deletes all.
    private static function deleteExpiredFromIndex()
    {
        self::debugOutput('PageCache::deleteExpiredFromIndex()');

        // Prep query
        $keys = DB::table('pageCacheIndex')->
            whereNotNull('expirationTime')->
            where('expirationTime', '<', Carbon::now())->
            pluck('cacheKey')->all();

        if ($keys) {
            // Delete all rows from index belonging to those keys (all records with the same key should have the same expirationTime
            DB::table('pageCacheIndex')->whereIn('cacheKey', $keys)->delete();
        }

        return $keys;
    }

    /*
        Low-level Cache Access

        Implementation: File Storage (see also Redis implementation below)
    */

    // (this method must be usable without Laravel)

    private static function cacheRootPath()
    {
        if (self::$pageCacheStorageOverride != '') {
            return self::$pageCacheStorageOverride;
        }

        return __DIR__ . '/../storage/pageCache/';
    }

    // (this method must be usable without Laravel)

    private static function cachePathForKey($key)
    {
        $path = self::cacheRootPath() . (crc32($key) % 100) . '/' . $key;

        return $path;
    }

    private static function createCacheStorage()
    {
        for ($i = 0; $i <= 99; $i++) {
            File::makeDirectory(self::cacheRootPath() . $i, 0770, true, true);
        }
    }

    // Get any left over files that may still be in the cache but weren't deleted when we did clearAll()

    private static function deleteAllFilesFromCacheStorage()
    {
        for ($i = 0; $i <= 99; $i++) {
            $files = File::files(self::cacheRootPath() . $i);
            foreach ($files as $file) {
                File::delete($file);
            }
        }
    }

    public static function removeCacheStorage()
    {
        File::deleteDirectory(self::cacheRootPath(), true);
    }

    private static function cacheSet($key, $value, $expirationMinutes)
    {
        // Note: $expirationMinutes isn't used for the static file cache storage implementation

        file_put_contents(self::cachePathForKey($key), $value, LOCK_EX);
    }

    private static function readLock($file)
    {
        if (! file_exists($file)) {
            return null;
        }

        $lockHandle = fopen($file, 'r');

        if (! flock($lockHandle, LOCK_SH)) {
            fclose($lockHandle);

            return null;
        }

        return $lockHandle;
    }

    private static function readUnlock($lockHandle)
    {
        flock($lockHandle, LOCK_UN);
        fclose($lockHandle);
    }

    // (this method must be usable without Laravel)
    // Note:  We use fopen() instead of file_get_contents() because we need to use flock().w2

    private static function cacheGet($key)
    {
        $file = self::cachePathForKey($key);
        $lockHandle = self::readLock($file);
        if (! $lockHandle) {
            return null;
        }

        $contents = file_get_contents($file);

        self::readUnlock($lockHandle);

        return $contents === false ? null : $contents;
    }

    private static function cacheDelete($key)
    {
        $file = self::cachePathForKey($key);
        $lockHandle = self::readLock($file);
        if (! $lockHandle) {
            return null;
        }

        $path = self::cachePathForKey($key);
        if (isset($path)) {
            unlink($path);
        }

        self::readUnlock($lockHandle);
    }

    /*

    // Previously used Redis, but then realized it's usually a bad idea to fit all the cashed pages in memory as Redis does.

    private static $redis = null;

    private static function cacheConnect($useDatabaseNumber = null)
    {
        if (!self::$redis) {
            if ($useDatabaseNumber === null) {
                $useDatabaseNumber = \Config::get('database.redis.default.database');
            }
            self::$redis = new Redis();
            self::$redis->pconnect('localhost');
            self::$redis->select($useDatabaseNumber); // totalcommercial production (ok to share with dev because it's keyed by the full URL)
        }
        return self::$redis;
    }

    private static function cacheSet($key, $value, $expirationMinutes)
    {
        $redis = self::cacheConnect();
        if ($expirationMinutes)
            $result = $redis->setex('pageCache_' . $key, $expirationMinutes * 60, $value);
        else
            $result = $redis->set('pageCache_' . $key, $value); // no expiration
    }

    private static function cacheGet($key)
    {
        $redis = self::cacheConnect();
        return $redis->get('pageCache_' . $key);
    }

    private static function cacheDelete($key)
    {
        $redis = self::cacheConnect();
        $redis->delete('pageCache_' . $key);
    }

    */
}
