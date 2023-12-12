{{-- 


--}}

<?php Lib\HttpAsset::requireAsset('select2-bootstrap'); ?>

<script>
    $(document).ready(function() {
        
        $("input[name='data\\[placeSelector\\]'], input[name='search\\[placeSelector\\]']").select2({
            placeholder: "Search by place or listing name.",
            minimumInputLength: 3,
            allowClear: true,
            multiple: false,
            @if ($formHandler->model && $formHandler->model->placeType != '')
                initSelection: function (element, callback) {
                    callback({id: "{{{ $formHandler->model->getEncodedPlaceString() }}}", text: "{!! addslashes($formHandler->model->placeFullDisplayName()) !!}"});
                },
            @endif
            ajax: { // instead of writing the function to execute the request we use Select2's convenient helper
                /* url: "", */
                dataType: 'json',
                quietMillis: 300,
                data: function (searchTerm, page) {
                    return {
                        command: 'placeSearch',
                        search: searchTerm,
                    };
                },
                results: function (data, page) { // parse the results into the format expected by Select2.
                    return { results: data.results };
                },
                cache: true
            }
        });
    
    });
</script>
