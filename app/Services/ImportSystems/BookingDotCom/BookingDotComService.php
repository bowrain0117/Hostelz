<?php

/*

- Primary contact: Brian Butts <brian.butts@booking.com>  206-293-4966.
    -> now Julie Jackson <julie.jackson@booking.com> 415-633-4066
        -> now effective 10/24/2017, Amy Guo <amy.guo@booking.com>

Make sure Wow Hostel gets imported next time: http://www.booking.com/hotel/sg/wow-hostel.en-gb.html?label=gen173nr-1DCAEoggJCAlhYSDNiBW5vcmVmaKEBiAEBmAEuwgEDYWJuyAEM2AED6AEBqAIE;sid=f61bf43bb106c3f4959641e7c8a6e6e8;dcid=4;ucfs=1;room1=A,A;soldout=0,0;srfid=9765454fe9678c92d6d67ae28dd85c9571962d97X1;highlight_room=


xmlfeed.support@booking.com (no response)

Do data updates 23:00 - 8:00 GMT -> 5pm - 2am

http://www.booking.com/partner
(old) Docs: https://distribution-xml.booking.com/affiliates/documentation/
(new) https://developers.booking.com/api/index.html

(?) https://hostelzcom:host_673Er@distribution-xml.booking.com/json/bookings.getHotelTypes?new_hotel_type=1 -> type 13 is Hostel

Current issues asked about:

    - Get taxes in the price information from bookings.getBlockAvailability. https://dev-secure.hostelz.com/listing-booking-search/68128?startDate=2016-06-15&nights=1&roomType=private&people=1&rooms=1&groupType=friends&currency=   https://secure.hostelz.com/staff/mail/558323

    - 2018-09 - Asked Amy about getting email addresses of people booking.

Answers:

    (not yet using, but could) Partners can refer to how many <incremental_price> field returns per block id. However, there is a quicker way by adding this param: {show_number_of_rooms_left=1}. This will add a field called <number_of_rooms_left> as an indication of how many rooms available to be booked per block id.

Partners can refer to how many <incremental_price> field returns per block id.
However, there is a quicker way by adding this param: {show_number_of_rooms_left=1}. This will add a field called <number_of_rooms_left> as an indication of how many rooms available to be booked per block id.

    prices get rounded?  "must be a test, probably temporary, will double-check that"

TO DO:

    https://distribution-xml.booking.com/documentation/affiliates/documentation/xml_getchangedhotels.html

    https://distribution-xml.booking.com/documentation/affiliates/documentation/xml_gethotelfacilitytypes.html
    https://distribution-xml.booking.com/documentation/affiliates/documentation/xml_getfacilitytypes.html


New V2 API:

    - https://developers.booking.com/api/commercial/index.html?version=2.2&page_url=getting-started



*/

namespace App\Services\ImportSystems\BookingDotCom;

use App\Booking\SearchCriteria;
use App\Services\ImportSystems\ImportSystems;
use App\Services\ImportSystems\ImportSystemServiceInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

class BookingDotComService implements ImportSystemServiceInterface /*, BookableImportSystemInterface */
{
    public const NUMBER_OF_NEXT_DAYS_SEARCH = 2;

    public const DIRECT_LINK_AFFILIATE_ID = 333472;

    // their code => our code (see https://developers.booking.com/api/commercial/index.html?version=2.2&page_url=possible-values)
    public static $LANGUAGE_CODES = ['en' => 'en', 'fr' => 'fr', 'es' => 'es', 'it' => 'it', 'de' => 'de',
        'pl' => 'pl', 'nl' => 'nl', 'ja' => 'ja', 'zh' => 'zh', 'ko' => 'ko', 'pt' => 'pt-br',
        'sv' => 'sv', 'fi' => 'fi', 'no' => 'no', 'da' => 'da', 'cs' => 'cs',
    ];

    public static function hourlyMaintenance(): string
    {
        try {
            $output = (new MaintenanceBookingDotCom())->addNewBookings();
        } catch (\Exception $e) {
            Log::channel('import')->error('BookingDotComService hourlyMaintenance', ['exception' => $e]);

            return '';
        }

        return $output;
    }

