<?php

namespace App\Schemas;

use App\Models\Listing\Listing;
use App\Models\Rating;
use Spatie\SchemaOrg\Review;
use Spatie\SchemaOrg\Schema;

class HostelReviewSchema
{
    private Listing $listing;

    private Rating $rating;

    public function __construct(Listing $listing, Rating $rating)
    {
        $this->listing = $listing;
        $this->rating = $rating;
    }

    public function getSchema(): Review
    {
        $listing = $this->listing;
        $listingPicsList = $listing->getBestPics();
        $rating = $this->rating;

        $hostelImage = $this->getHostelImage($listingPicsList);

        return Schema::review()
            ->itemReviewed(
                Schema::hostel()
                ->image($hostelImage)
                ->name($listing->name)
                ->priceRange('€€')
                ->address(
                    Schema::postalAddress()
                    ->addressCountry($listing->country)
                    ->addressLocality($listing->city)
                    ->addressRegion($listing->region)
                    ->postalCode($listing->zipcode)
                    ->streetAddress($listing->address)
                )
                ->telephone($listing->tel)
            )
            ->if(
                $rating->rating,
                fn (Review $schema) => $schema->reviewRating(
                    Schema::rating()
                    ->ratingValue($rating->rating)
                    ->bestRating(5)
                    ->worstRating(1)
                )
            )
            ->name($rating->summary)
            ->author(
                Schema::person()
                ->name(clearTextForSchema($rating->name))
            )
            ->reviewBody(clearTextForSchema($rating->comment))
            ->publisher(
                Schema::organization()
                ->name('Hostelz')
            );
    }

    private function getHostelImage($listingPicsList)
    {
        if ($listingPicsList->first()) {
            return url($listingPicsList->first()->url(['big', 'originals']));
        }

        return routeURL('images', 'logo-hostelz.png', 'absolute');
    }
}
