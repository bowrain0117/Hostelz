<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Enums\CategorySlp;
use App\Models\Listing\ListingFeatures;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class ListHostelsBest extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels,
    ) {
    }

    public function render()
    {
        return view('slp.components.shortcodes.list-hostels-best');
    }

    public function title()
    {
        return match ($this->slp->category) {
            CategorySlp::Best => 'Short and crisp: The Best Hostels in ' . $this->slp->subjectable->city,
            CategorySlp::Private => 'Quick answer: The Best Hostels in ' . $this->slp->subjectable->city . ' with Private Rooms',
            CategorySlp::Party => 'Short and Boozy: The Best Party Hostels in ' . $this->slp->subjectable->city,
            default => 'Short and crisp: Hostels in ' . $this->slp->subjectable->city,
        };
    }

    public function listings(): Collection
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:ListHostels:{$this->slp->id}",
            config('custom.page_cache_time'),
            function () {
                return $this->hostels->map(function ($listing) {
                    return [
                        'url' => $this->getUrl($listing),
                        'name' => $listing->name,
                        'goodFor' => ListingFeatures::getListingGoodForFeatures($listing->compiledFeatures),
                    ];
                });
            }
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
