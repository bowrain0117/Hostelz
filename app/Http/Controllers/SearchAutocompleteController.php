<?php

namespace App\Http\Controllers;

use App\Services\SearchAutocompleteService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Lib\Middleware\BrowserCache;
use Lib\PageCache;

class SearchAutocompleteController extends Controller
{
    public function __invoke(Request $request, SearchAutocompleteService $searchAutocompleteService)
    {
        PageCache::addCacheTags('city:aggregation'); // mark the cache so it can by cleared when any city is altered

        $search = $request->input('s');
        $url = $request->get('url');
        $location = $request->get('location');

        if (empty($search)) {
            BrowserCache::$disabled = true;
            PageCache::dontCacheThisPage();

            $suggestions = $searchAutocompleteService->makeNearbyDestinationsSearch();

            if ($this->isRegionsSearchValid($location, $url)) {
                $suggestions = $searchAutocompleteService->makeNearbyCountryRegionDestinationsSearch($location);
            }

            if ($this->isCitySearchValid($location, $url)) {
                $suggestions = $searchAutocompleteService->makeNearbyCityDestinationsSearch($url, $location);
            }

            if ($this->isListingSearchValid($location, $url)) {
                $suggestions = $searchAutocompleteService->makeNearbyListingDestinationsSearch($location);
            }

            if ($suggestions->isEmpty()) {
                $suggestions = $searchAutocompleteService->getFeaturedCities();
            }

            return ['suggestions' => $suggestions];
        }

        $suggestions = $searchAutocompleteService->makeSearch($search);

        return ['suggestions' => $suggestions];
    }

    private function isRegionsSearchValid(?string $location, ?string $url): bool
    {
        return ! empty($location) && Str::contains($url, '/hostels-in/');
    }

    private function isCitySearchValid(?string $location, ?string $url): bool
    {
        return ! empty($location) && Str::contains($url, '/hostels/');
    }

    private function isListingSearchValid(?string $location, ?string $url): bool
    {
        return ! empty($location) && (Str::contains($url, '/hostel/') || Str::contains($url, '/hotel/'));
    }
}
