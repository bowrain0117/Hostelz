<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Models\Listing\Listing;
use App\Models\PriceHistory;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\Component;

class WhenBook extends Component
{
    public $_data;

    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels,
    ) {
        $this->_data = $this->_data();
    }

    public function render()
    {
        return view('slp.components.shortcodes.when-book');
    }

    protected function _data()
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:WhenBook:{$this->slp->id}",
            config('custom.page_cache_time'),
            function () {
                $priceHistory = $this->getPriceHistory();

                return (object) [
                    'subjectName' => $this->slp->subjectable->city,
                    'minMonthData' => $this->getMinMonthData($priceHistory),
                    'maxMonthData' => $this->getMaxMonthData($priceHistory),
                    'hostelsCount' => $this->getHostelsCount(),
                    'partyHostelsCount' => $this->getPartyCount(),
                    'topRatedCount' => $this->getTopRatedCount(),
                    'mostRatingNeighborhood' => $this->getMostRatingNeighborhood(),
                ];
            }
        );
    }

    private function getPriceHistory(): Collection
    {
        $listingIDs = $this->hostels->pluck('id');
        $roomType = 'dorm';
        $peoplePerRoom = false;

        $data = PriceHistory::select(DB::raw('AVG(averagePricePerNight) as priceAverage, month, MONTH(month) as justMonth'))
                    ->whereIn('listingID', $listingIDs)
                    ->where('roomType', $roomType)
                    ->where('month', '>=', Carbon::now()->subMonths(12)->startOfMonth()->format('Y-m-d'))
                    ->where('month', '<', Carbon::now()->startOfMonth()->format('Y-m-d'))
                    ->when($peoplePerRoom, function ($query, $peoplePerRoom) {
                        $query->where('peoplePerRoom', $peoplePerRoom);
                    })
                    ->groupBy('justMonth')
                    ->orderBy('justMonth')
                    ->get();
        if ($data->isNotEmpty()) {
            return $data;
        }

        $roomType = 'private';
        $peoplePerRoom = 2;

        return PriceHistory::select(DB::raw('AVG(averagePricePerNight) as priceAverage, month, MONTH(month) as justMonth'))
                    ->whereIn('listingID', $listingIDs)
                    ->where('roomType', $roomType)
                    ->where('month', '>=', Carbon::now()->subMonths(12)->startOfMonth()->format('Y-m-d'))
                    ->where('month', '<', Carbon::now()->startOfMonth()->format('Y-m-d'))
                    ->when($peoplePerRoom, function ($query, $peoplePerRoom) {
                        $query->where('peoplePerRoom', $peoplePerRoom);
                    })
                    ->groupBy('justMonth')
                    ->orderBy('justMonth')
                    ->get();
    }

    private function getMinMonthData(Collection $priceHistory)
    {
        return $priceHistory->reduce(function ($result, $item) {
            return is_null($result) || $item->priceAverage < $result->priceAverage ? $item : $result;
        });
    }

    private function getMaxMonthData(Collection $priceHistory)
    {
        return $priceHistory->reduce(function ($result, $item) {
            return is_null($result) || $item->priceAverage > $result->priceAverage ? $item : $result;
        });
    }

    private function getHostelsCount()
    {
        return $this->slp->subjectable->hostelCount;
    }

    private function getPartyCount()
    {
        return Listing::byCityInfo($this->slp->subjectable)
                ->hostels()
                ->where(function ($query): void {
                    $query->where('compiledFeatures', 'like', '%partying%')
                          ->orWhere('mgmtFeatures', 'like', '%partying%');
                })
                ->areLive()
                ->count();
    }

    private function getTopRatedCount()
    {
        return Listing::byCityInfo($this->slp->subjectable)
                      ->hostels()
                      ->topRated()
                      ->areLive()
                      ->count();
    }

    private function getMostRatingNeighborhood()
    {
        return collect($this->slp->subjectable->getMostRatingNeighborhood())->implode(', ');
    }
}
