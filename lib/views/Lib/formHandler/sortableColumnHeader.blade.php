{{-- 

Display the table header for a sortable (or non-sortable) field.

Input Parameters:
    
    $formHandler
    $fieldName
    $label
    
--}}

@if ($formHandler->isFieldUserSortable($fieldName))
    <a href="{!! $formHandler->getSortByLink($fieldName) !!}" style="color: black; white-space: nowrap;">
        {{{ $label }}}
        {{-- Note: We only set the arrow based on the *first* sort field if there are multiple. --}}
        <?php if (@$formHandler->listSort) {
    reset($formHandler->listSort);
} ?>
        @if (!@$formHandler->listSort || key($formHandler->listSort) != $fieldName)
            <i class="fa fa-sort"></i>
        @elseif ($formHandler->listSort[$fieldName] == 'desc')
            <i class="fa fa-sort-desc"></i>
        @elseif ($formHandler->listSort[$fieldName] == 'asc')
            <i class="fa fa-sort-asc"></i>
        @else
        @endif
    </a>
@else
    {{{ $label }}}
@endif
