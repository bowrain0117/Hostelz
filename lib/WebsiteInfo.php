<?php

namespace Lib;

use Exception;

class WebsiteInfo
{
    const MAX_URLS_PER_QUERY = 10; // Moz allows up to 10 at a time

    public static function getAuthorityStats($urlOrURLs)
    {
        /*
            Moz free Rows Per Month 25,000
            https://moz.com/help/guides/moz-api/mozscape
            https://moz.com/help/guides/moz-api/mozscape/api-reference/url-metrics
        */

        $accessID = config('custom.mozAccessID');
        $expires = time() + 300; // they allow up to 5 minutes
        $stringToSign = $accessID . "\n" . $expires;
        $binarySignature = hash_hmac('sha1', $stringToSign, config('custom.mozSecretKey'), true);
        $urlSafeSignature = urlencode(base64_encode($binarySignature));

        $urlMetricFlags = ['pageAuthority' => 34359738368, 'domainAuthority' => 68719476736];

        $function = 'url-metrics';
        $cols = $urlMetricFlags['pageAuthority'] + $urlMetricFlags['domainAuthority'];

        $urlChunks = array_chunk((array) $urlOrURLs, self::MAX_URLS_PER_QUERY);

        $results = [];

        foreach ($urlChunks as $urls) {
            $apiURL = 'https://lsapi.seomoz.com/linkscape/' . $function . '/?' .
                'Cols=' . $cols . '&AccessID=' . $accessID . '&Expires=' . $expires . '&Signature=' . $urlSafeSignature;

            // Multiple tries because sometimes their API just returns nothing
            for ($try = 1; $try <= 3; $try++) {
                debugOutput("minDelayBetweenCalls('seoMozAPI')");
                minDelayBetweenCalls('seoMozAPI', 12000); // free API has limit of 1 request every 10 seconds
                debugOutput($apiURL);
                $data = WebsiteTools::fetchPage($apiURL, json_encode($urls));
                if ($data != '') {
                    break;
                }
                //logWarning("No data returned from Seomoz.");
            }

            if ($data == '') {
                logWarning("No data returned from Seomoz after multiple tries. $apiURL");
                continue; // this could be a break, but apparently their API just sometimes returns nothing, so might as well try some more.
            }

            $data = json_decode($data, true);
            if ($data == '') {
                logError("Couldn't decode '$data'.");
                break;
            }

            foreach ($data as $key => $dataForURL) {
                if (! isset($dataForURL['upa']) || ! isset($dataForURL['pda'])) {
                    logError("Can't find results in data from Seomoz. $key => " . json_encode($dataorURL));
                    break;
                }

                $results[$urls[$key]] = [
                    'pageAuthority' => (int) round($dataForURL['upa']),
                    'domainAuthority' => (int) round($dataForURL['pda']),
                ];
            }
        }

        // If query was about just one URL, just return the one result
        if (! is_array($urlOrURLs)) {
            return @$results[$urlOrURLs];
        }

        return $results;
    }

    /*
    They have an API aat https://aws.amazon.com/awis/ but wasn't able to get it to work.
    */

    public static function getTrafficStats($url)
    {
        minDelayBetweenCalls('alexaData', 1000); // don't know what their limit is, but 1 per second seems reasonable
        $data = WebsiteTools::fetchPage('http://data.alexa.com/data?cli=10&url=' . urlencode($url));
        if ($data == '') {
            logError('Alexa returned nothing.');

            return null;
        }

        if (! stripos($data, '<ALEXA VER="0.9"')) {
            logError("Unexpected Alexa response for $url: '$data'.");

            return null;
        }

        preg_match('`REACH RANK="(.*)"`U', $data, $result);

        if (! @$result[1]) {
            // (probably just means the website is unranked)
            // logWarning("Couldn't get rank for $url from '$data'.");
            return 0;
        }

        return $result[1];
    }
}
