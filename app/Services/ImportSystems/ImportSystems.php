<?php

namespace App\Services\ImportSystems;

use App\Services\ImportSystems\BookHostels\BookHostelsService;
use App\Services\ImportSystems\BookingDotCom\BookingDotComService;
use App\Services\ImportSystems\HI\HIService;
use App\Services\ImportSystems\Hostelsclub\HostelsclubService;
use Exception;

class ImportSystems
{
    // preferred is set true for systems we like (free booking, high affiliate percent)
    // geocodingTrustability - higher is better, may be negative.  our own values are "0".
    // propertyTypeAccuracy - On a scale of 0-10.
    // preventDisplayingThisSystemAsLowestPrice - If true, will show any other system instead if the other system has full availability, even if their price is more.
    // bookingPriority - Used if multiple systems have the same price.  Higher number is higher priority.
    // specialCallbacks - 'hourlyMaintenance', 'dailyMaintenance', 'emailFilter' to automatically call the class' hourlyMaintenance(), etc. methods.

    private static $systems = [
        'BookHostels' => [
            'isActive' => true, 'displayName' => 'Hostelworld/Hostels.com', 'shortName' => 'Hostelworld' /* optional */,
            'onlineBooking' => true, 'bookingPriority' => 91,
            'preventDisplayingThisSystemAsLowestPrice' => false, 'preventDisplayingThisSystemAsHigherPrice' => false,
            'isPreferredBookingSystem' => true,
            'qualifiesListingForOnlineBookingStatus' => true,
            'cancellationMethod' => 'directEmail', 'specialCallbacks' => ['hourlyMaintenance', 'dailyMaintenance'],
            'propertyTypeAccuracy' => 7, 'geocodingTrustability' => 19,
            'multipleRatingSites' => ['Hostelworld', 'Hostels.com'], 'displayRatings' => true, 'alwaysShowInRatingsList' => true,
            'newListingStatus' => 'ok',
            'invoiceAddress' => ['Hostelworld.com Limited', 'One Central Park', 'Leopardstown', 'Dublin 18', 'Ireland'],
            'invoicePassword' => 'abc123',
            // (keep HW/HB emails in a different field [importedEmail] since we aren't allowed to use their contact info to contact hostels)
            'listingFieldForImportedEmails' => 'importedEmail',
            'customerSupportEmail' => 'customerservice@hostelworld.com',
            'affiliatePortal' => 'https://phgconsole.performancehorizon.com/login/hostelworld/en', // previously was 'http://affiliates.bookhostels.com/'
        ],
        'BookingDotCom' => [
            'isActive' => true, 'displayName' => 'Booking.com', 'shortName' => '' /* optional */,
            'onlineBooking' => true, 'bookingPriority' => 90,
            'preventDisplayingThisSystemAsLowestPrice' => false, 'preventDisplayingThisSystemAsHigherPrice' => false,
            'isPreferredBookingSystem' => true,
            'qualifiesListingForOnlineBookingStatus' => true,
            'cancellationMethod' => 'API', 'specialCallbacks' => ['hourlyMaintenance', 'dailyMaintenance'],
            'propertyTypeAccuracy' => 0, 'geocodingTrustability' => 20, // (guessing their geocoding is good?)
            'multipleRatingSites' => false, 'displayRatings' => true, 'alwaysShowInRatingsList' => true,
            'newListingStatus' => 'ok',
            'listingFieldForImportedEmails' => 'supportEmail',
            'customerSupportEmail' => '',
            'affiliatePortal' => 'https://admin.booking.com/partner/',
        ],
        'Hostelsclub' => [
            'isActive' => true, 'displayName' => 'HostelsClub', 'shortName' => '' /* optional */,
            'onlineBooking' => true, 'bookingPriority' => 20, // lower priority because they have a booking fee, send payments slowly, and not as good a user experience as HW, etc.
            'preventDisplayingThisSystemAsLowestPrice' => false, 'preventDisplayingThisSystemAsHigherPrice' => false,
            'isPreferredBookingSystem' => false,
            'qualifiesListingForOnlineBookingStatus' => false, // Because listings in Hostelsclub are often actually not active.
            'cancellationMethod' => 'API', 'specialCallbacks' => ['emailFilter'],
            'propertyTypeAccuracy' => 7, 'geocodingTrustability' => -200,
            'multipleRatingSites' => false, 'displayRatings' => true, 'alwaysShowInRatingsList' => true,
            'newListingStatus' => 'ok',
            'invoiceAddress' => ['Hostelsclub.com', 'Mo.lo.ra. Srl', 'San Polo 1890', '30125 – Venezia', 'VAT: 03172780276'],
            'invoicePassword' => 'wiig0ab4',
            'listingFieldForImportedEmails' => 'supportEmail',
            'customerSupportEmail' => 'info@hostelsclub.com',
            'affiliatePortal' => 'https://www.hostelspoint.com/affiliate/index.php',
        ],
        'HI' => [
            'isActive' => false, 'displayName' => 'HI Hostels', 'shortName' => '' /* optional */,
            'onlineBooking' => false, 'preventDisplayingThisSystemAsLowestPrice' => false, 'preventDisplayingThisSystemAsHigherPrice' => false,
            'isPreferredBookingSystem' => false,
            'qualifiesListingForOnlineBookingStatus' => false,
            'cancellationMethod' => null, 'specialCallbacks' => [],
            'propertyTypeAccuracy' => 7, 'geocodingTrustability' => -300, /* not sure yet how good their geocoding is. */
            'displayRatings' => true,
            'newListingStatus' => 'ok',
            'listingFieldForImportedEmails' => 'supportEmail',
        ],

        'Hostelbookers' => [
            'isActive' => false, 'displayName' => 'HostelBookers', 'shortName' => '' /* optional */,
            'onlineBooking' => false /* so we still show their logo on booking pages for now */, 'bookingPriority' => 10,
            'preventDisplayingThisSystemAsLowestPrice' => false, 'preventDisplayingThisSystemAsHigherPrice' => false,
            'isPreferredBookingSystem' => false,
            'qualifiesListingForOnlineBookingStatus' => false,
            'cancellationMethod' => 'directEmail', 'specialCallbacks' => [],
            'propertyTypeAccuracy' => 4, 'geocodingTrustability' => -300,
            'multipleRatingSites' => false, 'displayRatings' => false, 'alwaysShowInRatingsList' => false,
            'newListingStatus' => 'ok',
            'listingFieldForImportedEmails' => 'importedEmail',
            'customerSupportEmail' => 'customerservice@hostelbookers.com',
            'affiliatePortal' => 'http://www.hostelbookers.com/affiliates',
        ],
        'Gomio' => [
            // Setting isActive and onlineBooking to false because we haven't made any bookings through them in a long time, probably doesn't even work
            'isActive' => false, 'displayName' => 'Gomio', 'shortName' => '' /* optional */,
            'onlineBooking' => false, 'bookingPriority' => 70,
            'preventDisplayingThisSystemAsLowestPrice' => false, 'preventDisplayingThisSystemAsHigherPrice' => false,
            'isPreferredBookingSystem' => false,
            'qualifiesListingForOnlineBookingStatus' => false, // Because listings in Gomio are often actually not active.
            'cancellationMethod' => 'directEmail', 'specialCallbacks' => [],
            'propertyTypeAccuracy' => 4, 'geocodingTrustability' => -300,
            'multipleRatingSites' => false, 'displayRatings' => false, 'alwaysShowInRatingsList' => false,
            'newListingStatus' => 'ok',
            'invoiceAddress' => ['Gomio.com', 'Calle Pau Clarís 162, 4º 1ª', '08037 Barcelona, Spain', 'Tax ID ES-B65065278'],
            'invoicePassword' => 'g37Gidb4',
            'listingFieldForImportedEmails' => 'supportEmail',
            'customerSupportEmail' => 'info@gomio.com',
            'affiliatePortal' => 'http://www.gomio.com/AffiliateManagement/Login.aspx',
        ],
        'Yelp' => [
            'isActive' => false, 'displayName' => 'Yelp', 'shortName' => '' /* optional */,
            'onlineBooking' => false,
            'preventDisplayingThisSystemAsLowestPrice' => false, 'preventDisplayingThisSystemAsHigherPrice' => false,
            'isPreferredBookingSystem' => false,
            'qualifiesListingForOnlineBookingStatus' => false,
            'cancellationMethod' => null, 'specialCallbacks' => [],
            'propertyTypeAccuracy' => 0, 'geocodingTrustability' => -300,
            'multipleRatingSites' => false, 'displayRatings' => false, 'alwaysShowInRatingsList' => false,
            'newListingStatus' => null,
            'listingFieldForImportedEmails' => 'supportEmail',
            'customerSupportEmail' => '',
            'affiliatePortal' => null,
        ],
    ];

