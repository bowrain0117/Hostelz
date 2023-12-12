<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Enums\CategorySlp;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class TopHostel extends Component
{
    public Listing|null $topHostel;

    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels
    ) {
        $this->topHostel = $this->hostels->first();
    }

    public function render()
    {
        return view('slp.components.shortcodes.top-hostel');
    }

    public function hostel()
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:TopHostel:{$this->slp->id}",
            config('custom.page_cache_time'),
            function () {
                return (object) [
                    'url' => $this->topHostel->path,
                    'name' => $this->topHostel->name,
                    'rating' => $this->topHostel->formatCombinedRating(),
                    'minPrice' => $this->topHostel->minPriceFormated,
                    'pic' => $this->topHostel->thumbnailURL(),
                ];
            }
        );
    }

    public function title()
    {
        return match ($this->slp->category) {
            CategorySlp::Party => sprintf('#1 Hostel in %s to Partying: %s', $this->slp->subjectable?->city, $this->topHostel->name),
            default => sprintf('#1 Top Hostel in %s: %s', $this->slp->subjectable?->city, $this->topHostel->name)
        };
    }

    public function ribonText()
    {
        return match ($this->slp->category) {
            CategorySlp::Party => sprintf('#1 Party Hostel in %s', $this->slp->subjectable?->city),
            default => sprintf('#1 Best Hostel in %s', $this->slp->subjectable?->city)
        };
    }

    public function text()
    {
        return match ($this->slp->category) {
            CategorySlp::Party => sprintf('Any doubts left? You cannot go wrong with %s.', $this->topHostel->name),
            default => sprintf('This is the overall best rated hostel in %s.', $this->topHostel->name),
        };
    }

    public function subjectName()
    {
        return $this->slp->subjectable->city;
    }

    public function shouldRender()
    {
        return $this->slp->hostels->isNotEmpty();
    }
}
