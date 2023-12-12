<?php

namespace Lib;

use SimpleXMLElement;

class PlagiarismChecker
{
    const COPYSCAPE_USERNAME = 'doe1';

    const COPYSCAPE_API_KEY = '0kffe1ec0rjr8kar';

    const COPYSCAPE_API_URL = 'http://www.copyscape.com/api/';

    public static $curl = null;

    // Calling code should throw an error if this function returns false.

    public static function textCheck($text, $testMode = false)
    {
        $params = ['e' => 'UTF-8', 'f' => 'xml', 'c' => 3];
        if ($testMode) {
            $params['x'] = '1';
        }
        $result = self::apiCall('csearch', $params, $text);

        if (! $result || property_exists($result, 'error') || ! property_exists($result, 'count')) {
            return false;
        }

        $details = '';
        $percentMatched = 0;

        if ($result->count && $result->result) {
            $maxDetails = 3;
            foreach ($result->result as $match) {
                if (! $maxDetails--) {
                    break;
                }
                $details .= $match->percentmatched . "%\n" . $match->url . "\n" . $match->viewurl . "\n\n";
                if ($match->percentmatched > $percentMatched) {
                    $percentMatched = (int) $match->percentmatched;
                }
            }
        }

        // Note: We can't just use $result->allpercentmatched because it seems to include the URLs of our own site that we told CopyScape to ignore.

        return [
            'percentMatched' => $percentMatched,
            'details' => trim($details),
        ];
    }

    private static function apiCall($operation, $params = [], $postData = null)
    {
        $url = self::COPYSCAPE_API_URL . '?u=' . urlencode(self::COPYSCAPE_USERNAME) .
            '&k=' . urlencode(self::COPYSCAPE_API_KEY) . '&o=' . urlencode($operation);

        foreach ($params as $name => $value) {
            $url .= '&' . urlencode($name) . '=' . urlencode($value);
        }

        if (! self::$curl) {
            self::$curl = curl_init();
            curl_setopt_array(self::$curl, [
                CURLOPT_TIMEOUT => 30,
                CURLOPT_RETURNTRANSFER => true,
            ]);
        }

        curl_setopt_array(self::$curl, [
            CURLOPT_URL => $url,
            CURLOPT_POST => $postData != null,
            CURLOPT_POSTFIELDS => $postData,
        ]);

        $response = curl_exec(self::$curl);

        if ($response == '') {
            return false;
        }

        return new SimpleXMLElement($response);
    }
}
