<?php

namespace App\View\Components\Slp;

use App\Lib\Common\Ota\OtaLinks\OtaLinks;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use App\Services\Listings\ListingsPoiService;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Map extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        private ListingsPoiService $listingsPoiService
    ) {
        //
    }

    public function render(): View|Closure|string
    {
        return view('slp.components.map');
    }

    public function pois()
    {
        $callback = function (Listing $listing) {
            return (object) [
                'lat' => $listing->latitude,
                'long' => $listing->longitude,
                'id' => $listing->id,
                'name' => $listing->name,
                'markerInfo' => $this->getMarkerInfo($listing),
            ];
        };

        return (object) $this->listingsPoiService->getMapPoi($this->slp->hostels, $callback);
    }

    protected function getMarkerInfo(Listing $listing)
    {
        $text = $listing->name;

        $rating = $listing->formatCombinedRating();
        if ($rating !== '0.0') {
            $text .= " - {$rating}";
        }

        if ($listing->price->min) {
            $text .= " ({$listing->price->min->formated})";
        }

        $link = OtaLinks::create(listing: $listing, cmpLabel: 'best_city_map')->main->link ?? $listing->path;

        return "<a target='_blank' rel='nofollow' href='{$link}'>{$text}</a>";
    }

    public function shouldRender(): bool
    {
        return $this->slp->hostels->isNotEmpty();
    }
}
