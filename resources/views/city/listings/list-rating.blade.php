{{-- ** Combined Rating ** --}}
@if ($listing->combinedRating)
    <div class="list-footer_rating d-flex flex-column align-items-center justify-content-center mb-3">
        <div class="mb-1 pre-title">
            @if ($listing->combinedRatingCount > 20)
                @if ($listing->combinedRating / 10 > 9.0 )
                    <span class="nowrap">@langGet('listingDisplay.1stBestRating')</span>
                @elseif ($listing->combinedRating / 10 > 8.5 )
                    <span class="nowrap">@langGet('listingDisplay.2ndBestRating')</span>
                @elseif ($listing->combinedRating / 10 > 7.9 )
                    <span class="nowrap">@langGet('listingDisplay.3rdBestRating')</span>
                @else
                    <span class="nowrap">@langGet('listingDisplay.CombinedRatingTitleTotal')</span>
                @endif
            @else
                <span class="nowrap">@langGet('listingDisplay.CombinedRatingTitleTotal')</span>
            @endif
        </div>

        <div class="hostel-card-rating mb-1">
                        <span class="combinedRating"
                              content="{!! round($listing->combinedRating / 10, 1) !!}">{!! $listing->formatCombinedRating() !!}</span>
        </div>

        @if ($listing->combinedRatingCount)
            <div class="text-sm cl-subtext nowrap">
                <span>{!! $listing->combinedRatingCount !!}</span> @langGet('city.Reviews')
            </div>
        @endif

    </div>
@endif