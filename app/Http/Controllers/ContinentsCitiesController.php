<?php

namespace App\Http\Controllers;

use App\Models\ContinentInfo;
use App\Services\AttachedTextService;
use App\Services\Listings\CountryInfoService;
use App\Services\PicsService;

class ContinentsCitiesController extends Controller
{
    private CountryInfoService $countryInfoService;

    private PicsService $picsService;

    private AttachedTextService $attachedTextService;

    public function __construct(CountryInfoService $countryInfoService, PicsService $picsService, AttachedTextService $attachedTextService)
    {
        $this->countryInfoService = $countryInfoService;
        $this->picsService = $picsService;
        $this->attachedTextService = $attachedTextService;
    }

    public function continent()
    {
        $continents = ContinentInfo::all();

        $continentsCountries = $this->countryInfoService->getCountriesWithContinents($continents);

        $continentPics = $this->picsService->getContinentPics($continentsCountries);
        $ogThumbnail = url('images', 'all-hostels-in-the-world.jpg');

        $continentDescriptions = $this->attachedTextService->getContinentsAttachedText($continentPics);

        return view('continents', compact(
            'continents',
            'continentsCountries',
            'continentPics',
            'continentDescriptions',
            'ogThumbnail',
        ));
    }
}
