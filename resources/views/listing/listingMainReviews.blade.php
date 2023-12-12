@if (isset($review) || isset($ratings) || isset($importedReviews))
<div class="border-bottom pb-3 mb-3 pb-lg-3 mb-lg-3" id="reviews">
    <h2 class="sb-title mb-3">{{ langGet('listingDisplay.reviewsPageTitle', [ 'listingName' => $listing->name]) }}</h2>
    <ul id="reviewsTabs" role="tablist" class="nav nav-tabs">
        @if ($review)
            <li class="nav-item">
                <a id="hostelzReview" data-toggle="tab" href="#hostelzReview-content" role="tab"
                   aria-controls="tab1-content" aria-selected="true" class="nav-link active">
                   <span class="official-hostelz-review">{{ langGet('listingDisplay.OfficialHostelzReview') }}</span>
                   <span class="official-review">{{ langGet('listingDisplay.OfficialReview') }}</span>
                </a>
            </li>
        @endif

        @if ($ratings || $importedReviews)
            <li class="nav-item">
                <a id="communityReviews" data-toggle="tab" href="#communityReviews-content" role="tab"
                   aria-controls="tab2-content" aria-selected="false" class="nav-link @if(!$review) active @endif">
                    {{ __('listingDisplay.CommunityReview') }}
                </a>
            </li>
        @endif
    </ul>

    <div class="tab-content">
        @if ($review)
            <div id="hostelzReview-content" role="tabpanel" aria-labelledby="hostelzReview-content" class="tab-pane fade active show pt-3">
                @include('listing.listingHostelzReviews')
            </div>
        @endif

        @if ($ratings || $importedReviews)
            <div id="communityReviews-content" role="tabpanel" aria-labelledby="communityReviews-content" class="tab-pane fade pt-3 @if(!$review) active show @endif">
                @include('listing.listingCommunityReviews')
            </div>
        @endif
    </div>
</div>
@endif