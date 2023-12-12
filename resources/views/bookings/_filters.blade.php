{{--

Input variables:

    $listingFilters = [
        'label'
        'selectType' -> 'multiple', 'single' (can be de-selected), or 'radio' (can't be de-selected)
        'options' -> name/value pairs, or array of [ 'name', 'value', 'type' ('option', 'divider', 'header') ]
    ]
    
    $dropUp - true to dropup instead of dropdown.

--}}

@php
    $count = 0;
@endphp

<div class="listingFilters container">

    <input type="hidden" value="@isset($hostelCount){{ $hostelCount }}@endisset" class="hostel-count">

    @foreach ($listingFilters as $menuName => $menu)
        @if(filled($menu['options']))
            @php $count++; @endphp
            <div class="row mb-3 filter-wrap border-bottom pb-3" id="filter-{{ $menuName }}"
                 data-type="{!! $menu['selectType'] !!}" data-label="{!! $menu['label'] !!}">
                <div class="col">

                    <h4 class="cl-text title-4 font-weight-600 mb-2 pb-1" data-toggle="collapse"
                        data-target="#filter-collapse-{{ $menuName }}" style="cursor: pointer">
                        <span id="filter-title-{{ $menuName }}">{!! $menu['label'] !!}
                            @if(!empty($menu['filtersCount']))
                                ({{ $menu['filtersCount'] }})
                            @endif</span>
                        <button @class(['btn-clear', 'float-right', 'arrow-collapse', 'collapsed' => $count !== 1])
                                type="button" data-toggle="collapse" data-target="#filter-collapse-{{ $menuName }}"
                                aria-expanded="true" aria-controls="filter-collapse-{{ $menuName }}"
                        >@include('partials.svg-icon', ['svg_id' => 'arrow-bottom', 'svg_w' => '24', 'svg_h' => '24'])</button>
                    </h4>

                    @php $type = $menu['selectType'] === 'multiple' ? 'checkbox' : 'radio'; @endphp

                    <div id="filter-collapse-{{ $menuName }}" data-parent=".listingFilters"
                            @class(['collapse', 'show' => $count === 1])>
                        @foreach ($menu['options'] as $optionKey => $option)
                                <?php
                                if (is_array($option)) {
                                    $optionName = $option['name'] ?? null;
                                    $optionType = $option['type'];
                                    $optionValue = collect($option)->toJson();
                                    $optionId = isset($option['value']) ? $option['sortBy'] . $option['value'] : null;
                                } else {
                                    $optionName = $option;
                                    $optionType = 'option';
                                    $optionValue = $optionId = $optionKey;
                                }

                                $id = $menuName . '-' . str_replace([' ', '(', ')'], '_', $optionId);
                                ?>

                            @if ($optionType === 'option')
                                @if(!isset($menu['count']) || (!empty($menu['count']) && $menu['count'][$optionKey] > 0))
                                    <div class="form-group mb-1">
                                        <div class="custom-control custom-{{ $type }}">
                                            <input id="{{{ $id }}}" name="{!! $menuName !!}" type="{{ $type }}"
                                                   value='{{ $optionValue }}'
                                                   data-label="{{{ $optionName }}}"
                                                   data-option='{!! collect($option)->toJson() !!}'
                                                   class="custom-control-input">
                                            <label for="{{{ $id }}}"
                                                   class="custom-control-label tx-small cursor-pointer filter-option">{{{ $optionName }}}
                                                @if(!empty($menu['count']))
                                                    ({{ $menu['count'][$optionKey] }})
                                                @endif
                                            </label>
                                        </div>
                                    </div>
                                @endif
                            @endif

                            @if ($optionType === 'header' && $optionName !== 'Distance')
                                <div class="form-group col-12 mb-1">
                                    <p class="text-center">{{{ $optionName }}}</p>
                                </div>
                            @endif

                            @if ($optionType === 'divider')
                                <hr>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    @endforeach
</div>
