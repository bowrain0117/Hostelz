<?php

namespace App\Helpers;

use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Languages;
use App\Models\Listing\Listing;
use DB;

class ListingAndCitySearch
{
    public static function search($search, $queryResultLimit, $maxResultsPerType)
    {
        $searchResults = self::performSearch($search, $queryResultLimit, Listing::areLive(), CityInfo::areLive(), CountryInfo::areLive());
        $searchResults = self::processSearchDataIntoSearchResults($searchResults);
        self::orderByMostRelevant($search, $searchResults, $maxResultsPerType);

        return $searchResults;
    }

    /*
        $listingBaseQuery and $cityInfoBaseQuery should be based if we want to search listings and/or cities.
    */

    public static function performSearch($search, $queryResultLimit, $listingBaseQuery = null, $cityInfoBaseQuery = null, $countryInfoBaseQuery = null)
    {
        $searchResultData = [];

        if ($listingBaseQuery) {
            $listingBaseQuery->limit($queryResultLimit);
            if (Languages::currentCode() != 'en') {
                $listingBaseQuery->with('cityInfo');
            } // used for translating the city
        }
        if ($cityInfoBaseQuery) {
            $cityInfoBaseQuery->orderBy('hostelCount', 'DESC')->orderBy('totalListingCount', 'DESC')->limit($queryResultLimit);
        }

        if ($listingBaseQuery) {
            // hostel id (used by staff)
            if (is_numeric($search) && intval($search) == $search) {
                // Note: We still use mergeSearchData() so that it only creates a 'listings' element if there were results found.
                return self::mergeSearchData($searchResultData, ['listings' => with(clone $listingBaseQuery)->where('id', $search)->get()->all()]);
            }

            // email (mostly for staff use)
            if (strpos($search, '@') && filter_var($search, FILTER_VALIDATE_EMAIL)) {
                debugOutput('Listing email');
                $data = with(clone $listingBaseQuery)->anyMatchingEmail($search)->get()->all();

                return self::mergeSearchData($searchResultData, ['listings' => $data]);
            }
        }

        // delete "hostels" from search strings
        if (preg_match('` ?hostels($| )`si', ' hostels') !== false) {
            $search = trim(str_replace('hostels', '', $search));
        }
        if ($search == '') {
            return [];
        }

        if ($countryInfoBaseQuery) {
            // Continent

            debugOutput('Continent with a word starting with the search');
            $data = self::queryForHasWordStartingWith(clone $countryInfoBaseQuery, $search, 'continent')->groupBy('continent')->get()->all();
            self::mergeSearchData($searchResultData, ['continents' => $data]);

            // Country

            debugOutput('Country with a word starting with the search');
            $data = self::queryForHasWordStartingWith(clone $countryInfoBaseQuery, $search, 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['countries' => $data]);

            debugOutput('country altNames');
            $data = with(clone $countryInfoBaseQuery)->byAltName($search)->get()->all();
            self::mergeSearchData($searchResultData, ['countries' => $data]);
        }

        if ($cityInfoBaseQuery) {
            // City Group
            //
            debugOutput('CityGroup with a word starting with the search');
            $data = self::queryForHasWordStartingWith(clone $cityInfoBaseQuery, $search, 'cityGroup')->groupBy('cityGroup', 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['cityGroups' => $data]);

            debugOutput('City with a word starting with the search');
            $data = self::queryForHasWordStartingWith(clone $cityInfoBaseQuery, $search, 'city')->groupBy('city', 'region', 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['cities' => $data]);

            debugOutput('city altNames');
            $data = with(clone $cityInfoBaseQuery)->byCityAltName($search)->groupBy('city', 'region', 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['cities' => $data]);

            // CityInfo's cityAlt

            debugOutput("CityInfo's cityAlt with a word starting with the search");
            // (note that we still group by the city in case multiple cities have the same cityAlt)
            $data = self::queryForHasWordStartingWith(clone $cityInfoBaseQuery, $search, 'cityAlt')->groupBy('city', 'region', 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['cities' => $data]);

            // Region

            debugOutput('Region with a word starting with the search');
            $data = self::queryForHasWordStartingWith(clone $cityInfoBaseQuery, $search, 'region')->where('displaysRegion', true)->groupBy('region', 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['regions' => $data]);

            debugOutput('region altNames');
            $data = with(clone $cityInfoBaseQuery)->byRegionAltName($search)->where('displaysRegion', true)->groupBy('region', 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['regions' => $data]);
        }

        if ($listingBaseQuery) {
            // Neighborhood

            debugOutput("Listing's Neighborhood with a word starting with the search");
            $data = self::queryForHasWordStartingWith(clone $listingBaseQuery, $search, 'cityAlt')->get()->all();
            self::mergeSearchData($searchResultData, ['listings' => $data]);

            debugOutput("Listing's name"); // (you would think the fulltext search would cover names, but it doesn't seem to do a good job of it)
            $data = self::queryForHasWordStartingWith(clone $listingBaseQuery, $search, 'name')->get()->all();
            self::mergeSearchData($searchResultData, ['listings' => $data]);
        }

        // Soundex

        if ($countryInfoBaseQuery && ! $searchResultData) {
            debugOutput('Soundex country');
            $data = with(clone $countryInfoBaseQuery)->whereRaw("SOUNDEX(?) != '' AND SOUNDEX(country) = SOUNDEX(?)", [$search, $search])->get()->all();
            self::mergeSearchData($searchResultData, ['countries' => $data]);
        }

        if ($cityInfoBaseQuery && ! $searchResultData) {
            debugOutput('Soundex city');
            $data = with(clone $cityInfoBaseQuery)->whereRaw("SOUNDEX(?) != '' AND SOUNDEX(city) = SOUNDEX(?)", [$search, $search])->get()->all();
            self::mergeSearchData($searchResultData, ['cities' => $data]);

            debugOutput('Soundex region');
            $data = with(clone $cityInfoBaseQuery)->whereRaw("SOUNDEX(?) != '' AND SOUNDEX(region) = SOUNDEX(?)", [$search, $search])
                ->where('displaysRegion', true)->groupBy('region', 'country')->get()->all();
            self::mergeSearchData($searchResultData, ['regions' => $data]);
        }

        // Fulltext

        if ($cityInfoBaseQuery) {
            debugOutput('FullText cityInfo');
            $data = with(clone $cityInfoBaseQuery)->whereRaw('MATCH(city, cityAlt, cityGroup, region, country) AGAINST (? IN NATURAL LANGUAGE MODE)', [$search])->get()->all();
            self::mergeSearchData($searchResultData, self::categorizeCityInfoFullTextResults($search, $data));
        }

        if ($listingBaseQuery) {
            debugOutput('FullText listings');
            $data = with(clone $listingBaseQuery)
                //->select(DB::raw('*, MATCH(name, address, city, cityAlt, region, country) AGAINST (? IN NATURAL LANGUAGE MODE) as score', [ $search ]))
                ->whereRaw('MATCH(name, address, city, cityAlt, region, country) AGAINST (? IN NATURAL LANGUAGE MODE)', [$search])->get()->all();
            self::mergeSearchData($searchResultData, ['listings' => $data]);
        }

        /*
        (probably not necessary?)

    	debugOutput("like oldValue city in dataCorrection");
    	$rows = dbGetAll("SELECT DISTINCT(cityInfo.id),city,region,country, (dataCorrection.oldValue='$search') as isExact FROM dataCorrection,cityInfo WHERE dataCorrection.dbTable='listings' AND dataCorrection.dbField='city' AND dataCorrection.oldValue LIKE '%$search%' AND cityInfo.city=dataCorrection.newValue AND cityInfo.totalListingCount!=0 ORDER BY isExact DESC, hostelCount DESC LIMIT $maxResults");
    	self::mergeSearchData($searchResultData, [ 'cities' => $rows ]);

    	debugOutput("like oldValue country in dataCorrection");
    	$rows = dbGetAll("SELECT DISTINCT(country), (dataCorrection.oldValue='$search') as isExact FROM dataCorrection,cityInfo WHERE dataCorrection.dbTable='listings' AND dataCorrection.dbField='country' AND dataCorrection.oldValue='$search' AND dataCorrection.oldValue!=dataCorrection.newValue AND dataCorrection.newValue!='' AND cityInfo.country=dataCorrection.newValue AND cityInfo.totalListingCount!=0 ORDER BY isExact DESC, hostelCount DESC LIMIT $maxResults");
    	self::mergeSearchData($searchResultData, [ 'countries' => $rows ]);

    	debugOutput("like oldValue region in dataCorrection");
    	$rows = dbGetAll("SELECT region, country, (dataCorrection.oldValue='$search') as isExact FROM dataCorrection,cityInfo WHERE dataCorrection.dbTable='listings' AND dataCorrection.dbField='region' AND dataCorrection.oldValue LIKE '%$search%' AND dataCorrection.newValue!='' AND cityInfo.region=dataCorrection.newValue AND cityInfo.totalListingCount!=0 AND cityInfo.region!='' GROUP BY region,country ORDER BY isExact DESC, hostelCount DESC LIMIT $maxResults");
    	self::mergeSearchData($searchResultData, [ 'regions' => $rows ]);
        */

        return $searchResultData;
    }

