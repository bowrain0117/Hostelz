{{-- 
    Optional Variables to Pass:
        - multiSelect - If true, show checkboxes next to each row to perform some action on the rows (such as multiDelete).  If not defined, it's set automatically if multiDelete is an allowed mode.
--}}

<?php
    Lib\HttpAsset::requireAsset('formHandler.js');
    if (! isset($multiSelect)) {
        $multiSelect = in_array('multiDelete', $formHandler->allowedModes);
    }
?>

@if ($formHandler->multiErrors)
    <p><div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> See error messages below.</div></p>
@endif

@if ($formHandler->list && !$formHandler->list->isEmpty())
        <?php
            $listFields = $formHandler->listDisplayFields ? $formHandler->listDisplayFields : $formHandler->listSelectFields;
            if (! $listFields) {
                $listFields = array_keys($formHandler->fieldInfo);
            }
        ?>
        
        <table class="table formHandlerList">
        
        {{-- Table header --}}
        
        <thead>
        <tr>
            @if ($multiSelect)
                <th>Select</th>
            @endif
            
            @foreach ($listFields as $fieldName)
                @if (array_key_exists($fieldName, $formHandler->fieldInfo) && !in_array($formHandler->determineInputType($fieldName, 'list', true), [ 'ignore', 'hidden' ]))
                    <th>
                        {!! $formHandler->getLanguageText('fieldLabel', $fieldName, 'list') !!}                  
                    </th>
                @endif
            @endforeach
        </tr>
        </thead>
        
        <tbody>
        
        {{-- Select/delect all controls --}}
        
        <tr>
            @if ($multiSelect)
                <td class="text-center"><div class="form-group"><input class="multiSelectAll" type="checkbox"></div></td>
            @endif
            
            @foreach ($listFields as $fieldName)
                @if (array_key_exists($fieldName, $formHandler->fieldInfo))
                    <?php
                        $fieldInfo = $formHandler->fieldInfo[$fieldName];
                        $type = $formHandler->determineInputType($fieldName, 'list', true);
                    ?>
                    
                    @if (in_array($type, [ 'checkbox', 'checkboxes', 'select', 'text', 'datePicker', 'textarea' ])) {{-- Note: We could also support other types if needed. --}}
                        <?php
                            // this is actually used to know what to search for when changing the values (see the JS below).
                            $varName = $formHandler->inputDataVarName.'[_ID_REGEX_HERE_]['.$fieldName.']';
                        ?>
                        <td class="editableListControlAll">
                            <div class="form-group">
                                @include('Lib/formHandler/inputField', [ 'pageType' => 'list', 'errors' => null, 'value' => '' ])
                            </div>
                        </td>
                    @elseif (!in_array($type, [ 'ignore', 'hidden' ]))
                        <td> </td>
                    @endif
                @endif
            @endforeach
        </tr>
        
        {{-- Table rows --}}
        
        @foreach ($formHandler->list as $rowKey => $row)
            <tr>
            @if ($multiSelect)
                <td class="text-center"><div class="form-group"><input name="multiSelect[]" type="checkbox" value="{!! $row->id !!}"></div></td>
            @endif
            
            @foreach ($listFields as $fieldName)
                @if (array_key_exists($fieldName, $formHandler->fieldInfo))
                    <?php
                        $fieldInfo = $formHandler->fieldInfo[$fieldName];
                        $type = $formHandler->determineInputType($fieldName, 'list', true); // or 'editableList' ?
                    ?>
                    
                    @if (!in_array($type, [ 'ignore', 'hidden' ]))
                        <?php
                            // if (array_key_exists($fieldName, $formHandler->fieldInfo))...
                            $value = $formHandler->getFieldValueForList($fieldName, $row, false);
                            $varName = $formHandler->inputDataVarName.'['.$row->id.']'.'['.$fieldName.']';
                        ?>
    
                        <td>
                            <div class="form-group">
                                @include('Lib/formHandler/inputField', [ 'pageType' => 'list', 'errors' => $formHandler->multiErrors ? @$formHandler->multiErrors[$row->id] : $formHandler->errors ])
                            </div>
                        </td>
                    @endif
                @endif
            @endforeach
            </tr>
        @endforeach
        </tbody>
    </table>
    
@else
    <br><div class="alert alert-info">No items found.</div>
@endif

@section('pageBottom')
    <script>
        {{-- Prep the controllAll controls by moving their name to a "controlAllMatchingName" property. --}}
        $('.editableListControlAll input, .editableListControlAll textarea, .editableListControlAll select').each(function() {
            /* set a special property called "controlAllMatchingName" */
            $(this).prop('controlAllMatchingName', escapeRegExp($(this).prop('name')).replace('_ID_REGEX_HERE_', '\\d+'));
            $(this).prop('name', ''); /* so that it doesn't get submitted with the form */
        });
        
        {{-- Control all: String, DatePicker --}}
        $('.editableListControlAll input[type="text"]').on('keyup change', function(event) {
            var nameRegEx = new RegExp($(this).prop('controlAllMatchingName'));
            var value = $(this).val();
            
            $(this).closest('table.formHandlerList').find('td:not(.editableListControlAll) input[type="text"]').each(function() {
                {{-- (note:  change() triggers any other jquery events, such as row highlighting) --}}
                if (nameRegEx.test($(this).prop('name'))) $(this).val(value); 
            });
        });
        
        {{-- Control all: textarea --}}
        $('.editableListControlAll textarea').on('keyup change', function(event) {
            var nameRegEx = new RegExp($(this).prop('controlAllMatchingName'));
            var value = $(this).val();
            
            $(this).closest('table.formHandlerList').find('td:not(.editableListControlAll) textarea').each(function() {
                {{-- (note:  change() triggers any other jquery events, such as row highlighting) --}}
                if (nameRegEx.test($(this).prop('name'))) $(this).val(value); 
            });
        });
        
        {{-- Control all: Checkbox --}}
        $('.editableListControlAll input[type="checkbox"]').change(function(event) {
            var nameRegEx = new RegExp($(this).prop('controlAllMatchingName'));
            var value = $(this).val();
            var isChecked = $(this).prop('checked');
            
            $(this).closest('table.formHandlerList').find('td:not(.editableListControlAll) input[type="checkbox"]').each(function() {
                {{-- (note:  change() triggers any other jquery events, such as row highlighting) --}}
                if (nameRegEx.test($(this).prop('name')) && $(this).val() == value) $(this).prop('checked', isChecked).change(); 
            });
        });
        
        {{-- Control all: Select --}}
        $('.editableListControlAll select').change(function(event) {
            var nameRegEx = new RegExp($(this).prop('controlAllMatchingName'));
            var value = $(this).val();
            $(this).closest('table.formHandlerList').find('td:not(.editableListControlAll) select').each(function() {
                {{-- (note:  change() triggers any other jquery events, such as row highlighting) --}}
                if (nameRegEx.test($(this).prop('name'))) $(this).val(value).change();
            });
        });
        
        @if ($multiSelect)
            {{-- multiSelectAll --}}
            $('table.formHandlerList input.multiSelectAll').change(function(event) {
                var isChecked = $(this).prop('checked');
                $(this).closest('table.formHandlerList').find('td input[name^="multiSelect"]').each(function() {
                    {{-- (note:  change() triggers any other jquery events, such as row highlighting) --}}
                    $(this).prop('checked', isChecked).change(); 
                });
            });
        
            {{-- Highlight selected rows --}}
            $('table.formHandlerList td input[name^="multiSelect"]').change(function(event) {
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