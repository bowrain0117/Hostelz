<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\ListingAndCitySearch;
use App\Models\AttachedText;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Review;
use Lib\FileListHandler;
use Lib\FileUploadHandler;
use Lib\FormHandler;
use Redirect;
use Request;
use Session;

class ReviewerController extends Controller
{
    private $userEditableReviewStatuses = ['newHostel', 'newReview', 'returnedReview'];

    public const MAX_DRAFT_DESCRIPTIONS = 5; // (note that this isn't actually enforced other than not showing the link to let them find more)

    public function reviewerInstructions()
    {
        if (App::environment() == 'production') {
            return view('user/reviewer/paidReviewsDisabled'); // this shows a (temporary?) message that we're no longer accepting reviews
        }

        return view('user/reviewer/instructions');
    }

    public function reviews($pathParameters = null)
    {
        if (App::environment() == 'production') {
            return view('user/reviewer/paidReviewsDisabled'); // this shows a (temporary?) message that we're no longer accepting reviews
        }

        if (Languages::currentCode() != 'en') {
            return view('error', ['errorMessage' => 'Sorry, we are only currently accepting paid reviews in English.']);
        }
        if (! auth()->user()->hasCompletedProfile()) {
            return view('user/requireCompleteProfile');
        }

        $message = '';

        if ($listingID = Request::input('addListing')) {
            $listing = Listing::find($listingID);
            if (! $listing || ! Review::listingIsAvailabileForReviewing($listing)) {
                App::abort(404);
            }

            $review = new Review(['reviewerID' => auth()->id(), 'hostelID' => $listingID, 'status' => 'newHostel',
                'language' => Languages::currentCode(), ]);
            $review->resetExpirationDate()->save();
            $message = '"' . $listing->name . '" added to your list.';
            if ($listing->isLive() && $listing->hasImportSystemWithOnlineBooking()) {
                $message .= ' <strong>Note:</strong> Your booking for this hostel must be made using Hostelz.com booking search form from <a class="underline" href="' . $listing->getURL() . '">their Hostelz.com listing</a>.';
            }
        } elseif ($reviewID = Request::input('renew')) {
            $review = Review::where('id', $reviewID)->where('reviewerID', auth()->id())->first();
            if (! $review) {
                App::abort(404);
            }
            if (! Review::listingIsAvailabileForReviewing($review->listing, $review->id)) {
                $message = 'Sorry, "' . $review->listing->name . '" is no longer available for reviewing.';
            } else {
                $review->resetExpirationDate()->save();
                $message = 'Your review hold for "' . $review->listing->name . '" has been renewed.';
            }
        }

        $formHandler = new FormHandler('Review', Review::fieldInfo('reviewer'), $pathParameters, 'App\Models');
        $formHandler->languageKeyBase = 'reviewer';
        $formHandler->allowedModes = ['list', 'display', 'updateForm', 'update', 'delete'];
        $formHandler->whereData = ['reviewerID' => auth()->id()];
        $formHandler->listPaginateItems = 50;
        $formHandler->logChangesAsCategory = 'user';
        $formHandler->listSort = ['newStaffComment' => 'desc', 'expirationDate' => 'desc', 'status' => 'asc'];

        if (Request::has('updateAndSetStatus')) {
            $formHandler->mode = 'update';
        }

        $formHandler->go();

        if ($formHandler->mode == 'updateForm') {
            $review = $formHandler->model;

            if ($review->newStaffComment) {
                $review->newStaffComment = false;
                $review->save();
            }

            if (! in_array($review->status, $this->userEditableReviewStatuses)) {
                $formHandler->mode = 'display';
            } else {
                // Set some review values (these only actually get saved if the review is updated)
                if ($review->review == '') {
                    // Start with the template
                    $template = "[your intro here]\n\n";
                    foreach (Review::$templateFormat as $title => $text) {
                        if ($title != 'intro') {
                            $template .= $title . "\n\n[your text here]\n\n";
                        }
                    }
                    $review->review = $template;
                }
            }
        } elseif ($formHandler->mode == 'update') {
            $review = $formHandler->model;
            switch (Request::input('updateAndSetStatus')) {
                case 'reviewHold':
                    $review->status = 'newHostel';

                    break;

                case 'submitted':
                    $review->status = 'newReview';
                    $review->reviewDate = date('Y-m-d');

                    break;
            }
            $review->resetExpirationDate();
            $review->save();
        }

        return $formHandler->display('user/reviewer/reviews', compact('message'));
    }

