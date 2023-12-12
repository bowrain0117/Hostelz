<?php

/*

Current both business and technical contact: sammer@hostelsclub.com (Previous contact: Paolo Mioni <pmioni@hce.it> for any API questions - haven't emailed him since 2013)


Issues:
- 1/11/2016 - Asked them if they can send us booking notice emails.
- 10/22/2015 - http://www.hostelsclub.com/hostel-en-976.html?aff_ID=77 - if select 10+ people, "group type" dro down isn't working.
- 10/8/2015 - asked if our affiliate links are working, and how to link directly to step03_book.php.
- 11/10/13 (paolo) -  "price_local" is returning euro prices for USA Hostels Hollywood.
- 2/26/13 (paolo) - we're getting back "too many connections" errors when we call OTA_HotelAvailRQ.
- 2/5/13 (paolo) - https://www.hostelz.com/hostel/+224130 avail, but city quick check not returning any beds.
- make them stop sending review request emails in our name -> done.
- It's allowing same day bookings when fetching room types, but not when proceding with the booking.

OTA_HotelResRQ: the reservation request which passes customer data and credit
card data (https)

OTA_CancelRQ: the reservation cancellation request (https)

OTA_ReadRQ:  the  reservation  read  request,  which  returns  data  about  a reservation (https)

returns </Success>, <Warnings>

A good hostel for testing Hostelsclub bookings: http://dev.hostelz.com/hostel/165092-A-O-K%C3%B6ln-Neumarkt

- "Occupancy is the number of *people* who can be put in a room. So for private rooms it will represent the number of people for the room, and for shared rooms the number of beds in the room. In terms of double bed or 2 separate beds, the information appears in the room description in the language of your request, so if you need to show it to the customer they will understand if it's two different beds or a double bed. Or do you need it to compare different offers from different portals?"

*/

namespace App\Services\ImportSystems\Hostelsclub;

use App\Booking\BookableImportSystemInterface;
use App\Booking\SearchCriteria;
use App\Models\Booking;
use App\Models\Imported;
use App\Models\MailMessage;
use App\Services\ImportSystems\ImportSystems;
use App\Services\ImportSystems\ImportSystemServiceInterface;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Lib\Currencies;

class HostelsclubService implements ImportSystemServiceInterface, BookableImportSystemInterface
{
    public const NUMBER_OF_NEXT_DAYS_SEARCH = 2;

    public const OUR_LINKING_AFFILIATE_ID = 77;

    public const COMMISSION_PERCENT = 50;

    public static $LANGUAGE_MAP = [
        'en' => 'en', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'de' => 'de', 'pl' => 'pl', 'nl' => 'nl', 'jp' => 'ja', 'cn' => 'zh', 'kr' => 'ko',
        'pg' => 'pt-br', 'sw' => 'sv', 'fi' => 'fi', 'no' => 'no', 'dk' => 'da', 'cs' => 'cs',
    ];

    public static function getAvailability(Collection $importeds, SearchCriteria $searchCriteria, $requireRoomDetails): array
    {
        try {
            $roomAvailabilities = (new AvailabilityHostelsclub())->get($importeds, $searchCriteria, $requireRoomDetails);
        } catch (\Exception $e) {
            Log::channel('import')->error('HostelsclubService getAvailability', ['searchCriteria' => $searchCriteria, 'exception' => $e]);

            return [];
        }

        return $roomAvailabilities;
    }

    public static function getNextDaysAvailability(Collection $importeds, SearchCriteria $searchCriteria, bool $roomDetails, int $initialNightsValue): array
    {
        $maxNightsForSearch = $initialNightsValue + static::NUMBER_OF_NEXT_DAYS_SEARCH;

        $searchCriteria->nights++;

        $roomAvailabilities = (new AvailabilityHostelsclub())->get($importeds, $searchCriteria, $roomDetails);

        if (! empty($roomAvailabilities)) {
            $roomAvailabilities['minimumNightsAlert'] = $searchCriteria->nights;

            $searchCriteria->nights = $initialNightsValue;

            return $roomAvailabilities;
        }

        if ($searchCriteria->nights === $maxNightsForSearch) {
            $searchCriteria->nights = $initialNightsValue;

            return [];
        }

        return self::getNextDaysAvailability($importeds, $searchCriteria, $roomDetails, $initialNightsValue);
    }

