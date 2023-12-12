<?php

namespace App\Services\Listings\Filters;

use App\Models\CityInfo;
use App\Models\Listing\Listing;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;

class ListingsFiltersService
{
    private Collection $listings;

    private ?array $cityInfoPoi;

    public array $listingFilterFeatures;

    public array $listingFilterSuitableFor;

    public array $listingFilterGadgets;

    public array $listingFilterAccessibility;

    public array $listingFilterComfort;

    public array $listingFilterActivities;

    public array $listingFilters = [];

    private CityInfo $cityInfo;

    public function __construct()
    {
        $this->listingFilterFeatures = config('listingFilters.features');
        $this->listingFilterSuitableFor = config('listingFilters.suitableFor');
        $this->listingFilterGadgets = config('listingFilters.gadgets');
        $this->listingFilterAccessibility = config('listingFilters.accessibility');
        $this->listingFilterComfort = config('listingFilters.comfort');
        $this->listingFilterActivities = config('listingFilters.activities');
    }

    public function setListings(CityInfo $cityInfo): void
    {
        $this->cityInfo = $cityInfo;

        $this->listings = Cache::has($cityInfo->getFiltersCacheKey())
            ? Cache::get($cityInfo->getFiltersCacheKey())
            : Listing::areLive()->byCityInfo($cityInfo)->get();
        $this->cityInfoPoi = $cityInfo->poi;
    }

    public function getListingsFilters(): array
    {
        $this->getFilterSuitableFor()
            ->getFilterFeatures()
            ->getFilterGadgets()
            ->getFilterAccessibility()
            ->getFilterComfort()
            ->getFilterActivities()
            ->getFilterDistricts()
            ->getFilterNeighborhoods()
            ->getFilterPoi()
            ->getFilterRatings()
            ->getFilterPropertyType()
            ->getFilterTypeOfDormRoom()
            ->getFilterTypeOfPrivateRoom();

        $this->moveCityCentreToDistrict();

        return $this->listingFilters;
    }

    private function getFilterSuitableFor(): self
    {
        $options = [];
        foreach ($this->listingFilterSuitableFor as $feature) {
            $options[$feature] = $this->getLabelForFeature($feature);
        }
        asort($options);

        $this->addToListingFilters('suitableFor', $options);

        return $this;
    }

    private function getFilterFeatures(): self
    {
        $options = [];
        foreach ($this->listingFilterFeatures as $feature) {
            $options[$feature] = $this->getLabelForFeature($feature);
        }
        asort($options);

        $this->addToListingFilters('features', $options);

        return $this;
    }

    private function getFilterGadgets(): self
    {
        foreach ($this->listingFilterGadgets as $gadget) {
            $options[$gadget] = $this->getLabelForFeature($gadget);
        }
        asort($options);

        $this->addToListingFilters('gadgets', $options);

        return $this;
    }

    private function getFilterAccessibility(): self
    {
        foreach ($this->listingFilterAccessibility as $feature) {
            $options[$feature] = $this->getLabelForFeature($feature);
        }
        asort($options);

        $this->addToListingFilters('accessibility', $options);

        return $this;
    }

    private function getFilterComfort(): self
    {
        foreach ($this->listingFilterComfort as $feature) {
            $options[$feature] = $this->getLabelForFeature($feature);
        }
        asort($options);

        $this->addToListingFilters('comfort', $options);

        return $this;
    }

    private function getFilterActivities(): self
    {
        foreach ($this->listingFilterActivities as $feature) {
            $options[$feature] = $this->getLabelForFeature($feature);
        }
        asort($options);

        $this->addToListingFilters('activities', $options);

        return $this;
    }

    private function getFilterNeighborhoods(): self
    {
        $neighborhoods = $this->listings->pluck('cityAlt')->filter()->unique()->sort('strnatcmp')->values()->all();

        if (count($neighborhoods) > 1) {
            $options = array_combine($neighborhoods, $neighborhoods);
            $this->addToListingFilters('neighborhoods', $options);
        }

        return $this;
    }

    private function getFilterPoi(): self
    {
        $options = [];

        // points of interest
        $pointsOfInterest = with(collect($this->cityInfoPoi))->sort(function ($a, $b) {
            return strnatcmp($a['name'], $b['name']);
        });
        foreach ($pointsOfInterest->groupBy('featureCode') as $poiType => $pois) {
            if ($poiType === 'Distance') {
                foreach ($pois as $poi) {
                    array_unshift($options, [
                        'name' => $poi['name'],
                        'value' => $pointsOfInterest->search($poi),
                        'type' => 'option',
                        'sortBy' => 'poi',
                    ]);
                }
                array_unshift($options, ['type' => 'header', 'name' => $poiType]);
                continue;
            }

            $options[] = ['type' => 'header', 'name' => $poiType];
            foreach ($pois as $poi) {
                $options[] = [
                    'name' => $poi['name'],
                    'value' => $pointsOfInterest->search($poi),
                    'type' => 'option',
                    'sortBy' => 'poi',
                ];
            }
        }
        $this->addToListingFilters('poi', $options);

        return $this;
    }

