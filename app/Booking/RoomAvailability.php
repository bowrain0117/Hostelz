<?php

namespace App\Booking;

use Exception;
use Illuminate\Support\Facades\Log;
use Lib\Currencies;
use Lib\MultiCurrencyValue;

class RoomAvailability
{
    /** @var SearchCriteria */
    public $searchCriteria;

    /** @var RoomInfo */
    public $roomInfo; // a RoomInfo object

    public $importedID; // the id of our Imported record associated with this room (should this be in the roomInfo instead?)

    public $isInfoAboutTotalAvailability; // whether the blocksAvailable is limited to just our request, or if they tell us the actual total available bed/room count.

    public $hasCompleteRoomDetails; // whether the roomInfo is useful only for availability summaries, or for the complete list of rooms.

    public $bookingLinkInfo; // Info saved about the request that can later be used by the platform's decodeBookingLinkInfo() method to generate the actual booking link.

    public $trackingCode; // Can be used by some bookings systems to let us track the booking and to find which click resulted in the booking.

    public $minimumNightsRequired;

    // An array keyed by the night # (starting from 0 as the first night) => [ 'blocksAvailable', MultiCurrencyValue 'pricePerBlock' ]
    // Some nights may be left empty if no availability for that night.
    public $availabilityEachNight = [];

    private $miscDataCaches = []; // Just used to temporarily store results from methods so they don't have to be generated multiple times

