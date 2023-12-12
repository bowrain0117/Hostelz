<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Services\Listings\CityInfoService;
use App\Services\Listings\CityListingsService;
use App\Services\Listings\Filters\ListingsFiltersService;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ListingsFilter extends Controller
{
    public function __construct(
        private CityInfoService $cityInfoService,
        private ListingsFiltersService $listingsFiltersService,
        private CityListingsService $cityListingsService,
    ) {
    }

    public function show(Request $request, $cityID)
    {
        $optionsData = $request->get('options');

        $cityInfo = $this->cityInfoService->getCityInfoByID($cityID);

        $this->listingsFiltersService->setListings($cityInfo);
        $listingFilters = $this->listingsFiltersService->getListingsFilters();

        if (! filter_var($optionsData['doBookingSearch'], FILTER_VALIDATE_BOOLEAN)) {
            $viewData = $this->cityListingsService->getDataByBookingAvailability($cityInfo, $optionsData, 'city', $listingFilters);

            return view('bookings._filters', [
                'listingFilters' => Arr::except($viewData['cityFilters'], ['typeOfPrivateRoom', 'typeOfDormRoom']),
                'hostelCount' => $viewData['hostelCount'],
            ]);
        }

        $viewData = $this->cityListingsService->getDataByBookingAvailability($cityInfo, $optionsData, 'city', $listingFilters);

        $filters = empty($viewData['cityFilters']) ? $listingFilters : $viewData['cityFilters'];

        return view('bookings._filters', [
            'listingFilters' => $filters,
            'hostelCount' => $viewData['hostelCount'],
        ]);
    }
}
