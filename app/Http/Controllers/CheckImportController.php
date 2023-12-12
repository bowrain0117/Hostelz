<?php

namespace App\Http\Controllers;

use App\Models\Imported;
use App\Services\ImportSystems\BookHostels\APIBookHostels;
use App\Services\ImportSystems\BookHostels\ImportBookHostels;
use App\Services\ImportSystems\BookingDotCom\APIBookingDotCom;
use App\Services\ImportSystems\BookingDotCom\ImportBookingDotCom;
use App\Services\ImportSystems\Hostelsclub\APIHostelsclub;

class CheckImportController extends Controller
{
    public function getForListing(Imported $imported)
    {
        $result = match ($imported->system) {
            'BookHostels' => $this->getBookHostels($imported),
            'BookingDotCom' => $this->getBooking($imported),
            'Hostelsclub' => $this->getHostelsclub($imported),
            default => "not added yet for '{$imported->system}' OTA"
        };

        return $result;
    }

    private function getBooking($imported)
    {
        $data = null;
        $message = '';

        try {
            $data = APIBookingDotCom::doRequest(false, 'hotels', [
                'hotel_ids' => [$imported->intCode],
                'extras' => 'room_description, key_collection_info, sustainability, hotel_facilities, hotel_policies, hotel_description, room_info, room_facilities, hotel_description_formatted, credit_card_exceptions, hotel_photos, payment_details, hotel_info, room_photos',
            ], 60, 10);
        } catch (\Exception $e) {
            $message = $e->getMessage();
        }

        $result = ! empty($data->result[0]) ? $data->result[0] : [];
        if ($result === []) {
            return view('staff.checkImport.BookingDotCom.listing', compact('result', 'imported', 'message', 'data'));
        }

        $hostelzFeatures = (new ImportBookingDotCom())->getFeaturesFromFacilities($result->hotel_data->hotel_facilities);

        return view('staff.checkImport.BookingDotCom.listing', compact('result', 'imported', 'hostelzFeatures'));
    }

    private function getBookHostels($imported)
    {
        $request = APIBookHostels::doRequest(
            'propertyinformation',
            [
                'PropertyNumber' => $imported->intCode,
            ],
            90,
            4
        );
        if (! $request['success']) {
            $message = $request['error']['message'];
            $result = $request;
            $hostelzFeatures = [];
        } else {
            $result = $request['data'];
            $message = '';
            $hostelzFeatures = (isset($result['facilities'])) ?
                (new ImportBookHostels())->getFeaturesFromFacilities($result['facilities']) :
                [];
        }

        $request = APIBookHostels::doRequest(
            'propertyreviews',
            [
                'PropertyNumber' => $imported->intCode,
                'LimitResults' => 20,
                'MonthCount' => 24,
            ],
            35,
            2
        );
        $reviews = $request;

        return view('staff.checkImport.BookHostels.listing', compact('result', 'imported', 'hostelzFeatures', 'reviews', 'message'));
    }

    private function getHostelsclub(Imported $imported)
    {
        $hostels = APIHostelsclub::getAllHostels();
        $result = $hostels->firstWhere('ID', $imported->intCode);

        return view('staff.checkImport.Hostelsclub.listing', compact('result', 'imported'));
    }
}
