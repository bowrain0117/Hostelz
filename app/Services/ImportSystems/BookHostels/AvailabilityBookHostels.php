<?php

namespace App\Services\ImportSystems\BookHostels;

use App\Booking\RoomAvailability;
use App\Booking\RoomInfo;
use App\Booking\SearchCriteria;
use App\Models\Imported;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Lib\MultiCurrencyValue;

class AvailabilityBookHostels
{
    // (Their docs say the min is 8, but it only works on the api/website if >= 9.)
    public const MIN_PEOPLE_FOR_GROUP_SEARCH = 9;

    public const CHUNK_COUNT = 20;

    public function get($importeds, $searchCriteria): array
    {
        $chunk = $importeds->chunk(self::CHUNK_COUNT);
        $items = APIBookHostels::getAvailabilityForImporteds($chunk, $this->getOptions($searchCriteria));

        $roomAvailabilities = [];
        foreach ($items as $item) {
            $isGroupBooking = false;
            if ($searchCriteria->people > $item->maxPax) {
                if (! $searchCriteria->hasGroupInfo()) {
                    Log::channel('import')->warning("No group booking info in the search criteria, but needed for {$searchCriteria->people}.");

                    continue;
                }
                $isGroupBooking = true;
                //  todo: check groupCriteria
                if (isset($item->groupCriteria) && ! self::isGroupAcceptable($searchCriteria, $item->groupCriteria)) {
                    continue;
                }
            }

            $imported = $importeds->firstWhere('intCode', $item->number);
            $itemArray = json_decode(json_encode($item), true);
            $roomAvailabilities[] = self::getRoomAvailabilities($itemArray, $imported, $searchCriteria, $isGroupBooking);
        }

        return array_merge(...$roomAvailabilities);
    }

    public function getOptions($searchCriteria): array
    {
        $options = [
            'DateStart' => $searchCriteria->startDate->format('Y-m-d'),
            'DateEnd' => $searchCriteria->getEndDate()->format('Y-m-d'),
            'Currency' => self::isSupportedCurrency($searchCriteria->currency) ? $searchCriteria->currency : 'USD',
            'Language' => self::ourLangCodeToImportedCode($searchCriteria->language),
            // 1 - all available, 2 - only fully bookable, 4 - longDescription, 64 - price breakdown, 128 - labelDescription
            'ShowRoomTypeInfo' => 1,
        ];

        if ($searchCriteria->people >= self::MIN_PEOPLE_FOR_GROUP_SEARCH) {
            $options += [
                'GroupSearch' => true,
                'GroupType' => self::convertToTheirGroupType($searchCriteria->groupType),
                'GroupAgeRanges' => implode(',', self::convertToTheirGroupAgeRanges($searchCriteria->groupAgeRanges)),
                'GroupSize' => $searchCriteria->people,
            ];
        }

        return $options;
    }

    private static function isGroupAcceptable($searchCriteria, $groupCriteria): bool
    {
        logNotice('isGroupAcceptable ' . json_encode($groupCriteria));

        if ($searchCriteria->people > $groupCriteria['groupMaxPax']) {
            return false;
        }
        if (! in_array(self::convertToTheirGroupType($searchCriteria->groupType), array_flip($groupCriteria['allowedGroupTypes']))) {
            return false;
        }
        if (array_diff(self::convertToTheirGroupAgeRanges($searchCriteria->groupAgeRanges), array_flip($groupCriteria['allowedGroupAgeRanges']))) {
            return false;
        }

        return true;
    }

