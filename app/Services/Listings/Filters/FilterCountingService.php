<?php

namespace App\Services\Listings\Filters;

use App\Booking\SearchCriteria;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;

class FilterCountingService
{
    private ?array $cityFilters;

    private Collection $listings;

    private Collection $hostelListings;

    private ?array $roomsAvailability;

    private array $dormTypeOptions;

    private array $privateTypeOptions;

    private const MALE = 'male';

    private const FEMALE = 'female';

    private const MIXED = 'mixed';

    private const ENSUITE = 'ensuite';

    private const SINGLE_ROOM = '1bed1person';

    private const DOUBLE_ROOM = '1bed2people';

    private const TWIN_ROOM = '2beds';

    private const TRIPLE_ROOM = '3people';

    private const QUAD_PLUS_ROOM = '4orMore';

    public function __construct()
    {
        $this->dormTypeOptions = __('city.filters.options.typeOfDormRoom');
        $this->privateTypeOptions = __('city.filters.options.typeOfPrivateRoom');
    }

    public function defineCityFilters(?array $filters, Collection $listings, Collection $hostelListings, ?SearchCriteria $searchCriteria, ?array $roomsAvailability): void
    {
        $this->cityFilters = $filters;
        $this->listings = $listings;
        $this->hostelListings = $hostelListings;
        $this->roomsAvailability = $roomsAvailability;

        if ($searchCriteria) {
            $this->cityFilters = $searchCriteria->roomType === 'dorm'
                ? Arr::except($filters, 'typeOfPrivateRoom')
                : Arr::except($filters, 'typeOfDormRoom');
        }
    }

    public function getCityFiltersWithCount(): ?array
    {
        $this
            ->getCountBySuitableFor()
            ->getCountByFeatures()
            ->getCountByGadgets()
            ->getCountByAccessibility()
            ->getCountByComfort()
            ->getCountByActivities()
            ->getCountByNeighborhoods()
            ->getCountByRating()
            ->getCountByPropertyType()
            ->getCountByDormRoom()
            ->getCountByPrivateRoom();

        return $this->cityFilters;
    }

