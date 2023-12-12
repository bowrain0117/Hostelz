<?php

namespace App\Services\Listings;

use App\Models\CityInfo;

class ListingsOptionsService
{
    private array $resultsOptions;

    private array $listingFilters;

    private array $bookingSearchCriteria;

    public function setOptionsDataForListingsSearch(array $optionsData): void
    {
        $this->resultsOptions = $optionsData['resultsOptions'];

        $this->resultsOptions['orderBy'] ??= self::getDefaultOrderByOptions();
        $this->resultsOptions['resultsPerPage'] ??= CityInfo::DEFAULT_Results_Per_Page;
        $this->resultsOptions['listFormat'] ??= CityInfo::DEFAULT_LIST_FORMAT;
        $this->resultsOptions['mapMode'] ??= CityInfo::DEFAULT_MAP_MODE;

        $this->listingFilters = $optionsData['listingFilters'] ?? [];

        $this->bookingSearchCriteria = $optionsData['bookingSearchCriteria'] ?? [];
    }

    public function getOptionsForListingsSearch(): array
    {
        return [
            $this->getResultOptions(),
            $this->getListingFilters(),
            $this->getBookingSearchCriteria(),
            $this->getDefaultResultsBool(),
        ];
    }

    private function getResultOptions(): array
    {
        return $this->resultsOptions;
    }

    private function getListingFilters(): array
    {
        return $this->listingFilters;
    }

    private function getBookingSearchCriteria(): array
    {
        return $this->bookingSearchCriteria;
    }

    private function getDefaultResultsBool(): bool
    {
        return (! $this->listingFilters
                && ! $this->bookingSearchCriteria
                && $this->resultsOptions['orderBy'] === 'default'
                && $this->resultsOptions['resultsPerPage'] === 'default')
            && $this->resultsOptions['listFormat'] === CityInfo::DEFAULT_LIST_FORMAT;
    }

    public static function getDefaultListingsShowOptions(?array $options = []): array
    {
        $default = [
            'resultsOptions' => [
                'mapMode' => 'closed',
                'orderBy' => self::getDefaultOrderByOptions(),
            ],
            'doBookingSearch' => false,
            'listingFilters' => [],
        ];

        return $options ? array_merge($default, $options) : $default;
    }

    public static function getDefaultOrderByOptions(): array
    {
        return [
            'sortBy' => CityInfo::DEFAULT_ORDER_BY,
            'type' => CityInfo::DEFAULT_ORDER_BY,
            'value' => __('city.resultsOptions.orderBy.ratings'),
            'title' => __('city.resultsOptions.orderBy.ratings'),
        ];
    }
}
