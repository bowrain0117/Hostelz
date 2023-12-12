<?php

/*

To limit the total time of multiple API calls:
        $startTime = time();
        if (time() > $startTime + self::AVAILABILITY_SEARCH_TIME_LIMIT) break; // no more time to do more properties, stop here

*/

namespace App\Booking;

use Illuminate\Support\Collection;

interface BookableImportSystemInterface
{
    /*
        $searchCriteria - has the currency already set (defaulting to the listings' local currency) before this function is called.

        Availability information returned as [ importedID => [ RoomAvailability, RoomAvailability, ... ]
        - Should return [ ] if no results.  Returning null would cause the result to not be cached (not usually what we want to happen).
    */

    public static function getAvailability(Collection $importeds, SearchCriteria $searchCriteria, $requireRoomDetails);

    /*
        This method takes the bookingLinkInfo that was previously saved to the RoomAvailability object,
        and returns the information needed to create a link to the actual booking system's booking page
        by returning an array with 'url' and (optionally) 'postVariables' defined.
    */
    public static function decodeBookingLinkInfo(string $bookingLinkInfo, string $bookingLinkLocation);

    /*
        Returns a BookingRequest
    */

    // public static function getBookingRequest(RoomAvailability $roomAvailability);

    // public static function submitBookingRequest(BookingRequest $bookingRequest, array $formData);

    /*
        $message can be used if there is a message with an explanation that should be passed to the user.
    */

    // public static function cancelBooking($booking, &$message);

    /*
        Some booking systems will call a webhook on our site to notify us when a booking is made.
        URL: https://secure.hostelz.com/booking-notification-webhook/{system}
    */

    // public static function bookingNoticeWebhook($booking, &$message);
}
