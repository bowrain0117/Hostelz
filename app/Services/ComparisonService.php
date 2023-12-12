<?php

namespace App\Services;

use App\Models\Comparison;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingFeatures;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class ComparisonService
{
    private array $options;

    public function __construct()
    {
        $this->options = __('ListingFeatures.forms.options');
    }

    public function getListingsId()
    {
        if (Auth::check()) {
            $comparisons = Auth::user()->comparisons;

            return $comparisons->pluck('id')->toArray();
        }

        if (session()->has(Comparison::SESSION_COMPARE_KEY)) {
            return session()->get(Comparison::SESSION_COMPARE_KEY);
        }

        return [];
    }

    public function getListingsWithFeaturesData(Collection $listings): Collection
    {
        return $listings->map(fn ($listing) => (object) [
            'id' => $listing->id,
            'name' => $listing->name,
            'minPrice' => $listing->price->min?->formated,
            'city' => $listing->city,
            'country' => $listing->country,
            'url' => $listing->getUrl(),
            'cityUrl' => $listing->cityInfo->getUrl(),
            'thumbnailUrl' => $listing->thumbnailURL(),
            'keyFeatures' => $this->getListingKeyFeatures($listing),
            'facilities' => $this->getListingFacilities($listing),
        ]);
    }

    public function getFeatures(): Collection
    {
        return collect([
            'keyFeatures' => [
                'rating' => 'Rating',
                'breakfast' => 'Breakfast',
                'distance' => 'Distance to City Center',
                'great' => 'Great for ...',
            ],
            'facilities' => $this->getFacilities(),
        ]);
    }

    private function getListingFacilities($listing): Collection
    {
        $listingFacilities = collect();

        foreach ($listing->compiledFeatures as $feature => $value) {
            if ($feature === 'extras') {
                foreach ($value as $extra) {
                    $listingFacilities->put($extra, 'yes');
                }
                continue;
            }

            if (is_array($value)) {
                continue;
            }

            if (array_key_exists($feature, $this->options) && array_key_exists($feature, $this->getFacilities())) {
                $value = $this->options[$feature][$value] ?? $value;
                $listingFacilities->put($feature, $value);
                continue;
            }

            if (array_key_exists($feature, $this->getFacilities())) {
                $listingFacilities->put($feature, $value);
            }
        }

        return $listingFacilities;
    }

    private function getListingKeyFeatures(Listing $listing): Collection
    {
        return collect([
            'rating' => $listing->formatCombinedRating(),
            'breakfast' => $listing->isBreakfastFree() ? 'included' : 'not included',
            'distance' => $listing->distanceToCityCenter,
            'great' => $this->getGoodForFeatures($listing),
        ]);
    }

    private function getGoodForFeatures(Listing $listing): array
    {
        if (! isset(ListingFeatures::getDisplayValues($listing->compiledFeatures)['goodFor'])) {
            return ['No'];
        }

        return array_column(
            Arr::flatten(
                ListingFeatures::getDisplayValues($listing->compiledFeatures)['goodFor'],
                1
            ),
            'label'
        );
    }

    private function getFacilities(): array
    {
        $features = __('ListingFeatures.forms.fieldLabel');
        unset($features['breakfast'], $features['extras']);

        return array_merge(
            $features,
            $this->options['extras']
        );
    }
}
