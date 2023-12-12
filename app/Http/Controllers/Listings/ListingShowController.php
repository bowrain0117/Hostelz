<?php

namespace App\Http\Controllers\Listings;

use App\Helpers\ListingAndCitySearch;
use App\Helpers\ListingDisplay;
use App\Http\Controllers\Controller;
use App\Models\Listing\Listing;
use App\Traits\Redirect as RedirectTrait;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Lib\PageCache;

class ListingShowController extends Controller
{
    use RedirectTrait;

    public function __invoke($slug)
    {
        $listingID = (int) substr($slug, 0, strpos($slug, '-') ?: strlen($slug)); // (a "-" is added so that URLs with no text at all after the number will work)

        abort_if(! is_numeric($listingID), 404);

        PageCache::addCacheTags('listing:' . $listingID); // mark the cache is it can be later cleared by listingID

        $listing = Listing::areNotListingCorrection()->firstWhere('id', $listingID);

        if (! $listing) {
            $redirect = $this->redirectToSearchOrAbort($slug);

            return redirect()->to($redirect, 301);
        }

        // * Check the URL *
        $correctURL = $this->getCorrectUrl($listing);
        if (Request::path() !== ltrim($correctURL, '/')) {
            return redirect($correctURL, 301);
        }

        Session::put('localCurrency', $listing->determineLocalCurrency());
        Session::put('availabilityLink', url()->current());

        /** @var Listing $listing */
        $liveOrWhyNot = $listing->isLiveOrWhyNot();
        if ($liveOrWhyNot === Listing::REMOVED) {
            // The removed status is a special status just for listings
            // that don't even want us to show the "closed listing" page.
            if ($listing->cityInfo) {
                return redirect()->to($listing->cityInfo->getURL(), 301);
            }

            abort(404);
        }

        $listingDisplayData = (new ListingDisplay($listing))->getListingViewData($liveOrWhyNot);

        return response(view('listing', $listingDisplayData));
    }

    public function redirectToSearchOrAbort(string $slug)
    {
        $search = substr($slug, strpos($slug, '-') + 1);

        if (strlen($search) >= 4) {
            $searchResult = ListingAndCitySearch::performSearch($search, 2, Listing::areLive());

            if (! empty($searchResult)) {
                return $searchResult['listings'][0]->getURL();
            }
        }

        Log::warning('redirectToSearchOrAbort Abort for slug: ' . $slug);
        abort(404);
    }
}