    public static function dailyMaintenance(): string
    {
        try {
            $output = (new MaintenanceBookingDotCom())->updateStatus();
        } catch (\Exception $e) {
            Log::channel('import')->error('BookingDotComService dailyMaintenance', ['exception' => $e]);

            return '';
        }

        return $output;
    }

    public static function getAvailability(Collection $importeds, SearchCriteria $searchCriteria, $requireRoomDetails, $bookingLinkLocation = ''): array
    {
        // Note: They only let us use 'detail_level' => 1 if we're getting info about 1 property.
        $withRoomDetails = ($importeds->count() === 1);
        if ($requireRoomDetails && ! $withRoomDetails) {
            // We can't get details for more than one listing at a time, so we call this function once for each one
            $roomAvailabilities = [];
            foreach ($importeds as $imported) {
                $result = self::getAvailability(collect([$imported]), $searchCriteria, true);
                if ($result) {
                    $roomAvailabilities[] = $result;
                }
            }

            return array_merge(...$roomAvailabilities);
        }

        try {
            $roomAvailabilities = (new AvailabilityBookingDotCom())->get($importeds, $searchCriteria, $withRoomDetails);
        } catch (\Exception $e) {
            Log::channel('import')->error('BookingDotComService getAvailability', ['exception' => $e]);

            return [];
        }

        return $roomAvailabilities;
    }

    public static function getNextDaysAvailability(Collection $importeds, SearchCriteria $searchCriteria, bool $roomDetails, int $initialNightsValue): array
    {
        $maxNightsForSearch = $initialNightsValue + static::NUMBER_OF_NEXT_DAYS_SEARCH;

        $searchCriteria->nights++;

        $roomAvailabilities = (new AvailabilityBookingDotCom())->get($importeds, $searchCriteria, $roomDetails);

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

    public static function import($isTestRun): void
    {
        try {
            (new ImportBookingDotCom())->handle($isTestRun);
        } catch (\Exception $e) {
            Log::channel('importedImport')->error('BookingDotComService import', ['exception' => $e]);
        }
    }

    public static function updateDataForImported($imported): void
    {
        try {
            (new UpdateImportedBookingDotCom())->handle($imported);
        } catch (\Throwable $e) {
            Log::channel('import')->error(
                'BookingDotComService updateDataForImported for $importedID=' . $imported->id,
                ['exception' => $e]
            );
        }
    }

    public static function isActive($imported): bool
    {
        $data = APIBookingDotCom::doRequest(false, 'hotels', ['hotel_ids' => $imported->intCode], 60, 10);

        return ! empty($data->result[0]->hotel_id);
    }

    //

    public static function decodeBookingLinkInfo(string $bookingLinkInfo, string $bookingLinkLocation): array
    {
        $bookingLinkInfo = json_decode($bookingLinkInfo, true);
        $bookingLinkInfo['label'] = "{$bookingLinkLocation}_t_{$bookingLinkInfo['trackingCode']}";
        unset($bookingLinkInfo['trackingCode']);

        $bookingLinkInfo['aid'] = self::DIRECT_LINK_AFFILIATE_ID;

        return [
            'url' => 'https://secure.booking.com/book.html?' . http_build_query($bookingLinkInfo),
        ];
    }

    public static function getDefaultLinkRedirect($bookingLinkLocation): string
    {
        return self::getStaticLinkRedirect(
            'https://www.booking.com/hostels/index.html',
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

    public static function getStaticURL($urlLink, $bookingLinkLocation, $trackingCode): string
    {
        $aid = self::DIRECT_LINK_AFFILIATE_ID;

        $bookingLinkLocation .= '_t_' . $trackingCode;

        return "$urlLink?aid={$aid}&label={$bookingLinkLocation}";
    }

    public static function getImportSystem(): ImportSystems
    {
        return new ImportSystems('BookingDotCom');
    }

    public static function ourLangCodeToImportedCode($lang, $defaultToEnglish = true)
    {
        $result = array_search($lang, self::$LANGUAGE_CODES);
        if ($result === false && $defaultToEnglish) {
            return self::ourLangCodeToImportedCode('en');
        }

        return $result;
    }

    public static function importedLangCodeToOurCode($lang)
    {
        return self::$LANGUAGE_CODES[$lang];
    }
}
