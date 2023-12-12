<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\CityInfo;
use App\Models\Languages;
use App\Models\Listing\Listing;
use Illuminate\Http\Request;
use Lib\PageCache;

class MainPageShowController extends Controller
{
    public function __invoke(Request $request)
    {
        $featuredCities = CityInfo::getFeaturedCitiesData();

        PageCache::addCacheTags(['homepage', 'listing:aggregation', 'city:aggregation']);

        return view('index', [
            'hostelCount' => Languages::current()->numberFormat(Listing::areLive()->count()),
            'cityCount' => Languages::current()->numberFormat(CityInfo::areLive()->count()),
            'featuredCities' => $featuredCities,
            'cityUrls' => $featuredCities->map(fn ($city) => $city->getUrl()),
            'blogs' => Article::getBlogs()->take(3),
            'ogThumbnail' => url('images', 'best-hostel-price-comparison.jpg'),
        ]);
    }
}
