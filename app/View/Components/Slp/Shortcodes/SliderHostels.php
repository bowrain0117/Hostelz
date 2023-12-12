<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Blade;
use Illuminate\View\Component;

class SliderHostels extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels,
    ) {
    }

    public function render()
    {
        return view('slp.components.shortcodes.slider-hostels');
    }

    public function listings(): Collection
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:SliderHostels:{$this->slp->id}",
            config('custom.page_cache_time'),
            function () {
                return $this->hostels->map(function (Listing $listing) {
                    return [
                        'id' => $listing->id,
                        'pic' => $listing->thumbnail,
                        'url' => $listing->path,
                        'name' => $listing->name,
                        'rating' => $listing->formatCombinedRating(),
                        'wishlistTemplate' => Blade::render(
                            '<x-wishlist-icon class="z-index-40" listing-id="{{ $listingId }}" />',
                            ['listingId' => $listing->id]
                        ),
                    ];
                });
            }
        );
    }
}
