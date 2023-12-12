<div class="mb-3">
    @if($listing->isTypeOfHostel())
        @php $features = \App\Models\Listing\ListingFeatures::getDisplayValues($listing->compiledFeatures) ?: []; @endphp
        @if (isset($features['goodFor'][langGet("ListingFeatures.categories.goodFor")]))
            @foreach ($features['goodFor'][langGet("ListingFeatures.categories.goodFor")] as $feature)
                @if ($feature['displayType'] === 'labelValuePair')
                    <img src="{!! routeURL('images', 'info.svg') !!}" alt="#" class="mr-2"
                         style="height:15px">
                    <p class="display-4 mb-0">{!! $feature['label'] !!}: <span
                                class="font-weight-bolder">{{{ $feature['value'] }}}</span></p>
                @else
                    <span class="pre-title listing-feature">{!! $feature['label'] !!} <span
                                class="font-weight-bolder">@if (!empty($feature['value']))
                                ({{{ $feature['value'] }}})
                            @endif</span></span>
                @endif
            @endforeach
        @endif
    @endif

    {{-- boutiqueHostel --}}
    @if(isset($listing->boutiqueHostel) && $listing->boutiqueHostel === 1)
        <span class="pre-title listing-feature">{{ langGet('ListingFeatures.forms.fieldLabel.boutiqueHostel') }}</span>
    @endif
</div>
