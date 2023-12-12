<?php

namespace App\View\Components\Slp\Shortcodes;

use App\Lib\Common\Ota\OtaLinks\OtaLinks;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingFeatures;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\View\Component;

class CardHostelsBest extends Component
{
    public function __construct(
        public SpecialLandingPage $slp,
        public Collection $hostels,
    ) {
    }

    public function render()
    {
        return view('slp.components.shortcodes.card-hostels-best');
    }

    public function listings(): Collection
    {
        return cache()->tags(['slp:' . $this->slp->id])->remember(
            "slp:CardHostels:{$this->slp->id}",
            config('custom.page_cache_time'),
            function () {
                return $this->hostels->map(function (Listing $listing) {
                    return [
                        'id' => $listing->id,
                        'url' => $listing->path,
                        'name' => $listing->name,
                        'idAttr' => str()->slug($listing->name),
                        'city' => $listing->city,
                        'rating' => $listing->formatCombinedRating(),
                        'specialText' => $listing->getSlpText($this->slp->category),
                        'otaLinks' => OtaLinks::create($listing, $this->slp->category),
                        'model' => $listing->setAppends(['path']),
                        'price' => $listing->price,
                        'features' => $this->getFeatures($listing),
                    ];
                });
            }
        );
    }

    public function subjectName()
    {
        return $this->slp->subjectable->city;
    }

    private function getFeatures(Listing $listing): array
    {
        $main = [
            'breakfast',
            'lounge',
            'kitchen',
            'bikeRental',
            'tours',
            'pubCrawls',
        ];

        $extras = [
            'gameroom',
            'board_games',
            'bar',
            'pooltable',
            'swimming',
            'hottub',
            'nightclub',
            'darts',
            'karaoke',
            'table_tennis',
            'laundry',
            'videoGames',
            'evening_entertainment',
            'walking_tours',
            'bike_tours',
            'themed_dinner_nights',
            'live_music_performance',
        ];

        $features = collect($main)->flip()
            ->map(fn ($value, $feature) => data_get($listing->compiledFeatures, $feature))
            ->filter();

        $features['extras'] = isset($listing->compiledFeatures['extras'])
            ? collect($extras)->intersect($listing->compiledFeatures['extras'])->toArray()
            : [];

        return ListingFeatures::getDisplayValues($features);
    }
}
