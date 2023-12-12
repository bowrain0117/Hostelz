@php
    /* define values for meta info here */ 
    $metaValues = ['hostelCount' => $cityInfo->hostelCount, 'count' => $cityInfo->totalListingCount, 'city' => $cityInfo->translation()->city, 'month' => date("M"), 'year' => date("Y"), 'lowestDormPrice' => $lowestDormPrice, 'area' => $cityInfo->translation()->country, 'continent' => $cityInfo->translation()->continent ];
    /* (Have to be careful not to make the title longer than about 50-60 characters or Google will cut it off.) */    

    if ($cityInfo->hostelCount >= 2){
        /* "backpackers" is more commonly used in Oceania */
        if ($cityInfo->continent == 'Australia & Oceania'){
            $title = langGet('SeoInfo.CityMetaTitleBackpackers', $metaValues);                
        }
        else {
            $title = langGet('SeoInfo.CityMetaTitle', $metaValues);  
        }
    }   
    elseif ($cityInfo->hostelCount == 1){
        $title = langGet('SeoInfo.CityMetaTitle1Hostel', $metaValues);
    }
    elseif ($cityInfo->hostelCount == 0 && $cityInfo->totalListingCount ){
        $title = langGet('SeoInfo.CityMetaTitleNoHostel', $metaValues);
    }
    else {
        $title = langGet('SeoInfo.CityMetaTitleFallback', $metaValues);   
    }
@endphp