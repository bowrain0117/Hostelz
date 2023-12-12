<?php

namespace App\Services\Listings;

use App\Booking\BookingService;
use App\Booking\RoomAvailability;
use App\Booking\SearchCriteria;
use App\Exceptions\BookingException;

class BookingAvailabilityService
{
    private SearchCriteria $searchCriteria;

    public function __construct()
    {
        $this->searchCriteria = new SearchCriteria();
    }

    /**
     * @throws BookingException
     */
    public function getBookingAvailability($bookingSearchCriteria, $listings, $bookingLinkLocation, $listingFilters): array
    {
        $this->searchCriteria->bookingSearchFormFields($bookingSearchCriteria);

        $validationError = $this->searchCriteria->getValidationError();

        if ($validationError === null) {
            return $this->checkBookingAvailability($listings,
                $this->searchCriteria,
                $bookingLinkLocation,
                $listingFilters
            );
        }

        throw new BookingException($validationError);
    }

    protected function checkBookingAvailability($listings, $searchCriteria, $bookingLinkLocation, $listingFilters): array
    {
        $availabilityByListingID = $this->getAvailabilityByListingID($listings, $searchCriteria, $bookingLinkLocation, $listingFilters);

        $bestAvailabilityByListingID = BookingService::bestAvailabilityByListingID($availabilityByListingID);
        $bestAvailabilityByOTA = BookingService::bestAvailabilityByListingIdAndOTA($availabilityByListingID);
        $bestAvailabilityByOTA = $this->sortByOtaPriority($bestAvailabilityByOTA);
        $availabilitySavingsPercent = BookingService::availabilitySavingsPercentByListingIdAndOTA($bestAvailabilityByOTA);

        foreach ($availabilitySavingsPercent as $key => $service) {
            if (isset($service['system'])) {
                $bestAvailabilityByOTA[$key] = [$service['system'] => $bestAvailabilityByOTA[$key][$service['system']]] + $bestAvailabilityByOTA[$key];
            }
        }

        $listings = $this->getFilteredListingsWithBestAvailability($listings, $bestAvailabilityByListingID);

        return [$bestAvailabilityByListingID, $bestAvailabilityByOTA, $availabilitySavingsPercent, $listings, $this->searchCriteria, $availabilityByListingID];
    }

    private function sortByOtaPriority(array $items): array
    {
        $result = [];
        foreach ($items as $key => $OTAs) {
            uasort($OTAs, function (RoomAvailability $a, RoomAvailability $b) {
                if ($a->averagePricePerBlockPerNight(true) !== $b->averagePricePerBlockPerNight(true)) {
                    return 0;
                }

                return $b->imported->getImportSystem()->bookingPriority <=> $a->imported->getImportSystem()->bookingPriority;
            });
            $result[$key] = $OTAs;
        }

        return $result;
    }

    protected function getAvailabilityByListingID($listings, $searchCriteria, $bookingLinkLocation, $listingFilters): array
    {
        $availabilityByListingID = BookingService::getAvailabilityForListings($listings, $searchCriteria, false, $bookingLinkLocation);

        if ($searchCriteria->roomType === 'dorm' && isset($listingFilters['typeOfDormRoom'])) {
            $availabilityByListingID = $this->filterByTypeOfDormRoom($availabilityByListingID, $listingFilters['typeOfDormRoom']);
        }

        if ($searchCriteria->roomType === 'private' && isset($listingFilters['typeOfPrivateRoom'])) {
            $availabilityByListingID = $this->filterByTypeOfPrivateRoom($availabilityByListingID, $listingFilters['typeOfPrivateRoom']);
        }

        return $availabilityByListingID;
    }

    protected function getFilteredListingsWithBestAvailability($listings, $bestAvailabilityByListingID)
    {
        return $listings->filter(function ($listing) use ($bestAvailabilityByListingID) {
            return array_key_exists($listing->id, $bestAvailabilityByListingID);
        });
    }

    private function filterByTypeOfDormRoom($availabilityByListingID, $typeOfRoomFilters): array
    {
        $return = [];
        foreach ($availabilityByListingID as $listingID => $availabilities) {
            foreach ($availabilities as $roomAvailability) {
                switch (reset($typeOfRoomFilters)) {
                    case 'maleOnly':
                        if ($roomAvailability->roomInfo->sex === 'male' && ! $roomAvailability->roomInfo->ensuite) {
                            break;
                        }

                        continue 2;

                    case 'femaleOnly':
                        if ($roomAvailability->roomInfo->sex === 'female' && ! $roomAvailability->roomInfo->ensuite) {
                            break;
                        }

                        continue 2;

                    case 'mixed':
                        if ($roomAvailability->roomInfo->sex === 'mixed' && ! $roomAvailability->roomInfo->ensuite) {
                            break;
                        }

                        continue 2;

                    case 'maleOnlyEnsuite':
                        if ($roomAvailability->roomInfo->sex === 'male' && $roomAvailability->roomInfo->ensuite) {
                            break;
                        }

                        continue 2;

                    case 'femaleOnlyEnsuite':
                        if ($roomAvailability->roomInfo->sex === 'female' && $roomAvailability->roomInfo->ensuite) {
                            break;
                        }

                        continue 2;

                    case 'mixedEnsuite':
                        if ($roomAvailability->roomInfo->sex === 'mixed' && $roomAvailability->roomInfo->ensuite) {
                            break;
                        }

                        continue 2;
                }
                $return[$listingID][] = $roomAvailability;
            }
        }

        return $return;
    }

    private function filterByTypeOfPrivateRoom($availabilityByListingID, $typeOfRoomFilters): array
    {
        $ensuiteOnly = false;
        if (in_array('ensuite', $typeOfRoomFilters, true)) {
            $ensuiteOnly = true;
            unset($typeOfRoomFilters[array_search('ensuite', $typeOfRoomFilters, true)]);
        }

        $return = [];

        foreach ($availabilityByListingID as $listingID => $availabilities) {
            foreach ($availabilities as $roomAvailability) {
                $roomInfo = $roomAvailability->roomInfo;

                if ($ensuiteOnly && $roomInfo->ensuite !== true) {
                    continue;
                }

                if ($typeOfRoomFilters) {
                    $foundMatch = false;
                    foreach ($typeOfRoomFilters as $filter) {
                        switch ($filter) {
                            case '1bed1person':
                                if ($roomInfo->bedsPerRoom === 1 && $roomInfo->peoplePerRoom === 1) {
                                    $foundMatch = true;
                                }

                                break;

                            case '1bed2people':
                                if ($roomInfo->bedsPerRoom === 1 && $roomInfo->peoplePerRoom === 2) {
                                    $foundMatch = true;
                                }

                                break;

                            case '2beds':
                                if ($roomInfo->bedsPerRoom === 2) {
                                    $foundMatch = true;
                                }

                                break;

                            case '3people':
                                if ($roomInfo->peoplePerRoom === 3) {
                                    $foundMatch = true;
                                }

                                break;

                            case '4orMore':
                                if ($roomInfo->peoplePerRoom >= 4) {
                                    $foundMatch = true;
                                }

                                break;

                            default:; // logWarning("Unknown room filter '$filter'."); (was causing the page to fail to load)
                        }

                        if ($foundMatch) {
                            break;
                        }
                    }
                    if (! $foundMatch) {
                        continue;
                    }
                }

                $return[$listingID][] = $roomAvailability;
            }
        }

        return $return;
    }
}
