<?php

namespace App\Services\Listings;

use App\Helpers\LegacyWebsite;
use App\Models\CityInfo;
use App\Models\Geonames;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;

class CityInfoService
{
    public const TOP_CITIES_MAX_NUMBER = 5;

    private bool $isCityGroupPage;

    public function getCityInfoByID($cityID): CityInfo
    {
        $cityInfo = CityInfo::areLive()
            ->where('id', $cityID)
            ->with('countryInfo')
            ->first();

        abort_if(! $cityInfo, 404);

        return $cityInfo;
    }

    public function getCityInfo(?string $country, ?string $city, ?string $region = '')
    {
        $cityInfo = CityInfo::areLive()->fromUrlParts($country, $region, $city)->first();

        if (! $cityInfo) {
            $cityInfo = $this->getOtherCityInfo($country, $region, $city);
        }

        return $cityInfo;
    }

    public function getCitiesInfo(?string $countrySlug, ?string $regionOrCityGroupSlug, ?string $slug)
    {
        $this->isCityGroupPage = false;

        $citiesInfo = CityInfo::areLive()->fromUrlParts($countrySlug, $regionOrCityGroupSlug)->orderBy('city')->get();
        if (! empty($regionOrCityGroupSlug) && $citiesInfo->isEmpty()) {
            $citiesInfo = CityInfo::areLive()->fromCityGroupUrlParts($countrySlug, $regionOrCityGroupSlug)->orderBy('city')->get();

            if ($citiesInfo->isNotEmpty()) {
                $this->isCityGroupPage = true;

                return $citiesInfo;
            }
        }

        if ($citiesInfo->isEmpty()) {
            return $this->getOtherCitiesInfo($countrySlug, $regionOrCityGroupSlug, $slug);
        }

        return $citiesInfo;
    }

    public function getTopCitiesByHostelsCount(Collection $citiesInfo): Collection
    {
        return $citiesInfo->sortByDesc('hostelCount')->take(self::TOP_CITIES_MAX_NUMBER);
    }

    public function getTopCitiesListings(Collection $topCities): Collection
    {
        return $topCities->mapWithKeys(function (CityInfo $city) {
            $listings = Listing::bestHostels($city)->take(5)->get();

            if ($listings->isEmpty()) {
                return [];
            }

            $sliderData = $listings->map(fn (Listing $listing, $key) => $listing->getExploreSectionData($key));

            return [
                $city->city => collect([
                    'url' => $city->url,
                    'listings' => $sliderData,
                    'slps' => SpecialLandingPage::query()
                        ->forCity($city->city)
                        ->get()
                        ->map(fn (SpecialLandingPage $slp) => (object) [
                            'slug_id' => $slp->slug_id,
                            'title' => $slp->meta->title,
                            'path' => $slp->path,
                        ]),
                ]),
            ];
        })
            ->filter();
    }

    public function addCityUrls(Collection $cities): Collection
    {
        return $cities->each(function (CityInfo $city): void {
            $city->url = $city->getUrl();
        });
    }

    public function getMetaValues(CityInfo $cityInfo, array $values = []): array
    {
        return array_merge([
            'hostelCount' => $cityInfo->hostelCount,
            'count' => $cityInfo->totalListingCount,
            'city' => $cityInfo->translation()->city,
            'month' => date('M'),
            'year' => date('Y'),
            'lowestDormPrice' => floor($cityInfo->lowestDormPrice * config('custom.minPriceCoefficient')),
            'area' => $cityInfo->translation()->country,
            'continent' => $cityInfo->translation()->continent,
            'roomType' => strtolower(__('bookingProcess.searchCriteria.dormbed')),
        ], $values);
    }

    public function getCityGroupPageBool(): bool
    {
        return $this->isCityGroupPage;
    }

    public function getCityGroupList(bool $isCityGroupPage, Collection $citiesInfo): ?Collection
    {
        if ($isCityGroupPage) {
            return null;
        }

        $cityGroupsList = $citiesInfo->unique('cityGroup')->filter(function ($item) {
            return $item->cityGroup !== '' && $item->cityGroup !== $item->region;
        })->sortBy('cityGroup', SORT_ASC);

        if ($cityGroupsList->isEmpty()) {
            return null;
        }

        return $cityGroupsList;
    }

