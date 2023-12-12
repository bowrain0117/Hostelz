<?php

namespace App\Booking;

use App\Jobs\RecordPriceListings;
use App\Models\Listing\Listing;
use App\Services\AsynchronousExecution;
use App\Services\ImportSystems\ImportSystems;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Lib\Currencies;

class BookingService
{
    public const LISTING_AVAILABILITY_CACHE_MINUTES = 15 * 60; // in seconds

    public const MORE_RATIO = 3;

    public const LISTING_PAGE = 'single_compare';

    public const ENSUITE = 'ensuite';

    private static array $minimumNightsAlert = [];

    private static Collection $roomTypes;

    private static string $roomKey = '';

    /*
        Returns an array composed of: listingID => [ importedID => [ RoomAvailability, RoomAvailability, ... ], ... ]
        (Or if $listingOrListings is just one listing object, it returns just the result for that listing.)
    */

    public static function getAvailabilityForListing(Listing $listing, SearchCriteria $searchCriteria, $requireRoomDetails, $bookingLinkLocation = '')
    {
        $results = self::getAvailabilityForListings([$listing], $searchCriteria, $requireRoomDetails, $bookingLinkLocation);

        return $results ? $results[$listing->id] : [];
    }

    public static function getAvailabilityForListings($listings, SearchCriteria $searchCriteria, $requireRoomDetails, $bookingLinkLocation = ''): array
    {
        $availabilityByListingID = $cachedAvailabilityByListingID = [];

        $useCache = config('custom.bookingAvailabilityCache') && ! config('custom.bookingDebugOutput');

        // For each listing, check for cached results and set up $importedIdsBySystem and $importedIdToListingIdMap arrays.

        $importedIdsBySystem = $listingIdsToCache = [];

        foreach ($listings as $listing) {
            if ($useCache) {
                $cached = self::getCachedListingAvailability($listing->id, $searchCriteria, $requireRoomDetails);
                if ($cached !== null) {
                    $cachedAvailabilityByListingID[$listing->id] = $cached;

                    continue;
                }
            }

            foreach ($listing->activeImporteds as $imported) {
                if (! $imported->getImportSystem()->onlineBooking) {
                    continue;
                }
                if (! in_array($listing->id, $listingIdsToCache)) {
                    $listingIdsToCache[] = $listing->id;
                }

                if (! isset($importedIdsBySystem[$imported->system])) {
                    $importedIdsBySystem[$imported->system] = collect();
                }
                $importedIdsBySystem[$imported->system]->push($imported);
                $importedIdToListingIdMap[$imported->id] = $listing->id;
            }
        }

        if ($importedIdsBySystem) {
            // Prep & Execute bookingAvailability Commands

            // Set the SearchCriteria's currency (which defaults to the local currency)
            $searchCriteriaWithCurrency = clone $searchCriteria;
            if ($searchCriteriaWithCurrency->currency === '') {
                $localCurrency = (is_array($listings) ? reset($listings)->determineLocalCurrency() :
                    $listings->first()->determineLocalCurrency());
                $searchCriteriaWithCurrency->currency = Currencies::defaultCurrency($localCurrency);
            }

            $serializedSearchCriteria = escapeshellarg(serialize($searchCriteriaWithCurrency));
            $commands = [];

            if (config('custom.bookingUseAyncConsoleCommands') && ! config('custom.bookingDebugOutput')) {
                // Async console commands (faster)

                foreach ($importedIdsBySystem as $systemName => $importeds) {
                    $commands[$systemName] = "hostelz:bookingAvailability $systemName " . implode(',', $importeds->pluck('id')->all()) . ' ' . $serializedSearchCriteria . ' ' .
                        ($requireRoomDetails ? '1' : '0') . ' ' . $bookingLinkLocation;
                }

                $results = AsynchronousExecution::executeArtisanCommandsInParallel(
                    $commands
                );

                if ($bookingLinkLocation === self::LISTING_PAGE) {
                    foreach ($results as $systemName => &$result) {
                        if (empty($result)) {
                            $systemClassName = ImportSystems::findByName($systemName)->getSystemService();
                            $result = $systemClassName::getNextDaysAvailability($importedIdsBySystem[$systemName], $searchCriteriaWithCurrency, $requireRoomDetails, $searchCriteriaWithCurrency->nights);
                        }

                        if (! empty($result) && isset($result['minimumNightsAlert'])) {
                            self::$minimumNightsAlert[$systemName] = $result['minimumNightsAlert'];
                            unset($result['minimumNightsAlert']);
                        }
                    }
                    unset($result);
                }
            } else {
                // Direct mode (slower, easier to test)
                $results = [];
                foreach ($importedIdsBySystem as $systemName => $importeds) {
                    $systemClassName = ImportSystems::findByName($systemName)->getSystemService();
                    $results[$systemName] = $systemClassName::getAvailability($importeds, $searchCriteriaWithCurrency, $requireRoomDetails, $bookingLinkLocation);
                    if (empty($results[$systemName])) {
                        $results[$systemName] = $systemClassName::getNextDaysAvailability($importeds, $searchCriteriaWithCurrency, $requireRoomDetails, $searchCriteriaWithCurrency->nights);
                    }

                    if (! empty($results[$systemName]) && isset($results[$systemName]['minimumNightsAlert'])) {
                        self::$minimumNightsAlert[$systemName] = $results[$systemName]['minimumNightsAlert'];
                        unset($results[$systemName]['minimumNightsAlert']);
                    }
                }
            }

            // Collate the results by listingID.

            foreach ($results as $systemName => $roomAvailabilities) {
                foreach ($roomAvailabilities as $roomAvailability) {
                    if (array_key_exists($systemName, self::$minimumNightsAlert)) {
                        $listingID = $importedIdToListingIdMap[$roomAvailability->importedID];
                        $availabilityByListingID[$listingID][] = $roomAvailability;
                        continue;
                    }
                    if (! $roomAvailability->isValid()) {
                        continue;
                    } // (isValid() reports its own warnings)
                    if ($requireRoomDetails && ! $roomAvailability->hasCompleteRoomDetails) {
                        logError("requireRoomDetails set, but $systemName returned an availability without hasCompleteRoomDetails.");

                        continue;
                    }
                    if (! $roomAvailability->hasAvailabilityForEitherAlltheNightsOrAllTheBlocks()) {
                        continue;
                    } // not enough availability to bother displaying
                    $listingID = $importedIdToListingIdMap[$roomAvailability->importedID];
                    $availabilityByListingID[$listingID][] = $roomAvailability;
                }
            }

            if ($useCache) {
                foreach ($listingIdsToCache as $listingID) {
                    $availabilityInfo = $availabilityByListingID[$listingID] ?? null;
                    if (! $availabilityInfo) {
                        $availabilityInfo = [];
                    } // if no results were returned, we still want to cache an empty array in the cache
                    self::saveListingAvailabilityToCache($listingID, $searchCriteria, $availabilityInfo);
                }
            }

            if (! empty($availabilityByListingID)) {
                RecordPriceListings::dispatch($listingIdsToCache, $availabilityByListingID, $searchCriteria);
            }
        }

        if (config('custom.debugOutput')) {
            if (isset($commands)) {
                debugOutput('getAvailabilityForListings: Commands: ' . json_encode($commands));
            }
            debugOutput('getAvailabilityForListings: Was cached: ' . json_encode($cachedAvailabilityByListingID));
            debugOutput('getAvailabilityForListings: Wasn\'t cached: ' . json_encode($availabilityByListingID));
        }

        return $availabilityByListingID + $cachedAvailabilityByListingID;
    }

