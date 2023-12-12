<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Lib\Common\Ota\OtaLinks\OtaLinks;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class CardHostelsPrivate extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels, )
    {
    }

    public function render()
    {
        return view('slp.components.shortcodes.card-hostels-private');
    }

    public function listings(): Collection
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:PrivateHostels:{$this->slp->id}",
            config('custom.page_cache_time'),
            function () {
                return $this->hostels->map(function (Listing $listing) {
                    return [
                        'id' => $listing->id,
                        'url' => $listing->path,
                        'name' => $listing->name,
                        'idAttr' => str()->slug($listing->name),
                        'city' => $listing->city,
                        'rating' => $listing->formatCombinedRating(),
                        'specialText' => $listing->getSlpText($this->slp->category),
                        'model' => $listing->setAppends(['path']),
                        'otaLinks' => OtaLinks::create($listing, $this->slp->category),
                        'minPrice' => $listing->minPricePrivateFormated,
                    ];
                });
            }
        );
    }

    public function subjectName()
    {
        return $this->slp->subjectable->city;
    }
}
