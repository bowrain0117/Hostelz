@props(['listing', 'cityName'])

@php
    if (empty($listing['features'])) {
        return;
    }
@endphp

<section class="my-3">
    <p>
        <a data-toggle="collapse" href="#collapseFeatures_{{$listing['id']}}"
           role="button" aria-expanded="false"
           aria-controls="collapseFeatures" style="color: #4a5268;"
           class="label text-dark-gray text-sm collapsed"
        >
            Beyond the Basics - {{ $cityName }}
            @include('partials.svg-icon', ['svg_id' => 'arrow-bottom-2', 'svg_w' => '12', 'svg_h' => '12'])
        </a>
    </p>
    <div class="collapse" id="collapseFeatures_{{$listing['id']}}">
        <div class="card card-body">
            @foreach ($listing['features'] as $featureCategory => $subcategories)
                <section class="d-flex flex-column justify-content-center justify-content-sm-around flex-sm-row">
                    @foreach ($subcategories as $subcategoryTitle => $features)
                        <div class="border-bottom mb-3 pb-3">
                            <h4 class=" cl-text tx-body mb-3 bg-white flex-center">
                                {!! $subcategoryTitle !!}
                            </h4>
                            <div>
                                @foreach ($features as $feature)
                                    <div class="d-flex align-items-center mb-2 tx-small">

                                        @if ($feature['displayType'] === 'labelValuePair')
                                            @include('partials.svg-icon', ['svg_id' => 'info', 'svg_w' => '24', 'svg_h' => '24'])
                                            <p class="cl-subtext mb-0 ml-2">
                                                {!! $feature['label'] !!}:
                                                <span class="cl-text">{{{ $feature['value'] }}}</span>
                                            </p>
                                        @else
                                            @if ($feature['displayType'] === 'yes' || $feature['displayType'] === 'free')
                                                @include('partials.svg-icon', ['svg_id' => 'green-check', 'svg_w' => '24', 'svg_h' => '24'])
                                            @elseif ($feature['displayType'] === 'no')
                                                @include('partials.svg-icon', ['svg_id' => 'red-restriction', 'svg_w' => '24', 'svg_h' => '24'])
                                            @elseif ($feature['displayType'] === 'pay')
                                                @include('partials.svg-icon', ['svg_id' => 'green-label', 'svg_w' => '24', 'svg_h' => '24'])
                                            @endif

                                            <p class="cl-subtext mb-0 ml-2">
                                                {!! $feature['label'] !!}
                                                <span class="cl-text">
                                            @if (data_get($feature, 'value'))
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
                </section>
            @endforeach
        </div>
    </div>
</section>

