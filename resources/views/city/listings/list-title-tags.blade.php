<div>
    @if (
        $listing->cityAlt !== '' || (isset($listing->compiledFeatures['breakfast']) && $listing->compiledFeatures['breakfast'] === 'free') || (isset($listing->compiledFeatures['extras']) &&
        in_array('privacyCurtains', $listing->compiledFeatures['extras']))
    )
        <div class="neighborhood mb-2 small">
            @if ($listing->cityAlt !== '')
                <span class="my-1 pre-title">
                    @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25'])
                    {{{ $listing->cityAlt }}}
                </span>
            @endif

            <br>

            @if (isset($listing->compiledFeatures['breakfast']) && $listing->compiledFeatures['breakfast'] === 'free')
                <span class="delimiter mt-2 d-inline-block"><i class="fa fa-coffee w-1rem mr-1"></i> @langGet('city.FeatureFreeBreakfast')</span>
            @endif

            @if (
                isset($listing->compiledFeatures['extras']) &&
                in_array('privacyCurtains', $listing->compiledFeatures['extras'])
            )
                <span class="delimiter mt-2 d-inline-block">
                    <i class="fa fa-person-booth w-1rem mr-1"></i> @langGet('city.PrivacyCurtains')
                </span>
            @endif
        </div>
    @endif
</div>