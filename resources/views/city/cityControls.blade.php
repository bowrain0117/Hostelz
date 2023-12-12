<div id="city-controls" class="d-flex flex-row justify-content-end mb-3 mb-lg-5 align-items-center">
    <div id="city-sortBy" class="mr-auto">
        <button class="btn-clear" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            Sort by: <span class="city-sort-value text-capitalize">{{ $resultsOptions['orderBy']['title'] }}</span>
            @include('partials.svg-icon', ['svg_id' => 'arrow-bottom', 'svg_w' => '24', 'svg_h' => '24'])
        </button>

        <div class="dropdown-menu p-3" aria-labelledby="dropdownMenuButton" style="min-width: 240px;max-height: 200px; overflow-y: auto;">
            <ul class="mb-0 pl-0 list-unstyled" >
                @foreach ($sortBy as $item)
                    @if(is_null($item['child']))
                        <li class="city-sortBy-item py-2 border-bottom cursor-pointer @if ($resultsOptions['orderBy']['value'] === $item['key']) selected @endif" data-value="{{ $item['value'] }}" >
                            {{ $item['title'] }}
                            <span>@include('partials.svg-icon', ['svg_id' => 'checked-icon', 'svg_w' => '24', 'svg_h' => '24'])</span>
                        </li>
                    @elseif($item['child']->isNotEmpty())
                        <li class="py-2 border-bottom">
                            {{ $item['title'] }}
                            <ul class="mb-0 pl-0 list-unstyled">
                                @foreach($item['child'] as $childItem)
                                    <li class="city-sortBy-item cursor-pointer pl-3 pt-2 @if ($resultsOptions['orderBy']['value'] === $childItem['key']) selected @endif" data-value="{{ $childItem['value'] }}" >
                                        {{ $childItem['title'] }}
                                        <span>@include('partials.svg-icon', ['svg_id' => 'checked-icon', 'svg_w' => '24', 'svg_h' => '24'])</span>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endforeach
            </ul>
        </div>
    </div>

    @if (isset($mapPoints) && $resultsOptions['mapMode'] == 'closed')
        <div class="mr-2">
            <a href="#" class="setMapMode" data-map-mode="small">
                <button class="btn-clear">@include('partials.svg-icon', ['svg_id' => 'map', 'svg_w' => '24', 'svg_h' => '24'])</button>
            </a></div>
    @endif

    <div id="city-filter">
        <button class="btn-clear" data-toggle="modal" data-target="#searchFilters">
            @include('partials.svg-icon', ['svg_id' => 'filters-icon', 'svg_w' => '24', 'svg_h' => '24'])
        </button>
    </div>
</div>