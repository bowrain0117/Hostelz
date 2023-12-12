<?php

namespace App\View\Components\Listing;

use App\Models\CityInfo;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class AwardSection extends Component
{
    public Collection $items;

    public function __construct(
        public Listing $listing
    ) {
        $this->items = $this->items();
    }

    public function render()
    {
        return view('listing.components.award-section');
    }

    private function items()
    {
        return cache()
            ->tags(['listing:' . $this->listing->id])
            ->remember(
                'award-section:' . $this->listing->id,
                config('custom.page_cache_time'),
                function () {
                    return SpecialLandingPage::query()
                        ->forCity($this->listing->city)
                        ->published()
                        ->get()
                        ->filter->hasListing($this->listing->id)
                        ->map(fn (SpecialLandingPage $slp) => (object) [
                            'slug_id' => $slp->slug_id,
                            'title' => $slp->meta->title,
                            'thumbnail' => $slp->mainPic,
                            'path' => $slp->path,
                        ]);
                });
    }

    public function shouldRender()
    {
        return $this->items->isNotEmpty();
    }
}