    private static function categorizeCityInfoFullTextResults($search, $resultsData)
    {
        $categorizedResults = [];
        foreach ($resultsData as $cityInfo) {
            // See whether the result is most similar to the search terms based on the city, region, etc...
            $similarities = [
                'cities' => max(
                    stringSimilarityPercent($search, $cityInfo->city),
                    stringSimilarityPercent($search, $cityInfo->translation()->city),
                    $cityInfo->citAlt != '' ? stringSimilarityPercent($search, $cityInfo->cityAlt) : 0
                ),
                'cityGroups' => $cityInfo->cityGroup != '' ? max(
                    stringSimilarityPercent($search, $cityInfo->cityGroup),
                    stringSimilarityPercent($search, $cityInfo->translation()->cityGroup)
                ) : 0,
                'regions' => $cityInfo->region != '' && $cityInfo->displaysRegion ? max(
                    stringSimilarityPercent($search, $cityInfo->region),
                    stringSimilarityPercent($search, $cityInfo->translation()->region)
                ) : 0,
                // (can't do 'countries' because that would need to be CountryInfo, not CityInfo)
            ];

            $maxSimilarity = max($similarities);
            if ($maxSimilarity < 33) {
                continue;
            }
            $mostSimilarType = array_search($maxSimilarity, $similarities);
            $categorizedResults[$mostSimilarType][] = $cityInfo;
        }

        return $categorizedResults;
    }

