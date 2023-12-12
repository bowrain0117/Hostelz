<?php

namespace App\Services\Listings;

use App\Models\CityInfo;
use App\Models\District;
use Illuminate\Support\Collection;
use Lib\GeoBounds;
use Lib\GeoPoint;

class ListingsPoiService extends BaseListingsService
{
    private ?GeoPoint $poiPoint;

    private ?array $poiInfo;

    public function setPoiPoint(CityInfo $cityInfo, $listingFilters, $resultsOptions): void
    {
        $this->poiPoint = null;
        $this->poiInfo = null;

        // orderBy - add district to poi
        if (data_get($resultsOptions, 'orderBy.type') === 'district') {
            $district = District::find($resultsOptions['orderBy']['value']);
            if ($district) {
                $this->poiPoint = $district->geoPoint;
                $this->poiInfo = [
                    'name' => $district->name,
                    'longitude' => $district->longitude,
                    'latitude' => $district->latitude,
                ];

                return;
            }
        }

        // filter -  add city centre/district to poi
        if (! empty($listingFilters['district']) && ($poiOptions = json_decode(reset($listingFilters['district']), true))) {
            if ($poiOptions['sortBy'] === 'poi') {
                $this->poiInfo = $cityInfo->poi[(int) $poiOptions['value']];
                $this->poiPoint = new GeoPoint($this->poiInfo);

                return;
            }

            $district = District::find($poiOptions['value']);
            if ($district) {
                $this->poiPoint = $district->geoPoint;
                $this->poiInfo = [
                    'name' => $district->name,
                    'longitude' => $district->longitude,
                    'latitude' => $district->latitude,
                ];

                return;
            }
        }

        // filter - add city to poi
        if (! empty($listingFilters['poi']) && ($poiOptions = json_decode(reset($listingFilters['poi']), true))) {
            $this->poiInfo = $cityInfo->poi[(int) $poiOptions['value']];
            $this->poiPoint = new GeoPoint($this->poiInfo);
        }
    }

    public function sortListingsWithPoi($listings, $isDefaultResults, $resultsOptions, $listingsCount, $bestAvailabilityByListingID)
    {
        $poiPoint = $this->poiPoint;

        // * Sorting *
        return $listings->sort(function ($a, $b) use (
            $isDefaultResults,
            $resultsOptions,
            $listingsCount,
            $poiPoint,
            $bestAvailabilityByListingID
        ) {
            if ($a->propertyType !== $b->propertyType) {
                $propertyTypePriorities = array_flip(['Hostel', 'Hotel', 'Guesthouse', 'Apartment', 'Campsite', 'Other']);

                return $propertyTypePriorities[$a->propertyType] - $propertyTypePriorities[$b->propertyType];
            }

            if ($poiPoint && $a->geoPoint() && $b->geoPoint()) {
                $distanceA = $poiPoint->distanceToPoint($a->geoPoint());
                $distanceB = $poiPoint->distanceToPoint($b->geoPoint());
                if ($distanceA !== null && $distanceB !== null && $distanceA !== $distanceB) {
                    return $distanceA > $distanceB;
                }
            }

            switch ($resultsOptions['orderBy']['type']) {
                case 'price':
                    if ($bestAvailabilityByListingID) {
                        $priceDifference = $bestAvailabilityByListingID[$a->id]->averagePricePerBlockPerNight() -
                            $bestAvailabilityByListingID[$b->id]->averagePricePerBlockPerNight();
                        if ($priceDifference) {
                            // we do the "* 100" because PHP converts the result of the sort function to an integer, so that's needed to compare the cents part of the price.
                            return (int) ($priceDifference * 100);
                        }
                    }
                    // (note that this continues on to sort by default below if there weren't prices or a price difference)

                    // no break
                case 'default':
                    // todo: also consider unavailableCount
                    $aScore = $a->onlineReservations * 100 + $a->preferredBooking * 10 + $a->combinedRating;
                    $bScore = $b->onlineReservations * 100 + $b->preferredBooking * 10 + $b->combinedRating;
                    if ($isDefaultResults) {
                        // Featured listings
                        $aScore += $a->featuredListingPriority * 1000;
                        $bScore += $b->featuredListingPriority * 1000;
                    }

                    return $bScore - $aScore;

                case 'ratings':
                    // We show onlineReservations ones first if there are a lot of listings
                    if ($listingsCount > 10 && $a->onlineReservations !== $b->onlineReservations) {
                        return $a->onlineReservations - $b->onlineReservations;
                    }
                    if ($a->combinedRating === $b->combinedRating) {
                        break;
                    } // ignore if the same
                    if (! $a->combinedRating) {
                        return 1;
                    }
                    if (! $b->combinedRating) {
                        return -1;
                    }

                    return $b->combinedRating - $a->combinedRating;

                case 'name':
                    return strnatcmp($a->name, $b->name); // this isn't really intended for UTF-8.  Could use a better comparison?

                case 'district':
                    $district = District::find($resultsOptions['orderBy']['value']);

                    if ($district->geoPoint->isValid() && $a->geoPoint() && $b->geoPoint()) {
                        $distanceA = $district->geoPoint->distanceToPoint($a->geoPoint());
                        $distanceB = $district->geoPoint->distanceToPoint($b->geoPoint());
                        if ($distanceA !== null && $distanceB !== null && $distanceA !== $distanceB) {
                            return $distanceA > $distanceB;
                        }
                    }
            }

            return 0;
        });
    }

    public function getPoiAndMapPoints(Collection $listings, callable $callback): array
    {
        $poiPoint = $this->poiPoint;
        $poiInfo = $this->poiInfo;

        if ($poiPoint) {
            $distances = [];

            foreach ($listings as $listing) {
                if ($listingPoint = $listing->geoPoint()) {
                    $distances[$listing->id] = $poiPoint->distanceToPoint($listingPoint);
                }
            }

            $this->setViewData([
                'selectedPoiInfo' => $poiInfo,
                'distancesToPoiInKm' => $distances,
            ]);
        }

        return $this->getMapPoi($listings, $callback);
    }

    public function getMapPoi(Collection $listings, callable $callback): array
    {
        $listingsWithGeocoding = $listings->filter(function ($listing) {
            return $listing->hasLatitudeAndLongitude();
        });

        $this->setViewData([
            'mapPoints' => $this->getMapPoints($listings, $callback),
        ]);

        if ($this->viewData['mapPoints']) {
            $this->setViewData([
                'mapBounds' => $this->getMapBounds($listingsWithGeocoding),
            ]);
        }

        return $this->getViewData();
    }

    private function getMapPoints(Collection $listings, callable $callback): ?Collection
    {
        return $listings->map($callback);
    }

    private function getMapBounds($listingsWithGeocoding): GeoBounds
    {
        return GeoBounds::makeFromPoints($listingsWithGeocoding)
            ->expandToMinimumRadius(2, 'miles');
    }
}