    public static function getMinimumStay(): array
    {
        return self::$minimumNightsAlert;
    }

    /*
        For booking systems that only let you search for availability by city but not multiple properties at once,
        this determines how to best search for availablity with a combination of a location search and multiple listing searches.
        - Assumes $importeds higher up in the list are higher priority.
        - $importeds must have theirCityCode values set.

        Returns: Array [ importedInTheCity (Imported), importedsNotInTheCity (Collection) ]
    */

    public static function determineCityAndImportedsToSearch(Collection $importeds, $maxImportedsNotInTheCity = 5)
    {
        if ($importeds->count() <= 2) {
            return [null /* city */, $importeds /* importedsNotInTheCity */];
        }

        // Find the most common city code
        $cityCodeCounts = [];
        foreach ($importeds as $imported) {
            if (! $imported->theirCityCode) {
                // logError("Zero value for theirCityCode for $imported->id.");
                continue;
            }
            $cityCodeCounts[$imported->theirCityCode] = ($cityCodeCounts[$imported->theirCityCode] ?? null) + 1;
        }
        arsort($cityCodeCounts, SORT_NUMERIC);
        $countOfImportedsInMostCommonCity = reset($cityCodeCounts);
        $mostCommonCityCode = key($cityCodeCounts);

        $importedInTheCity = null;
        if ($countOfImportedsInMostCommonCity <= 1) {
            // Too few of them are in the most common city, so just do individual searches on each listing instead
            $importedsNotInTheCity = $importeds;
        } else {
            // Get set of importeds not in the city
            $importedsNotInTheCity = new Collection();
            foreach ($importeds as $imported) {
                if ($imported->theirCityCode == $mostCommonCityCode) {
                    $importedInTheCity = $imported;
                } // (we really only need one, just used for the city info)
                else {
                    $importedsNotInTheCity->push($imported);
                }
            }
        }

        if ($importedsNotInTheCity->count() > $maxImportedsNotInTheCity) {
            $importedsNotInTheCity = $importedsNotInTheCity->take($maxImportedsNotInTheCity);
        }

        return [$importedInTheCity, $importedsNotInTheCity];
    }

