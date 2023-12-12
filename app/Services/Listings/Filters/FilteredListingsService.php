<?php

namespace App\Services\Listings\Filters;

use App\Models\CityInfo;
use App\Models\Listing\Listing;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class FilteredListingsService
{
    private Builder $query;

    private Collection $listings;

    public array $allFilters;

    public function __construct()
    {
        $this->allFilters = collect(config('listingFilters'))->flatten()->toArray();
    }

    public function getListings(): Collection
    {
        return $this->listings;
    }

    public function applyCityFilters(CityInfo $cityInfo, array $selectedFilters): void
    {
        // * Database Query *
        $this->query = Listing::areLive()
            ->with(['activeImporteds', 'cityInfo'])
            ->byCityInfo($cityInfo);

        $this
            ->applyPropertyTypeFilter($selectedFilters)
            ->applyRatingFilter($selectedFilters)
            ->applyNeighborhoodsFilter($selectedFilters);

        $this->listings = $this->query->get();

        $this->applyOtherFilters($selectedFilters);
    }

    private function applyOtherFilters(array $selectedFilters): void
    {
        if ($this->listings->isEmpty() || empty($selectedFilters)) {
            return;
        }

        $this->listings = $this->listings->filter(function ($listing) use ($selectedFilters) {
            $listingFeatures = $listing->compiledFeatures;

            if (isset($selectedFilters['suitableFor'])) {
                foreach ($selectedFilters['suitableFor'] as $selectedFeature) {
                    if ($selectedFeature === 'boutiqueHostel' && $listing->boutiqueHostel === 1) {
                        return $listing;
                    }
                    if (! $this->listingHasFeature($listingFeatures, $selectedFeature)) {
                        return false;
                    }
                }
            }

            if (isset($selectedFilters['features'])) {
                foreach ($selectedFilters['features'] as $selectedFeature) {
                    if (! $this->listingHasFeature($listingFeatures, $selectedFeature)) {
                        return false;
                    }
                }
            }

            if (isset($selectedFilters['gadgets'])) {
                foreach ($selectedFilters['gadgets'] as $selectedFeature) {
                    if (! $this->listingHasFeature($listingFeatures, $selectedFeature)) {
                        return false;
                    }
                }
            }

            if (isset($selectedFilters['accessibility'])) {
                foreach ($selectedFilters['accessibility'] as $selectedFeature) {
                    if (! $this->listingHasFeature($listingFeatures, $selectedFeature)) {
                        return false;
                    }
                }
            }

            if (isset($selectedFilters['comfort'])) {
                foreach ($selectedFilters['comfort'] as $selectedFeature) {
                    if (! $this->listingHasFeature($listingFeatures, $selectedFeature)) {
                        return false;
                    }
                }
            }

            if (isset($selectedFilters['activities'])) {
                foreach ($selectedFilters['activities'] as $selectedFeature) {
                    if (! $this->listingHasFeature($listingFeatures, $selectedFeature)) {
                        return false;
                    }
                }
            }

            return true;
        });
    }

    private function applyPropertyTypeFilter(array $listingFilters): self
    {
        if (isset($listingFilters['propertyType']) && is_array($listingFilters['propertyType'])) {
            $this->query->whereIn('propertyType', $listingFilters['propertyType']);
        }

        return $this;
    }

    private function applyRatingFilter(array $listingFilters): self
    {
        if (isset($listingFilters['rating']) && is_array($listingFilters['rating'])) {
            $ratings = array_map(fn ($item) => $item * 10, $listingFilters['rating']);
            $this->query->where('combinedRating', '>=', min($ratings));
        }

        return $this;
    }

    private function applyNeighborhoodsFilter(array $listingFilters): self
    {
        if (isset($listingFilters['neighborhoods']) && is_array($listingFilters['neighborhoods'])) {
            $neighborhood = str_replace('_', ' ', reset($listingFilters['neighborhoods']));
            $this->query->where('cityAlt', $neighborhood);
        }

        return $this;
    }

    private function listingHasFeature($listingFeatures, $featureName): bool
    {
        if ($featureName === 'underage') {
            return isset($listingFeatures['minAgeWithout']) && (
                $listingFeatures['minAgeWithout'] === 'allAges' || ((int) $listingFeatures['minAgeWithout'] && (int) $listingFeatures['minAgeWithout'] < 18)
            );
        }

        if ($featureName === 'seniors') {
            return isset($listingFeatures['maxAge']) && $listingFeatures['maxAge'] === 'allAges';
        }

        if (! in_array($featureName, $this->allFilters, true)) {
            throw new \RuntimeException("Unknown feature '$featureName'.");
        }

        return
            (! empty($listingFeatures[$featureName]) && $listingFeatures[$featureName] !== 'no') ||
            (isset($listingFeatures['extras']) && in_array($featureName, $listingFeatures['extras'], true)) ||
            (isset($listingFeatures['goodFor']) && in_array($featureName, $listingFeatures['goodFor'], true));
    }
}
