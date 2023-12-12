@if (! empty($description))
    <div class="border-bottom pb-3 mb-3 pb-lg-3 mb-lg-3 text-break" id="listing-description">
        <h3 class="sb-title cl-text mb-5" id="description">@langGet('listingDisplay.Description')</h3>

        <div class="text-content">
            <p>{!! nl2br(trim($description)) !!}</p>
        </div>
    </div>
@endif