    private function getFilterDistricts(): self
    {
        $options = [];

        $districts = $this->cityInfo->districts->sortBy('name');
        if ($districts->isNotEmpty()) {
            foreach ($districts as $district) {
                $options[] = [
                    'sortBy' => 'district',
                    'name' => $district->name,
                    'value' => $district->id,
                    'type' => 'option',
                ];
            }
        }

        $this->addToListingFilters('district', $options);

        return $this;
    }

    private function getFilterRatings(): self
    {
        $ratings = [9, 8, 6];
        $options = [];
        foreach ($ratings as $rating) {
            $options[$rating] = langGet("listingDisplay.combinedRatingScores.Score$rating", (string) $rating);
        }
        $this->addToListingFilters('rating', $options);

        return $this;
    }

    private function getFilterPropertyType(): self
    {
        $options = [];
        foreach (Listing::propertyTypes() as $propertyType) {
            $options[$propertyType] = __("global.propertyTypePlural.$propertyType");
        }
        $this->addToListingFilters('propertyType', $options);

        return $this;
    }

    private function getFilterTypeOfDormRoom(): self
    {
        $this->addToListingFilters('typeOfDormRoom', __('city.filters.options.typeOfDormRoom'));

        return $this;
    }

    private function getFilterTypeOfPrivateRoom(): self
    {
        $ensuiteOptions = [
            'divider' => ['type' => 'divider'],
            'ensuite' => __('bookingProcess.ensuite'),
        ];

        $options = array_merge(__('city.filters.options.typeOfPrivateRoom'), $ensuiteOptions);

        $this->addToListingFilters('typeOfPrivateRoom', $options);

        return $this;
    }

    private function addToListingFilters(string $name, array $options): void
    {
        $filtersArr = [
            'suitableFor' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.suitableFor'),
            ],
            'features' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.features'),
            ],
            'gadgets' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.gadgets'),
            ],
            'accessibility' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.accessibility'),
            ],
            'comfort' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.comfort'),
            ],
            'activities' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.activities'),
            ],
            'district' => [
                'selectType' => 'single',
                'label' => __('Landmarks (by distance)'),
            ],
            'neighborhoods' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.neighborhood'),
            ],
            'poi' => [
                'selectType' => 'single',
                'label' => __('city.filters.poi'),
            ],
            'rating' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.rating'),
            ],
            'propertyType' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.propertyType'),
            ],
            'typeOfDormRoom' => [
                'selectType' => 'single',
                'label' => __('city.filters.typeOfDormRoom'),
            ],
            'typeOfPrivateRoom' => [
                'selectType' => 'multiple',
                'label' => __('city.filters.typeOfPrivateRoom'),
            ],
        ];

        $filtersArr[$name]['options'] = $options;

        $this->listingFilters[$name] = $filtersArr[$name];
    }

    private function getLabelForFeature(string $featureName)
    {
        if (($text = langGet("city.overrideFeatureText.$featureName", null)) !== null) {
            return $text;
        }
        if (($text = langGet("ListingFeatures.forms.fieldLabel.$featureName", null)) !== null) {
            return $text;
        }
        if (($text = langGet("ListingFeatures.forms.options.extras.$featureName", null)) !== null) {
            return $text;
        }
        if (($text = langGet("ListingFeatures.forms.options.goodFor.$featureName", null)) !== null) {
            return $text;
        }

        throw new Exception("No text found for '$featureName'.");
    }

    private function moveCityCentreToDistrict()
    {
        if (empty($this->listingFilters['poi']['options'])) {
            return;
        }

        $issetCityCentre = collect($this->listingFilters['district']['options'])->contains(function (array $option) {
            return $option['name'] === 'City Centre';
        });

        if ($issetCityCentre) {
            return;
        }

        $cityCentreKey = collect($this->listingFilters['poi']['options'])->search(function ($option) {
            return $option['name'] === 'City Center';
        });

        $cityCentreOptions = Arr::pull($this->listingFilters['poi']['options'], $cityCentreKey);

        $this->listingFilters['district']['options'] = Arr::prepend($this->listingFilters['district']['options'], $cityCentreOptions);
    }
}
