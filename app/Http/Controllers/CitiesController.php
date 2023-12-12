<?php

namespace App\Http\Controllers;

use App\Exceptions\CitiesException;
use App\Services\CityService;
use App\Services\Listings\CityCommentsService;
use App\Services\Listings\CityInfoService;
use App\Services\Listings\CountryInfoService;
use App\Services\Listings\ListingsPoiService;
use App\Traits\Redirect as RedirectTrait;
use Illuminate\Http\Request;
use Lib\PageCache;

class CitiesController extends Controller
{
    use RedirectTrait;

    private CityService $cityService;

    private CityInfoService $cityInfoService;

    private CityCommentsService $cityCommentsService;

    private CountryInfoService $countryInfoService;

    private ListingsPoiService $listingsPoiService;

    public function __construct()
    {
        $this->cityService = new CityService();
        $this->cityInfoService = new CityInfoService();
        $this->cityCommentsService = new CityCommentsService();
        $this->countryInfoService = new CountryInfoService();
        $this->listingsPoiService = new ListingsPoiService();
    }

    public function __invoke(Request $request, $countrySlug, $regionOrCityGroupSlug = null)
    {
        $viewData = [];

        // * Decode the URL *
        $slug = implode('/', $request->route()->parameters());

        $viewData['regionOrCityGroupSlug'] = $regionOrCityGroupSlug;

        $viewData['citiesInfo'] = $citiesInfo = $this->cityInfoService->getCitiesInfo($countrySlug, $regionOrCityGroupSlug, $slug);

        $isCityGroupPage = $this->cityInfoService->getCityGroupPageBool();
        $viewData['isCityGroupPage'] = $isCityGroupPage;

        if (is_string($citiesInfo)) {
            return redirect()->to($citiesInfo, 301);
        }

        if (! $citiesInfo) {
            return $this->redirectToSearch($request->route()->parameters()); // last resort... send them to the search page.
        }

        $citiesInfo = $this->cityInfoService->addCityUrls($citiesInfo);

        $viewData['topCities'] = $topCities = $this->cityInfoService->getTopCitiesByHostelsCount($citiesInfo);
        $viewData['topCitiesListings'] = $this->cityInfoService->getTopCitiesListings($topCities);

        $pageCityInfo = $citiesInfo->first();
        if (! $regionOrCityGroupSlug) {
            $pageCityInfo->region = $pageCityInfo->cityGroup = '';
        } // it's a whole country page, so don't show the region or cityGroup
        $viewData['pageCityInfo'] = $pageCityInfo;
        // * Check the URL *

        $correctURL = $this->getCitiesCorrectUrl($pageCityInfo, $isCityGroupPage);
        if ('/' . $request->path() !== $correctURL) {
            return redirect($correctURL, 301);
        }

        // * CountryInfo *
        $viewData['countryInfo'] = $countryInfo = $pageCityInfo->countryInfo;
        if (! $countryInfo) {
            throw new CitiesException("No CountryInfo for country '" . $viewData['pageCityInfo']->country . "'.");
        }

        // * OG Thumbnail *
        $viewData['ogThumbnail'] = url('images', 'all-hostels-in-the-world.jpg');

        // * Regions List *
        $viewData['regionsList'] = $this->cityInfoService->getCityRegions($citiesInfo);

        // * CityGroup List *
        $viewData['cityGroupsList'] = $cityGroupList = $this->cityInfoService->getCityGroupList($isCityGroupPage, $citiesInfo);

        // * Description *
        $description = $countryInfo->getLiveDescription($isCityGroupPage ? $pageCityInfo->cityGroup : $pageCityInfo->region);
        $viewData['description'] = $this->countryInfoService->getCountryDescription($cityGroupList, $description);

        // * Mapping *
        $callback = function ($listing) {
            return [
                $listing->latitude,
                $listing->longitude,
                $listing->id,
                $listing->city,
                $listing->hostelCount > 0,
            ];
        };

        $pois = $this->listingsPoiService->getMapPoi($citiesInfo, $callback);

        // * Featured City Comments & City Descriptions *
        $cityIDsForCitiesWithHostels = $citiesInfo->where('hostelCount', true)->pluck('id');

        // City Comments
        $viewData['cityComments'] = $this->cityCommentsService->getCitiesComments($cityIDsForCitiesWithHostels);

        $viewData['search'] = $this->cityService->getViewDataForCitySearch($isCityGroupPage, $pageCityInfo, $countryInfo);

        PageCache::addCacheTags('city:aggregation'); // mark the cache so it can by cleared when any city is altered

        return view('cities', $pois ? array_merge($viewData, $pois) : $viewData);
    }
}