    public function reviewPics($reviewID)
    {
        $review = Review::where('id', $reviewID)->where('reviewerID', auth()->id())->whereIn('status', $this->userEditableReviewStatuses)->first();
        if (! $review) {
            App::abort(404);
        }

        // FileList

        $existingPics = $review->pics;

        $fileList = new FileListHandler($existingPics, ['caption'], ['caption'], true);
        $fileList->makeSortableUsingNumberField = 'picNum';
        $fileList->picListSizeTypeNames = ['thumbnail'];
        $response = $fileList->go();
        if ($response !== null) {
            return $response;
        }

        // FileUpload

        $fileUpload = new FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], 15, 12, $existingPics->count());
        $fileUpload->minImageWidth = Review::NEW_PIC_MIN_WIDTH;
        $fileUpload->minImageHeight = Review::NEW_PIC_MIN_HEIGHT;
        $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($review): void {
            $review->addPic($filePath);
        });
        if ($response !== null) {
            return $response;
        }

        return view('user/reviewer/review-pics', compact('review', 'fileList', 'fileUpload'));
    }

    public function findListingsToReview()
    {
        if (App::environment() == 'production') {
            return view('user/reviewer/paidReviewsDisabled'); // this shows a (temporary?) message that we're no longer accepting reviews
        }

        $search = Request::input('search');

        if ($search != '') {
            $queryResultLimit = 100;
            $maxResultsPerType = 20;
            $searchResults = ListingAndCitySearch::performSearch($search, $queryResultLimit, Listing::areLiveOrNew(), CityInfo::areLive());
        } elseif ($cityID = Request::input('cityID')) {
            $cityInfo = CityInfo::findOrFail($cityID);
            $searchResults = ['listings' => Listing::byCityInfo($cityInfo)->get()];
        } elseif ($listingID = Request::input('listingID')) {
            $listingDetails = Listing::findOrFail($listingID);

            return view('user/reviewer/findListings', compact('listingDetails'));
        }

        return view('user/reviewer/findListings', compact('search', 'searchResults'));
    }

    public function submitNewListing($pathParameters = null)
    {
        if (App::environment() == 'production') {
            return view('user/reviewer/paidReviewsDisabled'); // this shows a (temporary?) message that we're no longer accepting reviews
        }

        // fieldInfo
        $fieldInfo = Listing::fieldInfo('submitListing');
        unset($fieldInfo['propertyType']); // because we only allow hostels to be reviewed anyway

        $formHandler = new FormHandler('Listing', $fieldInfo, $pathParameters, 'App\Models\Listing');
        $formHandler->logChangesAsCategory = 'user';
        $formHandler->allowedModes = ['insertForm', 'insert'];
        $formHandler->callbacks['setModelData'] = function ($formHandler, $data, &$dataTypeEventValues) {
            $formHandler->model->propertyType = 'Hostel';
            $formHandler->model->verified = Listing::$statusOptions['new'];
            $formHandler->model->lastEditSessionID = Session::getId();

            return $formHandler->setModelData($data, $dataTypeEventValues, false);
        };
        $formHandler->go();

        if ($formHandler->mode == 'insert') {
            return Redirect::to(routeURL('reviewer:reviews') . '?addListing=' . $formHandler->model->id);
        }

        return $formHandler->display('user/reviewer/submitNewListing');
    }

    // ** Place Descriptions **

    public function placeDescriptionInstructions()
    {
        return view('user/placeDescriptions/instructions');
    }

    public function placeDescriptions($pathParameters = null)
    {
        return ''; // Place descriptions are disabled.

        if (! auth()->user()->hasCompletedProfile()) {
            return view('user/requireCompleteProfile');
        }

        $message = '';

        if ($cityInfoID = Request::input('addCity')) {
            $cityInfo = CityInfo::find($cityInfoID);
            if (! $cityInfo || ! $cityInfo->isAvailableForDescriptionWriting()) {
                App::abort(404);
            }
            $attachedText = new AttachedText(['subjectType' => 'cityInfo', 'subjectID' => $cityInfo->id, 'type' => 'description',
                'userID' => auth()->id(), 'status' => 'draft', 'language' => Languages::currentCode(), 'lastUpdate' => date('Y-m-d'), ]);
            $attachedText->save();
            $message = '"' . $cityInfo->fullDisplayName() . '" added to your list.';
        }

        if ($countryInfoID = Request::input('addRegionOrCountry')) {
            $regionOrCityGroupName = '';
            if ($regionCityInfoID = Request::input('regionCityInfo')) {
                // Region
                $regionCityInfo = CityInfo::find($regionCityInfoID);
                if (! $regionCityInfo || $regionCityInfo->region == '' || ! $regionCityInfo->displaysRegion) {
                    App::abort(404);
                }
                $regionOrCityGroupName = $regionCityInfo->region;
            } elseif ($cityGroupCityInfoID = Request::input('cityGroupCityInfo')) {
                // CityGroup
                $cityGroupCityInfo = CityInfo::find($cityGroupCityInfoID);
                if (! $cityGroupCityInfo || $cityGroupCityInfo->cityGroup == '') {
                    App::abort(404);
                }
                $regionOrCityGroupName = $cityGroupCityInfo->cityGroup;
            }
            $countryInfo = CountryInfo::find($countryInfoID);
            if (! $countryInfo || ! $countryInfo->isAvailableForDescriptionWriting($regionOrCityGroupName)) {
                App::abort(404);
            }
            $attachedText = new AttachedText(['subjectType' => 'countryInfo', 'subjectID' => $countryInfo->id, 'subjectString' => $regionOrCityGroupName, 'type' => 'description',
                'userID' => auth()->id(), 'status' => 'draft', 'language' => Languages::currentCode(), 'lastUpdate' => date('Y-m-d'), ]);
            $attachedText->save();
            $message = '"' . ($regionOrCityGroupName == '' ? $countryInfo->translation()->country : $regionOrCityGroupName) . '" added to your list.';
        }

        $formHandler = new FormHandler('AttachedText', AttachedText::fieldInfo('placeDescriptions'), $pathParameters, 'App\Models');
        $formHandler->query = AttachedText::where('userID', auth()->id())->whereIn('subjectType', ['cityInfo', 'countryInfo']);
        $formHandler->languageKeyBase = 'placeDescription';
        $formHandler->allowedModes = ['list', 'display', 'updateForm', 'update', 'delete'];
        $formHandler->listPaginateItems = 50;
        $formHandler->logChangesAsCategory = 'user';
        $formHandler->listSort = ['lastUpdate' => 'desc', 'status' => 'asc'];

        if (Request::has('updateAndSetStatus')) {
            $formHandler->mode = 'update';
        }

        $formHandler->go();

        if ($formHandler->mode == 'updateForm') {
            $attachedText = $formHandler->model;

            if ($attachedText->newStaffComment) {
                $attachedText->newStaffComment = false;
                $attachedText->save();
            }

            if (! in_array($attachedText->status, ['draft', 'submitted', 'returned'])) {
                $formHandler->mode = 'display';
            }
        } elseif ($formHandler->mode == 'update') {
            $attachedText = $formHandler->model;
            switch (Request::input('updateAndSetStatus')) {
                case 'draft':
                    $attachedText->status = 'draft';

                    break;

                case 'submitted':
                    $attachedText->status = 'submitted';

                    break;
            }
            $attachedText->lastUpdate = date('Y-m-d');
            $attachedText->save();
        }

        return $formHandler->display('user/placeDescriptions/placeDescriptions', compact('fileList', 'message'))->with('MAX_DRAFT_DESCRIPTIONS', self::MAX_DRAFT_DESCRIPTIONS);
    }

    public function findCities()
    {
        $search = Request::input('search');

        if ($search != '') {
            $queryResultLimit = 100;
            $maxResultsPerType = 20;
            $searchResultData = ListingAndCitySearch::performSearch($search, $queryResultLimit, null, CityInfo::areLive(), CountryInfo::areLive());

            // Add all Regions/CityGroups for any countries in the list
            if (isset($searchResultData['countries'])) {
                foreach ($searchResultData['countries'] as $countrySearchResult) {
                    // Regions
                    $data = CityInfo::areLive()->where('country', $countrySearchResult->country)->where('region', '!=', '')
                        ->where('displaysRegion', true)->groupBy('region', 'country')->get()->all();
                    ListingAndCitySearch::mergeSearchData($searchResultData, ['regions' => $data]);
                    // City Groups
                    $data = CityInfo::areLive()->where('country', $countrySearchResult->country)->where('cityGroup', '!=', '')
                        ->groupBy('cityGroup', 'country')->get()->all();
                    ListingAndCitySearch::mergeSearchData($searchResultData, ['cityGroups' => $data]);
                }
            }

            $searchResults = [];
            foreach ($searchResultData as $type => $items) {
                foreach ($items as $item) {
                    switch ($type) {
                        case 'cities':
                            $cityInfo = $item;
                            $searchResults[$type][] = [
                                'name' => $cityInfo->fullDisplayName(),
                                'url' => $cityInfo->getURL(),
                                'isAvailable' => $cityInfo->isAvailableForDescriptionWriting(),
                                'addLink' => routeURL('placeDescriptions') . '?' . http_build_query(['addCity' => $cityInfo->id]),
                            ];

                            break;

                        case 'cityGroups':
                            $cityInfo = $item;
                            $countryInfo = $cityInfo->countryInfo;
                            $searchResults[$type][] = [
                                'name' => $cityInfo->cityGroupFullDisplayName(),
                                'url' => $cityInfo->getCityGroupURL(),
                                'isAvailable' => $countryInfo->isAvailableForDescriptionWriting($cityInfo->cityGroup),
                                'addLink' => routeURL('placeDescriptions') . '?' . http_build_query(['addRegionOrCountry' => $countryInfo->id, 'cityGroupCityInfo' => $cityInfo->id]),
                            ];

                            break;

                        case 'regions':
                            $cityInfo = $item;
                            $countryInfo = $cityInfo->countryInfo;
                            $searchResults[$type][] = [
                                'name' => $cityInfo->regionFullDisplayName(),
                                'url' => $cityInfo->getRegionURL(),
                                'isAvailable' => $countryInfo->isAvailableForDescriptionWriting($cityInfo->region),
                                'addLink' => routeURL('placeDescriptions') . '?' . http_build_query(['addRegionOrCountry' => $countryInfo->id, 'regionCityInfo' => $cityInfo->id]),
                            ];

                            break;

                        case 'countries':
                            $countryInfo = $item;
                            $searchResults[$type][] = [
                                'name' => $countryInfo->translation()->country,
                                'url' => $countryInfo->getURL(),
                                'isAvailable' => $countryInfo->isAvailableForDescriptionWriting(),
                                'addLink' => routeURL('placeDescriptions') . '?' . http_build_query(['addRegionOrCountry' => $countryInfo->id]),
                            ];

                            break;
                    }
                }
            }
        }

        // dd($searchResults);
        return view('user/placeDescriptions/find', compact('search', 'searchResults'));
    }

    /* City Pics */

    public function submitCityPicsFindCity()
    {
        $searchResults = [];

        $search = Request::input('search');
        if ($search != '') {
            $queryResultLimit = 100;
            $maxResultsPerType = 20;
            $searchResultData = ListingAndCitySearch::performSearch($search, $queryResultLimit, null, CityInfo::areLive());

            foreach ($searchResultData as $type => $items) {
                foreach ($items as $item) {
                    switch ($type) {
                        case 'cities':
                            $cityInfo = $item;
                            $searchResults[$type][] = [
                                'name' => $cityInfo->fullDisplayName(),
                                'url' => $cityInfo->getURL(),
                                'addLink' => routeURL('submitCityPics', $cityInfo->id),
                            ];

                            break;
                    }
                }
            }
        }

        return view('user/submitCityPics-find', compact('search', 'searchResults'));
    }

    public function submitCityPics($cityID)
    {
        $cityInfo = CityInfo::where('id', $cityID)->first();
        if (! $cityInfo) {
            App::abort(404);
        }

        // FileList

        $existingPics = $cityInfo->pics->where('status', 'new')->where('source', auth()->id());
        $fileList = new FileListHandler($existingPics, ['caption'], ['caption'], true);
        $fileList->picListSizeTypeNames = [''];
        $response = $fileList->go();
        if ($response !== null) {
            return $response;
        }

        // Also display already approved/denied pics
        // (uses pics() to use a database query because Collection doesn't support '!=')
        $approvedExistingPics = $cityInfo->pics()->where('status', '!=', 'new')->where('source', auth()->id())->get();
        $approvedFileList = new FileListHandler($approvedExistingPics);
        $approvedFileList->picListSizeTypeNames = [''];
        $response = $fileList->go();
        if ($response !== null) {
            return $response;
        }

        // FileUpload

        $fileUpload = new FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], 15, 5, $existingPics->count());
        $fileUpload->minImageWidth = CityInfo::PIC_MIN_WIDTH;
        $fileUpload->minImageHeight = CityInfo::PIC_MIN_HEIGHT;
        $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($cityInfo): void {
            $cityInfo->addPic($filePath, auth()->id());
        });
        if ($response !== null) {
            return $response;
        }

        return view('user/submitCityPics-upload', compact('cityInfo', 'fileList', 'approvedFileList', 'fileUpload'));
    }
}