    private static function queryForHasWordStartingWith($query, $search, $fieldName)
    {
        // Note: Wildcard searches that start with '%' are slow because can't use the index, but it's probably worth it.  If we decide it's not, can remove it later.
        return $query->where(function ($query) use ($search, $fieldName): void {
            $query->where($fieldName, 'LIKE', $search . '%')->orWhere($fieldName, 'LIKE', '% ' . $search . '%');
        })->orderByRaw("$fieldName LIKE ? DESC", $search . '%'); // results that start with the string are preferred
    }

    public static function mergeSearchData(&$searchResultData, $merge)
    {
        $itemCount = $newItemCount = 0;
        foreach ($merge as $type => $mergeItems) {
            foreach ($mergeItems as $item) {
                $itemCount++;
                if (! isset($searchResultData[$type]) || ! self::searchResultExists($type, $searchResultData[$type], $item)) {
                    $searchResultData[$type][] = $item;
                    $newItemCount++;
                }
            }
        }

        debugOutput("(Result: $itemCount rows, $newItemCount unqiue new items added.)");

        return $searchResultData; // useful for chaining
    }

    private static function searchResultExists($type, $searchResultsOfSameType, $newItem)
    {
        foreach ($searchResultsOfSameType as $item) {
            switch ($type) {
                case 'regions':
                    // Special for regions because the cityInfo ID for regions aren't unique)
                    if ($item->country == $newItem->country && $item->region == $newItem->region) {
                        return true;
                    }

                    break;

                case 'cityGroups':
                    // Special for cityGroups because the cityInfo ID for regions aren't unique)
                    if ($item->cityGroups == $newItem->cityGroups && $item->country == $newItem->country) {
                        return true;
                    }

                    break;

                default:
                    if ($item->id == $newItem->id) {
                        return true;
                    }
            }
        }

        return false;
    }