    // Generate data for the list of rooms a user sees when looking at the room types for a listing.

    public static function formatAvailableRoomsForDisplay(array $roomAvailabilities)
    {
        $formattedRoomAvailabilities = self::mergeEquivalentRoomTypes($roomAvailabilities);

        // Don't bother showing partial availability rooms if there are at least a couple with full availability

        $primaryRoomAvailabilities = array_column($formattedRoomAvailabilities, 'primary');
        if (self::countOfAvailabilitiesHasFullAvailability($primaryRoomAvailabilities) >= 2) {
            $formattedRoomAvailabilities = array_filter($formattedRoomAvailabilities, function ($roomPrices) {
                return $roomPrices['primary']->hasFullAvailability();
            });
        }

        // Set otherPrices, etc.

        foreach ($formattedRoomAvailabilities as &$formattedAvailability) {
            // Remove any "preventDisplayingThisSystemAsHigherPrice" systems from otherPrices
            $primaryPrice = $formattedAvailability['primary']->averagePricePerBlockPerNight();
            $formattedAvailability['otherPrices'] = array_filter(
                $formattedAvailability['otherPrices'],
                function ($otherPrice) use ($primaryPrice) {
                    if (! $otherPrice->imported()->getImportSystem()->preventDisplayingThisSystemAsHigherPrice) {
                        return true;
                    } // always include it if we don't have to remove it
                    // We make sure the price is actually higher (because it's ok to display if it's exactly equal)
                    if ($otherPrice->averagePricePerBlockPerNight() > $primaryPrice) {
                        return false;
                    }

                    return true;
                }
            );

            // Add list of systemsNotUsed
            $systemsUsed = [$formattedAvailability['primary']->imported()->system];
            foreach ($formattedAvailability['otherPrices'] as $otherPrice) {
                $systemsUsed[] = $otherPrice->imported()->system;
            }

            $formattedAvailability['systemsNotUsed'] = array_diff_key(ImportSystems::all('onlineBooking'), array_flip($systemsUsed));

            // Savings %
            $highestPrice = null;
            foreach ($formattedAvailability['otherPrices'] as $otherPrice) {
                if (! $highestPrice || $highestPrice < $otherPrice->averagePricePerBlockPerNight()) {
                    $highestPrice = $otherPrice->averagePricePerBlockPerNight();
                }
            }
            if ($highestPrice) {
                $formattedAvailability['savingsPercent'] =
                    round(
                        100 *
                        (
                            $highestPrice -
                            $formattedAvailability['primary']->averagePricePerBlockPerNight()
                        ) / $highestPrice
                    );
            }
        }
        unset($formattedAvailability); // break the reference with the last element

        // Sort

        usort($formattedRoomAvailabilities, function ($a, $b) {
            // (we only care about the primary room, not the otherPrices)
            $a = $a['primary'];
            $b = $b['primary'];

            // Partial availability rooms at the bottom
            if ($a->hasFullAvailability() && ! $b->hasFullAvailability()) {
                return -1;
            }
            if (! $a->hasFullAvailability() && $b->hasFullAvailability()) {
                return 1;
            }

            if ($a->roomInfo->type === 'dorm') {
                // Sort dorm rooms by whether they're mixed or not
                $mixedSexDifference = ($b->roomInfo->sex === 'mixed') - ($a->roomInfo->sex === 'mixed');
                if ($mixedSexDifference) {
                    return $mixedSexDifference;
                }
                // Sort by non-mixed by sex
                $sexDifference = ($b->roomInfo->sex === 'female') - ($a->roomInfo->sex === 'female');
                if ($sexDifference) {
                    return $sexDifference;
                }
            } else {
                // Sort private rooms by number of people
                $peoplePerRoomDifference = $a->roomInfo->peoplePerRoom - $b->roomInfo->peoplePerRoom;
                if ($peoplePerRoomDifference) {
                    return $peoplePerRoomDifference;
                }
            }

            if ($a->imported()->system != $b->imported()->system) {
                $systemPriorityDifference = $b->imported()->getImportSystem()->bookingPriority - $a->imported()->getImportSystem()->bookingPriority;
                if ($systemPriorityDifference) {
                    return $systemPriorityDifference;
                }
            }

            return $a->averagePricePerBlockPerNight() - $b->averagePricePerBlockPerNight();
        });

        return self::getSortedRoomAvailabilities($formattedRoomAvailabilities);
    }

