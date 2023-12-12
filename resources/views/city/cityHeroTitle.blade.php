@php
    if ($metaValues['hostelCount'] >= 2){
        /* "backpackers" is more commonly used in Oceania */
        if ($metaValues['continent'] === 'Australia & Oceania'){
            $headertitle = langGet('city.TopTitleHostelsOceania', $metaValues);
            $headertext = langGet('city.TopTextHostelsOceania', $metaValues);
        }
        else {
            $headertitle = langGet('city.TopTitleHostels', $metaValues);
            $headertext = langGet('city.TopTextHostels', $metaValues);
        }
    }
    elseif ($metaValues['hostelCount'] === 1){
        $headertitle = langGet('city.TopTitle1Hostel', $metaValues);
        $headertext = langGet('city.TopText1Hostel', $metaValues);
    }
    elseif ($metaValues['hostelCount'] === 0 && $metaValues['count']){
        $headertitle = langGet('city.TopTitleNoHostelCheapPlaces', $metaValues);
        $headertext = langGet('city.TopTextNoHostelCheapPlaces', $metaValues);
    }
    else {
        $headertitle = langGet('city.TopTitleFallback', $metaValues);
        $headertext = langGet('city.TopTextFallback', $metaValues);
    }
@endphp

<h1 class="title-1 text-left text-white mb-2 mb-lg-3 hero-title">{{ $headertitle }}</h1>
<div class="sb-title text-left text-white hero-description" style="display: none;">{{ $headertext }}</div>