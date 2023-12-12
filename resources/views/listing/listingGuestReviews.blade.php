@if ($ratings)
    <div class="" id="hostelzratings">

        <h4 role="alert" class="alert alert-success tx-body" id="guestReviews">@include('partials.svg-icon', ['svg_id' => 'verify-woman-user', 'svg_w' => '24', 'svg_h' => '24']) @langGet('listingDisplay.guestRatingsTitle', [ 'hostelName' => $listing->name ])</h4>
 
        <div class="no-last-bb no-last-mpb">

            @include('listing._listingRatings', [ 'ratings' => $ratings, 'withRDFaMarkup' => true ])

        </div>
    </div>
@endif