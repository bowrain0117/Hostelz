<?php

namespace App\Services\Listings;

use App\Enums\Listing\CategoryPage;
use App\Exceptions\BookingException;
use App\Helpers\ListingsPaginator;
use App\Models\AttachedText;
use App\Models\CityInfo;
use App\Models\District;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Pic;
use App\Services\Listings\Filters\FilterCountingService;
use App\Services\Listings\Filters\FilteredListingsService;
use Illuminate\Support\Collection;

class CityListingsService extends BaseListingsService
{
    private BookingAvailabilityService $bookingAvailabilityService;

    private ListingsOptionsService $listingsOptionsService;

    private ListingsPoiService $listingsPoiService;

    private FilteredListingsService $filteredListingsService;

    private FilterCountingService $filterCountingService;

    private CityInfoService $cityInfoService;

    public function __construct()
    {
        $this->filteredListingsService = new FilteredListingsService();
        $this->listingsOptionsService = new ListingsOptionsService();
        $this->bookingAvailabilityService = new BookingAvailabilityService();
        $this->listingsPoiService = new ListingsPoiService();
        $this->filterCountingService = new FilterCountingService();
        $this->cityInfoService = new CityInfoService();
    }

    public function getListingsData($cityInfo, $optionsData, $page = 1, $bookingLinkLocation = '', &$cityFilters = null): array
    {
        [
            'resultsOptions' => $resultsOptions,
            'listingFilters' => $listingFilters,
            'isDefaultResults' => $isDefaultResults,
            'totalResults' => $listingsCount,
            'hostelCount' => $hostelCount,
            'listings' => $listings,
            'hostelListings' => $hostelListings,
            'bestAvailabilityByListingID' => $bestAvailabilityByListingID
        ] = $this->getDataByBookingAvailability($cityInfo, $optionsData, $bookingLinkLocation, $cityFilters);

        $goodContentHostelsCount = $this->getGoodContentHostelsCount($listings);

        $count = compact('listingsCount', 'hostelCount', 'goodContentHostelsCount');

        $pageResults = $this->getResultsPerPage($resultsOptions['resultsPerPage'], $isDefaultResults, $count);

        [$multiplePagesOfResults, $resultsPerPage] = $pageResults;

        $picUrls = $this->getListingsPics($listings);

        $sortBy = $this->getSortBy($cityInfo);

        $lowestDormPrice = $this->getLowestDormPrice(
            $hostelListings,
            $bestAvailabilityByListingID,
            $cityInfo
        );

        $metaValues = $this->cityInfoService->getMetaValues(
            $cityInfo,
            compact('hostelCount', 'lowestDormPrice')
        );

        if (! isset($resultsOptions['orderBy']['type'])) {
            $resultsOptions['orderBy'] = ListingsOptionsService::getDefaultOrderByOptions();
        }

        // title for district
        if ($resultsOptions['orderBy']['type'] === 'district') {
            $metaValues['districtTitle'] = District::find($resultsOptions['orderBy']['value'])?->title;
        }

        // title for category
        if (
            isset($resultsOptions['pageType'])
            && str($resultsOptions['pageType'])->startsWith('category')
            && ($category = CategoryPage::tryFrom(str($resultsOptions['pageType'])->after(':')))
        ) {
            /** @var CategoryPage $category */
            $metaValues['headerTitle'] = $category->title($cityInfo->city);
        }

        $pageType = $resultsOptions['pageType'] ?? 'city';

        $this->setViewData(
            compact('resultsPerPage', 'picUrls', 'sortBy', 'metaValues', 'pageType')
        );

        if ($listings->isNotEmpty()) {
            $this->listingsPoiService->setPoiPoint($cityInfo, $listingFilters, $resultsOptions);

            if ($bestAvailabilityByListingID) {
                $listings = $listings->filter(fn ($item) => array_key_exists($item->id, $bestAvailabilityByListingID));
            }

            // todo: sorting listings
            $listings = $this->listingsPoiService->sortListingsWithPoi(
                $listings,
                $isDefaultResults,
                $resultsOptions,
                $listingsCount,
                $bestAvailabilityByListingID
            );

            // POI
            $callback = function ($listing) {
                return [
                    $listing->latitude,
                    $listing->longitude,
                    $listing->id,
                    $listing->name,
                    $listing->getURL(),
                    $listing->formatCombinedRating(),
                    $listing->propertyType,
                ];
            };

            // adding map points for listings
            $poi = $this->listingsPoiService->getPoiAndMapPoints($listings, $callback);
            if (empty($poi)) {
                $poi = ['mapPoints' => null, 'mapBounds' => null];
            }
            $this->setViewData($poi);

            // * Pagination *
            if ($multiplePagesOfResults) {
                if ($isDefaultResults) {
                    $listings = $this->moveGoodContentListingsToFirstPage($listings, $resultsPerPage);
                } // for SEO reasons
                $listings = $listings->forPage($page, $resultsPerPage);

                $this->setViewData([
                    'paginationHTML' => with(new ListingsPaginator($listings, $listingsCount, $resultsPerPage, $page))->render('partials._listingsPaginator'),
                ]);
            }

            // Text snippets for the listings
            // Group by propertyType

            $this->setViewData([
                'listingsGroupedByPropertyType' => $this->getListingsGroupedByPropertyType($listings),
                'snippets' => $this->getTextSnippets($listings),
                'lowestDormPrice' => $lowestDormPrice,
            ]);
        } else {
            $this->setViewData([
                'listingsGroupedByPropertyType' => null,
            ]);
        }

        return $this->getViewData();
    }

