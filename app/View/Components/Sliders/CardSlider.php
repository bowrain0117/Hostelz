<?php

namespace App\View\Components\Sliders;

use App\Models\Listing\Listing;
use App\Models\Pic;
use Illuminate\View\Component;

class CardSlider extends Component
{
    public function __construct(
        protected Listing $listing,
    ) {
    }

    public function render()
    {
        return view('components.sliders.card-slider');
    }

    public function pics()
    {
        return cache()->tags(["listing:{$this->listing->id}"])->remember(
            "listing:CardSlider:pics:{$this->listing->id}",
            config('custom.page_cache_time'),
            fn () => $this->listing->getPics(Pic::GALLERY_PREVIEW)
        );
    }

    public function listing()
    {
        return cache()->tags(["listing:{$this->listing->id}"])->remember(
            "listing:CardSlider:listing:{$this->listing->id}",
            config('custom.page_cache_time'),
            fn () => collect([
                'name' => $this->listing->name,
                'url' => $this->listing->path,
            ])
        );
    }

    public function listingId()
    {
        return $this->listing->id;
    }
}