    // This isn't really within the scope of ImportSystems, but this is the trustability rating
    // of other sources of geocoding (relative to the geocodingTrustability scores above).
    public static $OTHER_GEO_TRUSTABILITY = [
        // Note that geocdedRooftop takes precidence over owner lat/long. If this is an issue for some listings,
        // set mapAddress for the listing to "disregard geocoding" or anything that isn't the address.
        'owner' => 16, 'geocodedinterpolated' => 15, 'geocodedRooftop' => 18,
    ];

    public $systemName;

    public $systemInfo;

    public function __construct($systemName)
    {
        $this->systemName = $systemName;
        $this->systemInfo = self::$systems[$systemName];
    }

    public function shortName()
    {
        return $this->systemInfo['shortName'] !== '' ? $this->systemInfo['shortName'] : $this->systemInfo['displayName'];
    }

    public function getSystemService(): ImportSystemServiceInterface
    {
        return match ($this->systemName) {
            'BookHostels' => new BookHostelsService(),
            'BookingDotCom' => new BookingDotComService(),
            'Hostelsclub' => new HostelsclubService(),
            'HI' => new HIService(),
            default => throw new Exception('ImportSystem: no system found for - ' . $this->systemName)
        };
    }

    public function image($type = 'logo')
    {
        return routeURL('images', 'systems/' . $this->systemName . ' - logo.png');
    }

