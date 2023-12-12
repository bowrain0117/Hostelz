<?php

namespace App\Services\ImportSystems\BookingDotCom;

use App\Booking\RoomAvailability;
use App\Booking\RoomInfo;
use App\Booking\SearchCriteria;
use App\Models\Geonames;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Lib\MultiCurrencyValue;
use Stevebauman\Location\Facades\Location;

class AvailabilityBookingDotCom
{
    public const ITEMS_COUNT_IN_ONE_POOL = 20;

    public function get($importeds, SearchCriteria $searchCriteria, $withRoomDetails): array
    {
        try {
            $blockAvailabilities = $this->apiCall(
                $importeds,
                $searchCriteria,
                $withRoomDetails
            );
        } catch (\Exception $e) {
            Log::channel('import')->error('booking.com getAvailability error:' . $e->getMessage());

            return [];
        }

        if (! $blockAvailabilities) {
            return [];
        }

        return $this->prepare($blockAvailabilities, $importeds, $searchCriteria, $withRoomDetails);
    }

    public function apiCall($importeds, $searchCriteria, $withRoomDetails)
    {
        $guestCountryCode = self::getUserCountryCode($searchCriteria->userIp, $importeds);

        return APIBookingDotCom::doBlockAvailabilityReqest(
            $this->getOptions($searchCriteria, $guestCountryCode, $withRoomDetails),
            $importeds->chunk(self::ITEMS_COUNT_IN_ONE_POOL)
        );
    }

    public function getOptions($searchCriteria, $guestCountryCode, $withRoomDetails): array
    {
        // Specify extra info we want them to return
        $extras = [
            'room_type_id', // to get room_type_id to used to determine dorm vs private
            'number_of_rooms_left',
            'facilities',
            //            'all_extra_charges',
            //            'extra_charges'
        ];

        if ($withRoomDetails) {
            $extras[] = 'additional_room_info';
        } // for "bed_configurations" info

        $options = [
            'checkin' => $searchCriteria->startDate->format('Y-m-d'),
            'checkout' => $searchCriteria->getEndDate()->format('Y-m-d'),
            'detail_level' => $withRoomDetails ? 1 : 0, // needed to enable all the other detailed info
            'language' => BookingDotComService::ourLangCodeToImportedCode($searchCriteria->language),
            'currency' => $searchCriteria->currency,
            'guest_cc' => $guestCountryCode,
            //            'guest_ip' => request()->ip() ?? '',
            'guest_qty' => $searchCriteria->people,
            'extras' => implode(',', $extras),
        ];

        return $options;
    }

    public function prepare($blockAvailabilitys, $importeds, SearchCriteria $searchCriteria, $withRoomDetails): array
    {
        $roomAvailabilities = [];

        foreach ($blockAvailabilitys as $listingData) {
            $imported = $importeds->firstWhere('intCode', $listingData->hotel_id);
            if (! $imported) {
                Log::channel('import')->warning("Got data for unknown imported with code '" . $listingData->hotel_id . "'.");

                continue;
            }

            foreach ($listingData->block as $roomData) {
                $phrases = self::getPhrases($searchCriteria->language);
                $roomInfo = self::getRoomInfo($roomData, $searchCriteria, $phrases, $withRoomDetails);
                if ($roomInfo === null) {
                    continue;
                }

                $isNonrefundableRoom = self::isNonrefundableRoom($roomInfo->name, $phrases['nonrefundable']);
                if ($isNonrefundableRoom) {
                    // For "non-refundable" rooms, we remove that part of the name so that it merges with the
                    // equivalent refundable room.  But when they click the link to book it, we take them to the
                    // page where they can choose the room and there there can decide if they want refundable or not.
                    $roomInfo->name = str_replace($phrases['nonrefundable'], '', $roomInfo->name);
                }

                $roomAvailability = $this->getAvailability(
                    $roomData,
                    $searchCriteria,
                    $imported,
                    $roomInfo,
                    $isNonrefundableRoom,
                    $withRoomDetails
                );

                $roomAvailabilities[] = $roomAvailability;
            }
        }

        return $roomAvailabilities;
    }

