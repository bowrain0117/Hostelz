<?php

namespace App\Http\Controllers;

use App\Models\Listing\Listing;

class MoreHostelsController extends Controller
{
    public function __invoke(Listing $listing)
    {
        $minRating = max(($listing->combinedRating - 5), 50);

        $listings = Listing::areLive()
                           ->byCityInfo($listing->cityInfo)
                           ->where('id', '!=', $listing->id)
                           ->where('onlineReservations', true)
                           ->where('propertyType', $listing->propertyType)
                           ->where('combinedRating', '>=', $minRating)
                           ->hasActivePriceHistoryPastMonths()
                           ->orderBy('combinedRating', 'desc')
                           ->limit(5)
                           ->get()
                           ->map(fn ($listing, $key) => $listing->getExploreSectionData($key));

        return view(
            'listing.listingSlider',
            [
                'listings' => $listings,
                'blockId' => 'moreHostels',
            ]
        );
    }
}
