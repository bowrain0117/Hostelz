<?php

namespace App\Http\Controllers;

use App\Models\CityInfo;
use App\Services\CityService;

class ExploreSectionController extends Controller
{
    public function __invoke(CityInfo $cityInfo, CityService $cityService)
    {
        $listings = $cityService
            ->getBestFor($cityInfo)
            ->map(fn ($listing, $key) => $listing->getExploreSectionData($key));

        return view(
            'listings.listingsRowSlider',
            [
                'blockId' => 'exploreSection',
                'title' => 'Explore ' . $cityInfo->translation()->city,
                'subtitle' => 'Discover the best hostels to suit every traveller type.',
                'listings' => $listings,
            ]
        );
    }
}