    public function __construct($properties = [])
    {
        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }
    }

    public function averagePricePerBlockPerNight($formattedForDisplay = false, $currency = '')
    {
        if ($currency === '') {
            $currency = $this->searchCriteria->currency;
            if ($currency === '') {
                throw new Exception('No currency set for the searchCriteria.');
            }
        }

        $cacheKey = "averagePricePerBlockPerNight:$currency:$formattedForDisplay";
        if (isset($this->miscDataCaches[$cacheKey])) {
            return $this->miscDataCaches[$cacheKey];
        }

        $priceSum = 0;
        foreach ($this->availabilityEachNight as $availability) {
            $priceSum += $availability['pricePerBlock']->getValue($currency);
        }
        $average = $priceSum / count($this->availabilityEachNight);

        return $this->miscDataCaches[$cacheKey] = ($formattedForDisplay ? Currencies::format($average, $currency) : $average);
    }

    public function totalPrice($formattedForDisplay = false, $currency = '')
    {
        if ($currency === '') {
            $currency = $this->searchCriteria->currency;
            if ($currency === '') {
                throw new Exception('No currency set for the searchCriteria.');
            }
        }

        $cacheKey = "totalPrice:$currency:$formattedForDisplay:" . $this->searchCriteria->people;
        if (isset($this->miscDataCaches[$cacheKey])) {
            return $this->miscDataCaches[$cacheKey];
        }

        $priceSum = 0;
        foreach ($this->availabilityEachNight as $availability) {
            $priceSum += $availability['pricePerBlock']->getValue($currency);
        }

        if ($this->roomInfo->type === RoomInfo::TYPE_DORM) {
            $priceSum *= $this->searchCriteria->people;
        }

        return $this->miscDataCaches[$cacheKey] = ($formattedForDisplay ? Currencies::format($priceSum, $currency) : $priceSum);
    }

    public function numberOfNightsWithFullAvailability()
    {
        $cacheKey = 'numberOfNightsWithFullAvailability';
        if (isset($this->miscDataCaches[$cacheKey])) {
            return $this->miscDataCaches[$cacheKey];
        }

        $nightsWIthFullAvailability = 0;
        for ($night = 0; $night < $this->searchCriteria->nights; $night++) {
            // Note: We decided to only count nights that are available from the start of the reservation,
            // so nights that are available for later dates don't matter if earlier dates aren't available.
            // This probably makes more sense for customers, and simplifies determining their booking dates.
            if (! isset($this->availabilityEachNight[$night])) {
                break;
            }
            if ($this->roomInfo->type === 'private' && $this->availabilityEachNight[$night]['blocksAvailable'] < $this->searchCriteria->rooms) {
                break;
            }
            if ($this->availabilityEachNight[$night]['blocksAvailable'] * $this->roomInfo->peoplePerBookableBlock() < $this->searchCriteria->people) {
                break;
            }
            $nightsWIthFullAvailability++;
        }

        return $this->miscDataCaches[$cacheKey] = $nightsWIthFullAvailability;
    }

    public function maxBlocksAvailableAllNights()
    {
        $cacheKey = 'maxBlocksAvailableAllNights';
        if (isset($this->miscDataCaches[$cacheKey])) {
            return $this->miscDataCaches[$cacheKey];
        }

        $maxBlocksAvailableAllNights = null;
        for ($night = 0; $night < $this->searchCriteria->nights; $night++) {
            if (! isset($this->availabilityEachNight[$night])) {
                $maxBlocksAvailableAllNights = 0;

                break;
            }
            $blocksAvailable = $this->availabilityEachNight[$night]['blocksAvailable'];
            if ($maxBlocksAvailableAllNights === null || $blocksAvailable < $maxBlocksAvailableAllNights) {
                $maxBlocksAvailableAllNights = $blocksAvailable;
            }
        }

        return $this->miscDataCaches[$cacheKey] = $maxBlocksAvailableAllNights;
    }

    public function maxPeopleAvailableAllNights()
    {
        $cacheKey = 'maxPeopleAvailableAllNights';
        if (isset($this->miscDataCaches[$cacheKey])) {
            return $this->miscDataCaches[$cacheKey];
        }

        $maxPeopleAvailableAllNights = null;
        for ($night = 0; $night < $this->searchCriteria->nights; $night++) {
            if (! isset($this->availabilityEachNight[$night])) {
                $maxPeopleAvailableAllNights = 0;

                break;
            }
            $availabilityForPeople = $this->availabilityEachNight[$night]['blocksAvailable'] * $this->roomInfo->peoplePerBookableBlock();
            if ($maxPeopleAvailableAllNights === null || $availabilityForPeople < $maxPeopleAvailableAllNights) {
                $maxPeopleAvailableAllNights = $availabilityForPeople;
            }
        }

        return $this->miscDataCaches[$cacheKey] = $maxPeopleAvailableAllNights;
    }

    public function meetsMinimumNightsRequirement(): bool
    {
        return ! $this->minimumNightsRequired || $this->searchCriteria->nights >= $this->minimumNightsRequired;
    }

    public function hasFullAvailability()
    {
        $cacheKey = 'hasFullAvailability';
        if (isset($this->miscDataCaches[$cacheKey])) {
            return $this->miscDataCaches[$cacheKey];
        }

        return $this->miscDataCaches[$cacheKey] = ($this->numberOfNightsWithFullAvailability() === $this->searchCriteria->nights &&
            $this->meetsMinimumNightsRequirement());
    }

    public function isBetterThan(self $test): bool
    {
        return ($this->hasFullAvailability() && ! $test->hasFullAvailability())
            || $this->averagePricePerBlockPerNight() < $test->averagePricePerBlockPerNight();
    }

    public function hasAvailabilityForEitherAlltheNightsOrAllTheBlocks(): bool
    {
        return $this->numberOfNightsWithFullAvailability() || $this->maxBlocksAvailableAllNights();
    }

    /* Get the associated Imported object. */

    public function imported()
    {
        //  todo: maybe live just $this->imported
        if (isset($this->miscDataCaches['imported'])) {
            return $this->miscDataCaches['imported'];
        }

        return $this->miscDataCaches['imported'] = $this->imported;
    }

    /* Return false if there are errors or missing information.  Also logs a warning if there is. */

    public function isValid(): bool
    {
        $expectedProperties = ['searchCriteria', 'roomInfo', 'importedID', 'imported', 'bookingLinkInfo', 'trackingCode',
            'isInfoAboutTotalAvailability', 'hasCompleteRoomDetails', 'minimumNightsRequired', 'availabilityEachNight', 'miscDataCaches', ];
        if (! arraysHaveEquivalentValues(array_keys(get_object_vars($this)), $expectedProperties)) {
            return $this->logValidationWarning('Has unknown properties added: ' . implode(', ', array_diff(array_keys(get_object_vars($this)), $expectedProperties)));
        }

        if (! $this->searchCriteria) {
            return $this->logValidationWarning('Missing searchCriteria.');
        }
        if ($this->searchCriteria->currency === '') {
            return $this->logValidationWarning('searchCriteria currency not set..');
        }
        if (! $this->roomInfo) {
            return $this->logValidationWarning('Missing roomInfo.');
        }
        if (! $this->roomInfo->isValid()) {
            return false;
        } // (their isValid() will report its own warnings)
        if ($this->roomInfo->type !== $this->searchCriteria->roomType) {
            return $this->logValidationWarning('Room type mismatch.');
        }
        if (! $this->imported()) {
            return $this->logValidationWarning("Can't get imported for importedID '$this->importedID'.");
        }
        if ($this->isInfoAboutTotalAvailability !== true && $this->isInfoAboutTotalAvailability !== false) {
            return $this->logValidationWarning("Unknown value '$this->isInfoAboutTotalAvailability' for isInfoAboutTotalAvailability.");
        }
        if ($this->hasCompleteRoomDetails !== true && $this->hasCompleteRoomDetails !== false) {
            return $this->logValidationWarning("Unknown value '$this->hasCompleteRoomDetails' for hasCompleteRoomDetails.");
        }
        if ($this->minimumNightsRequired < 2) {
            $this->minimumNightsRequired = null;
        }
        if (! $this->availabilityEachNight) {
            return $this->logValidationWarning('availabilityEachNight is empty.');
        }

        foreach ($this->availabilityEachNight as $night => $availability) {
            if ($night !== intval($night)) {
                return $this->logValidationWarning("availabilityEachNight index '$night' isn't a valid integer.");
            }
            if ($night < 0 || $night >= $this->searchCriteria->nights) {
                return $this->logValidationWarning("'$night' is invalid for result of search for " . $this->searchCriteria->nights . ' nights.');
            }
            if (! arraysHaveEquivalentValues(array_keys($availability), ['blocksAvailable', 'pricePerBlock'])) {
                return $this->logValidationWarning('availabilityEachNight element has extra or missing elements: ' . json_encode($availability));
            }
            if (! $availability['pricePerBlock']) {
                return $this->logValidationWarning("pricePerBlock isn't set.");
            }
            if (! is_a($availability['pricePerBlock'], MultiCurrencyValue::class)) {
                return $this->logValidationWarning("pricePerBlock isn't a MultiCurrencyValue.");
            }
            if (! $availability['pricePerBlock']->isValid(false)) {
                return $this->logValidationWarning('pricePerBlock invalid.');
            }
            if (! $availability['blocksAvailable']) {
                return $this->logValidationWarning('availabilityEachNight has zero blocksAvailable value.');
            }
        }

        return true;
    }

    public function isPriceLowerThen(self $other): bool
    {
        return $this->averagePricePerBlockPerNight() < $other->averagePricePerBlockPerNight();
    }

    public function isSystemEqualWith(self $other): bool
    {
        return $this->imported()->system === $other->imported()->system;
    }

    public function bookingPageLink()
    {
        return routeURL(
            'bookings-linkRedirect',
            [
                $this->importedID,
                'b' => urlencode(obfuscateString($this->bookingLinkInfo)),
                't' => $this->trackingCode !== '' ? urlencode(obfuscateString($this->trackingCode)) : '',
            ],
            'protocolRelative'
        );
    }

    private function logValidationWarning($text)
    {
        Log::channel('roomAvailability')->warning("Validation error for imported {$this->importedID}: {$text}");

        return false; // used as the return value of isValid()
    }

    public function getDebugInfo()
    {
        return
            '<div>system: ' . $this->imported()->system . '</div>' .
            '<div>averagePricePerBlockPerNight: ' . $this->averagePricePerBlockPerNight() . '</div>' .
            '<div>hasFullAvailability: ' . $this->hasFullAvailability() . '</div>' .
            '<div>meetsMinimumNightsRequirement: ' . $this->meetsMinimumNightsRequirement() . '</div>' .
            '<div>maxBlocksAvailableAllNights: ' . $this->maxBlocksAvailableAllNights() . '</div>' .
            '<div>numberOfNightsWithFullAvailability: ' . $this->numberOfNightsWithFullAvailability() . '</div>' .
            '<div>hasAvailabilityForEitherAlltheNightsOrAllTheBlocks: ' . $this->hasAvailabilityForEitherAlltheNightsOrAllTheBlocks() . '</div>' .
            '<div>numberOfBookableBlocksRequested(): ' . $this->searchCriteria->numberOfBookableBlocksRequested() . '</div>' .
            $this->roomInfo->getDebugInfo();
    }
}
