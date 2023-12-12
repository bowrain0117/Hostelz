@if ($importedReviews && !in_array('isClosed', $listingViewOptions))
    <div class="">
        <h3 class="font-weight-600 cl-text tx-body" id="reviews">{{ langGet('listingDisplay.importedReviewsTitle', [ 'hostelName' => $listing->name ]) }}</h3>

        <div class="mt-3 pt-3">@include('listing._listingRatings', [ 'ratings' => $importedReviews, 'withRDFaMarkup' => false ])</div>
    </div>
@endif