<?php 
    use App\Models\CityInfo;
    use App\Models\CountryInfo;
?>

{{-- Warn about issues with the description. --}}

@if ($attachedText->data != '')
    @if ($attachedText->subjectType == 'cityInfo' && str_word_count($attachedText->data) < CityInfo::CITY_DESCRIPTION_MINIMUM_WORDS)
        <div class="alert alert-warning">Note: This description is less than the minimum of {!! CityInfo::CITY_DESCRIPTION_MINIMUM_WORDS !!} words.</div>
    @elseif ($attachedText->subjectType == 'countryInfo' && $attachedText->subjectString == '' && str_word_count($attachedText->data) < CountryInfo::COUNTRY_DESCRIPTION_MINIMUM_WORDS)
        <div class="alert alert-warning">Note: This description is less than the minimum of {!! CountryInfo::COUNTRY_DESCRIPTION_MINIMUM_WORDS !!} words.</div>
    @elseif ($attachedText->subjectType == 'countryInfo' && $attachedText->subjectString != '' && str_word_count($attachedText->data) < CountryInfo::REGION_DESCRIPTION_MINIMUM_WORDS)
        <div class="alert alert-warning">Note: This description is less than the minimum of {!! CountryInfo::REGION_DESCRIPTION_MINIMUM_WORDS !!} words.</div>
    @endif
@endif
