<?php

namespace App\Services\ImportSystems\BookHostels;

/*

New API documentation: https://partner-api.hostelworld.com/ (this documentation is replacing their old PDF documentation)

https://api-docs.partnerize.com/partner/#tag/Partner-Conversions

api partnerize

New affiliate portal: https://phgconsole.performancehorizon.com/login/hostelworld/en

TO DO:

Affiliate contact: Sennai.Daniel@hostelworld.com
    -> Adam.Shellam@hostelworld.com now? No response to last email.


WAITING ON:
- Currency list from the API (have excel list) -> done "currency call" just need to know the specifics
- 8/29/2014 - Emailed Max about NoConfirmationEmail.

Answers:
- 2016-01 - Asked them if they can send us booking notice emails. -> "no longer offer that feature"
- 2020-06 - Asterisks on the API names just means you can add .json or .xml.

TO DO:
- supported currencies
- group booking (have to ask user for group type and age ranges)
- get maxnights value for each property and save it for use. (and minnights?)

Notes:
- There is no difference between the GrossDeposit and Deposit
- issueNo and validFrom are not currently used.


UPGRADE TO THEIR V2 API STATUS:

- 'propertyinformation' keeping on V1 ->
   Also for propertyinformation, is the PagesCount value capped at a maximum of 99?  I noticed it has 99 pages, when the old API says 403 pages (of 100 listings each).
    SD: There is a ticket for the fix on this. Will keep you updated.

    TODO:
    - has all images -> use them
    - Prices are different

- 'propertybookinginformation' keeping on V1 -> ok. TODO: use new info?

- 'currencies' keeping on V1 ->
    We were using the "currencies" API call to get the list of supported currencies.  Is that not going to be available in the new API?
    SD: This endpoint is being migrated over to the new Partner API. As the Old XSAPI will still be active until the end of June, hopefully you can use this in the meantime?

*/