    public function getDataByBookingAvailability($cityInfo, $optionsData, $bookingLinkLocation = '', &$cityFilters = null): array
    {
        $this->listingsOptionsService->setOptionsDataForListingsSearch($optionsData);
        [$resultsOptions, $listingFilters, $bookingSearchCriteria, $isDefaultResults] = $this->listingsOptionsService->getOptionsForListingsSearch();

        $this->setViewData(
            compact('cityInfo', 'resultsOptions', 'listingFilters', 'isDefaultResults')
        );

        // apply chosen city filters
        $this->filteredListingsService->applyCityFilters($cityInfo, $listingFilters);
        $listings = $this->filteredListingsService->getListings();

        // * Booking Availability *
        $bestAvailabilityByListingID = null;
        $searchCriteria = null;
        $roomsAvailability = null;

        if ($bookingSearchCriteria && $listings->isNotEmpty()) {
            try {
                [
                    $bestAvailabilityByListingID,
                    $bestAvailabilityByOTA,
                    $availabilitySavingsPercent,
                    $listings,
                    $searchCriteria,
                    $roomsAvailability
                ] = $this->bookingAvailabilityService->getBookingAvailability(
                    $bookingSearchCriteria,
                    $listings,
                    $bookingLinkLocation,
                    $listingFilters
                );

                $this->setViewData(
                    compact('bestAvailabilityByOTA', 'availabilitySavingsPercent', 'searchCriteria')
                );
            } catch (BookingException $e) {
                $this->setViewData(['errorMessage' => $e->getMessage()]);
            }
        }

        $hostelListings = $this->getHostelListings($listings, $optionsData);

        $this->filterCountingService->defineCityFilters(
            $cityFilters,
            $listings,
            $hostelListings,
            $searchCriteria,
            $roomsAvailability
        );

        // Listing/hostel Counts and ResultsPerPage
        $this->setViewData([
            'cityFilters' => $cityFilters = $this->filterCountingService->getCityFiltersWithCount(),
            'totalResults' => $listings->count(),
            'hostelCount' => $hostelListings->count(),
            'roomType' => $searchCriteria && $searchCriteria->roomType === 'private' ? 'private room' : 'dorm bed',
            'listings' => $listings,
            'hostelListings' => $hostelListings,
            'bestAvailabilityByListingID' => $bestAvailabilityByListingID,
        ]);

        return $this->getViewData();
    }

    public function getListingsPics(Collection $listings): Collection
    {
        return $listings->mapWithKeys(function (Listing $listing) {
            return [$listing->id => $listing->getPics(limit: Pic::GALLERY_PREVIEW)];
        });
    }

    private function getHostelListings($listings, $optionsData)
    {
        $listingsProperty = 'Hostel';

        $hostelListings = $listings->filter(function ($listing) use ($listingsProperty) {
            return $listing->propertyType === $listingsProperty;
        });

        if (! empty($optionsData['listingFilters']['propertyType'])) {
            $hostelListings = $listings->filter(function ($listing) use ($optionsData) {
                return in_array($listing->propertyType, $optionsData['listingFilters']['propertyType'], true);
            });
        }

        return $hostelListings;
    }

    private function getAvailableHostels($listings, $listingsIDs, $searchCriteria): Collection
    {
        return $listings->filter(
            fn ($listing) => array_key_exists($listing->id, $listingsIDs) &&
                $listing->hadAvailabilityOfType($searchCriteria->roomType)
        );
    }