    public static function processSearchDataIntoSearchResults($searchResultData)
    {
        $searchResults = [];
        foreach ($searchResultData as $type => $items) {
            foreach ($items as $item) {
                switch ($type) {
                    case 'listings':
                        $translatedCityInfo = ($item->cityInfo ? $item->cityInfo->translation() : $item);
                        $itemResult = ['url' => $item->getURL(), 'text' => $item->name, 'extraText' => "($translatedCityInfo->city, $translatedCityInfo->country)",
                            'sortingScore' => ($item->propertyType == 'hostel') + $item->onlineBooking, ];

                        break;
                    case 'cities':
                        if ($item->cityAlt != '') {
                            $extraText = '(' . $item->translation()->cityAlt . ') ';
                        } elseif ($item->cityGroup != '') {
                            $extraText = '(' . $item->translation()->cityGroup . ') ';
                        } else {
                            $extraText = '';
                        }
                        $extraText .= ($item->translation()->region != '' ? $item->translation()->region . ', ' : '') .
                            '<strong>' . $item->translation()->country . '</strong>';
                        $itemResult = ['url' => $item->getURL(), 'text' => $item->translation()->city,
                            'extraText' => $extraText, 'sortingScore' => $item->totalListingCount, ];

                        break;
                    case 'regions':
                        $itemResult = ['url' => $item->getRegionURL(), 'text' => $item->translation()->region,
                            'extraText' => $item->translation()->country, 'sortingScore' => 0, /* nothing useful to use as a score */];

                        break;
                    case 'cityGroups':
                        $itemResult = ['url' => $item->getCityGroupURL(), 'text' => $item->translation()->cityGroup,
                            'extraText' => $item->translation()->country, 'sortingScore' => 0, /* nothing useful to use as a score */];

                        break;
                    case 'countries':
                        $itemResult = ['url' => $item->getURL(), 'text' => $item->translation()->country,
                            'extraText' => '', 'sortingScore' => 0, /* nothing useful to use as a score */];

                        break;
                    case 'continents':
                        $itemResult = ['url' => $item->getContinentURL(), 'text' => $item->translation()->continent,
                            'extraText' => '', 'sortingScore' => 0, /* nothing useful to use as a score */];

                        break;
                }

                $searchResults[$type][] = $itemResult;
            }
        }

        return $searchResults;
    }

    public static function orderByMostRelevant($searchString, &$searchResults, $maxResultsPerType = null): void
    {
        foreach ($searchResults as $type => &$items) {
            uksort($items, function ($keyA, $keyB) use ($searchString, $items) {
                $a = $items[$keyA];
                $b = $items[$keyB];

                // check if only one is an exact match
                if (strcasecmp($a['text'], $searchString) === 0 && strcasecmp($b['text'], $searchString) !== 0) {
                    return -1;
                }
                if (strcasecmp($b['text'], $searchString) === 0 && strcasecmp($a['text'], $searchString) !== 0) {
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
                if (stripos($a['text'], ' ' . $searchString) !== false && stripos($b['text'], ' ' . $searchString) === false) {
                    return -1;
                }
                if (stripos($b['text'], ' ' . $searchString) !== false && stripos($a['text'], ' ' . $searchString) === false) {
                    return 1;
                }

                /* (this was too slow)
                // check if one is more similar in text
                $similarityA = stringSimilarityPercent($searchString, "$a[text] $a[extraText]");
                $similarityB = stringSimilarityPercent($searchString, "$b[text] $b[extraText]");
                if ($similarityA != $similarityB) return $similarityA > $similarityB ? -1 : 1;
                */

                // check if one has a better score
                if ($a['sortingScore'] != $b['sortingScore']) {
                    return $a['sortingScore'] > $b['sortingScore'] ? -1 : 1;
                }

                // Default (retain the original sort order)
                return $keyA > $keyB ? 1 : -1;
            });

            if ($maxResultsPerType && count($items) > $maxResultsPerType) {
                $items = array_slice($items, 0, $maxResultsPerType);
            }
        }
        unset($items); // break the reference with the last element just to be safe
    }
}
