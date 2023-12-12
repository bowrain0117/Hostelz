<?php

/*

This is information that is used to generate the booking form, and then submitted to the submitBookingRequest() method (along with the form data).

*/

namespace App\Booking;

use Cache;

class BookingRequest
{
    public $bookingSubmitStatus; // '', 'waiting', 'success', 'failed'

    public $trackingCode;

    // Errors/warnings
    public $warnings; // array of [ 'code' (optional), 'message' (optional) ]

    public $fatalError; // [ 'code' (optional), 'message' (optional) ]

    public $localCurrency;

    public $billingCurrency;

    // MultiCurrencyValue values. Any of these are optional (and will be calculated automatically)
    public $totalPriceBeforeFees;

    public $taxes;

    public $deposit;

    public $bookingFee;

    public $chargeTotal;

    public $remainingDueAtArrival;

    public $depositPercent;

    public $nationalityOptions;

    public $terms;

    public $creditCardOptions; // array of [ name, typeCode, reqIssueNum, reqStartDate ] (may contain other misc values for use when passed back to the booking site)

    public $tokenForBookingSystemUse;

    // An array keyed by the night # (starting from 0 as the first night) => [ 'roomInfo' (RoomInfo object), 'count' (optional, just for dorms), 'price' ]
    // Some nights may be left empty if no availability for that night.
    public $roomsEachNight;

    public const CACHE_MINUTES = 60 * 60; // ( in second ) pretty long so that it will still show them the status of their completed booking if they reload the page a long time later.

    // For booking systems that don't provide an API function to help us creat the booking request form, this function simply uses the availData to create the bookingDetails data.

    public function fillInRoomsEachNightFromAvailability(RoomAvailability $roomAvailability): void
    {
        if (! $roomAvailability->isValid()) {
            return;
        }

        $searchCriteria = $roomAvailability->searchCriteria;

        $this->roomsEachNight = [];

        foreach ($roomAvailability->availabilityEachNight as $nightNumber => $nightAvailability) {
            if ($roomAvailability->roomInfo->type == 'dorm') {
                $rooms = 1; // We display dorm beds as multiple people in one room
                $count = min($searchCriteria->people, $nightAvailability['blocksAvailable']);
                $price = with(clone $nightAvailability['pricePerBlock'])->multiplyBy($count);
            } else { // Private Room
                $rooms = min($searchCriteria->rooms, $nightAvailability['blocksAvailable']);
                $count = null; // not used for private rooms
                $price = $nightAvailability['pricePerBlock'];
            }

            $this->roomsEachNight[] = array_fill(0, $rooms, ['roomInfo' => $roomAvailability->roomInfo, 'count' => $count, 'price' => $price]);
        }
    }

    /*
        Fill in any data that wasn't supplied based on other known information.
        Also sets $fatalError[] and $warnings[] if errors or warnings.
    */

    public function fillInMissingDataAndValidate(RoomAvailability $roomAvailability): void
    {
        if ($this->fatalError) {
            return;
        } // already has an error, just abort.

        /*foreach ($roomAvailability->availabilityEachNight as $nightNumber => $availability) {
        if ($roomAvailability->roomInfo->type == 'dorm') {
            if ($searchCriteria->people > $availability['blocksAvailable']) $this->addWarning('partialAvail');
        } else { // Private Room
            if ($availability['blocksAvailable'] < $searchCriteria->rooms) {
        if ($rooms * $roomAvailability->roomInfo->peoplePerRoom > $searchCriteria['people']) $this->addWarning('tooManyPeopleForRooms')

        logWarning() if a 'count' value is set for a private room

        call isValid() on each night's roomInfo?
       */
    }

    /*
        We use these functions to store/retrieve the BookingRequest so it can be re-used if the user mis-filled out the booking form,
        or if the info is useful for when the booking is submitted.
    */

    public function saveToCache()
    {
        if ($this->trackingCode == '') {
            $this->trackingCode = uniqid();
        }
        Cache::put('BookingRequest:' . $this->trackingCode, $this, self::CACHE_MINUTES);

        return $this->trackingCode;
    }

    public static function getFromCache($trackingCode)
    {
        return Cache::get('BookingRequest:' . $trackingCode);
    }

    private function addWarning($code, $message = ''): void
    {
        $toAdd = compact('code', 'message');
        if (! in_array($toAdd, $this->warnings)) {
            $this->warnings[] = $toAdd;
        }
    }
}
