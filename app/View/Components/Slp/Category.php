<?php

namespace App\View\Components\Slp;

use App\Models\SpecialLandingPage;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class Category extends Component
{
    public $listings;

    public int $perPage = 30;

    public function __construct(
        public $category
    ) {
        $this->listings = SpecialLandingPage::query()
            ->whereCategory($this->category)
            ->published()
            ->orderBy('slug')
            ->with(['subjectable', 'media'])
            ->paginate($this->perPage);
    }

    public function render()
    {
        return view('slp.components.category', );
    }

    public function items(): Collection
    {
        return $this->listings
            ->map(fn (SpecialLandingPage $slp) => (object) [
                'title' => $slp->meta->title,
                'thumbnail' => $slp->mainPic,
                'path' => $slp->path,
            ]);
    }

    public function pagination(): Htmlable
    {
        return $this->listings->onEachSide(1)->links('partials._listingsPaginator');
    }
}
