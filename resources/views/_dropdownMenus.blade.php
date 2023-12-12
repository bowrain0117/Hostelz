{{--

Input variables:

    $dropdownMenus = [ 
        'label'
        'selectType' -> 'multiple', 'single' (can be de-selected), or 'radio' (can't be de-selected)
        'options' -> name/value pairs, or array of [ 'name', 'value', 'type' ('option', 'divider', 'header') ]
    ]
    
    $dropUp - true to dropup instead of dropdown.

--}}

@foreach ($dropdownMenus as $menuName => $menu)
    <div class="@if (@$dropUp) dropup @endif ourDropdownMenus btn-group" data-dropdown-name="{!! $menuName !!}" data-dropdown-select-type="{!! $menu['selectType'] !!}">
        <button class="btn btn-sm btn-default dropdown-toggle" data-toggle="dropdown" type="button">
            {!! $menu['label'] !!} <span class="caret"></span>
        </button>

        <ul class="dropdown-menu">
            @foreach ($menu['options'] as $optionKey => $option)
                {{-- Each can be simple value => name pairs, ?? an array of [ 'name', 'type', 'value' ] --}}
                <?php 
                    if (is_array($option)) {
                        $optionName = @$option['name'];
                        $optionType = $option['type'];
                        $optionValue = @$option['value'];
                    } else {
                        $optionName = $option;
                        $optionType = 'option';
                        $optionValue = $optionKey;
                    }
                ?>
                
                @if ($optionType == 'option')
                    <li data-dropdown-value="{{{ $optionValue }}}">
                        <a href="#"><i class="fa @if ($menu['selectType'] == 'radio') fa-circle @else fa-check @endif"></i> {{{ $optionName }}}</a>
                    </li>
                @elseif ($optionType == 'header')
                    <li class="dropdown-header">{{{ $optionName }}}</li>
                @elseif ($optionType == 'divider')
                    <li role="separator" class="divider"></li>
                @endif
            @endforeach
        </ul>
    </div>
@endforeach

