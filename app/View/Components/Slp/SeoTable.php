<?php

namespace App\View\Components\Slp;

use App\Enums\CategorySlp;
use App\Lib\Common\Ota\OtaLinks\OtaLinks;
use App\Models\Listing\Listing;
use App\Models\SpecialLandingPage;
use Closure;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class SeoTable extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
    ) {
    }

    public function render(): View|Closure|string
    {
        return view('slp.components.seo-table.index');
    }

    public function title()
    {
        return match ($this->slp->category) {
            CategorySlp::Best => 'Hostel Haven: Choose Your Character',
            CategorySlp::Private => 'Hostel Haven: Choose Your Character',
            CategorySlp::Cheap => 'Hostel Haven: Choose Your Character',
            CategorySlp::Party => 'Hostel Haven: Select Your Character',
        };
    }

    public function items(): Collection
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:SeoTable:{$this->slp->id}:category:{$this->slp->category->value}",
            config('custom.page_cache_time'),
            function () {
                $method = str($this->slp->category->value)->camel()->value();

                return $this->$method();
            }
        );
    }

    public function partyHostels()
    {
        return $this->bestHostels();
    }

    public function bestHostels()
    {
        $best = $this->slp->bestHostel();
        $femaleSolo = $this->slp->femaleSoloTraveller();
        $solo = $this->slp->soloTraveler();
        $families = $this->slp->families();
        $couples = $this->slp->couples();
        $party = $this->slp->parting();

        return collect()
            ->when($best, fn (Collection $items) => $items->push([
                'title' => '🏆 Overall Best Hostel',
                'name' => $best->name,
                'link' => $this->getReservationLink($best),
            ]))
            ->when($femaleSolo, fn (Collection $items) => $items->push([
                'title' => '🙋‍♀️ Female Solo-Traveler',
                'name' => $femaleSolo->name,
                'link' => $this->getReservationLink($femaleSolo),
            ]))
            ->when($solo, fn (Collection $items) => $items->push([
                'title' => '🤹 Solo-Traveler',
                'name' => $solo->name,
                'link' => $this->getReservationLink($solo),
            ]))
            ->when($families, fn (Collection $items) => $items->push([
                'title' => '👨‍👩‍👦 Family Hostel with Kids',
                'name' => $families->name,
                'link' => $this->getReservationLink($families),
            ]))
            ->when($couples, fn (Collection $items) => $items->push([
                'title' => '👩‍❤️‍👨 for Couples',
                'name' => $couples->name,
                'link' => $this->getReservationLink($couples),
            ]))
            ->when($party, fn (Collection $items) => $items->push([
                'title' => '🎉 Party Hostel',
                'name' => $party->name,
                'link' => $this->getReservationLink($party),
            ]))
            ->reject(fn ($item) => ! $item['link']);
    }

    public function privateHostels()
    {
        $topPrivate = $this->slp->bestPrivateHostel();
        $cheapest = $this->slp->cheapestPrivate();
        $couples = $this->slp->couplesPrivate();
        $families = $this->slp->familiesPrivate();

        return collect()
            ->when($topPrivate, fn (Collection $items) => $items->push([
                'title' => '🤩 Top Hostel with Private Room',
                'name' => $topPrivate->name,
                'link' => $this->getReservationLink($topPrivate),
            ]))
            ->when($cheapest, fn (Collection $items) => $items->push([
                'title' => '🤑 Cheapest Private Room',
                'name' => $cheapest->name,
                'link' => $this->getReservationLink($cheapest),
            ]))
            ->when($couples, fn (Collection $items) => $items->push([
                'title' => '👩‍❤️‍👨 For Couples',
                'name' => $couples->name,
                'link' => $this->getReservationLink($couples),
            ]))
            ->when($families, fn (Collection $items) => $items->push([
                'title' => '👨‍👩‍👦 For Families with Kids',
                'name' => $families->name,
                'link' => $this->getReservationLink($families),
            ]))
            ->reject(fn ($item) => ! $item['link']);
    }

    public function cheapHostels()
    {
        $cheapest = $this->slp->cheapest();
        $best = $this->slp->bestHostel();
        $party = $this->slp->parting();
        $solo = $this->slp->soloTraveler();

        return collect()
            ->when($cheapest, fn (Collection $items) => $items->push([
                'title' => '🤑 Cheapest Hostel',
                'name' => $cheapest->name,
                'link' => $this->getReservationLink($cheapest),
            ]))
            ->when($best, fn (Collection $items) => $items->push([
                'title' => '🤩 Best Cheap Hostel',
                'name' => $best->name,
                'link' => $this->getReservationLink($best),
            ]))
            ->when($party, fn (Collection $items) => $items->push([
                'title' => '🎉 Party Hostel',
                'name' => $party->name,
                'link' => $this->getReservationLink($party),
            ]))
            ->when($solo, fn (Collection $items) => $items->push([
                'title' => '🤹 Solo-Traveler',
                'name' => $solo->name,
                'link' => $this->getReservationLink($solo),
            ]))
            ->reject(fn ($item) => ! $item['link']);
    }

    protected function getReservationLink(Listing $listing)
    {
        return OtaLinks::create($listing, $this->slp->category)->main?->link;
    }

    public function shouldRender()
    {
        return $this->items()->isNotEmpty();
    }
}
