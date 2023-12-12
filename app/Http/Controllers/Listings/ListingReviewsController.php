<?php

namespace App\Http\Controllers\Listings;

use App\Http\Controllers\Controller;
use App\Http\Requests\Listings\ListingReviewsRequest;
use App\Models\Listing\Listing;
use App\Services\Listings\ListingReviewsService;

class ListingReviewsController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function __invoke(ListingReviewsRequest $request, ListingReviewsService $service, Listing $listing)
    {
        $listingReviews = $service->getReviews($listing, $request->safe()->all());

        return response()->json($listingReviews);
    }
}