    public static function formatCompareListings(array $listingsAvailabilities): Collection
    {
        foreach ($listingsAvailabilities as &$listingAvailability) {
            $listingAvailability = self::getUniqueRooms($listingAvailability);
        }
        unset($listingAvailability);

        $rooms = collect();
        self::$roomTypes = collect();

        foreach ($listingsAvailabilities as $listingId => $availabilities) {
            $rooms->put($listingId, collect());

            foreach ($availabilities as $availability) {
                /** @var RoomAvailability $availability */
                $system = $availability->imported()->getImportSystem()->systemName;
                $roomKey = $availability->roomInfo->name;
                $ensuite = $availability->roomInfo->ensuite;

                $listingPriceForSystem = collect([
                    'bookingPageLink' => $availability->bookingPageLink(),
                    'price' => $availability->averagePricePerBlockPerNight(true),
                ]);
                $systemPrice = collect([
                    $system => $listingPriceForSystem,
                ]);

                if (self::isSimilarToExistingRooms($availability)) {
                    if ($rooms[$listingId]->has(self::$roomKey)) {
                        $rooms[$listingId][self::$roomKey]->put($system, $listingPriceForSystem);
                        $rooms[$listingId][self::$roomKey]->put(self::ENSUITE, $ensuite);
                        continue;
                    }

                    $rooms[$listingId]->put(self::$roomKey, $systemPrice);
                    $rooms[$listingId][self::$roomKey]->put(self::ENSUITE, $ensuite);
                    continue;
                }

                self::$roomTypes->put($roomKey, $availability);
                $rooms[$listingId]->put($roomKey, $systemPrice);
                $rooms[$listingId][$roomKey]->put(self::ENSUITE, $ensuite);
            }
        }

        return $rooms;
    }

    private static function isSimilarToExistingRooms(RoomAvailability $availability): bool
    {
        $bool = false;
        self::$roomKey = $availability->roomInfo->name;

        self::$roomTypes->each(function ($roomAvailability) use ($availability, &$bool) {
            if ($roomAvailability->roomInfo->isSimilarForCompare($availability->roomInfo)) {
                self::$roomKey = $roomAvailability->roomInfo->name;
                $bool = true;

                return false;
            }
        });

        return $bool;
    }

    private static function getUniqueRooms(array $listingAvailabilities): Collection
    {
        $groupsOfSimilarRooms = collect([]);

        foreach ($listingAvailabilities as $roomAvailability) {
            if (
                $groupsOfSimilarRooms->has($roomAvailability->roomInfo->name) &&
                self::whichRoomIsBetter($groupsOfSimilarRooms[$roomAvailability->roomInfo->name], $roomAvailability) === 'b'
            ) {
                $groupsOfSimilarRooms[$roomAvailability->roomInfo->name] = $roomAvailability;
                continue;
            }

            $groupsOfSimilarRooms->put($roomAvailability->roomInfo->name, $roomAvailability);
        }

        return $groupsOfSimilarRooms;
    }

    public static function getRoomTypes(): Collection
    {
        return self::$roomTypes->sortByDesc(function ($room) {
            return $room->roomInfo->peoplePerRoom;
        });
    }

    private static function getSortedRoomAvailabilities(array $formattedRoomAvailabilities): array
    {
        return collect($formattedRoomAvailabilities)->sortByDesc(function ($availability) {
            return $availability['primary']->roomInfo->peoplePerRoom;
        })->toArray();
    }

