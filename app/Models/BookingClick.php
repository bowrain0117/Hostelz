<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Lib\BaseModel;

/*
    Saves information about a user who has clicked a "Book Now" link.

    Only saves the info for a couple days, just long enough to link
    the user to the booking info that we get from the booking system.
 */

class BookingClick extends BaseModel
{
    protected $table = 'bookingClicks';

    public static $staticTable = 'bookingClicks'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    protected $casts = [
        'clickTime' => 'datetime',
    ];

    /* Static */

    public static function recordClick($importedID, $trackingCode = '')
    {
        $origination = isset($_COOKIE[config('custom.originationReferrerCookie')])
            ? (string) $_COOKIE[config('custom.originationReferrerCookie')]
            : '';

        if ($origination !== '' && ! filter_var($origination, FILTER_VALIDATE_URL)) {
            $origination = '';
        }

        $affiliate = isset($_COOKIE[config('custom.affiliateIdCookie')])
            ? (int) $_COOKIE[config('custom.affiliateIdCookie')]
            : 0;

        return self::create([
            'importedID' => $importedID,
            'clickTime' => Carbon::now(),
            'origination' => $origination,
            'affiliateID' => $affiliate,
            'language' => Languages::currentCode(),
            'userID' => (int) auth()->id(),
            'trackingCode' => $trackingCode,
        ]);
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'daily':
                $output .= "\nOptimimize table.\n";
                // This needs to be done daily because it gets large fast if not optimized.
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                $output .= "\nDelete old clicks.\n";
                self::where('clickTime', '<', Carbon::now()->subDays(2))->delete();

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function fillInBookingFieldsFromMatchingClick(Booking $booking, $trackingCode): void
    {
        $matchingClicks = self::where('importedID', $booking->importedID)->where('trackingCode', $trackingCode)->get();
        if ($matchingClicks->isEmpty()) {
            logWarning("Click not found for booking {$booking->system} {$booking->importedID} {$trackingCode}.");

            return;
        }

        if ($matchingClicks->count() > 1) {
            // Multiple clicks, have to find closest time match
            $matchingClicks = $matchingClicks->sort(function ($clickA, $clickB) use ($booking) {
                return $booking->bookingTime->diffInMinutes($clickA->clickTime) - $booking->bookingTime->diffInMinutes($clickB->clickTime);
            });
        }

        $matchingClicks->first()->fillInBookingInfo($booking);
    }

    /* Misc */

    public function fillInBookingInfo(Booking $booking): void
    {
        if ($booking->email == '') {
            $booking->email = $this->email;
        }
        if ($booking->origination == '') {
            $booking->origination = $this->origination;
        }
        if (! $booking->userID) {
            $booking->userID = $this->userID;
        }
        if (! $booking->affiliateID) {
            $booking->affiliateID = $this->affiliateID;
        }
        if ($booking->language == '') {
            $booking->language = $this->language;
        }
    }

    /* Relationships */

    public function imported()
    {
        return $this->belongsTo(\App\Models\Imported::class, 'importedID');
    }
}