    private static function getRoomAvailabilities($listingData, Imported $imported, SearchCriteria $searchCriteria, $isGroupBooking): array
    {
        if (! $listingData['roomTypes']) {
            return [];
        } // probably just means no availability found

        $roomAvailabilities = [];

        foreach ($listingData['roomTypes'] as $roomData) {
            // * RoomInfo *

            $codeParts = self::parseRoomCode($roomData['code']);
            if (! $codeParts) {
                continue;
            } // error already reported by parseRoomCode()

            $roomInfo = new RoomInfo([
                'code' => (string) $roomData['code'],
                'name' => $roomData['description'],
                'ensuite' => (bool) $codeParts['isEnsuite'],
                'peoplePerRoom' => $codeParts['peoplePerRoom'],
                'type' => $codeParts['type'] === 'priv' ? 'private' : 'dorm',
                // (no longer provided) 'description' => mb_trim($roomData['longDescription'] . ' ' . $roomData['labelDescription'])
            ]);

            if ($roomInfo->type !== $searchCriteria->roomType) {
                continue;
            }

            if ($roomInfo->type === 'private') {
                if (strpos($codeParts['description'], 'DblX') === 0) {
                    $roomInfo->bedsPerRoom = 1;
                } // Double
                elseif (strpos($roomData['code'], 'x1_Private_') === 0) {
                    $roomInfo->bedsPerRoom = 1;
                } // Single
                elseif (strpos($roomData['code'], 'x2_Private_') === 0) {
                    $roomInfo->bedsPerRoom = 2;
                } // Twin (two beds)
                else {
                    $roomInfo->bedsPerRoom = $roomInfo->peoplePerRoom;
                } // This applies to apparently all of the other types
            } else {
                $roomInfo->bedsPerRoom = $roomInfo->peoplePerRoom;
            }

            if (strpos($codeParts['description'], 'Female') === 0) {
                $roomInfo->sex = 'female';
            } elseif (strpos($codeParts['description'], 'Male') === 0) {
                $roomInfo->sex = 'male';
            } else {
                $roomInfo->sex = 'mixed';
            }

            if (! $roomData['availability'] || ! is_array($roomData['availability'])) {
                Log::channel('import')->warning("'Availability' missing.");

                continue;
            }

            $maxNights = ($isGroupBooking && isset($listingData['groupCriteria']['groupMaxNights'])) ?
                $listingData['groupCriteria']['groupMaxNights'] : $listingData['maxNights'];

            $nights = [];
            foreach ($roomData['availability'] as $date => $nightMultiples) {
                $nightNum = Carbon::createFromFormat('Y-m-d', $date)->diff($searchCriteria->startDate)->days;

                // They may return nights that are before/after our requested nights, or more than their max.
                if ($nightNum >= $searchCriteria->nights || $nightNum < 0 || $nightNum >= $maxNights) {
                    continue;
                }

                // each night is in a an array because each night could have multiple prices for the same room types.
                // (each element has its own bed count and price)
                // We sum the available beds and use the highest price (probably the safest method).
                if (! is_array($nightMultiples)) {
                    Log::channel('import')->warning('Day not in a sub-array.');

                    continue;
                }
                $night = ['beds' => 0, 'price' => 0, 'currency' => ''];
                foreach ($nightMultiples as $nightMultiple) {
                    if (is_array($nightMultiple['price'])) {
                        if (count($nightMultiple['price']) !== 1) {
                            Log::channel('import')->warning('Unknown price format: ' . serialize($nightMultiple['price']));

                            continue;
                        }
                        /* Probably a bug in BookHostel's system, but for propertylocationsearch results it's returning "[price] => Array ( [USD] => 28.00 )", so we fix it. */
                        $nightMultiple['currency'] = key($nightMultiple['price']);
                        $nightMultiple['price'] = $nightMultiple['price'][$nightMultiple['currency']];
                    }
                    $night['beds'] += $nightMultiple['beds'];
                    if ($night['price'] === 0 || $nightMultiple['price'] < $night['price']) {
                        $night['price'] = $nightMultiple['price'];
                    }
                    if ($night['currency'] !== '' && $nightMultiple['currency'] !== $night['currency']) {
                        Log::channel('import')->warning('Multiple currencies in night multiples: ' . serialize($nightMultiple));

                        continue 2;
                    }
                    $night['currency'] = $nightMultiple['currency'];
                }

                if (! $night['beds'] || ! $night['price'] || $night['currency'] === '') {
                    // (Some of their results have $0 prices and other missing info)
                    Log::channel('import')->warning('Values missing from night: ' . serialize($night));

                    continue 2;
                }

                if ($night['beds'] < 1) {
                    continue;
                } // not sure if this ever happens, but check anyway

                $blocksAvailable = ($roomInfo->type === 'private' ? $night['beds'] / $roomInfo->peoplePerRoom : $night['beds']);

                if ($blocksAvailable !== (int) $blocksAvailable) {
                    // they say they're going to fix this, is it fixed now? (not fixed as of 7/2016)
                    // logWarning("$night[beds]/$codeParts[peoplePerRoom] isn't a whole number ($searchCriteria->startDate for imported $imported->id '$roomData[code]').");
                    $blocksAvailable = floor($blocksAvailable);
                    if (! $blocksAvailable) {
                        continue;
                    }
                }

                $nights[$nightNum] = [
                    'blocksAvailable' => $blocksAvailable,
                    'pricePerBlock' => new MultiCurrencyValue([$night['currency'] => (float) $night['price'] * $roomInfo->peoplePerBookableBlock()]),
                ];
            }
            if (count($nights) === 0) {
                continue;
            } // may happen if the only availability was the night before/after

            $trackingCode = makeTrackingCode();

            $roomAvailabilities[] = new RoomAvailability([
                'searchCriteria' => $searchCriteria,
                'importedID' => $imported->id,
                'imported' => $imported,
                'roomInfo' => $roomInfo,
                // It would be nice if we could jump straight to the avaialbility page with the room code, but
                // it's probably too slow to call their propertylinks for each room type. Tried just appending
                // that info, but didn't seem to work.
                'bookingLinkInfo' => self::encodeBookingLinkInfo($imported, $searchCriteria, $roomInfo->code, $trackingCode),
                'isInfoAboutTotalAvailability' => true,
                'hasCompleteRoomDetails' => true,
                'minimumNightsRequired' => $isGroupBooking && isset($listingData['groupCriteria']['groupMinNights']) ?
                    $listingData['groupCriteria']['groupMinNights'] : $listingData['minNights'],
                'availabilityEachNight' => $nights,
                'trackingCode' => $trackingCode,
            ]);
        }

        return $roomAvailabilities;
    }

