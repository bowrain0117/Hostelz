<?php

namespace App\Services\ImportSystems\Hostelsclub;

use App\Helpers\HttpHelper;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use SimpleXMLElement;

class APIHostelsclub
{
    public const OUR_API_AFFILIATE_ID = 483;

    public static function doJsonRequest($method, $options, $timeoutSeconds)
    {
        $url = 'https://www.hostelspoint.com/webservices/affiliates/json.php';

        $options['IDSite'] = self::OUR_API_AFFILIATE_ID;
        $options['requestType'] = $method;
        $httpOptions = (config('custom.httpUseProxy')) ? HttpHelper::addProxyOption([]) : [];
        $tries = 3;

        $response = Http::acceptJson()
            ->withOptions($httpOptions)
            ->retry($tries, $timeoutSeconds)
            ->get($url, $options)
            ->throw()
            ->json();

        if ($response === false || $response === '') {
            Log::channel('import')->warning("APIHostelsclub API Request $method failed.");

            return null;
        }

        return $response;
    }

    public static function doXmlRequest($method, $request, $language = 'en', $extraParams = '', $timeoutSeconds = 20, $sendPassword = false)
    {
        // bookingTimeElapsed();

        $xmlHeader = '<?xml version="1.0" encoding="UTF-8"?><' . $method .
            ' xmlns="http://www.opentravel.org/OTA/2003/05" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" xsi:schemaLocation="http://www.opentravel.org/OTA/2003/05 ' . $method . '.xsd" Version="1.006" Target="' . (config('custom.bookingTestMode') ? 'Test' : 'production') . '" PrimaryLangID="' . $language . '" ' . $extraParams . ' ><POS><Source><RequestorID ID="' . self::OUR_API_AFFILIATE_ID . '"' .
            ($sendPassword ? ' MessagePassword="hzz567br1258"' : '') .
            '/></Source></POS>';

        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-type: application/x-www-form-urlencoded',
                'content' => 'OTA_request=' . urlencode("$xmlHeader$request</$method>"),
                'timeout' => $timeoutSeconds,
                'max_redirects' => 0,
            ],
        ]);

        $data = file_get_contents('https://www.hostelspoint.com/xml/xml.php', false, $context);
        if ($data === false || $data === '') {
            logWarning("Hostelsclub API Request $method failed.");

            return null;
        }

        // $timeElapsed = bookingTimeElapsed();
        // bookingLog("Hostelsclub $method ($timeElapsed)".($data != false ? 'Success' : 'FAILED')." $_SERVER[REQUEST_URI]");

        try {
            $xml = new SimpleXMLElement($data);
        } catch (Exception $e) {
            $data = convertStringToValidUTF8($data); // strips invalid characters that may have caused SimpleXMLElement() to fail.

            try {
                $xml = new SimpleXMLElement($data);
            } catch (Exception $e) {
                logWarning("self::doXmlRequest($method) can't make XML from data.");

                return null;
            }
        }

        if (! $xml) {
            logWarning("SimpleXMLElement Error on $url");

            return null;
        }

        if ($xml->attributes()->ErrorMessage) {
            logWarning("Error on '$method': " . $xml->attributes()->ErrorMessage);

            return null;
        }

        return $xml;
    }

    public static function getAllHostels(): Collection
    {
        return cache()->remember('AllHostelsclubHostels', now()->addDay(), function () {
            $xmlFile = file_get_contents('https://www.hostelspoint.com/xml_aff/hostels_en.xml');

            $xmlObject = simplexml_load_string($xmlFile);
            $jsonFormatData = json_encode($xmlObject);
            $response = json_decode($jsonFormatData, true);

            if (empty($response['HOSTEL'])) {
                throw new Exception('APIHostelsclub getAll empty result');
            }

            return collect($response['HOSTEL']);
        });
    }
}
