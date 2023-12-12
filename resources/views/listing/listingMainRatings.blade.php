@if ($importedRatingScoreCount)
    <div class="mb-3" id="hostelzratings">
        <h3 class="font-weight-600 cl-text tx-body mb-3"
            id="ratings">{!! langGet('listingDisplay.importedRatingsTitle', [ 'hostelName' => $listing->name ]) !!}</h3>

        <div id="vue-listings-rates-slider">
            <slider
                    :average-score="{{ json_encode($importedRatingScores['average'], JSON_THROW_ON_ERROR) }}"
                    :imported-ratings="{{ json_encode(__('listingDisplay.importedRatings'), JSON_THROW_ON_ERROR) }}"
            ></slider>
        </div>
    </div>

@endif