    private static function convertToTheirGroupType($groupType): int
    {
        $conversion = ['friends' => 1, 'juniorSchool' => 2, 'highSchool' => 3, 'college' => 4, 'business' => 5, 'party' => 6, 'sports' => 7, 'cultural' => 8];

        return $conversion[$groupType];
    }

    private static function convertToTheirGroupAgeRanges($ageRanges): array
    {
        return array_map(function ($age) {
            $conversion = ['0to12' => 1, '13to17' => 2, '18to21' => 3, '22to35' => 4, '36to49' => 5, '50plus' => 6];

            return $conversion[$age];
        }, $ageRanges);
    }

    private static function parseRoomCode($code)
    {
        /* roomtype code: 'x<beds>_<roomDescription>_<ensuite>_<roomLabel1>_<roomLabel2>'

        Format Explained:
    		<beds> - number of *people* in each room (not nec. the # of beds)
    		<roomDescription> - a short description of the type of room. (e.g. private, shared, etc...)
    		<ensuite> - en-suite [1], or not [0]
    		<roomLabel1> - information on the room type (e.g. basic, standard, deluxe, etc..)
    		<roomLabel2> - optional (tent, appartment, family room, etc.)
    	*/
        if (strpos($code, 'x') !== 0) {
            Log::channel('import')->warning("'$code' doesn't start with an 'x'.");

            return false;
        }
        $codeParts = explode('_', substr($code, 1));
        if (count($codeParts) !== 6) {
            Log::channel('import')->warning("'{$code}' code not 6 parts.");

            return false;
        }

        if (strpos($codeParts[1], 'Private') !== false) {
            $type = 'priv';
        } elseif (strpos($codeParts[1], 'Dorm') !== false) {
            $type = 'dorm';
        } else {
            Log::channel('import')->warning("Not Private or Dorm in code '{$code}'.");

            return false;
        }

        return ['peoplePerRoom' => (int) $codeParts[0], 'description' => $codeParts[1], 'isEnsuite' => (bool) $codeParts[2], 'type' => $type];
    }

    /*
    Note: We could pass $roomInfo->code to this function and use that to pass to HostelWorld's propertylinks method as "RoomPreference1",
    but it would probably be too slow to do that for each room type.  So instead we just link to the checkout URL without setting the room type.
*/
    private static function encodeBookingLinkInfo(Imported $imported, SearchCriteria $searchCriteria, $roomCode, $trackingCode)
    {
        return json_encode([
            'lang' => self::ourLangCodeToImportedCode($searchCriteria->language),
            'propertyNumber' => $imported->intCode,
            'startDate' => $searchCriteria->startDate->format('Y-m-d'),
            'night' => $searchCriteria->nights,
            'people' => $searchCriteria->people,
            'roomCode' => $roomCode,
            'tracingCode' => $trackingCode,
        ]);
    }

    public static function ourLangCodeToImportedCode($lang, $defaultToEnglish = true)
    {
        $result = array_search($lang, BookHostelsService::$LANGUAGE_CODES);
        if ($result === false && $defaultToEnglish) {
            return self::ourLangCodeToImportedCode('en');
        }

        return $result;
    }

    // * Currency *

    private static function isSupportedCurrency($currency): bool
    {
        $supportedCurrencies = cache()->tags('imported')->get('BookHostels:supportedCurrencies');

        if ($supportedCurrencies) {
            $supportedCurrencies = unserialize($supportedCurrencies);
        } else {
            $result = APIBookHostels::doRequest('currencies', [], 30, 2);
            if (! $result['success'] || ! is_array($result['data'])) {
                Log::channel('import')->error('BookHostels Currencies() failed. response ' . json_encode($result));

                return false;
            }
            $supportedCurrencies = array_keys($result['data']);
            cache()->tags('imported')->put('BookHostels:supportedCurrencies', serialize($supportedCurrencies), 24 * 60 * 15 * 60);
        }

        return in_array($currency, $supportedCurrencies);
    }
}