    public static function getRoomInfo(object $roomData, SearchCriteria $searchCriteria, $phrases, $withRoomDetails): ?RoomInfo
    {
        $roomInfo = new RoomInfo([
            'code' => $roomData->block_id,
            'name' => $roomData->name,
            'roomSurface' => $roomData->room_surface_in_m2 ?? null,
        ]);

        $roomInfo->type = self::getRoomType($roomData, $roomInfo, $phrases['dorm']);

        if ($roomInfo->type !== $searchCriteria->roomType) {
            return null;
        }

        // peoplePerRoom / bedsPerRoom

        if ($roomInfo->type === 'dorm') {
            if ($num = self::matchRoomStrings($roomInfo->name, $phrases['beds'], true)) {
                $roomInfo->bedsPerRoom = $roomInfo->peoplePerRoom = $num;
            } else {
                $roomInfo->bedsPerRoom = $roomInfo->peoplePerRoom = 1;
            }
        } else {
            $roomInfo->peoplePerRoom = $roomData->max_occupancy ?? 1;
            if ($withRoomDetails && isset($roomData->bed_configurations)) {
                // There can be multiple bed_configurations per room. We just use the first useable one (sometimes the first one is empty)
                foreach ($roomData->bed_configurations as $bedConfigurations) {
                    if (isset($bedConfigurations->bed_types[0]->count)) {
                        $roomInfo->bedsPerRoom = $bedConfigurations->bed_types[0]->count;

                        break;
                    }
                }
                if (! $roomInfo->bedsPerRoom) { // TEMP - to find what listings were causing an error by not having this
                    Log::channel('import')->warning("No bed_types for '" . $roomData->name . "' " . $searchCriteria->summaryForDebugOutput());

                    return null;
                }
            } else {
                // We don't have bed_configurations, so we have to improvise.
                // Use https://distribution-xml.booking.com/xml/bookings.getRoomTypes?languagecodes=en to see the current room types.
                // (Only including room types where the number of beds per room is known.)
                // room_type_id => bedsPerRoom
                $roomIdMap = [
                    4 => 4, 7 => 3, 8 => 2, 9 => 1, 10 => 1, 23 => 1, 26 => 1,
                ];
                $roomInfo->bedsPerRoom = $roomIdMap[$roomData->room_type_id] ?? $roomInfo->peoplePerRoom;
            }
            if ($roomInfo->bedsPerRoom > $roomInfo->peoplePerRoom) {
                // if ($withRoomDetails) logError("bedsPerRoom ($roomInfo->bedsPerRoom) > peoplePerRoom ($roomInfo->peoplePerRoom) " .
                //    "for '$roomInfo->name' for $imported->hostelID ".$searchCriteria->summaryForDebugOutput());
                // This happens some times with some types of rooms
                $roomInfo->bedsPerRoom = $roomInfo->peoplePerRoom;
            }
        }

        $roomInfo->sex = self::getSex($roomInfo->name, $phrases);

        // Ensuite
        // Note: Some listings use "Shared Bathroom" even if it's ensuite in a dorm room,
        // so this may not be the best way to handle this.  May need to go by the title instead for dorm rooms.
        if ($withRoomDetails) {
            if (! isset($roomData->facilities)) {
                Log::channel('import')->warning("No facilities for '$roomInfo->name' " . $searchCriteria->summaryForDebugOutput());

                return null;
            }

            $phrases['notEnsuite'] = array_map('strtolower', $phrases['notEnsuite']);
            $phrases['isEnsuite'] = array_map('strtolower', $phrases['isEnsuite']);
            $facilities = array_map('strtolower', $roomData->facilities);

            if (self::arrayContainsAnyOf($phrases['notEnsuite'], $facilities)) {
                $roomInfo->ensuite = false;
            } elseif (self::arrayContainsAnyOf($phrases['isEnsuite'], $facilities)) {
                $roomInfo->ensuite = true;
            }
            // else
            //     logError("Unknown ensuite status for facilities ".json_encode($roomData->facilities)."."); // (temp until we figure out what facilities are usually used)
        }

        return $roomInfo;
    }

    private static function getUserCountryCode(?string $ip, Collection $importeds)
    {
        $userIpData = Location::get($ip);
        if ($userIpData) {
            return $userIpData->countryCode;
        }

        return Geonames::findCountry($importeds->first()->country)->countryCode;
    }

    public static function matchRoomStrings($roomName, $strings, $grepForNumberOfPeople = false)
    {
        if (! is_array($strings)) {
            $strings = [$strings];
        }
        foreach ($strings as $string) {
            if ($grepForNumberOfPeople) {
                if (preg_match($string, $roomName, $matches)) {
                    return (int) $matches[1];
                }
            } elseif (stripos($roomName, $string) !== false) {
                return true;
            }
        }

        return false;
    }

    private static function arrayContainsAnyOf($find, $array): bool
    {
        if (is_array($find)) {
            foreach ($find as $findElement) {
                if (self::arrayContainsAnyOf($findElement, $array)) {
                    return true;
                }
            }

            return false;
        }

        return in_array($find, $array);
    }

    public static function getSex(string $name, array $phrases): string
    {
        if (containsWord($name, $phrases['male'])) {
            return 'male';
        }

        if (containsWord($name, $phrases['female'])) {
            return 'female';
        }

        return 'mixed';
    }

