<?php

namespace App\Services\ImportSystems\HI;

use App\Booking\SearchCriteria;
use App\Services\ImportSystems\ImportSystemServiceInterface;
use Illuminate\Support\Collection;

class HIService implements ImportSystemServiceInterface
{
    public static function import($isTestRun)
    {
        /*
        $featureMap = [
            'Common room(s)' => 'lounge',
            'Individual traveller welcome' => null,
            'Groups welcome' => [ 'goodFor' => 'groups' ],
            'Sports' => null, 'Cycle store at Hostel' => null, 'Sauna' => null, 'Basic store available at or near the hostel' => null, 'Garden' => null, 'Green Hostel' => null, 'Rates include local tax' => null, 'Playground' => null,
            'Male only' => [ 'gender' => 'maleOnly' ],
            'Female only' => [ 'gender' => 'femaleOnly' ],
            'Discounts and concessions available' => null,
            'Family rooms available' => [ 'goodFor' => 'families' ],
            'Breakfast in price' => [ 'breakfast' => 'free' ],
            'Credit card accepted' => 'cc',
            'Café/Bar' => null,
            'Laundry facilities' => [ 'extras' => 'laundry' ],
            'Meals available' => [ 'extras' => 'food' ],
            'Luggage Store' => 'luggageStorage',
            'Non smoking room/area' => null,
            'BBQ' => [ 'extras' => 'bbq' ],
            'Internet access' => null,
            'Sheets in price' =>  [ 'sheets' => 'free' ],
            'Cycle rental available at or near the hostel' => 'bikeRental',
            'Travel/Tour bureau' => [ 'extras' => 'info' ],
            'Lockers available' => 'lockersInCommons',
            'Hostel open 24h' => [ 'curfew' => 'noCurfew' ],
            'Air conditioning' => [ 'extras' => 'ac' ],
            'Currency exchange at or near hostel' => [ 'extras' => 'exchange' ],
            'Lift' => [ 'extras' => 'elevator' ],
            'TV room' => [ 'extras' => 'tv' ],
            'Self-catering kitchen' => 'kitchen',
            'Games room' => [ 'extras' => 'gameroom' ],
            'Sheets for hire' => [ 'sheets' => 'pay' ],
            'Disco' => null,
            'Suitable for wheelchair users' => 'wheelchair',
        ];
        */

        return false;
    }

    public static function updateDataForImported($imported): void
    {
    }

    public static function isActive($imported): void
    {
        logNotice('updateStatusForImported ' . get_class());
    }

    public static function getDefaultLinkRedirect($bookingLinkLocation): string
    {
        logError('getDefaultLinkRedirect');

        return self::getStaticLinkRedirect(
            '',
            $bookingLinkLocation
        );
    }

    public static function getStaticLinkRedirect($urlLink, $bookingLinkLocation, $importedId = null): string
    {
        logError('getStaticLinkRedirect');
        $trackingCode = makeTrackingCode();

        return makeStaticLinkRedirect(
            self::getStaticURL($urlLink, $bookingLinkLocation, $trackingCode),
            'HI',
            $trackingCode,
            $importedId
        );
    }

    public static function getStaticURL($urlLink, $bookingLinkLocation, $trackingCode)
    {
        logError('getStaticURL');

        return $urlLink;
    }

    public static function decodeBookingLinkInfo(string $bookingLinkInfo, string $bookingLinkLocation)
    {
        logError('decodeBookingLinkInfo');

        return '';
    }

    public static function getNextDaysAvailability(Collection $importeds, SearchCriteria $searchCriteria, bool $roomDetails, int $initialNightsValue): array
    {
        return [];
    }
}
