<?php

namespace App\Schemas;

use App\Models\Listing\Listing;
use App\Models\Review;
use DateTime;
use Illuminate\Support\Facades\Log;
use Spatie\SchemaOrg\Hostel;
use Spatie\SchemaOrg\PostalAddress;
use Spatie\SchemaOrg\Schema;

class HostelSchema
{
    private Listing $listing;

    private ?Review $review;

    private array $features;

    public function __construct(Listing $listing, ?Review $review, array $features)
    {
        $this->listing = $listing;
        $this->review = $review;
        $this->features = $features;
    }

    public function getSchema(): Hostel
    {
        $listing = $this->listing;
        $review = $this->review;

        $audience = $this->getAudience($this->features);
        $checkInTime = $this->getCheckInTime($this->features);
        $amenityFeatures = $this->getAmenityFeature($this->features);
        $priceRange = $this->getPriceRange();

        $combinedRatingCount = $listing->combinedRatingCount;
        $isCombinedRatingValid = $combinedRatingCount && $listing->combinedRating;

        return Schema::hostel()
            ->name(clearTextForSchema($listing->name))
            ->url(clearTextForSchema($listing->getURL('absolute')))
            ->image($listing->thumbnailURL())
            ->if(
                ! empty($review->editedReview),
                fn (Hostel $schema) => $schema->description(clearTextForSchema($review->editedReview))
            )
            ->if(
                ! empty($listing->tel),
                fn (Hostel $schema) => $schema->telephone($listing->tel)
            )
            ->hasMap('https://maps.google.com/?q=' . $listing->latitude . ',' . $listing->longitude)
            ->if(
                isset($listing->compiledFeatures['petsAllowed']),
                fn (Hostel $schema) => $schema->petsAllowed($listing->compiledFeatures['petsAllowed'] === 'yes')
            )
            ->address(
                Schema::postalAddress()
                    ->addressCountry($listing->country)
                    ->if(! empty($listing->region), fn (PostalAddress $schema) => $schema->addressRegion($listing->region))
                    ->addressLocality($listing->city)
                    ->streetAddress($listing->address)
                    ->postalCode($listing->zipcode)
            )
            ->if(
                $review,
                fn (Hostel $schema) => $schema->review(
                    Schema::review()
                        ->reviewRating(
                            Schema::rating()
                                ->ratingValue($review->rating)
                        )
                        ->author(
                            Schema::person()
                                ->name($this->getAuthor())
                        )
                )
            )
            ->if(
                $isCombinedRatingValid,
                fn (Hostel $schema) => $schema->aggregateRating(
                    Schema::aggregateRating()
                        ->ratingValue($listing->combinedRating / 10)
                        ->ratingCount($combinedRatingCount)
                        ->bestRating(10)
                )
            )
            ->if(
                $priceRange,
                fn (Hostel $schema) => $schema->priceRange($priceRange)
            )
            ->geo(
                Schema::geoCoordinates()
                    ->latitude($listing->latitude)
                    ->longitude($listing->longitude)
            )
            ->if(
                $amenityFeatures,
                fn (Hostel $schema) => $schema->amenityFeature($amenityFeatures)
            )
            ->if(
                $audience,
                fn (Hostel $schema) => $schema->audience(
                    Schema::audience()
                        ->audienceType($audience)
                )
            )
            ->if(
                $checkInTime,
                fn (Hostel $schema) => $schema->checkoutTime($checkInTime)
            );
    }

    private function getAudience(array $features): ?string
    {
        if (isset($features['restrictions']['Ages/Restrictions'])) {
            $audience = collect($features['restrictions']['Ages/Restrictions'])->where('label', 'Genders')->first();

            return $audience['value'] ?? $audience;
        }

        return null;
    }

    private function getCheckInTime(array $features): ?DateTime
    {
        if (! isset($features['details']['Details'])) {
            return null;
        }

        $time = collect($features['details']['Details'])->where('label', 'Checkout')->first();

        if (! $time) {
            return null;
        }

        try {
            $time = new DateTime(substr($time['value'], 0, 5));
        } catch (\Exception $exception) {
            Log::channel('dateTime')
                ->info('Invalid time value', [
                    'exception' => $exception->getMessage(), 'timeValue' => $time['value'],
                ]);

            $time = null;
        }

        return $time;
    }

    private function getAmenityFeature(array $features): mixed
    {
        if (! isset($features['amenities'])) {
            return null;
        }

        unset($features['amenities']['Not Offered'], $features['amenities']['Extra Services']);

        return collect($features['amenities'])->flatten(1)->pluck('label');
    }

    private function getPriceRange(): ?string
    {
        if ($priceRange = $this->listing->getPriceRange()) {
            return '$' . $priceRange['min'] . ' - $' . $priceRange['max'];
        }

        if (! empty($this->listing->privatePrice)) {
            return $this->listing->privatePrice;
        }

        return null;
    }

    private function getAuthor(): string
    {
        $review = $this->review;

        if (! $review->user) {
            return config('custom.pressSupportEmail');
        }

        return $review->user->name ?: $review->user->nickname ?: $review->user->username ?: config('custom.pressSupportEmail');
    }
}
