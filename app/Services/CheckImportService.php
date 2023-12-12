<?php

namespace App\Services;

use App\Services\ImportSystems\BookingDotCom\APIBookingDotCom;

class CheckImportService
{
    private function getSystemApi(string $systemName): string
    {
        $api = match ($systemName) {
            'BookingDotCom' => 'APIBookingDotCom',
            'BookHostels' => 'APIBookHostels',
        };

        return $api;
    }

    public function bookingListings()
    {
        return APIBookingDotCom::doRequest(false, 'hotels', [
            'hotel_ids' => [2858160],
            'extras' => 'hotel_info,hotel_photos,hotel_facilities',
            'hotel_type_ids' => 203,
        ], 60, 10);
    }
}
