<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Models\PriceHistory;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class AveragePriceGraph extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels
    ) {
    }

    public function render()
    {
        return view('slp.components.shortcodes.average-price-graph');
    }

    public function pricePerMonth()
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            'slp:AveragePriceGraph:' . $this->slp->id,
            config('custom.page_cache_time'),
            function () {
                $listingIDs = $this->hostels->pluck('id');

                return (object) [
                    'dorm' => $this->getPricePerMonthGraphData($listingIDs, 'dorm'),
                    'private' => $this->getPricePerMonthGraphData($listingIDs, 'private', 2),
                ];
            }
        );
    }

    public function subjectName()
    {
        return $this->slp->subjectable->city;
    }

    protected function getPricePerMonthGraphData($listingIDs, $roomType, $peoplePerRoom = null): array
    {
        $prices = PriceHistory::query()
                              ->select(DB::raw('AVG(averagePricePerNight) as priceAverage, month, MONTH(month) as justMonth'))
                              ->whereIn('listingID', $listingIDs)
                              ->where('roomType', $roomType)
                              ->where('month', '>=', Carbon::now()->subMonths(12)->startOfMonth()->format('Y-m-d'))
                              ->where('month', '<', Carbon::now()->startOfMonth()->format('Y-m-d'))
                              ->when($peoplePerRoom, function ($query, $peoplePerRoom) {
                                  $query->where('peoplePerRoom', $peoplePerRoom);
                              })
                              ->groupBy('justMonth')
                              ->orderBy('justMonth')
                              ->get()
                              ->map(fn ($item) => [
                                  'month' => $item->month->format('M'),
                                  'priceAverage' => round($item->priceAverage),
                              ]);

        return collect()
            ->range(1, 12)
            ->map(fn ($i) => [
                'month' => Carbon::create()->startOfMonth()->month($i)->format('M'),
                'priceAverage' => 0,
            ])
            ->merge($prices)
            ->keyBy('month')
            ->pipe(fn ($items) => [
                'labels' => $items->pluck('month')->toArray(),
                'data' => $items->pluck('priceAverage')->toArray(),
            ]);
    }
}
