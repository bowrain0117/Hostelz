<?php

namespace Lib;

use SimpleXMLElement;

class ImageSearch
{
    // based on https://services.tineye.com/developers/tineyeapi/libraries.html

    public static function searchByURL($url, $maxResults = 10)
    {
        minDelayBetweenCalls('ImageSearch:tinEyeAPI', 2000); // they allow up to 30 calls per minute, so we limit to 1 call per 2 seconds.

        $api_url = 'https://api.tineye.com/rest/search/';
        $api_private_key = config('custom.tinEye.privateKey');
        $api_public_key = config('custom.tinEye.publicKey');

        $p = [
            'offset' => '0',
            'limit' => $maxResults,
            'image_url' => $url,
        ];

        $sorted_p = ksort($p);
        $query_p = http_build_query($p);
        $signature_p = strtolower($query_p);
        $http_verb = 'GET';
        $date = time();
        $nonce = uniqid();

        $string_to_sign = $api_private_key . $http_verb . $date . $nonce . $api_url . $signature_p;

        $api_sig = hash_hmac('sha256', $string_to_sign, $api_private_key);

        $url = $api_url . '?api_key=' . $api_public_key . '&' .
            $query_p . '&date=' . $date . '&nonce=' . $nonce . '&api_sig=' . $api_sig;

        $response = json_decode(WebsiteTools::fetchPage($url), true);

        if ($response['code'] != 200) {
            logError("searchByURL error for $response[code] '$url'.");

            return null;
        }

        $return = [];

        foreach ($response['results']['matches'] as $match) {
            foreach ($match['backlinks'] as $link) {
                $return[] = $link['backlink'];
                if (count($return) >= $maxResults) {
                    return $return;
                }
            }
        }

        return $return;
    }
}
