<?php

namespace App\Services\ImportSystems\BookHostels;

use App\Exceptions\ImportSystemException;
use App\Helpers\HttpHelper;
use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;

class APIBookHostels
{
    public const USERNAME = 'hostelz.com';

    public const PASSWORD = 'howq452';

    public const CAMREF = '1100l3SZe';

    public const SECRET = '6yUqN4aE';

    public static function doRequest($command, $options, $timeoutSeconds, $tries = 1, $apiVersion = 2)
    {
        $url = $apiVersion === 2 ?
            'https://partner-api.hostelworld.com/' :
            'https://affiliate.xsapi.webresint.com/1.1/';
        $url .= $command . '.json';

        $httpOptions = (config('custom.httpUseProxy')) ? HttpHelper::addProxyOption([]) : [];

        $response = Http::acceptJson()
            ->withOptions($httpOptions)
            ->retry($tries, $timeoutSeconds)
            ->get($url, self::addConsumerToOptions($options))
            ->throw(function ($response, $e): void {
                throw new ImportSystemException(
                    'APIBookHostels doRequest error.',
                    ['response' => $response->json(), 'exception' => $e]
                );
            })
            ->json();

        if ($response['api']['status'] !== 'Success') {
            $errorCode = self::getErrorCode($response);

            if (! in_array($errorCode, [2027, 2022])) {
                report(new ImportSystemException(
                    "APIBookHostels doRequest for command '{$command}'.",
                    compact('response', 'options')
                ));
            }

            return [
                'success' => false,
                'error' => [
                    'code' => $errorCode,
                    'message' => json_encode($response['result']['errors']),
                ],
                'data' => $response,
            ];
        }

        return [
            'success' => true,
            'data' => $response['result'],
        ];
    }

    public static function getErrorCode($response): int
    {
        // 2015: // "Covered period is too wide"
        // 2016: // "Check-in date (DateStart) cant be in the past"
        // 2021: // "Start date(DateStart) must be specified in YYYY-MM-DD format" (happens for invalid dates like 02-31)
        // 2027: // "Specified property is not active"
        // 2022: // "Property number (PropertyNumber) is not valid property number"

        return array_key_first($response['result']['errors']);
    }

    public static function getAvailabilityForImporteds($chunk, $options)
    {
        $url = 'https://partner-api.hostelworld.com/propertyavailabilitysearch.json';

        $httpOptions = (config('custom.httpUseProxy')) ? HttpHelper::addProxyOption([]) : [];

        $responses = Http::withOptions($httpOptions)->pool(
            fn (Pool $pool) => $chunk->map(
                fn (Collection $_importeds) => $pool->withOptions($httpOptions)->retry(3, 30)
                    ->get($url, array_merge(self::addConsumerToOptions($options), ['PropertyNumbers' => $_importeds->implode('intCode', ',')]))
            )->toArray()
        );

        try {
            $result = $chunk
                ->keys()
                ->reduce(function ($carry, $key) use ($responses, $options) {
                    $item = $responses[$key];

                    if (! method_exists($item, 'failed')) {
                        throw new \Exception('BookHostels getAvailabilityForImporteds Error [failed] is missing! item ' . json_encode($item));
                    }

                    if ($item->failed()) {
                        $item->throw();
                    }

                    if (empty($item->object()->result)) {
                        throw new \Exception('BookHostels getAvailabilityForImporteds Error [result] is missing! item ' . json_encode($item));
                    }

                    if (! $item->ok()) {
                        throw new \Exception('BookHostels getAvailabilityForImporteds Error! item ' . json_encode($item));
                    }

                    $result = $item->object()->result;
                    if (isset($result->errors)) {
                        throw new \Exception('BookHostels getAvailabilityForImporteds Error! result ' . json_encode($result));
                    }

                    return $carry->merge($result->Properties);
                }, collect([]));
        } catch (\Exception $e) {
            report($e);

            return collect([]);
        }

        return $result;
    }

    public static function getConversionReport($options)
    {
        // https://api-docs.partnerize.com/partner/#tag/Partner-Conversions
        $url = sprintf(
            'https://api.partnerize.com/reporting/report_publisher/publisher/%s/conversion.json',
            config('ota.partnerize.publisher_id')
        );

        return Http::withBasicAuth(
            config('ota.partnerize.user_app_key'),
            config('ota.partnerize.user_api_key')
        )
            ->get($url, $options)
            ->throw()
            ->object();
    }

    public static function getImportedRating($intCode)
    {
        $url = 'https://www.hostelworld.com/properties/' . $intCode . '/rating';

        return Http::get($url)->throw()->object();
    }

    private static function addConsumerToOptions($options)
    {
        $options['consumer_key'] = self::USERNAME;
        $options['consumer_signature'] = self::getConsumerSignature();

        return $options;
    }

    private static function getConsumerSignature(): string
    {
        return sha1(self::USERNAME . '--' . self::SECRET);
    }
}
