{{-- 

Input: $valueName

Usage example:

    <a class="objectCommandPostFormValue" data-object-command="duplicate" href="#">Make Duplicate Ad</a>
    
    (At bottom of page:)
    @include('Lib/_postFormValue', [ 'valueName' => 'objectCommand' ])
    
--}}

<form id="{!! $valueName !!}Form" method="post">
    <input type="hidden" name="_token" value="{!! csrf_token() !!}">
    <input type="hidden" name="{!! $valueName !!}">
</form>

<script>
    $("a.{!! $valueName !!}PostFormValue").click(function (event) {
        event.preventDefault();
        $("form#{!! $valueName !!}Form input[name='{!! $valueName !!}']").val($(this).data('{!! $valueName !!}'));
        $("form#{!! $valueName !!}Form").submit();
    });
</script>
