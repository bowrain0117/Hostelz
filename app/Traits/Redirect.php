<?php

namespace App\Traits;

use App\Models\CityInfo;
use App\Models\ContinentInfo;
use App\Models\Listing\Listing;
use Illuminate\Http\RedirectResponse;

trait Redirect
{
    private function redirectToSearch($arrayFromSlug): RedirectResponse
    {
        // This one we keep as a 302 temporary redirect (no reason to pass link authority to the search results page)
        return redirect()->route('search', ['search' => implode(', ', array_reverse($arrayFromSlug))]);
    }

    private function getCorrectUrl(CityInfo|Listing|ContinentInfo $item): string
    {
        return $item->getURL();
    }

    private function getCitiesCorrectUrl(CityInfo $cityInfo, $isCityGroupPage): string
    {
        $correctURL = ($cityInfo->region === '' ? $cityInfo->getCountryURL() : $cityInfo->getRegionURL('auto', null, true));

        if ($isCityGroupPage) {
            $correctURL = $cityInfo->getCityGroupURL();
        }

        return $correctURL;
    }
}
