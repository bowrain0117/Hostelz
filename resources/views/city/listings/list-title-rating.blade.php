<div class="pl-0 pl-lg-5 d-lg-none text-center mt-3">
    {{-- ** Combined Rating ** --}}
    @if ($listing->combinedRating)
        <div class="list-footer_rating d-flex flex-column align-items-center justify-content-center mb-3">
            <div class="hostel-card-rating hostel-card-rating-small mb-1">
                {{ $listing->formatCombinedRating() }}
            </div>

            @if ($listing->combinedRatingCount)
                <div class="pre-title cl-subtext nowrap">
                    <span property="ratingCount">{!! $listing->combinedRatingCount !!}</span> @langGet('city.Reviews')
                </div>
            @endif
        </div>
    @endif
</div>