    public static function import($isTestRun)
    {
        try {
            $status = (new ImportHostelsclub())->handle($isTestRun);
        } catch (\Exception $e) {
            Log::channel('import')->error('HostelsclubService import', ['exception' => $e]);

            return false;
        }

        return $status;
    }

    public static function updateDataForImported($imported): void
    {
        try {
            (new UpdateImportedHostelsclub())->handle($imported);
        } catch (\Exception $e) {
            Log::channel('import')->error('HostelsclubService updateDataForImported', ['imported_id' => $imported->id, 'exception' => $e]);
        }
    }

    public static function isActive($imported): bool
    {
        $hostels = APIHostelsclub::getAllHostels();
        $hostel = $hostels->firstWhere('ID', $imported->intCode);

        return ! empty($hostel);
    }

    public static function decodeBookingLinkInfo(string $bookingLinkInfo, string $bookingLinkLocation): array
    {
        parse_str($bookingLinkInfo, $params);
        $url = "https://www.hostelsclub.com/hostel-en-{$params['property_id']}.html?aff_ID=" . self::OUR_LINKING_AFFILIATE_ID;
        unset($params['property_id']);
        $url .= '&' . http_build_query($params, '', '&');

        return ['url' => $url];
    }

    public static function getDefaultLinkRedirect($bookingLinkLocation): string
    {
        return self::getStaticLinkRedirect(
            'https://www.hostelsclub.com/index.php',
            $bookingLinkLocation
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

    public static function getStaticURL($urlLink, $bookingLinkLocation)
    {
        $affiliateQuery = 'aff_ID=' . self::OUR_LINKING_AFFILIATE_ID;

        if (strpos($urlLink, $affiliateQuery) === false) {
            $urlLink .= "?$affiliateQuery";
        }

        return str_replace('http://', 'https://', $urlLink);
    }

    public static function getImportSystem(): ImportSystems
    {
        return new ImportSystems('Hostelsclub');
    }

    public static function emailFilter(MailMessage $message, $testMode = false)
    {
        if ($message->senderAddress !== 'reservations@hostelsclub.com' || strpos($message->subject, 'New Reservation for ') !== 0) {
            return false;
        }

        preg_match("`You have just received a new reservation for (.*)[\n\r]" .
            ".*Reservation No. (.*)[\n\r]" .
            ".*Name: (.*)[\n\r]" .
            ".*Nationality: (.*)[\n\r]" .
            ".*Email: (.*)[\n\r]" .
            ".*Telephone No.: (.*)[\n\r]" .
            '.*Estimated arrival time: (.*):' .
            '.*(... [0-9]{1,2}, [0-9]{4}) - ' . // start date
            ".* Deposit: (.*) (...)[\n\r]" .
            '`sU', $message->bodyText, $matches);

        if (! $matches) {
            logError("Hostelsclub emailFilter couldn't parse the email.");

            return false;
        }

        $matches = array_combine(['all', 'listingName', 'bookingID', 'name', 'nationality', 'email',
            'phone', 'arrivalTime', 'startDate', 'deposit', 'depositCurrency', ], $matches);

        $booking = new Booking(['system' => 'Hostelsclub', 'email' => mb_strtolower($matches['email']), 'bookingID' => $matches['bookingID'], 'arrivalTime' => (int) $matches['arrivalTime'],
            'nationality' => $matches['nationality'], 'phone' => $matches['phone'],
            'messageText' => $message->bodyText, 'bookingTime' => Carbon::createFromFormat('Y-m-d H:i:s', $message->transmitTime),
            'startDate' => Carbon::createFromFormat('M d, Y', $matches['startDate']), ]);

        $matches['listingName'] = mb_trim($matches['listingName']);
        $importeds = Imported::where('system', 'Hostelsclub')->where('name', $matches['listingName'])->where('status', 'active')->get();
        if ($importeds->count() !== 1) {
            logWarning("Wrong number of imported matches for '$matches[listingName]' found " . $importeds->count());

            return false;
        }
        $booking->importedID = $importeds->first()->id;

        $booking->parseFirstAndLastName($matches['name']);

        if (! Currencies::isKnownCurrencyCode($matches['depositCurrency'], false)) {
            logError("Unknown currency '{$matches['depositCurrency']}'.");

            return false;
        }
        $booking->depositUSD = Currencies::convert($matches['deposit'], $matches['depositCurrency'], 'USD');
        $booking->commission = round(self::COMMISSION_PERCENT / 100 * $booking->depositUSD, 2);

        if ($testMode) {
            return $booking;
        }

        return $booking->validateAndSave();
    }
}
