@if (!in_array('isClosed', $listingViewOptions))
    <section class="py-2 bg-second">
        <ul class="nav text-white font-weight-600 container header-navigation">
            <li class="nav-item" id="availability-link"><a href="#availability" class="nav-link" data-smooth-scroll="">@langGet('listingDisplay.MenuItem1')</a></li>

            {{--Hostelz Review--}}
            @if (isset($review))
                <li class="nav-item" id="hostelzreview-link"><a href="#hostelzreview" class="nav-link" data-smooth-scroll="">@langGet('listingDisplay.MenuItem2')</a></li>
            @elseif (isset($ratings) || isset($importedReviews))
                <li class="nav-item" id="hostelzreview-link"><a href="#reviews" class="nav-link" data-smooth-scroll="">@langGet('listingDisplay.MenuItem2a')</a></li>
            @endif

            @if($importedRatingScoreCount && empty($review) && empty($ratings) && empty($importedReviews))
                <li class="nav-item" id="hostelzratings-link"><a href="#ratings" class="nav-link" data-smooth-scroll="">@langGet('listingDisplay.MenuItem7')</a></li>
            @endif

            @if (@$description != '' && !in_array('isClosed', $listingViewOptions))
                <li class="nav-item" id="description-link"><a href="#description" data-smooth-scroll="" class="nav-link">@langGet('listingDisplay.MenuItem3')</a></li>
            @endif

            {{--Video embedded--}}
            @if ($listing->videoEmbedHTML != '' && !in_array('isClosed', $listingViewOptions))
                <li class="nav-item" id="video-link"><a href="#video" data-smooth-scroll="" class="nav-link">@langGet('listingDisplay.MenuItem4')</a></li>
            @endif

            @if ($listing->combinedRating && !in_array('isClosed', $listingViewOptions))
                <li class="nav-item" id="facilities-link"><a href="#facilities" data-smooth-scroll="" class="nav-link">@langGet('listingDisplay.MenuItem5')</a></li>
            @endif

            @if (!in_array('isClosed', $listingViewOptions))
                <li class="nav-item" id="contact-link"><a href="#contact" data-smooth-scroll="" class="nav-link">@langGet('listingDisplay.MenuItem6')</a></li>
            @endif

            {{--Hostelz.com Ratings--}}
            {{-- @if ($importedRatingScoreCount)
                <li class="nav-item" id="hostelzratings-link"><a href="#hostelzratings" class="nav-link" data-smooth-scroll="">@langGet('listingDisplay.MenuItem7')</a></li>
            @endif --}}
        </ul>
    </section>
@endif