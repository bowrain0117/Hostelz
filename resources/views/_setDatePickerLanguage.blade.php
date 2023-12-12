{{-- This should be included in the pageBottom section of pages that use a DatePicker that needs to use the current language. --}}

@if (\App\Models\Languages::currentCode() != 'en')
    {{-- 
        * Datepicker Internationalization *
        See https://github.com/wikimedia/jquery.i18n for the list of languages. Their codes seem to match Google's language codes. 
    --}}
    <?php $datepickerLanguage = \App\Models\Languages::current()->otherCodeStandard('Google'); ?>
    <script src="/vendor/jquery-ui-i18n/datepicker-{!! $datepickerLanguage !!}.js" type="text/javascript"></script>
    <script type="text/javascript">
        $.datepicker.setDefaults( $.datepicker.regional['{!! $datepickerLanguage !!}'] );
    </script>
@endif
