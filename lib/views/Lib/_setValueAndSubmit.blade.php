{{--

Usage:

    <button class="btn btn-success setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="ok">Approve</button>

--}}

$("button.setValueAndSubmit").click(function() {
    var nameOfField = $(this).data('nameOfField');
    var valueOfField = $(this).data('valueOfField');
    $("[name='"+nameOfField+"']").val(valueOfField);
    {{-- Add a hidden input to include info about what button was clicked (some scripts may want to know) --}}
    $("[name='"+nameOfField+"']").closest('form').append('<input type="hidden" name="setValueAndSubmit_'+nameOfField+'" value="'+escape(valueOfField)+'" />');
    $("form.formHandlerForm button[value='update']").click();
});
