<?php

namespace App\Http\Controllers\City;

use App\Enums\CategorySlp;
use App\Http\Controllers\Controller;
use App\Models\CityInfo;
use App\Models\SpecialLandingPage;
use App\Services\ListingCategoryPageService;
use App\Services\Listings\CityCommentsService;
use App\Services\Listings\CityInfoService;
use App\Services\Listings\CityListingsService;
use App\Services\Listings\Filters\ListingsFiltersService;
use App\Services\Listings\ListingsOptionsService;
use App\Services\PicsService;
use App\Traits\Redirect as RedirectTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Lib\PageCache;

class CityController extends Controller
{
    use RedirectTrait;
    use UseCategoryPage, UseDistrict;

    private CityInfoService $cityInfoService;

    private ListingsFiltersService $listingsFiltersService;

    private CityCommentsService $cityCommentsService;

    private PicsService $picsService;

    private CityListingsService $cityListingsService;

    public function __construct()
    {
        $this->cityInfoService = new CityInfoService();
        $this->listingsFiltersService = new ListingsFiltersService();
        $this->cityCommentsService = new CityCommentsService();
        $this->picsService = new PicsService();
        $this->cityListingsService = new CityListingsService();
    }

    /**
     * could be:
     * hostels/country/city
     * hostels/country/region/city
     *
     * hostels/country/city/district
     *
     * hostels/country/city/youth-hostels
     * hostels/country/city/family-hostels
     */
    public function city(Request $request, $country, $region = null, $city = null)
    {
        if ($city === null) {
            [$city, $region] = [$region, null];
        }

        if ($city === null) {
            return redirect()->route('cities', ['country' => $country, 'cityOrRegion' => $region]);
        }

        $citySearchCriteriaCookie = json_decode($request->cookie(config('custom.citySearchCriteriaCookie')), true);
        $doBookingSearch = data_get($citySearchCriteriaCookie, 'doBookingSearch', false);

        Session::put('availabilityLink', url()->current());

        $categoryData = $this->getCategoryPageData($country, $region, $city, $doBookingSearch);
        if (isset($categoryData['data'])) {
            return view('city.category.show', $categoryData['data']);
        }

        if (isset($categoryData['cityRedirectUrl'])) {
            return redirect()->to($categoryData['cityRedirectUrl'], 302);
        }

        $district = $this->findDistrict($country, $region, $city);
        if (! is_null($district)) {
            return $this->handleDistrictRequest($district, $doBookingSearch);
        }

        return $this->handleCityRequest($country, $region, $city, $request, $doBookingSearch);
    }

    private function handleCityRequest($country, mixed $region, mixed $city, Request $request, bool $doBookingSearch = false)
    {
        $cityInfo = $this->cityInfoService->getCityInfo($country, $city, $region);

        if (is_string($cityInfo)) {
            return redirect()->to($cityInfo, 301);
        }

        if (! $cityInfo) {
            return $this->redirectToSearch($request->route()->parameters()); // last resort... send them to the search page.
        }

        // * Check the URL *
        $correctURL = $this->getCorrectUrl($cityInfo);
        if ('/' . $request->path() !== $correctURL) {
            return redirect($correctURL, 301);
        }

        Session::put('localCurrency', $cityInfo->determineLocalCurrency());

        // * City Pics *
        $cityPics = $this->picsService->getCityPics($cityInfo);
        if ($cityPics !== null) {
            $cityPics = $cityPics->random();
        }

        $ogThumbnail = $cityPics ? $cityPics->url([''], 'absolute') : url('images', 'best-hostel-price-comparison.jpg');

        // * listingFilters *
        $this->listingsFiltersService->setListings($cityInfo);
        $listingFilters = $this->listingsFiltersService->getListingsFilters();

        //metaValues
        $metaValues = $this->cityInfoService->getMetaValues($cityInfo);

        $cityComments = $this->cityCommentsService->getCityComments($cityInfo);

        $priceAVG = $cityInfo->getPriceAVG();

        $seoTable = $this->getSeoTable($cityInfo, $priceAVG);

        $pageType = 'city';

        $districts = $cityInfo->districts()->active()->get();

        $listingsData = ! $doBookingSearch
            ? $this->cityListingsService->getListingsData(
                $cityInfo,
                ListingsOptionsService::getDefaultListingsShowOptions(),
            )
            : null;

        $cityCategories = resolve(ListingCategoryPageService::class)
            ->activeForCity($cityInfo);

        PageCache::addCacheTags('city:' . $cityInfo->id); // mark the cache so it can by cleared when the city is edited

        return view('city', compact(
            'cityInfo',
            'cityPics',
            'listingFilters',
            'districts',
            'metaValues',
            'cityComments',
            'ogThumbnail',
            'seoTable',
            'priceAVG',
            'pageType',
            'listingsData',
            'cityCategories'
        ));
    }

    private function getSeoTable(CityInfo $cityInfo, $priceAVG): array|null
    {
        if ($cityInfo->hostelCount === 0) {
            return null;
        }

        $items['totalNumber'] = $cityInfo->hostelCount;
        $items['average'] = $priceAVG;

        $items['cheapest'] = $this->getCheapest($cityInfo);

        $slp = SpecialLandingPage::forCity($cityInfo->city)->where(
            'category',
            CategorySlp::Cheap
        )->first();
        $items['slp'] = $slp ? $slp->path : null;

        $partyHostels = $cityInfo->getPartyHostels();
        $items['partyHostels'] = ($partyHostels && $partyHostels['count'] > 0) ?
            [
                'count' => $partyHostels['count'],
                'best' => [
                    'url' => $partyHostels['best']->getURL(),
                    'name' => $partyHostels['best']->name,
                ],
            ] :
            null;

        $neighborhood = $cityInfo->getMostRatingNeighborhood();
        $items['neighborhood'] = $neighborhood ? implode(', ', $neighborhood) : null;

        $cityAVGHostelsRating = $cityInfo->getAVGHostelsRating();
        $items['cityAVGHostelsRating'] = $cityAVGHostelsRating / 10;

        return $items;
    }

    private function getCheapest(CityInfo $cityInfo): array|null
    {
        if (! ($cheapest = $cityInfo->getCheapestHostel())) {
            return null;
        }

        return [
            'name' => $cheapest->name,
            'url' => $cheapest->path,
            'price' => $cheapest->getDormAveragePrice(),
        ];
    }
}
