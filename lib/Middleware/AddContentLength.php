<?php

/*

Adds the Content-Length header (as required by KeyCDN, and probably a good thing for browers in general).

Also, if zlib.output_compression was turned on, this compresses the output (and turns zlib.output_compression off).
We have to do that or else we wouldn't know what the real Content-Length is after it gets compressed.

I posted a version of this code here:  https://laracasts.com/discuss/channels/general-discussion/add-content-length-header-on-views

Original bits of code from http://serverfault.com/questions/183843/content-length-not-sent-when-gzip-compression-enabled-in-apache.

This only works with Nginx if gzip is off for PHP.

*/

namespace Lib\Middleware;

use Closure;

class AddContentLength
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
        $response = $next($request);

        if (headers_sent() || ob_get_contents() != '') {
            return $response;
        } // some output must have been echo'd or something.
        if (! method_exists($response, 'content')) {
            return $response;
        } // the 'throttle' middleware returns a response with no content() method.
        $content = $response->content();
        $contentLength = strlen($content);
        $useCompressedOutput = ($contentLength && ini_get('zlib.output_compression') &&
            isset($_SERVER['HTTP_ACCEPT_ENCODING']) && strpos($_SERVER['HTTP_ACCEPT_ENCODING'], 'gzip') !== false);

        if ($useCompressedOutput) {
            // In order to accurately set Content-Length, we have to compress the data ourselves rather than letting PHP do it automatically.
            $compressedContent = gzencode($content, 9, FORCE_GZIP);
            $compressedContentLength = strlen($compressedContent);
            if ($compressedContentLength / $contentLength < 0.9) {
                ini_set('zlib.output_compression', false);
                $response->header('Content-Encoding', 'gzip');
                $response->setContent($compressedContent);
                $contentLength = $compressedContentLength;
            }
        }

        // compressed or not, sets the Content-Length
        $response->header('Content-Length', $contentLength);

        return $response;
    }
}
