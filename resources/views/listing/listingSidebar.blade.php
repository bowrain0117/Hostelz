@if (($listing->propertyType != 'Hostel' || $listing->compiledFeatures))
    @php $features = \App\Models\Listing\ListingFeatures::getDisplayValues($listing->compiledFeatures) ?: []; @endphp

    @if($features)
        <div id="facilities">
            <h3 class="title-3 cl-dark mb-3">@langGet('listingDisplay.SidebarTitle')</h3>

            {{-- ** Features ** --}}
            @foreach ($features as $featureCategory => $subcategories)
                @foreach ($subcategories as $subcategoryTitle => $features)
                    <div class="border-bottom mb-3 pb-3">
                        <button class="border-0 cl-text tx-body mb-3 bg-white flex-center">
                            {!! $subcategoryTitle !!}
                        </button>
                        <div>
                            @foreach ($features as $feature)
                                <div class="d-flex align-items-center mb-2 tx-small">

                                    @if ($feature['displayType'] == 'labelValuePair')
                                        @include('partials.svg-icon', ['svg_id' => 'info', 'svg_w' => '24', 'svg_h' => '24'])
                                        <p class="cl-subtext mb-0 ml-2">
                                            {!! $feature['label'] !!}:
                                            <span class="cl-text">{{{ $feature['value'] }}}</span>
                                        </p>
                                    @else
                                        @if ($feature['displayType'] == 'yes' || $feature['displayType'] == 'free')
                                            @include('partials.svg-icon', ['svg_id' => 'green-check', 'svg_w' => '24', 'svg_h' => '24'])
                                        @elseif ($feature['displayType'] == 'no')
                                            @include('partials.svg-icon', ['svg_id' => 'red-restriction', 'svg_w' => '24', 'svg_h' => '24'])
                                        @elseif ($feature['displayType'] == 'pay')
                                            @include('partials.svg-icon', ['svg_id' => 'green-label', 'svg_w' => '24', 'svg_h' => '24'])
                                        @endif

                                        <p class="cl-subtext mb-0 ml-2">
                                            {!! $feature['label'] !!} <span class="cl-text">
                                                @if (@$feature['value'] != '')
                                                    ({{{ $feature['value'] }}})
                                                @endif
                                            </span>
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            @endforeach

            @isset($cityInfo->totalListingCount)
                <div class="border-bottom mb-3 pb-3 tx-small text-content">
                    @if ($listing->propertyType == 'Hostel' && $cityInfo->hostelCount > 2)
                        <div class="addressCityLink">
                            @langGet('listingDisplay.SeeAllHostels', ['city' => $cityInfo->translation()->city, 'count' => $cityInfo->hostelCount, 'cityurl' => $cityInfo->getURL()])
                        </div>
                    @elseif ($cityInfo->totalListingCount > 2)
                        <div class="addressCityLink">
                            @langGet('listingDisplay.SeeAllHostelswithCount', ['city' => $cityInfo->translation()->city, 'count' => $cityInfo->hostelCount, 'cityurl' => $cityInfo->getURL()])
                        </div>
                    @else
                        <div class="addressCityLink">
                            @langGet('listingDisplay.SeeAllHostels', ['city' => $cityInfo->translation()->city, 'cityurl' => $cityInfo->getURL()])
                        </div>
                    @endif
                </div>
            @endisset

        </div>
    @endif
@endif

{{-- Ad 
<div class="asidebar-wrap-hide p-3 p-sm-4 shadow-1 rounded mr-sm-n3 ml-sm-n3 ml-lg-0 mb-4 sticky-top" style="top: 50px;">
    <div class="ad adlisting"></div>
</div> --}}