use App;
use App\Booking\BookableImportSystemInterface;
use App\Booking\RoomAvailability;
use App\Booking\SearchCriteria;
use App\Models\CityInfo;
use App\Models\Imported;
use App\Services\ImportSystems\ImportSystems;
use App\Services\ImportSystems\ImportSystemServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BookHostelsService implements ImportSystemServiceInterface, BookableImportSystemInterface
{
    private const NUMBER_OF_NEXT_DAYS_SEARCH = 2;

    // (was BOOKHOSTELS_LANGUAGES on the old site)
    // also currently used by detectLanguage() (their code => our code)
    // also Catalan, Brazilian, TradChinese, Hungarian, Lithuanian, Latvian, Russian, Slovak, Slovenian
    public static $LANGUAGE_CODES = ['English' => 'en', 'French' => 'fr', 'Spanish' => 'es', 'Italian' => 'it', 'German' => 'de',
        'Polish' => 'pl', 'Dutch' => 'nl', 'Japanese' => 'ja', 'Chinese' => 'zh', 'Korean' => 'ko', 'Portuguese' => 'pt-br',
        'Swedish' => 'sv', 'Finnish' => 'fi', 'Norwegian' => 'no', 'Danish' => 'da', 'Czech' => 'cs',

        // New 1.2 list: Brazilian, Catalan, TradChinese, Czech, Danish, German, Spanish, Finnish, French, Hungarian, Italian, Japanese, Korean, Lithuanian, Latvian, Dutch, Norwegian, Polish, Portuguese, Russian, Slovak, Slovenian, Swedish, Turkish and Chinese.
    ];

    public static function hourlyMaintenance(): string
    {
        try {
            $output = (new MaintenanceBookHostels())->addNewBookings();
        } catch (\Throwable $e) {
            Log::channel('import')->error(
                'BookHostelsService hourlyMaintenance',
                ['exception' => $e]
            );

            return '';
        }

        return $output;
    }

    public static function dailyMaintenance(): string
    {
        try {
            $output = (new MaintenanceBookHostels())->updateStatus();
        } catch (\Throwable $e) {
            Log::channel('import')->error(
                'BookHostelsService dailyMaintenance',
                ['exception' => $e]
            );

            return '';
        }

        return $output;
    }

    public static function getAvailability(Collection $importeds, SearchCriteria $searchCriteria, $requireRoomDetails, $bookingLinkLocation = ''): array
    {
        try {
            $roomAvailabilities = (new AvailabilityBookHostels())->get($importeds, $searchCriteria);
        } catch (\Throwable $e) {
            Log::channel('import')->error(
                'BookHostelsService getAvailability',
                ['exception' => $e, 'searchCriteria' => $searchCriteria]
            );

            return [];
        }

        return $roomAvailabilities;
    }

    public static function getNextDaysAvailability(Collection $importeds, SearchCriteria $searchCriteria, bool $roomDetails, int $initialNightsValue): array
    {
        $maxNightsForSearch = $initialNightsValue + static::NUMBER_OF_NEXT_DAYS_SEARCH;

        $searchCriteria->nights++;

        $roomAvailabilities = (new AvailabilityBookHostels())->get($importeds, $searchCriteria);

        if (! empty($roomAvailabilities)) {
            $searchNightsMatchMinNights = $searchCriteria->nights === (int) reset($roomAvailabilities)->minimumNightsRequired;

            if ($searchNightsMatchMinNights) {
                $roomAvailabilities['minimumNightsAlert'] = $searchCriteria->nights;

                $searchCriteria->nights = $initialNightsValue;

                return $roomAvailabilities;
            }
        }

        if ($searchCriteria->nights === $maxNightsForSearch) {
            $searchCriteria->nights = $initialNightsValue;

            return [];
        }

        return self::getNextDaysAvailability($importeds, $searchCriteria, $roomDetails, $initialNightsValue);
    }

    public static function import($isTestRun): void
    {
        try {
            (new ImportBookHostels())->handle($isTestRun);
        } catch (\Throwable $e) {
            Log::channel('import')->error(
                'BookHostelsService import',
                ['exception' => $e]
            );
        }
    }

    public static function updateDataForImported(Imported $imported): void
    {
        try {
            (new UpdateImportedBookHostels())->handle($imported);
        } catch (\Throwable $e) {
            Log::channel('import')->error(
                'BookHostelsService updateDataForImported for $importedID=' . $imported->id,
                ['exception' => $e]
            );
        }
    }

    public static function isActive(Imported $imported): bool
    {
        return (new UpdateImportedBookHostels())->isActiveImport($imported);
    }

    public static function getDefaultLinkRedirect($bookingLinkLocation): string
    {
        return self::getStaticLinkRedirect(
            'https://www.hostelworld.com/',
            $bookingLinkLocation,
        );
    }

    public static function getStaticLinkRedirect($urlLink, $bookingLinkLocation, $importedId = null): string
    {
        $trackingCode = makeTrackingCode();

        return makeStaticLinkRedirect(
            self::getStaticURL($urlLink, $bookingLinkLocation, $trackingCode),
            self::getImportSystem()->shortName(),
            $trackingCode,
            $importedId
        );
    }

    public static function getStaticURL($urlLink, $bookingLinkLocation, $trackingCode): string
    {
        $camref = APIBookHostels::CAMREF;

        $bookingLinkLocation .= '_t_' . $trackingCode;

        return "https://hostelworld.prf.hn/click/camref:{$camref}/pubref:{$bookingLinkLocation}/destination:{$urlLink}";
    }

    /*
        After the user actually clicks the Book Now link, this method has to decode the bookingLinkInfo into an actual URL (and optionally, post variables).
    */
    public static function decodeBookingLinkInfo(string $bookingLinkInfo, string $bookingLinkLocation): ?array
    {
        $bookingLinkInfo = json_decode($bookingLinkInfo, true);

        // We now have to use their propertylinks API call
        $result = APIBookHostels::doRequest(
            'propertylinks',
            [
                'Language' => $bookingLinkInfo['lang'],
                'PropertyNumbers' => $bookingLinkInfo['propertyNumber'],
                'DateStart' => $bookingLinkInfo['startDate'],
                'NumNights' => $bookingLinkInfo['night'],
                'Persons1' => $bookingLinkInfo['people'],
                'RoomPreference1' => $bookingLinkInfo['roomCode'],
            ],
            12,
            2
        );

        if (! $result['success']) {
            logError("HostelWorld's propertylinks didn't return a URL: " . json_encode($result));

            return null;
        }

        // $url = @$result['result']['properties'][0]['links']['checkout'];  (would be better, but currently has errors)
        $url = $result['data']['properties'][0]['links']['availability'] ?? '';

        if (! $url) {
            logError("HostelWorld's propertylinks didn't return a URL: " . json_encode($result));

            return null;
        }

        return ['url' => self::addPubrefToUrl($url, $bookingLinkLocation, $bookingLinkInfo['tracingCode'])];
    }

    // * Languages *

    public static function importedLangCodeToOurCode($lang)
    {
        return self::$LANGUAGE_CODES[$lang];
    }

    // Booking Notice Webhook

    public static function bookingNoticeWebhook($request)
    {
        // We could use this data, but instead we're fetching it using hourlyMaintenance().
        // It seems to maybe have more data when we fetch it that way?
        // The code below was for their old system, so it doesn't work as it.  The data they send
        // us now is more like the code that hourlyMaintenance() gets, so we could re-use that code
        // if we wanted to make this work.

        //logError("bookingNoticeWebhook for $request");
        return 'ok';
    }

    // * Requests *

    public static function getBookingRequest(RoomAvailability $roomAvailability): void
    {
    }

    public static function getCitiesOfCountry($country)
    {
        $response = APIBookHostels::doRequest('citycountrylist', ['Country' => $country], 35, 2);

        return $response['data'];
    }

    public static function getImportSystem()
    {
        return new ImportSystems('BookHostels');
    }

    private static function addPubrefToUrl(string $bookingLinkInfo, string $bookingLinkLocation, string $tracingCode): string
    {
        return str_replace(
            '/destination',
            "/pubref:{$bookingLinkLocation}_t_{$tracingCode}/destination",
            $bookingLinkInfo);
    }

    private static function parseBookingID($bookingID)
    {
        $temp = explode('-', $bookingID);
        if (count($temp) !== 2) {
            logError("Invalid bookingID '$bookingID'.");

            return null;
        }

        return ['propertyID' => $temp[0], 'customerID' => $temp[1]];
    }

    public static function getCityLinkRedirect(CityInfo|null $city, string $bookingLinkLocation): string
    {
        if (is_null($city)) {
            $url = 'https://www.hostelworld.com';
        } else {
            $continents = match ($city->continent) {
                'UK & Ireland', 'Western Europe' => 'europe',
                'Central & East Asia', 'Eastern Europe & Russia', 'Middle East', 'South & Southeast Asia' => 'asia',
                'North America', 'Mexico & Caribbean' => 'north-america',
                'Central & South America' => 'south-america',
                'Australia & Oceania' => 'oceania',
                'Africa' => 'africa',
            };
            $url = sprintf('https://www.hostelworld.com/st/hostels/%s/%s/%s', $continents, strtolower($city->country), strtolower($city->city));
        }

        return self::getStaticLinkRedirect(
            $url,
            $bookingLinkLocation
        );
    }
}