    public static function getPhrases(string $language = ''): array
    {
        switch ($language) {
            // Note: The values can be arrays if multiple words can be used for a similar type of room.
            // (Some of these aren't really translated yet.)
            //case 'es':
            //case 'it':
            case 'fr':
                $phrases = [
                    'dorm' => 'Dortoir',
                    'nonrefundable' => ' - Non-refundable',
                    'male' => 'male',
                    'female' => 'female',
                    'mixed' => ['mixte', 'mixtes', 'co-ed'],
                    'isEnsuite' => ['salle de bains'],
                    'notEnsuite' => ['Salle de bains commune', 'Toilettes communes'],
                    'beds' => ['`(\d+) Lit`'],
                ];

                break;
                //case 'de':
                //case 'pt-br':
            default:
                $phrases = [
                    'dorm' => 'Dorm',
                    'nonrefundable' => ' - Non-refundable',
                    'male' => ['male', 'boy', 'boys'],
                    'female' => ['female', 'girl', 'girls', 'woman', 'women'],
                    'mixed' => ['mixed', 'co-ed'],
                    'isEnsuite' => ['Bathroom', 'Shower', 'Bath', 'Private bathroom'],
                    'notEnsuite' => ['Shared Bathroom'/*, 'Shared Toilet' */],
                    'beds' => ['`(\d+)-\s*[Bb]ed`'],
                ];
        }

        return $phrases;
    }

    public static function isNonrefundableRoom(string $roomInfoName, string $nonrefundable): bool
    {
        return str_contains($roomInfoName, $nonrefundable);
    }

    public static function getRoomType(object $roomData, RoomInfo $roomInfo, $phrasesDorm): string
    {
        // (The room_type_id isn't always accurate, so we also check the name for "dorm" or "dormitory".)

        return (int) $roomData->room_type_id === 26 ||
        str_contains($roomInfo->name, $phrasesDorm) ? 'dorm' : 'private';
    }

    private function getAvailability(
        mixed $roomData,
                 $searchCriteria,
        mixed $imported,
        RoomInfo $roomInfo,
        bool $isNonrefundableRoom,
                 $withRoomDetails
    ): RoomAvailability {
        $blocksAvailable = $roomData->number_of_rooms_left; /* alternative method: count($roomData->incremental_price) */
        $blocksToRequest = $searchCriteria->numberOfBookableBlocksRequested();
        if ($blocksToRequest > $blocksAvailable) {
            $blocksToRequest = $blocksAvailable;
        }

        $trackingCode = makeTrackingCode();
        $roomAvailability = new RoomAvailability([
            'searchCriteria' => $searchCriteria,
            'importedID' => $imported->id,
            'imported' => $imported,
            'roomInfo' => $roomInfo,
            'bookingLinkInfo' => json_encode(
                $this->getBookingLinkInfo(
                    $imported,
                    $searchCriteria,
                    $trackingCode,
                    $isNonrefundableRoom,
                    $roomData,
                    $blocksToRequest)
            ),
            'trackingCode' => $trackingCode,
            'isInfoAboutTotalAvailability' => true,
            // (means the info they give us tells us about availability beyond the quantity we requested)
            'hasCompleteRoomDetails' => $withRoomDetails,
        ]);

        // pricePerBlock
        $priceValues = [
            $roomData->min_price->currency => $roomData->min_price->price / $searchCriteria->nights,
        ];
        if (isset($roomData->min_price->other_currency)) {
            $priceValues[$roomData->min_price->other_currency->currency] = $roomData->min_price->other_currency->price / $searchCriteria->nights;
        }
        $pricePerBlock = new MultiCurrencyValue($priceValues);

        // Nights
        for ($night = 0; $night < $searchCriteria->nights; $night++) {
            $roomAvailability->availabilityEachNight[$night] = compact('blocksAvailable', 'pricePerBlock');
        }

        return $roomAvailability;
    }

    private function getBookingLinkInfo($imported, $searchCriteria, $trackingCode, $isNonrefundableRoom, $roomData, $blocksToRequest)
    {
        $linkInfo = [
            'hotel_id' => $imported->intCode,
            'checkin' => urlencode($searchCriteria->startDate->format('Y-m-d')),
            'interval' => $searchCriteria->nights,
            'selected_currency' => $searchCriteria->currency,
            'trackingCode' => urlencode($trackingCode),

            'stage' => '1',
            'nr_rooms_' . $roomData->block_id => $blocksToRequest,
        ];

        /*        if ($isNonrefundableRoom) {
                    $linkInfo += [
                        'stage' => '0'
                    ];
                } else {
                    $linkInfo += [
                        'stage' => '1',
                        'nr_rooms_' . $roomData->block_id => $blocksToRequest,
                    ];
                }*/

        return $linkInfo;
    }
}
