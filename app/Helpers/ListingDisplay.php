<?php

namespace App\Helpers;

use App\Models\Imported;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingFeatures;
use App\Models\Pic;
use App\Models\Rating;
use App\Schemas\HostelSchema;
use App\Services\ImportSystems\BookHostels\BookHostelsService;
use App\Services\ImportSystems\ImportSystems;
use App\Services\Listings\ListingReviewsService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Response;

class ListingDisplay
{
    public const LISTING_MAP_MARKER_HEIGHT = 43; // this has to be the heigh/width of the actual PNG image file

    public const LISTING_MAP_MARKER_WIDTH = 32; // this has to be the heigh/width of the actual PNG image file

    public const PIC_MARGIN = 4; // If this is too big it will show white space between pics for some windows sizes.

    public const TOTAL_WIDTH = 670; // If this is too small it will crop too much off of the top and bottom of the pics.

    public $listing;

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    public function getListingViewData($liveOrWhyNot): array
    {
        $activeImporteds = $this->listing->getActiveImporteds();

        $features = ListingFeatures::getDisplayValues($this->listing->compiledFeatures) ?: [];

        if ($this->listing->boutiqueHostel && isset($features['goodFor'])) {
            $features['goodFor'][__('ListingFeatures.categories.goodFor')][] = ['displayType' => 'yes', 'label' => __('ListingFeatures.forms.fieldLabel.boutiqueHostel')];
        }

        $viewData = [
            'listing' => $this->listing,
            'features' => $features,
            'cityInfo' => $this->listing->liveCityInfo, // just for convenience
            'needToFetchContent' => false, // may get changed to true below
            'contactActiveImports' => $this->contactActiveImports($this->listing, $activeImporteds),
            'siteBarActiveImports' => self::getSiteBarActiveImports($this->listing, $activeImporteds, 'single_sidebar'),
        ];

        // * Description / Location *

        foreach (['description', 'location'] as $textType) {
            $text = $this->listing->getText($textType);
            if ($text) {
                $viewData[$textType] = $text->data;
            }
        }

        // * ogThumbnail *

        $listingPics = $this->listing->getBestPics();
        if ($listingPics) {
            $viewData['ogThumbnail'] = $listingPics->first()?->url(['webp_big', 'big', 'originals'], 'absolute');
        }

        /* Review */

        $viewData['review'] = $this->listing->getLiveReview();

        if ($viewData['review']) {
            $reviewPics = $viewData['review']->livePicsAtLeastMinimumSize;
            if ($reviewPics->isEmpty()) {
                $viewData['reviewPics'] = null;
            } else {
                $viewData['reviewPics'] = Pic::createLayout(
                    $reviewPics,
                    self::TOTAL_WIDTH,
                    self::PIC_MARGIN,
                    3 /* Review pics aren't as pretty, keep them smaller */,
                    false,
                    3
                );
            }
        }

        /* Imported Rating Scores */

        $viewData['importedRatingScores'] = $this->listing->getImportedRatingScoresForDisplay();
        $viewData['importedRatingScoreCount'] = ($viewData['importedRatingScores']['average']['count'] ?? 0);

        $ourRatingsCountUsedInCombinedScore = Rating::getRatingsForListing($this->listing)->count(); // includes all languages
        $viewData['totalRatingsCount'] = $viewData['importedRatingScoreCount'] + $ourRatingsCountUsedInCombinedScore;

        $viewData['schema'] = (new HostelSchema($this->listing, $viewData['review'], $viewData['features']))->getSchema();
        $viewData['description'] = $this->description();
        $viewData['listingViewOptions'] = $this->listingViewOptions($liveOrWhyNot);
        // todo: do we need this?
        $viewData['hreflangLinks'] = $this->hreflangLinks();

        return [
            ...$viewData,
            ...(new ListingReviewsService)->getReviews($this->listing),
        ];
    }