    public static function mergeEquivalentRoomTypes(array $roomAvailabilities)
    {
        // Group similar room types

        $groupsOfSimilarRooms = [];

        foreach ($roomAvailabilities as $roomAvailability) {
            /** @var $roomAvailability RoomAvailability */
            // See if we already have a group of similar rooms
            $addedToGroup = false;
            foreach ($groupsOfSimilarRooms as $groupKey => $groupRooms) {
                $firstRoomInGroup = reset($groupRooms); // only need to check one for simlarity

                if (! $roomAvailability->roomInfo->isSimilar($firstRoomInGroup->roomInfo)) {
                    continue;
                }

                // Don't group if there already is a room from this same system in the group (keep them separate)
                foreach ($groupRooms as $groupRoom) {
                    // (Except we do let it group them if the room names are also identical)
                    if (
                        $roomAvailability->imported()->system === $groupRoom->imported()->system &&
                        $roomAvailability->roomInfo->name !== $groupRoom->roomInfo->name
                    ) {
                        continue 2;
                    }
                }

                $groupsOfSimilarRooms[$groupKey][] = $roomAvailability;
                $addedToGroup = true;

                break;
            }

            if (! $addedToGroup) {
                $groupsOfSimilarRooms[] = [$roomAvailability];
            } // create a new group for this room
        }

        foreach ($groupsOfSimilarRooms as $groupsKey => $roomsInGroup) {
            foreach ($groupsOfSimilarRooms as $key => $otherRoomsInGroup) {
                if ($groupsKey === $key) {
                    continue;
                }

                $room = collect($roomsInGroup)->first();
                $otherRoom = collect($otherRoomsInGroup)->first();

                if (! $otherRoom->roomInfo->isSimilarForMergeCheck($room->roomInfo)) {
                    continue;
                }

                foreach ($roomsInGroup as $roomKey => $roomInGroup) {
                    if ($roomInGroup->averagePricePerBlockPerNight() > ($otherRoom->averagePricePerBlockPerNight() * self::MORE_RATIO)) {
                        $groupsOfSimilarRooms[$groupsKey][$roomKey] = $otherRoom;
                        $groupsOfSimilarRooms[$key][0] = $roomInGroup;
                    }
                }
            }
        }

        // For each group of similar rooms choose a primary room and "otherPrice" ones

        $resultingRoomGroups = [];

        foreach ($groupsOfSimilarRooms as $groupOfSimilarRooms) {
            // Find the primary room for each group of similar rooms

            $bestRoomAvailability = $bestRoomAvailabilityKey = null;
            foreach ($groupOfSimilarRooms as $roomKey => $roomAvailability) {
                if (! $bestRoomAvailability) {
                    $bestRoomAvailability = $roomAvailability;

                    continue;
                }

                if (self::whichRoomIsBetter($bestRoomAvailability, $roomAvailability) === 'b') {
                    $bestRoomAvailability = $roomAvailability;
                    $bestRoomAvailabilityKey = $roomKey;
                }
            }

            // Create the list of otherPrices with the other rooms
            $otherPrices = [];
            foreach ($groupOfSimilarRooms as $roomKey => $roomAvailability) {
                if ($roomKey === $bestRoomAvailabilityKey) {
                    continue;
                }

                // Don't include certain rooms in otherPrices

                // Same system as bestRoomAvailability
                if ($roomAvailability->isSystemEqualWith($bestRoomAvailability)) {
                    continue;
                }

                // Is a lower price (probably means the price is lower, but only partial availability, etc.)
                if ($roomAvailability->isPriceLowerThen($bestRoomAvailability)) {
                    continue;
                }

                // Check for existing same system otherPrice...
                foreach ($otherPrices as $existingOtherKey => $existingOtherPrice) {
                    if ($existingOtherPrice->imported()->system !== $roomAvailability->imported()->system) {
                        continue;
                    }

                    // Only show the lowest comparison price...
                    if (! $roomAvailability->isPriceLowerThen($existingOtherPrice)) {
                        // ignore this price and keep the existing one that's already in $otherPrices
                        continue 2;
                    }

                    unset($otherPrices[$existingOtherKey]);
                }

                $otherPrices[] = $roomAvailability;
            }

            $resultingRoomGroups[] = [
                'primary' => $bestRoomAvailability,
                'otherPrices' => $otherPrices,
            ];
        }

        /*
            We do a second merging of any rooms that now have the identically named primary room as another one.
            This can happen if the a booking system had two rooms with identical attributes but different names and prices,
            but we found a better price for those rooms from another booking system that only had one room of an apparently similar type,
            so both those rooms end up getting replaced with the same better price room and we end up with duplicate rooms that need to be merged.
        */

        $uniqueResultingRoomGroups = [];
        foreach ($resultingRoomGroups as $room) {
            foreach ($uniqueResultingRoomGroups as &$uniqueRoom) {
                if (
                    $room['primary']->imported()->system !== $uniqueRoom['primary']->imported()->system &&
                    $room['primary']->roomInfo->name == $uniqueRoom['primary']->roomInfo->name
                ) {
                    // Merge primary
                    if ($room['primary']->averagePricePerBlockPerNight() > $uniqueRoom['primary']->averagePricePerBlockPerNight()) {
                        $uniqueRoom['primary'] = $room['primary'];
                    }
                    // Merge otherPrices (choosing the highest price)
                    $uniqueRoom['otherPrices'] = self::mergeOtherPrices($uniqueRoom['otherPrices'], $room['otherPrices']);

                    continue 2;
                }
            }
            unset($uniqueRoom); // break the reference with the last element
            $uniqueResultingRoomGroups[] = $room;
        }

        return $uniqueResultingRoomGroups;
    }

