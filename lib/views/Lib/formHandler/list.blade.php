{{-- 
    Optional Variables to Pass:

        - $multiSelect - If true, show checkboxes next to each row to perform some action on the rows (such as multiDelete).  If not defined, it's set automatically if multiDelete is an allowed mode.
        - $extraListFields - Array (matching same keys as the list) of array of extra values to display for each row. Not escaped, not linked (so they can have their own links). 
--}}

<?php

/** @var FormHandler $formHandler */

use Lib\FormHandler;

if (! isset($multiSelect)) {
    $multiSelect = in_array('multiDelete', $formHandler->allowedModes);
}
?>

@if ($formHandler->list && !$formHandler->list->isEmpty())
        <?php
        $listFields = $formHandler->listDisplayFields ?: $formHandler->listSelectFields;
        ?>
    <div class="table-responsive">
        <table class="table table-hover table-striped formHandlerList">
            <thead>
            <tr>
                @if ($multiSelect)
                    <th class="text-center"><input class="multiSelectAll" type="checkbox"></th>
                @endif

                @foreach ($listFields as $fieldName)
                    <th>
                        @include('Lib/formHandler/sortableColumnHeader', [ 'fieldName' => $fieldName, 'label' => $formHandler->getLanguageText('fieldLabel', $fieldName, 'list') ])
                    </th>
                @endforeach
            </tr>
            </thead>

            <tbody>
            @foreach ($formHandler->list as $rowKey => $row)
                <tr>
                    @if ($multiSelect)
                        <td class="text-center">
                            <div class="form-group">
                                <input name="multiSelect[]" type="checkbox" value="{!! $row->id !!}">
                            </div>
                        </td>
                    @endif

                    @foreach ($listFields as $fieldName)
                        <td>
                            @if(isset($title) && $title === 'Hostelgeeks')
                                <a href="{{ route('staff-listings', $row->id) }}" target="_blank">
                                    @else
                                        <a href="{!! $formHandler->listRowLink($row, $fieldName) !!}" target="_blank">
                                            @endif

                                            @if (isset($specialValues[$rowKey][$fieldName]))
                                                {!! $specialValues[$rowKey][$fieldName] !!}
                                            @else
                                                {!! $formHandler->getFieldValueForList($fieldName, $row, true) !!}
                                            @endif

                                        </a>
                        </td>
                    @endforeach

                    @if (isset($extraListFields))
                        @foreach ($extraListFields[$rowKey] as $fieldValue)
                            <th>{!! $fieldValue !!}</th>
                        @endforeach
                    @endif
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@else
    <br>
    <div class="alert alert-info">No items found.</div>
@endif

@section('pageBottom')
    <script>
        @if ($multiSelect)
        {{-- multiSelectAll --}}
        $('table.formHandlerList th input.multiSelectAll').change(function (event) {
            var isChecked = $(this).prop('checked');
            $(this).closest('table.formHandlerList').find('td input[name^="multiSelect"]').each(function () {
                {{-- (note:  change() triggers any other jquery events, such as row highlighting) --}}
                $(this).prop('checked', isChecked).change();
            });
        });

        {{-- Highlight selected rows --}}
        $('table.formHandlerList td input[name^="multiSelect"]').change(function (event) {
            $(this).closest('tr').find('td').toggleClass('background-primary-lt', $(this).prop('checked'));

            {{-- Show/hide any multiSelectHiddenIfNone elements. --}}
            var numberSelected = $(this).closest('table.formHandlerList').find('td input[name^="multiSelect"]:checked').length;
            if (numberSelected)
                $(this).closest('form').find('.multiSelectHiddenIfNone').show();
            else
                $(this).closest('form').find('.multiSelectHiddenIfNone').hide();
        });
        @endif
    </script>

    @parent
@stop