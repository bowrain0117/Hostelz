<?php

namespace App\Http\Controllers\City;

use App\Enums\CategorySlp;
use App\Http\Controllers\Controller;
use App\Models\CityInfo;
use App\Models\SpecialLandingPage;
use App\Services\Listings\CityCommentsService;
use App\Services\Listings\CityInfoService;
use App\Services\Listings\CityListingsService;
use App\Services\Listings\Filters\ListingsFiltersService;
use App\Services\Listings\ListingsOptionsService;
use App\Services\PicsService;
use App\Traits\Redirect as RedirectTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Lib\PageCache;

class CityListingsController extends Controller
{
    use RedirectTrait;
    use UseCategoryPage;
    use UseDistrict;

    private CityInfoService $cityInfoService;

    private ListingsFiltersService $listingsFiltersService;

    private CityListingsService $cityListingsService;

    public function __construct()
    {
        $this->cityInfoService = new CityInfoService();
        $this->listingsFiltersService = new ListingsFiltersService();
        $this->cityListingsService = new CityListingsService();
    }

    public function cityListingsListStatic($cityID, $mapMode, $page)
    {
        $cityInfo = $this->cityInfoService->getCityInfoByID($cityID);

        return $this->getListingsListContent(
            $cityInfo,
            ['resultsOptions' => ['mapMode' => $mapMode]],
            $page
        );
    }

    public function cityListingsListDynamic(Request $request, $cityID)
    {
        // Decode optionsData input
        $optionsData = json_decode($request->input('optionsData'), true, 512, JSON_THROW_ON_ERROR);
        if (! $optionsData || ! isset($optionsData['resultsOptions'])) {
            abort(400);
        }

        $cityInfo = $this->cityInfoService
            ->getCityInfoByID($cityID)
            ->load('districts');

        $this->listingsFiltersService->setListings($cityInfo);
        $listingFilters = $this->listingsFiltersService->getListingsFilters();

        $viewResponse = $this->getListingsListContent(
            $cityInfo, $optionsData,
            $request->input('page', 1),
            'city',
            $listingFilters
        );

        return setCorsHeadersToAllowOurSubdomains(Response::make($viewResponse), false);
    }

    private function getListingsListContent($cityInfo, $optionsData, $page = 1, $bookingLinkLocation = '', $listingFilters = null)
    {
        PageCache::addCacheTags('city:' . $cityInfo->id); // mark the cache so it can by cleared when the city is edited

        return view(
            'city.listingsList',
            $this->cityListingsService->getListingsData(
                $cityInfo,
                $optionsData,
                $page,
                $bookingLinkLocation,
                $listingFilters,
            )
        );
    }
}
