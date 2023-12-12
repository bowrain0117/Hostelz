<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Helpers\ListingDisplay;
use App\Models\Listing\ListingFeatures;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class CompareTable extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels,
    ) {
    }

    public function render()
    {
        return view('slp.components.shortcodes.compare-table', );
    }

    public function listings(): Collection
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:CompareTable:{$this->slp->id}",
            config('custom.page_cache_time'),
            function () {
                return $this->hostels->map(function ($hostel) {
                    return [
                        'id' => $hostel->id,
                        'url' => $hostel->path,
                        'name' => $hostel->name,
                        'city' => $hostel->city,
                        'rating' => $hostel->formatCombinedRating(),
                        'moreInfo' => $this->getCheckDirectly($hostel),
                        'goodFor' => $this->getGoodFor($hostel),
                        'distance' => $hostel->distanceToCityCenter,
                        'minPrice' => $hostel->minPriceFormated,
                        'hasFreeBreakfast' => $this->hasFreeBreakfast($hostel),
                        'hasKitchen' => $this->hasKitchen($hostel),
                    ];
                });
            }
        );
    }

    private function getCheckDirectly($listing)
    {
        $activeImporteds = $listing->getActiveImporteds();

        return ListingDisplay::getSiteBarActiveImports($listing, $activeImporteds, $this->slp->category);
    }

    public function getGoodFor($hostel): Collection
    {
        return collect(ListingFeatures::getListingGoodForFeatures($hostel->compiledFeatures));
    }

    public function hasFreeBreakfast($hostel): bool
    {
        return ($hostel->compiledFeatures['breakfast'] ?? '') === 'free';
    }

    public function hasKitchen($hostel): bool
    {
        return ($hostel->compiledFeatures['kitchen'] ?? '') === 'yes';
    }
}