    private function getResultsPerPage($resultsPerPage, $isDefaultResults, $count): array
    {
        if ($resultsPerPage === 'default') {
            $resultsPerPage = 30; // default max is 30 hostels/hotels
            if ($count['hostelCount'] > $resultsPerPage) {
                $resultsPerPage = min($count['hostelCount'], 45);
            } // up to 45 hostels
            if ($count['goodContentHostelsCount'] > $resultsPerPage && $isDefaultResults) {
                // If it's a default search, show lots of good content listings (for SEO reasons)
                $resultsPerPage = min($count['goodContentHostelsCount'], 90);
            }
            // Multiples of 3 are used because they look better on the page
            // (Doesn't actually work if there are multiple property types on the page.)
            $resultsPerPage = round($resultsPerPage / 3) * 3;
        }

        $multiplePagesOfResults = ($resultsPerPage !== 'all' && $resultsPerPage < $count['listingsCount']);

        return [$multiplePagesOfResults, $resultsPerPage];
    }

    private function getTextSnippets($listings)
    {
        $listingIDs = $listings->pluck('id');

        return AttachedText::where('subjectType', 'hostels')
            ->whereIn('subjectID', $listingIDs)
            ->where('type', 'snippet')
            ->where('language', Languages::currentCode())
            ->pluck('data', 'subjectID');
    }

    private function getListingsGroupedByPropertyType($listings)
    {
        return $listings->groupBy(function ($listing) {
            return $listing->propertyType;
        });
    }

    private function getGoodContentHostelsCount($listings)
    {
        return $listings->sum(function ($listing) {
            return $listing->propertyType === 'Hostel' && ! $listing->isPoorContentPage();
        });
    }

    private function getLowestDormPrice($hostelListings, $bestAvailabilityByListingID, $cityInfo)
    {
        $hostelListingsIDs = $hostelListings->pluck('id');

        $lowestAvailablePrice = collect($bestAvailabilityByListingID)
            ->filter(function ($item, $listingID) use ($hostelListingsIDs) {
                return $hostelListingsIDs->search($listingID) !== false;
            })
            ->map(function ($item) {
                return $item->averagePricePerBlockPerNight();
            })
            ->min();

        return floor($lowestAvailablePrice) ?: $cityInfo->lowestDormPrice;
    }

    private function moveGoodContentListingsToFirstPage($listings, $resultsPerPage)
    {
        $firstPageListingsReversed = $listings->forPage(1, $resultsPerPage)->reverse();
        $addedOtherPageListings = new Collection();
        $otherPageListings = $listings->slice($resultsPerPage);

        // We only work with items of the property type of the first listing on the 2nd page.
        $propertyType = $otherPageListings->first()->propertyType;

        // Loop through the $otherPageListings to find listings that can be moved to the first page
        $otherPageListings = $otherPageListings->filter(function ($listing) use (&$firstPageListingsReversed, $propertyType, &$addedOtherPageListings) {
            if ($listing->propertyType !== $propertyType || $listing->isPoorContentPage()) {
                return true;
            }

            // We have a listing that could be moved to the first page. Find a spot for it.
            foreach ($firstPageListingsReversed as $key => $firstPageItem) {
                if ($firstPageItem->propertyType !== $propertyType || $firstPageItem->featuredListingPriority || ! $firstPageItem->isPoorContentPage()) {
                    continue;
                }

                // We found an available first page spot for the good content listing...
                $firstPageListingsReversed->pull($key); // remove the poor content listing
                $addedOtherPageListings->prepend($firstPageItem); // add the poor content one to the top of the second page
                $firstPageListingsReversed->prepend($listing); // add the good content listing to the first page listings

                return false; // remove the item from $otherPageListings
            }

            return true; // no first page spot found for it, so just keep it where it is
        });

        $result = $firstPageListingsReversed->reverse()->merge($addedOtherPageListings)->merge($otherPageListings);

        if (! env('production')) {
            // Sanity checks
            if ($result->count() !== $listings->count()) {
                throw new Exception('Count mismatch.');
            }
        }

        return $result;
    }

    private function getSortBy(CityInfo $cityInfo)
    {
        return collect(langGet('city.resultsOptions.orderBy'))
            ->mapWithKeys(fn ($value, $key) => [
                $key => [
                    'key' => $key,
                    'title' => $value,
                    'value' => collect([
                        'sortBy' => $key,
                        'type' => $key,
                        'value' => $value,
                        'title' => $value,
                    ])->toJson(),
                    'child' => null,
                ],
            ])
            ->map(function ($item) use ($cityInfo) {
                if ($item['key'] !== 'distanceTo') {
                    return $item;
                }

                $item['child'] = $cityInfo
                    ->districts()
                    ->active()
                    ->orderBy('name')
                    ->get()
                    ->map(function ($district) {
                        return [
                            'key' => $district->id,
                            'title' => $district->name,
                            'value' => collect([
                                'sortBy' => 'district',
                                'type' => 'district',
                                'value' => $district->id,
                                'title' => $district->name,
                            ])->toJson(),
                        ];
                    });

                return $item;
            });
    }
}