    // So we can get info directly such as $importSystem->isActive, etc.

    public function __get($name)
    {
        return $this->systemInfo[$name] ?? null;
    }

    // * Static Methods (repository) *

    public static function systemExists($systemName): bool
    {
        return array_key_exists((string) $systemName, self::$systems);
    }

    public static function findByName($systemName): self
    {
        static $instances = [];

        if (! self::systemExists($systemName)) {
            throw new Exception("Unknown system '$systemName'.");
        }
        if (! isset($instances[$systemName])) {
            $instances[$systemName] = new static($systemName);
        }

        return $instances[$systemName];
    }

    // Returns objects for all systems, or systems where $field is $value (or contains $value in an array).

    public static function all($field = null, $value = true): array
    {
        $return = [];
        foreach (self::$systems as $systemName => $info) {
            if ($field !== null) {
                if ((is_array($info[$field]) && ! in_array($value, $info[$field])) ||
                    (! is_array($info[$field]) && $info[$field] !== $value)) {
                    continue;
                }
            }
            $return[$systemName] = self::findByName($systemName);
        }

        return $return;
    }

    public static function allActive(): array
    {
        return self::all('isActive');
    }

    public static function allActiveSystemsName(): array
    {
        return array_keys(self::all('isActive'));
    }

    public static function allNamesKeyedByDisplayName(): array
    {
        $return = [];
        foreach (self::$systems as $systemName => $info) {
            $return[$info['displayName']] = $systemName;
        }

        return $return;
    }

    public static function allDisplayNames(): array
    {
        return array_flip(self::allNamesKeyedByDisplayName());
    }

    public static function maintenanceTasks($timePeriod): string
    {
        $output = '';

        switch ($timePeriod) {
            case 'hourly':
            case 'daily':
                $callBack = $timePeriod . 'Maintenance';
                foreach (self::all('specialCallbacks', $callBack) as $systemName => $system) {
                    $output .= "\nspecialCallbacks '$systemName::$callBack': " . call_user_func([$system->getSystemService(), $callBack]) . "\n";
                }

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }
}