    private function getCountBySuitableFor(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['suitableFor'])) {
            foreach ($this->cityFilters['suitableFor']['options'] as $key => $option) {
                if ($key === 'boutiqueHostel') {
                    $this->cityFilters['suitableFor']['count'][$key] = $this->hostelListings->filter(
                        fn ($listing) => $listing->boutiqueHostel === 1
                    )->count();

                    continue;
                }
                $this->cityFilters['suitableFor']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => in_array($key, $listing->compiledFeatures['goodFor'] ?? [], true)
                )->count();
            }
        }

        return $this;
    }

    private function getCountByFeatures(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['features'])) {
            foreach ($this->cityFilters['features']['options'] as $key => $option) {
                $this->cityFilters['features']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => (! empty($listing->compiledFeatures[$key]) && $listing->compiledFeatures[$key] !== 'no') ||
                    (in_array($key, $listing->compiledFeatures['extras'] ?? [], true))
                )->count();
            }
        }

        return $this;
    }

    private function getCountByGadgets(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['gadgets'])) {
            foreach ($this->cityFilters['gadgets']['options'] as $key => $option) {
                $this->cityFilters['gadgets']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => (! empty($listing->compiledFeatures[$key]) && $listing->compiledFeatures[$key] !== 'no') ||
                        (in_array($key, $listing->compiledFeatures['extras'] ?? [], true))
                )->count();
            }
        }

        return $this;
    }

    private function getCountByAccessibility(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['accessibility'])) {
            foreach ($this->cityFilters['accessibility']['options'] as $key => $option) {
                $this->cityFilters['accessibility']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => (! empty($listing->compiledFeatures[$key]) && $listing->compiledFeatures[$key] !== 'no') ||
                        (in_array($key, $listing->compiledFeatures['extras'] ?? [], true))
                )->count();
            }
        }

        return $this;
    }

    private function getCountByComfort(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['comfort'])) {
            foreach ($this->cityFilters['comfort']['options'] as $key => $option) {
                $this->cityFilters['comfort']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => (! empty($listing->compiledFeatures[$key]) && $listing->compiledFeatures[$key] !== 'no') ||
                        (in_array($key, $listing->compiledFeatures['extras'] ?? [], true))
                )->count();
            }
        }

        return $this;
    }

    private function getCountByActivities(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['activities'])) {
            foreach ($this->cityFilters['activities']['options'] as $key => $option) {
                $this->cityFilters['activities']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => (! empty($listing->compiledFeatures[$key]) && $listing->compiledFeatures[$key] !== 'no') ||
                        (in_array($key, $listing->compiledFeatures['extras'] ?? [], true))
                )->count();
            }
        }

        return $this;
    }

    private function getCountByNeighborhoods(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['neighborhoods'])) {
            foreach ($this->cityFilters['neighborhoods']['options'] as $key => $option) {
                $this->cityFilters['neighborhoods']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => $listing->cityAlt === $key
                )->count();
            }
        }

        return $this;
    }

    private function getCountByPropertyType(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['propertyType'])) {
            foreach ($this->cityFilters['propertyType']['options'] as $key => $option) {
                $this->cityFilters['propertyType']['count'][$key] = $this->listings->filter(
                    fn ($listing) => $listing->propertyType === $key
                )->count();
            }
        }

        return $this;
    }

    private function getCountByDormRoom(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['typeOfDormRoom']) && $this->roomsAvailability) {
            foreach ($this->dormTypeOptions as $key => $value) {
                $this->cityFilters['typeOfDormRoom']['count'][$key] = 0;
            }

            $this->cityFilters['typeOfDormRoom']['count'][self::ENSUITE] = 0;

            foreach ($this->roomsAvailability as $availabilities) {
                $availabilities = collect($availabilities);

                $this->cityFilters['typeOfDormRoom']['count'][self::ENSUITE] +=
                    $availabilities->where('roomInfo.ensuite', true)->count();

                $this->countDormRooms($availabilities, 'maleOnly', self::MALE, '!=');
                $this->countDormRooms($availabilities, 'femaleOnly', self::FEMALE, '!=');
                $this->countDormRooms($availabilities, 'mixed', self::MIXED, '!=');
                $this->countDormRooms($availabilities, 'maleOnlyEnsuite', self::MALE, '=');
                $this->countDormRooms($availabilities, 'femaleOnlyEnsuite', self::FEMALE, '=');
                $this->countDormRooms($availabilities, 'mixedEnsuite', self::MIXED, '=');
            }
        }

        return $this;
    }

    private function countDormRooms(Collection $availabilities, string $key, string $sex, string $sign): void
    {
        $this->cityFilters['typeOfDormRoom']['count'][$key] +=
            $availabilities->where('roomInfo.sex', $sex)->where('roomInfo.ensuite', $sign, true)->count();
    }

    private function getCountByPrivateRoom(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['typeOfPrivateRoom']) && $this->roomsAvailability) {
            foreach ($this->privateTypeOptions as $key => $value) {
                $this->cityFilters['typeOfPrivateRoom']['count'][$key] = 0;
            }

            $this->cityFilters['typeOfPrivateRoom']['count'][self::ENSUITE] = 0;

            foreach ($this->roomsAvailability as $availabilities) {
                $availabilities = collect($availabilities);

                $this->cityFilters['typeOfPrivateRoom']['count'][self::ENSUITE] +=
                    $availabilities->where('roomInfo.ensuite', true)->count();

                $this->cityFilters['typeOfPrivateRoom']['count'][self::SINGLE_ROOM] +=
                    $availabilities->where('roomInfo.bedsPerRoom', 1)->where('roomInfo.peoplePerRoom', 1)->count();

                $this->cityFilters['typeOfPrivateRoom']['count'][self::DOUBLE_ROOM] +=
                    $availabilities->where('roomInfo.bedsPerRoom', 1)->where('roomInfo.peoplePerRoom', 2)->count();

                $this->cityFilters['typeOfPrivateRoom']['count'][self::TWIN_ROOM] +=
                    $availabilities->where('roomInfo.bedsPerRoom', 2)->count();

                $this->cityFilters['typeOfPrivateRoom']['count'][self::TRIPLE_ROOM] +=
                    $availabilities->where('roomInfo.peoplePerRoom', 3)->count();

                $this->cityFilters['typeOfPrivateRoom']['count'][self::QUAD_PLUS_ROOM] +=
                    $availabilities->where('roomInfo.peoplePerRoom', '>=', 4)->count();
            }
        }

        return $this;
    }

    private function getCountByRating(): static
    {
        if (isset($this->cityFilters) && isset($this->cityFilters['rating'])) {
            foreach ($this->cityFilters['rating']['options'] as $key => $option) {
                $this->cityFilters['rating']['count'][$key] = $this->hostelListings->filter(
                    fn ($listing) => $listing->combinedRating >= $key * 10
                )->count();
            }
        }

        return $this;
    }

    private function getFilterCount(): static
    {
        if (isset($this->cityFilters)) {
            foreach ($this->cityFilters as $key => &$filterType) {
                if (isset($filterType['count'])) {
                    if ($key === 'rating') {
                        continue;
                    }

                    if ($key !== 'typeOfDormRoom') {
                        $filterType['filtersCount'] = count(array_filter($filterType['count'], fn ($item) => $item > 0));

                        continue;
                    }

                    $filterType['filtersCount'] = array_sum($filterType['count']);
                }
            }
        }

        return $this;
    }
}