    public function listingFetchContent()
    {
        $description = $this->listing->getText('description');
        $location = $this->listing->getText('location');

        $viewData = [
            'listing' => $this->listing,
            'importedReviews' => $this->listing->getImportedReviewsAsRatings(20, Languages::currentCode()),
            // Note: description/location are only included in the javascript if they weren't original enough to include in the HTML page.
            'description' => $description && ! $this->listing->isTextOriginalEnough($description) ? $description->data : '',
            'location' => $location && ! $this->listing->isTextOriginalEnough($location) ? $location->data : '',
        ];

        return Response::make(view('listingFetchContent', $viewData))->header('Content-Type', 'application/javascript');
    }

    public function listingDynamicData()
    {
        if (auth()->check() && auth()->user()->hasPermission('staffEditHostels')) {
            $editListingLink = routeURL('staff-listings', $this->listing->id);
        } elseif (auth()->check() && auth()->user()->userCanEditListing($this->listing->id)) {
            $editListingLink = routeURL('mgmt:menu');
        } else {
            $editListingLink = '';
        }

        $result = [
            'lastUpdateStamp' => $this->listing->lastUpdatedTimeStamp(),
            'editListingLink' => $editListingLink,
        ];

        return preventBrowserCaching(setCorsHeadersToAllowOurSubdomains(Response::json($result), true));
    }

    public static function getSiteBarActiveImports($listing, $activeImporteds, $cmpLabel = 'city')
    {
        return $activeImporteds->map(function (Imported $item) use ($listing, $cmpLabel) {
            $importSystem = $item->getImportSystem();

            $city = $importSystem->systemName === 'BookHostels' ? $item->city : $listing->city;

            return [
                'systemName' => $importSystem->systemName,
                'systemShortName' => $importSystem->shortName(),
                'href' => $item->staticLink(getCMPLabel($cmpLabel, $city, $listing->name)),
            ];
        });
    }

    private function contactActiveImports($listing, $activeImporteds): Collection
    {
        $activeSystems = collect(ImportSystems::allActive())->sort();

        if ($activeImporteds->isEmpty()) {
            return $this->getHostelworldSystemData($activeSystems, $listing);
        }

        return $activeSystems->map(function (ImportSystems $importSystem) use ($listing, $activeImporteds) {
            /** @var Imported $import */
            $import = $activeImporteds
                ->filter(fn ($value) => $value->system === $importSystem->systemName)
                ->first();

            $href = $import
                ? $importSystem->getSystemService()::getStaticLinkRedirect(
                    $import->urlLink,
                    getCMPLabel('single_contact_listed', $listing->city, $listing->name)
                )
                : $importSystem->getSystemService()::getDefaultLinkRedirect(
                    getCMPLabel('single_contact_not_listed', $listing->city, $listing->name)
                );

            return [
                'isListed' => $import !== null,
                'systemName' => $importSystem->systemName,
                'systemShortName' => $importSystem->shortName(),
                'href' => $href,
            ];
        });
    }

    private function getHostelworldSystemData(Collection $activeSystems, Listing $listing): Collection
    {
        $href = BookHostelsService::getCityLinkRedirect(
            $listing->cityInfo()->first(),
            getCMPLabel('single_sidebar_is_closed', $listing->city, $listing->name)
        );

        return collect([
            'BookHostels' => [
                'isListed' => false,
                'systemName' => $activeSystems['BookHostels']->systemName,
                'systemShortName' => $activeSystems['BookHostels']->shortName(),
                'href' => $href,
            ],
        ]);
    }

    private function description(): string
    {
        return data_get(
            (new ListingEditHandler($this->listing->id))->getText('description'),
            Languages::currentCode(),
            ''
        );
    }

    private function listingViewOptions($liveOrWhyNot): array
    {
        $listingViewOptions = ['getDynamicDataForListing', 'showPreviousAndNext'];
        if ($liveOrWhyNot !== Listing::LIVE) {
            $listingViewOptions[] = 'isClosed';
        }

        return $listingViewOptions;
    }

    private function hreflangLinks(): array
    {
        $hreflangLinks = [];
        // * Alternate Language URLs *
        // (We have to specify these explicitly because listings in some languages may have a "+" and others not.)
        foreach (Languages::allLiveSiteCodes() as $languageCode) {
            $hreflangLinks[$languageCode] = $this->listing->getURL('auto', $languageCode);
        }

        return $hreflangLinks;
    }
}
