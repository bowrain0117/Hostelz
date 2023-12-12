{{-- 
  
TODO: Make this a Javascript function so that this block of code doesn't have to be included repeated.

Input:

    $fieldName
    $placeholderText
    $minCharacters (optional) 
    $selectSelector (optional) - jQuery selector of the <select> element.
    $allowClear (optional) - Show "x" to clear it. Defaults to true.
    $initSelection
    
--}}

<?php Lib\HttpAsset::requireAsset('select2-bootstrap'); ?>

<?php

    //  name of relation method if exist
    $relationMethodName = $fieldName . 'List';

    if (!isset($initSelection)) {
        $initSelection = $formHandler->model
                         && ($formHandler->model->$fieldName || (method_exists($formHandler->model, $relationMethodName) && !$formHandler->model->$relationMethodName()->get()->isEmpty()));
    }

?>

<script>
    $(document).ready(function() {
        
        var initSelect2 = function () {
            $(
                @if (@$selectSelector) 
                    "{!! $selectSelector !!}" 
                @else 
                    {{-- The input element with a name as data[fieldName], search[fieldName], data[fieldName][], ?? search[fieldName][] --}}
                    "input[name='data\\[{!! $fieldName !!}\\]'], input[name='search\\[{!! $fieldName !!}\\]'], input[name='data\\[{!! $fieldName !!}\\]\\[\\]'], input[name='search\\[{!! $fieldName !!}\\]\\[\\]']" 
                @endif
            ).each(function(index) {
                var $inputElement = $(this);
                if ($inputElement.closest('tr').hasClass('fhMulti_template')) return; // skip the fhMulti_template

                $inputElement.select2({
                    placeholder: "{{{ $placeholderText }}}",
                    minimumInputLength: {!! isset($minCharacters) ? $minCharacters : 1 !!},
                    allowClear: @if (@$allowClear === false) false @else true @endif,
                    multiple: false,
                    @if ( $initSelection )
                        initSelection: function (element, callback) {
                            $.getJSON(window.location.pathname,
                                { {!! $fieldName !!}_selectorIdFind: $inputElement.val() },
                                function(data) {
                                  callback(data.results);
                                }
                            );
                        },
                    @endif
                    ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                        dataType: 'json',
                        quietMillis: 300,
                        data: function (searchTerm, page) {
                            return {
                                {!! $fieldName !!}_selectorSearch: searchTerm,
                            };
                        },
                        results: function (data, page) { // parse the results into the format expected by Select2.
                            return { results: data.results };
                        },
                        cache: true
                    }
                });
            })
        };
        
        initSelect2();
        
        // So it also works with elements 'multi' FormHandler inputs.
        $.Topic('formHandler.updateFormHandlerElements').subscribe(initSelect2);
    });
</script>
