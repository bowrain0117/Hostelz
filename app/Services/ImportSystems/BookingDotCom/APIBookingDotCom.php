<?php

namespace App\Services\ImportSystems\BookingDotCom;

use Illuminate\Http\Client\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class APIBookingDotCom
{
    public const USERNAME = 'hostelzcom';

    public const PASSWORD = 'host_673Er';

    public static function doBlockAvailabilityReqest($options, $chunk)
    {
        $url = self::getURL('blockAvailability');

        $responses = Http::pool(
            fn (Pool $pool) => $chunk->map(
                fn (Collection $_importeds) => $pool->retry(3, 30)
                    ->post($url, array_merge($options, ['hotel_ids' => $_importeds->implode('intCode', ',')]))
            )->toArray()
        );

        $result = $chunk
            ->keys()
            ->reduce(function ($carry, $key) use ($responses) {
                $item = $responses[$key];

                if (! method_exists($item, 'failed')) {
                    Log::channel('import')->error('BlockAvailability error, item: ' . json_encode($item));

                    return $carry;
                }

                if ($item->failed() && isset($item->object()->errors[0])) {
                    // (There can be multiple errors, but we basically just act on the first one.)
                    $error = $item->object()->errors[0];
                    Log::channel('import')->error("BlockAvailability error: {$error->message} ({$error->code})");

                    return $carry;
                }

                if (! $item->ok()) {
                    return $carry;
                }

                return $carry->merge($responses[$key]->object()->result);
            }, collect([]));

        return $result ?? [];
    }

    // $useSecureSite - Some of their API requests have to be sent to a different server named "secure-distribution-xml.booking.com"
    public static function doRequest($useSecureSite, $method, $options, $timeoutSeconds = 0, $tries = 1)
    {
        return Http::acceptJson()
            ->retry($tries, $timeoutSeconds)
            ->post(self::getURL($method, $useSecureSite), $options)
            ->throw(fn ($response, $e) => self::logErrors($response))
            ->object();
    }

    private static function getURL($method, $useSecureSite = false): string
    {
        return 'https://' . self::USERNAME . ':' . self::PASSWORD . '@' .
            ($useSecureSite ? 'secure-distribution-xml.booking.com' : 'distribution-xml.booking.com') .
            '/2.10/json/' . $method;
    }

    public static function logErrors($response): void
    {
        if (empty($response->object()->errors)) {
            return;
        }

        Log::error(
            'APIBookingDotCom error, headers:' . json_encode($response->headers()) .
            "; body {$response->body()}"
        );

        foreach ($response->object()->errors as $error) {
            Log::channel('import')->error("Unknown bookingDetails error: $error->message ($error->code)");
        }
    }
}
