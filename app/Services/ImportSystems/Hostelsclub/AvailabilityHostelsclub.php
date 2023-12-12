<?php

namespace App\Services\ImportSystems\Hostelsclub;

use App\Booking\BookingService;
use App\Booking\RoomAvailability;
use App\Booking\RoomInfo;
use App\Booking\SearchCriteria;
use App\Models\Imported;
use Illuminate\Support\Collection;
use Lib\MultiCurrencyValue;

class AvailabilityHostelsclub
{
    public function get($importeds, $searchCriteria, $requireRoomDetails)
    {
        if (! $requireRoomDetails) {
            [$cityToSearch, $importedsToSearch] = BookingService::determineCityAndImportedsToSearch($importeds);
        } else {
            $cityToSearch = null;
            $importedsToSearch = $importeds;
        }

        if ($cityToSearch) {
            $roomAvailabilities = self::getAvailabilityForCity($cityToSearch, $importeds, $searchCriteria);
        } else {
            $roomAvailabilities = [];
        }

        foreach ($importedsToSearch as $imported) {
            $data = APIHostelsclub::doXmlRequest(
                'OTA_HotelAvailRQ',
                '<AvailRequestSegments><AvailRequestSegment><HotelSearchCriteria><Criterion>' .
                                                           '<StayDateRange Start="' . $searchCriteria->startDate->format('Y-m-d') . '" Duration="P' . $searchCriteria->nights . 'N" />' .
                                                           '<HotelRef HotelCode="' . $imported->intCode . '" /></Criterion></HotelSearchCriteria>' .
                                                           '</AvailRequestSegment></AvailRequestSegments>',
                self::ourLangCodeToImportedCode($searchCriteria->language),
                'RequestedCurrency="' . $searchCriteria->currency . '"',
                12
            );

            if (! $data || ! isset($data->RoomStays->RoomStay)) {
                continue;
            } // errors should already have been reported. probably just no availability.

            foreach ($data->RoomStays->RoomStay as $roomData) {
                $roomTypeData = $roomData->RoomTypes->RoomType;

                $roomInfo = new RoomInfo([
                    'code' => (string) $roomTypeData['RoomTypeCode'],
                    'name' => (string) $roomTypeData->RoomDescription->Text,
                    'peoplePerRoom' => (int) $roomTypeData->Occupancy->attributes()->MinOccupancy,
                    'type' => strpos($roomTypeData['RoomTypeCode'], '1_') === 0 ? 'private' : 'dorm',
                ]);

                if ($roomInfo->type != $searchCriteria->roomType) {
                    continue;
                }

                /* Todo: Make this also work with other languages. */
                if (stripos($roomTypeData->RoomDescription->Text, 'shared bath') !== false ||
                    stripos($roomTypeData->RoomDescription->Text, 'shared WC') !== false) {
                    $roomInfo->ensuite = false;
                } elseif (stripos($roomTypeData->RoomDescription->Text, 'with bath') !== false ||
                          stripos($roomTypeData->RoomDescription->Text, 'with WC') !== false ||
                          stripos($roomTypeData->RoomDescription->Text, 'With Private Bathroom') !== false) {
                    $roomInfo->ensuite = true;
                } else {
                    $roomInfo->ensuite = null;
                } // unknown

                if (stripos($roomTypeData->RoomDescription->Text, 'female') !== false) {
                    $roomInfo->sex = 'female';
                } elseif (stripos($roomTypeData->RoomDescription->Text, 'male') !== false) {
                    $roomInfo->sex = 'male';
                } elseif (stripos($roomTypeData->RoomDescription->Text, 'mixed') !== false) {
                    $roomInfo->sex = 'mixed';
                } else {
                    $roomInfo->sex = null;
                } // unknown

                if ($roomInfo->type === 'private') {
                    switch ($searchCriteria->language) {
                        case 'es':
                            $phrases = ['double' => 'cama matrimonial', 'single' => 'single bed', 'beds' => '`(\d+) cama/s`'];

                            break;
                        case 'it':
                            $phrases = ['double' => 'letto matrimoniale', 'single' => 'single bed', 'beds' => '`(\d+) letto/i`'];

                            break;
                        case 'fr':
                            $phrases = ['double' => 'lit à deux places', 'single' => 'single bed', 'beds' => '`(\d+) lit/s`'];

                            break;
                        case 'ja':
                            $phrases = ['double' => 'ダブルベッド', 'single' => 'single bed', 'beds' => '`(\d+) ベッド`'];

                            break;
                        case 'de':
                            $phrases = ['double' => 'Doppelbett', 'single' => 'single bed', 'beds' => '`(\d+) Bett/en`'];

                            break;
                        case 'pt-br':
                            $phrases = ['double' => 'cama de casal banheiro', 'single' => 'single', 'beds' => '`(\d+) cama\(s\)/s`'];

                            break;
                        default:
                            $phrases = ['double' => 'double bed', 'single' => 'single bed', 'beds' => '`(\d+) bed/s`'];
                    }
                    if (strpos($roomTypeData->RoomDescription->Text, $phrases['double']) !== false) {
                        $roomInfo->bedsPerRoom = 1;
                    }
                    /*else if (strpos($roomTypeData->RoomDescription->Text, 'Twin')!==false)
    					$roomInfo->bedsPerRoom = 2;
    				else if (strpos($roomTypeData->RoomDescription->Text, 'Triple')!==false)
    					$roomInfo->bedsPerRoom = 3;
    				*/
                    elseif (strpos($roomTypeData->RoomDescription->Text, $phrases['single']) !== false) {
                        $roomInfo->bedsPerRoom = 1;
                    } elseif (preg_match($phrases['beds'], $roomTypeData->RoomDescription->Text, $matches)) {
                        $roomInfo->bedsPerRoom = $roomInfo->peoplePerRoom;
                    } else {
                        logWarning('Unknown bed count for private type: ' . $roomTypeData->RoomDescription->Text . " (language:$searchCriteria->language)");
                        $roomInfo->bedsPerRoom = 1;
                    }
                } else { // dorm
                    $roomInfo->bedsPerRoom = $roomInfo->peoplePerRoom;
                }
                if (isset($roomTypeData->TimeSpan)) {
                    $resStartTime = mktime(0, 0, 0, $month, $day, $year);
                    $startingDayNum = round((strtotime((string) $roomTypeData->TimeSpan->attributes()->Start) - $resStartTime) / (60 * 60 * 24));
                    $endingDayNum = round((strtotime((string) $roomTypeData->TimeSpan->attributes()->End) - $resStartTime) / (60 * 60 * 24));
                } else { // if TimeSpan doesn't exist then all nights are available.
                    $startingDayNum = 0;
                    $endingDayNum = $searchCriteria->nights - 1;
                }

                $nights = [];

                $currency = (string) $roomData->RoomRates->RoomRate->Rates->Rate->Base['CurrencyCode'];
                $price = (float) $roomData->RoomRates->RoomRate->Rates->Rate->Base['AmountBeforeTax'] * $roomInfo->peoplePerBookableBlock();

                for ($nightNum = 0; $nightNum < $searchCriteria->nights; $nightNum++) {
                    if ($nightNum >= $startingDayNum && $nightNum <= $endingDayNum) {
                        /* "The amountBeforeTax is for all purposes a price after tax.
    					There are a few properties in Holland who will charge an extra tourist tax and
    					don't want to factor in the tax in their standard prices,
    					that's the only reason I've chosen "amountBeforeTax" as a label for the prices." */
                        $nights[$nightNum] = [
                            'blocksAvailable' => (int) $roomTypeData['NumberOfUnits'],
                            'pricePerBlock' => new MultiCurrencyValue([$currency => $price]),
                        ];
                    }
                }

                $roomAvailabilities[] = new RoomAvailability([
                    'searchCriteria' => $searchCriteria,
                    'importedID' => $imported->id,
                    'imported' => $imported,
                    'roomInfo' => $roomInfo,

                    // http://www.hostelsclub.com/hostel-en-40270.html?aff_ID=77&scroll=true&checkin=2022-06-10&checkout=2022-06-16&guests=2&room_type=0&view_mode=details&currency=EUR&order=price
                    'bookingLinkInfo' => http_build_query([
                        'property_id' => $imported->intCode,
                        'checkin' => $searchCriteria->startDate->format('Y-m-d'),
                        'checkout' => $searchCriteria->getEndDate()->format('Y-m-d'),
                        'guests' => $searchCriteria->people,
                        'room_type' => $searchCriteria->roomType === 'private' ? 1 : 2,
                        'currency' => $searchCriteria->currency,
                        'scroll' => 'true',
                        'view_mode2' => 'details',
                        'view_mode' => 'details',
                        'order' => 'price',
                        'lang' => self::ourLangCodeToImportedCode($searchCriteria->language),
                    ]),
                    'availabilityEachNight' => $nights,
                    'isInfoAboutTotalAvailability' => true,
                    'hasCompleteRoomDetails' => true, // the API method we used doesn't give us complete room type info, not useful for hostel room type details
                ]);
            }
        }

        return $roomAvailabilities;
    }

