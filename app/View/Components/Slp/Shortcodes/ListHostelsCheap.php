<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Models\PriceHistory;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ListHostelsCheap extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels,
    ) {
    }

    public function render()
    {
        return view('slp.components.shortcodes.list-hostels-cheap');
    }

    public function listings()
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:CheapHostelsList:{$this->slp->id}",
            config('custom.page_cache_time'),
            fn () => $this->hostels->map(function ($listing) {
                return [
                    'id' => $listing->id,
                    'url' => $this->getUrl($listing),
                    'name' => $listing->name,
                    'idAttr' => str()->slug($listing->name),
                    'city' => $listing->city,
                    'specialText' => $listing->getSlpText($this->slp->category),
                    'minPrice' => $listing->minPriceFormated,
                ];
            })
        );
    }

    public function subjectName()
    {
        return $this->slp->subjectable->city;
    }

    private function getUrl($listing)
    {
        $label = getCMPLabel($this->slp->category, $listing->city, $listing->name);

        $hwImport = $listing->getHwImporteds()->first();
        if ($hwImport) {
            return $hwImport->staticLink($label);
        }

        $bdcImport = $listing->getBdcImporteds()->first();
        if ($bdcImport) {
            return $bdcImport->staticLink($label);
        }

        return $listing->path;
    }
}
