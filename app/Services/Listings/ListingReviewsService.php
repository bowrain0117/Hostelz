<?php

namespace App\Services\Listings;

use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Rating;
use App\Schemas\HostelReviewSchema;
use Illuminate\Support\Collection;

class ListingReviewsService
{
    public const NUMBER_OF_REVIEWS_ON_PAGE = 10;

    public const DEFAULT_ORDER_BY = 'commentDate';

    private Collection $reviews;

    private int $numberOfReviewsToSkip = 0;

    public function getReviews(Listing $listing, array $request = []): array
    {
        $importedReviews = $listing->getImportedReviewsAsRatings(20, Languages::currentCode());

        /* Our Ratings */
        $ratings = Rating::getRatingsForListing($listing, Languages::currentCode(), true, false, true);

        $this->reviews = ! empty($importedReviews) ? $importedReviews->merge($ratings) : $ratings;

        $sortBy = self::DEFAULT_ORDER_BY;

        if ($request !== [] && $this->reviews->isNotEmpty()) {
            $sortBy = $request['sortBy'];
            $this->getSortedReviews($sortBy)
                ->getReviewsForSearch($request['search'])
                ->getReviewsByPage($request['page']);
        } else {
            $this->getSortedReviews($sortBy);
        }

        $pagesNumber = $this->getPagesNumber();

        if ($this->reviews->isNotEmpty()) {
            $this->reviews->map(function ($review) use ($listing) {
                if ($review->user && $review->user->profilePhoto && $review->name !== 'Anonymous') {
                    $review->profilePhotoUrl = $review->user->profilePhoto->url(['thumbnails']);
                }

                if ($review->livePics && ! $review->livePics->isEmpty()) {
                    $review->livePics->map(function ($pic) {
                        $pic->thumbnailUrl = $pic->url(['thumbnail']);
                        $pic->fullsizePicUrl = $pic->url(['big', 'originals']);
                    });
                }

                $reviewSchema = (new HostelReviewSchema($listing, $review))->getSchema();
                $review->schema = $reviewSchema->toScript();
            });
        }

        $reviews = $this->reviews->skip($this->numberOfReviewsToSkip)->take(self::NUMBER_OF_REVIEWS_ON_PAGE)->values();

        return compact('reviews', 'importedReviews', 'ratings', 'sortBy', 'pagesNumber');
    }

    private function getSortedReviews(string $sortBy): static
    {
        $this->reviews = match ($sortBy) {
            'ratingDesc' => $this->reviews->sortByDesc('rating'),
            'rating' => $this->reviews->sortBy('rating'),
            default => $this->reviews->sortByDesc('commentDate'),
        };

        return $this;
    }

    private function getReviewsForSearch(string $search = ''): static
    {
        if ($search) {
            $this->reviews = $this->reviews->filter(
                fn ($review) => str_contains(strtolower($review->comment), strtolower($search))
            );
        }

        return $this;
    }

    private function getReviewsByPage(int $page): static
    {
        $this->numberOfReviewsToSkip = ($page - 1) * 10;

        return $this;
    }

    private function getPagesNumber(): int
    {
        return (int) ceil($this->reviews->count() / 10);
    }
}
