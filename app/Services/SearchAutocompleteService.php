<?php

namespace App\Services;

use App\Exceptions\LocationException;
use App\Models\AltName;
use App\Models\City;
use App\Models\CityGroup;
use App\Models\CityInfo;
use App\Models\Country;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Region;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SearchAutocompleteService
{
    private const QUERY_RESULT_LIMIT = 10;

    private const FINAL_RESULT_LIMIT = 50;

    private Collection $searchResults;

    public function makeSearch(string $query): Collection
    {
        $this->getSearchResults($query);

        $searchResult = $this->searchResults->all();
        $this->orderByMostRelevantSearchResult($query, $searchResult);
        $this->removeDuplicateSearchResults($searchResult);

        return $this->collectResults($searchResult);
    }

    public function makeNearbyDestinationsSearch(): Collection
    {
        try {
            $locationService = new LocationService();

            $latitude = $locationService->getLatitudeByIP();
            $longitude = $locationService->getLongitudeByIP();
            $countryName = $locationService->getCountryByIP();
            $city = $locationService->getCityByIP();

            $cities = $this->searchNearbyCities($latitude, $longitude, $countryName, $city);
        } catch (LocationException $exception) {
            Log::channel('searchAutocomplete')->error($exception);

            $cities = collect([]);
        }

        return $this->collectResults($cities);
    }

    public function makeNearbyCountryRegionDestinationsSearch(string $location): Collection
    {
        $geo = CityInfo::query()
            ->select('latitude', 'longitude', 'city', 'country', 'region')
            ->where('country', $location)
            ->orWhere('region', $location)
            ->get()
            ->first()
            ->toArray();

        $latitude = $geo['latitude'];
        $longitude = $geo['longitude'];
        $countryName = $geo['country'];
        $region = $geo['region'] ?? null;
        $city = $geo['city'];

        $cities = $this->searchNearbyCities($latitude, $longitude, $countryName, $city, $region);

        return $this->collectResults($cities);
    }

    public function makeNearbyCityDestinationsSearch(string $url, string $city): Collection
    {
        $countryName = $this->parseCityPageUrl($url);

        $geo = CityInfo::query()
            ->select('latitude', 'longitude', 'city')
            ->where('country', $countryName)
            ->where('city', $city)
            ->get()
            ->first()
            ->toArray();

        $latitude = $geo['latitude'];
        $longitude = $geo['longitude'];

        $cities = $this->searchNearbyCities($latitude, $longitude, $countryName, $city);

        return $this->collectResults($cities);
    }

    public function makeNearbyListingDestinationsSearch(string $hostelName): Collection
    {
        $geo = Listing::query()
            ->select('latitude', 'longitude', 'country')
            ->where('name', $hostelName)
            ->get()
            ->first()
            ->toArray();

        $latitude = $geo['latitude'];
        $longitude = $geo['longitude'];
        $countryName = $geo['country'];

        $listings = $this->searchNearbyListings($latitude, $longitude, $countryName);

        return $this->collectResults($listings);
    }

    public function getFeaturedCities(): Collection
    {
        $cities = CityInfo::getFeaturedCitiesData(['*'])->map(function ($item) {
            $item['text'] = "$item->city, $item->country";
            $item['category'] = __('index.PopularCities');
            $item['order'] = 0;
            $item['url'] = $item->getUrl('absolute');

            return $item;
        });

        return $this->collectResults($cities);
    }

    private function collectResults(array|Collection $results): Collection
    {
        return collect($results)
            ->sortBy('order')
            ->values()
            ->transform(function ($item) {
                return [
                    'value' => $item['text'],
                    'data' => [
                        'category' => $item['category'],
                        'query' => $item['query'] ?? $item['text'],
                        'url' => urldecode($item['url']) ?? '',
                        'img' => $item['img'] ?? '',
                        'itemId' => $item['id'] ?? 0,
                        'highlighted' => $item['highlighted'] ?? '',
                    ],
                ];
            })
            ->slice(0, self::FINAL_RESULT_LIMIT);
    }

    private function getSearchResults(string $query): void
    {
        $queryResults = collect();

        if (Languages::currentCode() !== 'en') {
            $queryResults = $queryResults->merge($this->searchAltNames($query));
        }
        $queryResults = $queryResults->merge($this->searchCities($query));
        $queryResults = $queryResults->merge($this->searchCityGroups($query));
        $queryResults = $queryResults->merge($this->searchRegions($query));
        $queryResults = $queryResults->merge($this->searchCountries($query));
        $queryResults = $queryResults->merge($this->searchListings($query));

        $this->searchResults = $queryResults;
    }

    private function searchNearbyListings($latitude, $longitude, $countryName)
    {
        return Listing::nearbyListingsInCountry($latitude, $longitude, $countryName)
            ->orderByDesc('combinedRating')
            ->limit(5)
            ->get()
            ->map(function ($item) {
                $item['text'] = "{$item->name}, {$item->city}, {$item->country}";
                $item['category'] = __('global.autocomplete.nearby');
                $item['order'] = 0;
                $item['url'] = $item->getUrl('absolute');
                $item['img'] = $item->thumbnailURL();

                return $item;
            });
    }

    private function searchNearbyCities($latitude, $longitude, $countryName, $city, $region = null)
    {
        $currentCity = CityInfo::query()
            ->select('id', 'nearbyCities', 'city')
            ->where('country', $countryName)
            ->when(
                $region,
                fn ($query) => $query->where('region', $region)
            )
            ->where('city', $city)
            ->orWhere(function ($query) use ($longitude, $latitude): void {
                $query->where('latitude', '<', $latitude + 1)
                    ->where('latitude', '>', $latitude - 1)
                    ->where('longitude', '<', $longitude + 1)
                    ->where('longitude', '>', $longitude - 1);
            })
            ->get()
            ->first();

        if (! $currentCity) {
            Log::channel('searchAutocomplete')
                ->error("Can't recognized current city for these options - $countryName, $city, latitude - $latitude, longitude - $longitude");

            return collect([]);
        }

        $cities = collect([]);

        foreach ($currentCity->nearbyCities as $nearbyCity) {
            $city = CityInfo::find($nearbyCity['cityID']);

            if ($city) {
                $cities[] = $city;
            }
        }

        return $cities->map(function ($item) {
            $item['text'] = "$item->city, $item->country";
            $item['category'] = __('global.autocomplete.nearby');
            $item['order'] = 0;
            $item['url'] = $item->getUrl('absolute');

            return $item;
        });
    }

    private function searchAltNames($query): Collection
    {
        return AltName::query()
            ->select(
                'altName as text',
                DB::raw('99999 as score')
            )
            ->where('altName', 'LIKE', $query . '%')
            ->where('language', Languages::currentCode())
            ->orderBy('isShortName', 'desc')
            ->orderBy('isPreferredName', 'desc')
            ->limit(self::QUERY_RESULT_LIMIT)
            ->get()
            ->map(function ($item) {
                $item['category'] = langGet('global.autocomplete.altNames');
                $item['order'] = 50;
                $item['url'] = '';

                return $item;
            });
    }

    private function searchListings(string $query): Collection
    {
        try {
            $items = Listing::search($query, function ($meili, $query, $options) {
                $meili->updateSettings([
                    'rankingRules' => [
                        'exactness',
                        'words',
                        'typo',
                        'proximity',
                        'attribute',
                        'sort',
                    ],
                    'sortableAttributes' => [
                        'combinedRating',
                        'combinedRatingCount',
                    ],
                ]);

                $options['attributesToHighlight'] = ['name'];
                $options['sort'] = ['combinedRating:desc', 'combinedRatingCount:desc'];
                $options['limit'] = self::QUERY_RESULT_LIMIT;

                return $meili->search($query, $options);
            })
            ->raw();

            $itemsCount = count($items['hits']);

            return collect($items['hits'])
                ->map(function ($item) use ($itemsCount) {
                    $highlighted = $item['_formatted']['name'];

                    $item = Listing::find($item['id']);
                    $item['text'] = "{$item->name}, {$item->city}, {$item->country}";
                    $item['query'] = $item->name;
                    $item['highlighted'] = $highlighted;
                    $item['category'] = __('global.autocomplete.properties');
                    $item['order'] = 60;
                    $item['score'] = $itemsCount;
                    $item['url'] = $item->getURL('absolute');
                    $item['img'] = $item->thumbnailURL();

                    return $item;
                });
        } catch (Exception $exception) {
            Log::channel('searchAutocomplete')
                ->error('Returning to old listings search because of ' . $exception->getMessage() . ' with query: ' . $query);

            return $this->queryForHasWordStartingWith(Listing::areLive(), $query, 'name')
                ->select(
                    DB::raw("CONCAT(name, ', ', city, ', ' ,country) AS text"),
                    'name as query',
                    DB::raw('count(*) as score'),
                    'id'
                )
                ->groupBy('name')->orderByRaw('count(*) DESC')
                ->orderByRaw('name LIKE ? DESC', $query . '%')
                ->limit(self::QUERY_RESULT_LIMIT)->get()
                ->map(function ($item) {
                    $item['category'] = langGet('global.autocomplete.properties');
                    $item['order'] = 60;
                    $item['img'] = $item->thumbnailURL();
                    $item['url'] = $item->getURL('absolute');

                    return $item;
                });
        }
    }

    private function searchCities(string $query): Collection
    {
        try {
            $items = City::search($query, function ($meili, $query, $options) {
                $meili->updateSettings([
                    'searchableAttributes' => [
                        'city',
                    ],
                    'sortableAttributes' => [
                        'hostelCount',
                        'totalListingCount',
                    ],
                ]);

                $options['attributesToHighlight'] = ['city'];
                $options['sort'] = ['totalListingCount:desc', 'hostelCount:desc'];
                $options['limit'] = self::QUERY_RESULT_LIMIT;

                return $meili->search("$query%", $options);
            })
            ->raw();

            return collect($items['hits'])
                ->map(function ($item) {
                    $highlighted = $item['_formatted']['city'];

                    $item = City::find($item['id']);
                    $item->text = "{$item->city}, {$item->country}";
                    $item->query = $item->city;
                    $item->highlighted = $highlighted;
                    $item->category = __('global.autocomplete.city');
                    $item->order = 0;
                    $item->score = $item->totalListingCount;
                    $item->url = $item->getURL('absolute');

                    return $item;
                });
        } catch (Exception $exception) {
            Log::channel('searchAutocomplete')
                ->error('Returning to old city search because of ' . $exception->getMessage() . ' with query: ' . $query);

            return $this->queryForHasWordStartingWith(CityInfo::areLive(), $query, 'city')
                ->select(
                    DB::raw("CONCAT(city, ', ' ,country) AS text"),
                    'city as query',
                    'totalListingCount as score',
                    'id',
                    'city',
                    'country'
                )
                ->orderBy('hostelCount', 'DESC')
                ->orderBy('totalListingCount', 'DESC')
                ->limit(self::QUERY_RESULT_LIMIT)
                ->get()
                ->map(function ($item) {
                    $item['category'] = __('global.autocomplete.city');
                    $item['order'] = 0;
                    $item['url'] = $item->getURL('absolute');

                    return $item;
                });
        }
    }

    private function searchCityGroups(string $query): Collection
    {
        try {
            $items = CityGroup::search($query, function ($meili, $query, $options) {
                $meili->updateSettings([
                    'searchableAttributes' => [
                        'cityGroup',
                    ],
                    'distinctAttribute' => 'cityGroup',
                ]);

                $options['attributesToHighlight'] = ['cityGroup'];
                $options['limit'] = self::QUERY_RESULT_LIMIT;

                return $meili->search($query, $options);
            })
            ->raw();

            $itemsCount = count($items['hits']);

            return collect($items['hits'])
                ->map(function ($item) use ($itemsCount) {
                    $highlighted = $item['_formatted']['cityGroup'];

                    $item = CityGroup::find($item['id']);
                    $item->text = $item->cityGroup;
                    $item->query = $item->cityGroup;
                    $item->highlighted = $highlighted;
                    $item->category = __('global.autocomplete.cityGroup');
                    $item->order = 5;
                    $item->score = $itemsCount;
                    $item->url = $item->getCityGroupURL('absolute');

                    return $item;
                });
        } catch (Exception $exception) {
            Log::channel('searchAutocomplete')
                ->error('Returning to old city groups search because of ' . $exception->getMessage() . ' with query: ' . $query);

            return $this->queryForHasWordStartingWith(CityInfo::areLive(), $query, 'cityGroup')
                ->select(
                    'cityGroup as text',
                    DB::raw('count(*) as score'),
                    'id',
                    'cityGroup',
                    'country'
                )
                ->groupBy('cityGroup')
                ->orderByRaw('count(*) DESC')
                ->limit(self::QUERY_RESULT_LIMIT)
                ->get()
                ->map(function ($item) {
                    $item['category'] = __('global.autocomplete.cityGroup');
                    $item['order'] = 5;
                    $item['url'] = $item->getCityGroupURL('absolute');

                    return $item;
                });
        }
    }

    private function searchRegions(string $query): Collection
    {
        try {
            $items = Region::search($query, function ($meili, $query, $options) {
                $meili->updateSettings([
                    'searchableAttributes' => [
                        'region',
                    ],
                    'filterableAttributes' => [
                        'displaysRegion',
                    ],
                    'distinctAttribute' => 'region',
                ]);

                $options['filter'] = ['displaysRegion = 1'];
                $options['attributesToHighlight'] = ['region'];
                $options['limit'] = self::QUERY_RESULT_LIMIT;

                return $meili->search($query, $options);
            })
            ->raw();

            $itemsCount = count($items['hits']);

            return collect($items['hits'])
                ->map(function ($item) use ($itemsCount) {
                    $highlighted = $item['_formatted']['region'];

                    $item = Region::find($item['id']);
                    $item->text = $item->region;
                    $item->query = $item->region;
                    $item->highlighted = $highlighted;
                    $item->category = __('global.autocomplete.region');
                    $item->order = 10;
                    $item->score = $itemsCount;
                    $item->url = $item->getRegionURL('absolute');

                    return $item;
                });
        } catch (Exception $exception) {
            Log::channel('searchAutocomplete')
                ->error('Returning to old region search because of ' . $exception->getMessage() . ' with query: ' . $query);

            return $this->queryForHasWordStartingWith(CityInfo::areLive(), $query, 'region')
                ->select(
                    'region as text',
                    DB::raw('count(*) as score'),
                    'id',
                    'region',
                    'country',
                    'displaysRegion'
                )
                ->where('displaysRegion', true)
                ->groupBy('region')->orderByRaw('count(*) DESC')
                ->limit(self::QUERY_RESULT_LIMIT)
                ->get()
                ->map(function ($item) {
                    $item['category'] = __('global.autocomplete.region');
                    $item['order'] = 10;
                    $item['url'] = $item->getRegionURL('absolute');

                    return $item;
                });
        }
    }

    private function searchCountries(string $query): Collection
    {
        try {
            $items = Country::search($query, function ($meili, $query, $options) {
                $meili->updateSettings([
                    'rankingRules' => [
                        'exactness',
                        'sort',
                    ],
                    'searchableAttributes' => [
                        'country',
                    ],
                    'sortableAttributes' => [
                        'hostelCount',
                        'totalListingCount',
                    ],
                    'distinctAttribute' => 'country',
                ]);

                $options['attributesToHighlight'] = ['country'];
                $options['sort'] = ['totalListingCount:desc', 'hostelCount:desc'];
                $options['limit'] = self::QUERY_RESULT_LIMIT;

                return $meili->search($query, $options);
            })
            ->raw();

            $itemsCount = count($items['hits']);

            return collect($items['hits'])
                ->map(function ($item) use ($itemsCount) {
                    $highlighted = $item['_formatted']['country'];

                    $item = Country::find($item['id']);
                    $item->text = $item->country;
                    $item->query = $item->country;
                    $item->highlighted = $highlighted;
                    $item->category = __('global.autocomplete.country');
                    $item->order = 20;
                    $item->score = $itemsCount;
                    $item->url = $item->getCountryURL('absolute');
                    $item->img = ($item->countryInfo) ? routeURL(
                        'images',
                        'flags/' . strtolower($item->countryInfo->countryCode()) . '.svg'
                    ) : '';

                    return $item;
                });
        } catch (Exception $exception) {
            Log::channel('searchAutocomplete')
                ->error('Returning to old country search because of ' . $exception->getMessage() . ' with query: ' . $query);

            return $this->queryForHasWordStartingWith(CityInfo::areLive(), $query, 'country')
                ->select(
                    'country as text',
                    DB::raw('count(*) as score'),
                    'id',
                    'city',
                    'country'
                )
                ->with('countryInfo.geonames')
                ->groupBy('country')
                ->orderByRaw('count(*) DESC')
                ->limit(self::QUERY_RESULT_LIMIT)
                ->get()
                ->map(function ($item) {
                    $item['category'] = __('global.autocomplete.country');
                    $item['order'] = 20;
                    $item['url'] = $item->getCountryURL('absolute');
                    $item['img'] = ($item->countryInfo) ? routeURL(
                        'images',
                        'flags/' . strtolower($item->countryInfo->countryCode()) . '.svg'
                    ) : '';

                    return $item;
                });
        }
    }

    private function parseCityPageUrl(string $url): string
    {
        $url = explode('/', parse_url($url)['path']);

        $country = Languages::currentCode() === 'en' ? $url[2] : $url[4];

        return str_replace('-', ' ', $country);
    }

    private function queryForHasWordStartingWith($query, $search, $fieldName)
    {
        // Note: Wildcard searches that start with '%' are slow because can't use the index, but it's probably worth it.  If we decide it's not, can remove it later.
        return $query
            ->where($fieldName, 'LIKE', "%{$search}%")
            ->orderByRaw("$fieldName LIKE ? DESC", $search . '%');
        // results that start with the string are preferred
    }

    // $list is expected to be an array of arrays that contain 'text' and 'score' elements.
    private function orderByMostRelevantSearchResult($searchString, &$list): void
    {
        uksort($list, function ($keyA, $keyB) use ($searchString, $list) {
            $a = $list[$keyA];
            $b = $list[$keyB];

            // check if only one is an exact match
            if (strcasecmp($a['text'], $searchString) === 0) {
                return -1;
            }
            if (strcasecmp($b['text'], $searchString) === 0) {
                return 1;
            }

            // check if only one starts with the search string
            if (stripos($a['text'], $searchString) === 0 && stripos($b['text'], $searchString) !== 0) {
                return -1;
            }
            if (stripos($b['text'], $searchString) === 0 && stripos($a['text'], $searchString) !== 0) {
                return 1;
            }

            // check if only one has a word that starts with the search string
            if (stripos($a['text'], ' ' . $searchString) !== false && stripos(
                $b['text'],
                $searchString
            ) !== 0 && stripos($b['text'], ' ' . $searchString) === false) {
                return -1;
            }
            if (stripos($b['text'], ' ' . $searchString) !== false && stripos(
                $a['text'],
                $searchString
            ) !== 0 && stripos($a['text'], ' ' . $searchString) === false) {
                return 1;
            }

            // order
            if ($a['order'] !== $b['order']) {
                return $a['order'] < $b['order'] ? -1 : 1;
            }

            // check if one has a better score
            if ($a['score'] !== $b['score']) {
                return $a['score'] > $b['score'] ? -1 : 1;
            }

            // Default (retain the original sort order)
            return $keyA > $keyB ? 1 : -1;
        });
    }

    // Removes duplicates from list based just on the contents of their 'text' element.
    private function removeDuplicateSearchResults(&$list): void
    {
        $list = array_filter($list, function ($item) {
            static $alreadySeen = [];
            if (in_array($item['text'], $alreadySeen, true)) {
                return false;
            }
            $alreadySeen[] = $item['text'];

            return true;
        });
    }
}