    // Merge "otherPrices" (choosing the highest price)
    private static function mergeOtherPrices($pricesA, $pricesB)
    {
        // Merge and remove duplicates (keeping the *highest* price)

        $result = [];

        foreach (array_merge($pricesA, $pricesB) as $room) {
            foreach ($result as &$resultRoom) {
                if ($room->imported()->system == $resultRoom->imported()->system) {
                    if ($room->averagePricePerBlockPerNight() > $resultRoom->averagePricePerBlockPerNight()) {
                        $resultRoom = $room;
                    } // use the new room instead

                    continue 2;
                }
            }
            unset($resultRoom); // break the reference with the last element
            $result[] = $room;
        }

        return $result;
    }

    // Returns 'a' or 'b'

    private static function whichRoomIsBetter(RoomAvailability $a, RoomAvailability $b)
    {
        // echo "<h3>whichRoomIsBetter</h3><h4>a:</h4>".$a->getDebugInfo()."<h4>b:</h4>".$b->getDebugInfo();

        if ($a->hasFullAvailability() && ! $b->hasFullAvailability()) {
            return 'a';
        }
        if (! $a->hasFullAvailability() && $b->hasFullAvailability()) {
            return 'b';
        }

        $aRoomsSystem = $a->imported()->getImportSystem();
        $bRoomsSystem = $b->imported()->getImportSystem();

        if ($bRoomsSystem->preventDisplayingThisSystemAsLowestPrice && ! $aRoomsSystem->preventDisplayingThisSystemAsLowestPrice) {
            return 'a';
        }
        if (! $bRoomsSystem->preventDisplayingThisSystemAsLowestPrice && $aRoomsSystem->preventDisplayingThisSystemAsLowestPrice) {
            return 'b';
        }

        $priceDifference = $a->averagePricePerBlockPerNight() - $b->averagePricePerBlockPerNight();

        if ($priceDifference > 0) {
            return 'b';
        }
        if ($priceDifference < 0) {
            return 'a';
        }

        return $aRoomsSystem->bookingPriority > $bRoomsSystem->bookingPriority ? 'a' : 'b';
    }

    public static function findRoomAvailabilityByRoomCode(Listing $listing, $importedID, SearchCriteria $searchCriteria, $roomCode)
    {
        // This gets all availability information for the listing, but usually it's just fetching it from the cache.
        $roomAvailabilities = self::getAvailabilityForListing($listing, $searchCriteria, true);
        if (! $roomAvailabilities) {
            return null;
        }

        // Find the matching one
        foreach ($roomAvailabilities as $roomAvailability) {
            if ($roomAvailability->importedID == $importedID && $roomAvailability->roomInfo->code == $roomCode) {
                return $roomAvailability;
            }
        }

        return null;
    }

    /*

    Summary of availability for each listing (average price, min nights, max people, etc.).
    Used when displaying the results of an availability search for an entire city.

    Returns array of elements of $listingID => availablity

    */

