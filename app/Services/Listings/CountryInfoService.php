<?php

namespace App\Services\Listings;

use App\Models\AttachedText;
use App\Models\ContinentInfo;
use App\Models\CountryInfo;
use Illuminate\Support\Collection;

class CountryInfoService
{
    public function getCountriesByContinent(ContinentInfo $continent)
    {
        return $continent->countries();
    }

    public function getCountriesWithCities($countryInfos): Collection
    {
        $citiesInfo = [];

        foreach ($countryInfos as $countryInfo) {
            $citiesInfo[$countryInfo->country] = $countryInfo->cityInfos()->liveCities()->get()
                ->sortBy('city', SORT_ASC);
        }

        return collect($citiesInfo);
    }

    public function getCountriesWithContinents(array $continents): Collection
    {
        $continentsCountries = collect($continents)->map(fn ($continent) => $this->getCountriesByContinent($continent));

        return $this->addCountsAndUrlsToCountries($continentsCountries);
    }

    public function getCountryDescription(?Collection $cityGroupList, ?AttachedText $description): ?AttachedText
    {
        $descriptionText = $description->data ?? '';

        // Replace cityGroups with links
        if ($cityGroupList && $description) {
            $replacementsToDo = [];

            foreach ($cityGroupList as $cityGroupCityInfo) {
                $replacementsToDo[] = [
                    'searchStrings' => ["$cityGroupCityInfo->cityGroup hostels", "hostels in $cityGroupCityInfo->cityGroup",
                        "hostel in $cityGroupCityInfo->cityGroup", $cityGroupCityInfo->translation()->cityGroup, ],
                    'replacement' => '<a href="' . $cityGroupCityInfo->getCityGroupURL() . '">\\1</a>',
                ];
            }

            foreach ($replacementsToDo as $replacementToDo) {
                $descriptionText = $this->replaceWholeWordStringIgnoringLinks(
                    $descriptionText,
                    $replacementToDo['searchStrings'],
                    $replacementToDo['replacement'],
                    1
                );
            }

            $description->data = $descriptionText;
        }

        return $description;
    }

    private function addCountsAndUrlsToCountries(Collection $continentsCountries): Collection
    {
        return collect($continentsCountries)
            ->each(
                fn (Collection $countries) => $countries
                ->each(function (CountryInfo $country): void {
                    $this
                        ->addCityHostelsCountToCountries($country)
                        ->addUrlsToCountries($country);
                })
            );
    }

    private function addCityHostelsCountToCountries($country): static
    {
        $liveListings = $country->listings()->areLive();

        $country->totalListingCount = $liveListings->count();
        $country->hostelCount = $liveListings->where('propertyType', 'Hostel')->count();

        return $this;
    }

    private function addUrlsToCountries($country): static
    {
        $country->url = $country->getUrl();

        return $this;
    }

    private function replaceWholeWordStringIgnoringLinks($subject, $find, $replace, $limit = -1)
    {
        // Have to make sure existing links don't get their text replaced
        // so we remove the links, and then add them back after.
        preg_match_all('`(\<a .*\<\/a\>)`Uu', $subject, $links);
        $links = $links[1];
        foreach ($links as $key => $link) {
            $subject = str_replace($link, "***$key***", $subject);
        }

        // Have to do the loop ourselves instead of just passing an array to wholeWordReplace()
        // because PHP's preg_replace()'s limit is per search string.
        foreach ($find as $findString) {
            $subject = wholeWordReplace($subject, $findString, $replace, $limit, false, $count);
            if ($count) {
                break;
            }
        }

        foreach ($links as $key => $link) {
            $subject = str_replace("***$key***", $link, $subject);
        }

        return $subject;
    }
}