    public function getCityRegions(Collection $citiesInfo): ?Collection
    {
        $regionsList = $citiesInfo->unique('region')->filter(function ($city) {
            return $city->displaysRegion && $city->region !== '';
        })->sortBy('region', SORT_ASC);

        if ($regionsList->isEmpty()) {
            return null;
        }

        return $regionsList;
    }

    private function getOtherCitiesInfo(string $countrySlug, $regionOrCityGroupSlug, string $slug)
    {
        if ($continentInfo = LegacyWebsite::findContinentByTranslatedURL($slug)) {
            return '/hostels-in/' . $continentInfo->continentName;
        }

        // Try Geonames (mostly to handle old website URLs that were foreign language translated)
        $cityInfo = $this->searchGeonamesForCityInfoFromUrlParts($countrySlug, $regionOrCityGroupSlug);

        if ($cityInfo) {
            if (! $regionOrCityGroupSlug) {
                return $cityInfo->getCountryURL();
            }
            if ($cityInfo->displaysRegion) {
                return $cityInfo->getRegionURL();
            }
        }
    }

    private function getOtherCityInfo(?string $country, ?string $region, ?string $city)
    {
        $cityInfo = CityInfo::areLive()->fromUrlParts($country, $city, null)->where('displaysRegion', true)->first();
        if ($cityInfo) {
            return $cityInfo->getRegionURL();
        }
        // Check to see if we moved it to a cityGroup
        $cityInfo = CityInfo::areLive()->fromCityGroupUrlParts($country, $city, null)->first();
        if ($cityInfo) {
            return $cityInfo->getCityGroupURL();
        }
        // Try Geonames (mostly to handle old website URLs that were foreign language translated)
        $cityInfo = $this->searchGeonamesForCityInfoFromUrlParts($country, $region, $city);

        if ($cityInfo) {
            return $cityInfo->getURL();
        }

        // At least get the country.
        $countryCityInfo = CityInfo::areLive()->fromUrlParts($country)->first();
        if ($countryCityInfo) {
            $citySearchString = str_replace('-', '_', $city);
            // Check to see if it's now the cityAlt of another city...
            $cityInfo = CityInfo::areLive()
                ->where('country', $countryCityInfo->country)
                ->where('cityAlt', strpos($citySearchString, '_') !== false ? 'LIKE' : '=', $citySearchString)
                ->first();

            if ($cityInfo) {
                return $cityInfo->getURL();
            }
            // Check to see if we moved it to a neighborhood
            $listing = Listing::areLive()
                ->where('country', $countryCityInfo->country)
                ->where('cityAlt', strpos($citySearchString, '_') !== false ? 'LIKE' : '=', $citySearchString)
                ->first();

            if ($listing && $listing->cityInfo) {
                return $listing->cityInfo->getURL();
            }
        }
    }

    private function searchGeonamesForCityInfoFromUrlParts(?string $country, ?string $region = '', ?string $city = ''): ?CityInfo
    {
        // Change dashes to the SQL wildcard character "_" because some city characters are replaced with "-" when generating URLs.
        $country = str_replace('-', '_', $country);
        $region = str_replace('-', '_', $region);
        $city = str_replace('-', '_', $city);

        $geonamesResult = Geonames::findCityRegionCountry(
            $country,
            $region,
            $city,
            strpos($country, '_') !== false || strpos($region, '_') !== false || strpos($city, '_') !== false ? 'LIKE' : '='
        );

        if (isset($geonamesResult['city'])) {
            $cityInfo = CityInfo::where('gnCityID', $geonamesResult['city']->id)->areLive()->first();
            if ($cityInfo) {
                return $cityInfo;
            }
        }

        if ($city === '' && isset($geonamesResult['region'])) { // If just searching for a region or country...
            $cityInfo = CityInfo::where('gnRegionID', $geonamesResult['region']->id)->areLive()->first();
            if ($cityInfo) {
                return $cityInfo;
            }
        }

        if ($city === '' && $region === '' && isset($geonamesResult['country'])) { // If just searching for a country...
            $cityInfo = CityInfo::where('gnCountryID', $geonamesResult['country']->geonamesID)->areLive()->first();
            if ($cityInfo) {
                return $cityInfo;
            }
        }

        return null;
    }
}