    public static function bestAvailabilityByListingID($availabilityByListingID)
    {
        if (! $availabilityByListingID) {
            return [];
        }

        $bestAvailabilityByListingID = [];

        foreach ($availabilityByListingID as $listingID => $availabilities) {
            // We call mergeEquivalentRoomTypes() because it removes room types in undesirable booking systems (which could change the perRoomPrice).
            $availabilities = self::mergeEquivalentRoomTypes($availabilities);
            $bestAvailabilityForListing = null;

            foreach ($availabilities as $availability) {
                $availability = $availability['primary']; // we only care about the primary room time, not the otherPrices

                if (! $bestAvailabilityForListing ||
                    ($availability->hasFullAvailability() && ! $bestAvailabilityForListing->hasFullAvailability()) ||
                    $availability->averagePricePerBlockPerNight() < $bestAvailabilityForListing->averagePricePerBlockPerNight()) {
                    $bestAvailabilityForListing = $availability;
                }
            }

            if ($bestAvailabilityForListing) {
                $bestAvailabilityByListingID[$listingID] = $bestAvailabilityForListing;
            }
        }

        if (self::countOfAvailabilitiesHasFullAvailability($bestAvailabilityByListingID)) {
            $bestAvailabilityByListingID = self::removeAnyWithPartialAvailability($bestAvailabilityByListingID);
        }

        return $bestAvailabilityByListingID;
    }

    /*

    Summary of availability for each listing (average price, min nights, max people, etc.).
    Used when displaying the results of an availability search for an entire city and we want to show the price per OTA.

    Returns array of elements of listingID => OTA => availablity

    */

    public static function bestAvailabilityByListingIdAndOTA($availabilityByListingID)
    {
        if (! $availabilityByListingID) {
            return [];
        }

        $bestAvailabilities = [];
        foreach ($availabilityByListingID as $listingID => $availableRooms) {
            $bestAvailabilityForOTAs = [];

            /** @var RoomAvailability[] $availableRooms */
            foreach ($availableRooms as $availability) {
                $ota = $availability->imported()->system;

                if (
                    empty($bestAvailabilityForOTAs[$ota]) ||
                    $availability->isBetterThan($bestAvailabilityForOTAs[$ota])
                ) {
                    $bestAvailabilityForOTAs[$ota] = $availability;
                }
            }

            $bestAvailabilities[$listingID] = $bestAvailabilityForOTAs;
        }

        return $bestAvailabilities;
    }

    public static function availabilitySavingsPercentByListingIdAndOTA($bestAvailabilityByOTA)
    {
        return array_map(function ($systems) {
            $systemsPrice = array_map(function ($item) {
                return $item->averagePricePerBlockPerNight();
            }, $systems);

            if (count($systemsPrice) <= 1) {
                return [];
            }

            $max = max($systemsPrice);
            $min = min($systemsPrice);

            if ($max === $min) {
                return [];
            }

            $system = array_search($min, $systemsPrice);

            return ['system' => $system, 'percent' => round(100 * ($max - $min) / $max)];
        }, $bestAvailabilityByOTA);
    }

    public static function countOfAvailabilitiesHasFullAvailability(array $availabilities)
    {
        $count = 0;
        foreach ($availabilities as $availability) {
            if ($availability->hasFullAvailability()) {
                $count++;
            }
        }

        return $count;
    }

    public static function removeAnyWithPartialAvailability(array $availabilities)
    {
        return array_filter($availabilities, function ($availability) {
            return $availability->hasFullAvailability();
        });
    }

    /* Availability Cache */

    private static function getCachedListingAvailability($listingID, SearchCriteria $searchCriteria, $requireRoomDetails)
    {
        $result = Cache::get(self::listingAvailabilityKey($listingID, $searchCriteria));
        if (! $result) {
            return null;
        }
        $result = unserialize($result);

        if ($requireRoomDetails) {
            // (The cached availability might be from a city search, and not all systems give us full room details from a city search)
            foreach ($result as $availability) {
                if (! $availability->hasCompleteRoomDetails) {
                    return null;
                }
            }
        }

        return $result;
    }

    private static function saveListingAvailabilityToCache($listingID, SearchCriteria $searchCriteria, $result): void
    {
        Cache::put(
            self::listingAvailabilityKey($listingID, $searchCriteria),
            serialize($result),
            self::LISTING_AVAILABILITY_CACHE_MINUTES
        );
    }

    private static function listingAvailabilityKey($listingID, SearchCriteria $searchCriteria): string
    {
        return "listingAvailabilityCache:$listingID:" . $searchCriteria->hashValue();
    }
}