    private static function getAvailabilityForCity(Imported $cityToSearch, Collection $importeds, SearchCriteria $searchCriteria): array
    {
        // This uses Hostelclub's new JSON API method

        $data = APIHostelsclub::doJsonRequest('AvailSearchCity', [
            'day' => $searchCriteria->startDate->format('d'),
            'year_month' => $searchCriteria->startDate->format('Y-m'),
            'nights' => $searchCriteria->nights,
            'currency' => $searchCriteria->currency,
            'cat' => 0,
            'room_type' => $searchCriteria->roomType === 'private' ? 1 : 2,
            'guests' => 1, // just tell them 1, and we'll use whatever availaility info is relevant
            'city' => $cityToSearch->theirCityCode,
            'lang' => self::ourLangCodeToImportedCode($searchCriteria->language),
        ], 15);

        if (! $data || ! isset($data['response']) || ! isset($data['response']['properties'])) {
            return [];
        } // errors should already have been reported

        if (isset($data['errors'][0]['code']) && $data['errors'][0]['code'] == 402) {
            return [];
        } // "No availability found in this city."

        $roomAvailabilities = [];

        foreach ($data['response']['properties'] as $propertyCode => $property) {
            $imported = $importeds->where('intCode', $propertyCode)->first();
            if (! $imported) {
                continue;
            } // probably just means they returned results for other listings in the city

            foreach ($property['rooms'] as $room) {
                $roomInfo = new RoomInfo([
                    'code' => (string) $room['roomCode'],
                    'name' => $room['name'],
                    'ensuite' => $room['wc'] ? true : false,
                    'peoplePerRoom' => $room['beds'], // not very accurate, but doesn't matter for the city summary
                    'type' => $room['type'] == 1 ? 'private' : 'dorm',
                    'bedsPerRoom' => $room['beds'],
                ]);

                if (! isset($room['gender'])) {
                    $roomInfo->sex = null;
                } // not specified
                elseif ($room['gender'] == 0) {
                    $roomInfo->sex = 'mixed';
                } elseif ($room['gender'] == 1) {
                    $roomInfo->sex = 'male';
                } elseif ($room['gender'] == 2) {
                    $roomInfo->sex = 'female';
                }

                $price = filter_var($room['price'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

                $nights = [];
                for ($nightNum = 0; $nightNum < $searchCriteria->nights; $nightNum++) {
                    $nights[$nightNum] = [
                        'blocksAvailable' => $room['availability'],
                        'pricePerBlock' => new MultiCurrencyValue([$property['currency'] => (float) $price * $roomInfo->peoplePerBookableBlock()]),
                    ];
                }

                $roomAvailabilities[] = new RoomAvailability([
                    'searchCriteria' => $searchCriteria,
                    'importedID' => $imported->id,
                    'imported' => $imported,
                    'roomInfo' => $roomInfo,
                    // http://www.hostelsclub.com/hostel-en-40270.html?aff_ID=77&scroll=true&checkin=2022-06-10&checkout=2022-06-16&guests=2&room_type=0&view_mode=details&currency=EUR&order=price
                    'bookingLinkInfo' => http_build_query([
                        'property_id' => $imported->intCode,
                        'checkin' => $searchCriteria->startDate->format('Y-m-d'),
                        'checkout' => $searchCriteria->getEndDate()->format('Y-m-d'),
                        'guests' => $searchCriteria->people,
                        'room_type' => $searchCriteria->roomType === 'private' ? 1 : 2,
                        'currency' => $searchCriteria->currency,
                        'scroll' => 'true',
                        'view_mode' => 'details',
                        'order' => 'price',
                        'lang' => self::ourLangCodeToImportedCode($searchCriteria->language),
                    ]),
                    'isInfoAboutTotalAvailability' => true,
                    'hasCompleteRoomDetails' => false, // the API method we used doesn't give us complete room type info, not useful for hostel room type details
                    'availabilityEachNight' => $nights,
                ]);
            }
        }

        return $roomAvailabilities;
    }

    public static function ourLangCodeToImportedCode($lang, $defaultToEnglish = true)
    {
        $result = array_search($lang, HostelsclubService::$LANGUAGE_MAP);
        if ($result === false && $defaultToEnglish) {
            return self::ourLangCodeToImportedCode('en');
        }

        return $result;
    }

    public static function theirCurrencyCode($currency, $defaultTo = 'USD'): int
    {
        // Currency codes from their spreadsheet
        $currencyCodes = [
            'EUR' => 1, 'USD' => 2, 'AED' => 3, 'AFA' => 4, 'ALL' => 5, 'ANG' => 6, 'AON' => 7, 'ARS' => 8, 'AUD' => 9, 'BDT' => 10, 'BGL' => 11, 'BHD' => 12, 'BIF' => 13, 'BMD' => 14, 'BND' => 15, 'BOB' => 16, 'BRL' => 17, 'BTN' => 18, 'BWP' => 19, 'CAD' => 20, 'CHF' => 21, 'CLP' => 22, 'CNY' => 23, 'COP' => 24, 'CRC' => 25, 'CUP' => 26, 'CVE' => 27, 'CYP' => 28, 'CZK' => 29, 'DKK' => 30, 'DOP' => 31, 'DZD' => 32, 'ECS' => 33, 'EEK' => 34, 'EGP' => 35, 'ETB' => 36, 'FJD' => 37, 'GBP' => 38, 'GHC' => 39, 'GMD' => 40, 'GNF' => 41, 'GTQ' => 42, 'GYD' => 43, 'HKD' => 44, 'HNL' => 45, 'HRK' => 46, 'HTG' => 47, 'HUF' => 48, 'IDR' => 49, 'ILS' => 50, 'INR' => 51, 'IRR' => 52, 'ISK' => 53, 'JMD' => 54, 'JOD' => 55, 'JPY' => 56, 'KES' => 57, 'KHR' => 58, 'KMF' => 59, 'KPW' => 60, 'KRW' => 61, 'KWD' => 62, 'KZT' => 63, 'LAK' => 64, 'LBP' => 65, 'LKR' => 66, 'LTL' => 67, 'LVL' => 68, 'LYD' => 69, 'MAD' => 70, 'MGF' => 71, 'MMK' => 72, 'MNT' => 73, 'MOP' => 74, 'MRO' => 75, 'MTL' => 76, 'MUR' => 77, 'MVR' => 78, 'MWK' => 79, 'MXN' => 80, 'MYR' => 81, 'MZM' => 82, 'NAD' => 83, 'NGN' => 84, 'NIO' => 85, 'NOK' => 86, 'NPR' => 87, 'NZD' => 88, 'PAB' => 89, 'PEN' => 90, 'PGK' => 91, 'PHP' => 92, 'PKR' => 93, 'PLN' => 94, 'PYG' => 95, 'QAR' => 96, 'ROL' => 97, 'RUR' => 98, 'SAR' => 99, 'SBD' => 100, 'SCR' => 101, 'SDP' => 102, 'SEK' => 103, 'SGD' => 104, 'SHP' => 105, 'SIT' => 106, 'SKK' => 107, 'SLL' => 108, 'SVC' => 109, 'SZL' => 110, 'THB' => 111, 'TND' => 112, 'TOP' => 113, 'TRL' => 114, 'TTD' => 115, 'TWD' => 116, 'TZS' => 117, 'UAH' => 118, 'UGX' => 119, 'UYU' => 120, 'VEB' => 121, 'VND' => 122, 'VUV' => 123, 'WST' => 124, 'XAF' => 125, 'XOF' => 126, 'CSD' => 127, 'ZAR' => 128, 'ZMK' => 129, 'ZWD' => 130, 'TRY' => 131, 'RON' => 132,
        ];

        $result = $currencyCodes[$currency] ?? '';
        if (! $result && $defaultTo != '') {
            return self::theirCurrencyCode($defaultTo);
        }

        return $result;
    }
}
