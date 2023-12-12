<?php

namespace App\Http\Controllers;

use App\Models\ContinentInfo;
use App\Services\Listings\CountryInfoService;
use App\Traits\Redirect as RedirectTrait;
use Illuminate\Support\Facades\Request;
use Lib\PageCache;

class ContinentController extends Controller
{
    use RedirectTrait;

    private CountryInfoService $countryInfoService;

    public function __construct(CountryInfoService $countryInfoService)
    {
        $this->countryInfoService = $countryInfoService;
    }

    public function continent($slug)
    {
        PageCache::addCacheTags('city:aggregation'); // mark the cache so it can by cleared when any city is altered

        $continentInfo = ContinentInfo::findByUrlSlug($slug);

        // * Check the URL *
        $correctURL = $this->getCorrectUrl($continentInfo);
        if ('/' . Request::path() !== $correctURL) {
            return redirect($correctURL, 301);
        }
        $ogThumbnail = url('images', 'all-hostels-in-the-world.jpg');
        $countriesInfo = $this->countryInfoService->getCountriesByContinent($continentInfo);
        $citiesInfo = $this->countryInfoService->getCountriesWithCities($countriesInfo);

        return view('continent', [
            'continentInfo' => $continentInfo,
            'countryInfos' => $countriesInfo,
            'cityInfos' => $citiesInfo, ],
            compact(

                'ogThumbnail',
            ));
    }
}
