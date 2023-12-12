<?php
    use App\Models\CityInfo;
?>

@php
    /* (Have to be careful not to make the title longer than about 50-60 characters or Google will cut it off.) */    
    if ($cityInfo->hostelCount >= 2){
        /* "backpackers" is more commonly used in Oceania */
        if ($cityInfo->continent === 'Australia & Oceania'){
            $title = langGet('SeoInfo.CityMetaTitleBackpackers', $metaValues);                
        }
        else {
            $title = langGet('SeoInfo.CityMetaTitle', $metaValues);  
        }
    }   
    elseif ($cityInfo->hostelCount === 1){
        $title = langGet('SeoInfo.CityMetaTitle1Hostel', $metaValues);
    }
    elseif ($cityInfo->hostelCount === 0 && $cityInfo->totalListingCount ){
        $title = langGet('SeoInfo.CityMetaTitleNoHostel', $metaValues);
    }
    else {
        $title = langGet('SeoInfo.CityMetaTitleFallback', $metaValues);   
    }
@endphp


@section('title', $title)

@section('header')
    <?php
    $metaCityName = $cityInfo->translation()->cityAlt != ''
        ? $cityInfo->translation()->cityAlt
        : $cityInfo->translation()->city;

    if (CityInfo::areLive()->where('id', '!=', $cityInfo->id)->where('city', $metaCityName)->first())
        $metaCityName .= ', ' . ($cityInfo->country === 'USA' ? $cityInfo->translation()->region : $cityInfo->translation()->country);

        if ($cityInfo->continent === 'Australia & Oceania')
            $metaDescription = langGet('SeoInfo.CityMetaDescriptionBackpackers', $metaValues);
        elseif ($cityInfo->totalListingCount >= 1)
            $metaDescription = langGet('SeoInfo.CityMetaDescription1Hostel', $metaValues);
        else
            $metaDescription = langGet('SeoInfo.CityMetaDescription', $metaValues);
    
        if ($cityInfo->hostelCount >= 2){
            /* "backpackers" is more commonly used in Oceania */
            if ($cityInfo->continent === 'Australia & Oceania'){
                $metaDescription = langGet('SeoInfo.CityMetaDescriptionBackpackers', $metaValues);                
            }
            else {
                $metaDescription = langGet('SeoInfo.CityMetaDescription1Hostel', $metaValues);  
            }
        }   
        elseif ($cityInfo->hostelCount === 1){
            $title = langGet('SeoInfo.CityMetaDescription1Hostel', $metaValues);
        }
        elseif ($cityInfo->hostelCount === 0 && $cityInfo->totalListingCount ){
            $title = langGet('SeoInfo.CityMetaDescriptionNoHostel', $metaValues);
        }
        else {
            $title = langGet('SeoInfo.CityMetaMetaDescriptionFallback', $metaValues);   
        }
    ?>

    <meta name="description" content="{{{ $metaDescription }}}">
    <meta property="og:title" content="{{{ $title }}}" />
    <meta property="og:description" content="{{{ $metaDescription }}}" />

    @parent
@stop

@section('bodyAttributes') class = "city-page" @stop