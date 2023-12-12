<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\EventLog;
use App\Helpers\ListingDisplay;
use App\Helpers\ListingEditHandler;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingDuplicate;
use App\Models\MailMessage;
use App\Models\User;
use App\Services\AjaxDataQueryHandler;
use DB;
use Illuminate\Support\MessageBag;
use Lib\DataCorrection;
use Lib\FormHandler;
use Lib\Geocoding;
use Lib\WebsiteTools;
use Request;
use Response;
use Session;
use URL;

class StaffListingsController extends Controller
{
    public function instructions()
    {
        return view('staff/listings-instructions');
    }

    public function listings($pathParameters = null)
    {
        $message = '';

        $getKML = Request::input('getKML');

        $formHandler = new FormHandler(
            'Listing',
            Listing::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models\Listing'
        );
        $formHandler->query = Listing::areNotListingCorrection();
        $formHandler->allowedModes = auth()->user()?->hasPermission('admin')
            ? ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete']
            : ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';

        if (! $getKML) {
            $formHandler->listSelectFields = ['verified', 'name', 'address', 'city', 'cityAlt', 'country', 'combinedRating'];

            $formHandler->listDisplayFields = ['verified', 'name', 'address', 'city', 'country', 'combinedRating', 'uniqueText', 'hasStoredPrice'];
        }

        $formHandler->listSort['name'] = 'asc';
        $formHandler->callbacks = [
            'validate' => function ($formHandler, $useInputData, $fieldInfoElement) {
                if ($formHandler->model) {
                    $submittedValue = $formHandler->getFieldValue('propertyType', $useInputData);
                    if ($submittedValue != $formHandler->model->propertyType && ! $formHandler->getFieldValue('propertyTypeVerified', $useInputData)) {
                        return new MessageBag(['propertyTypeVerified' => "Must set this to Yes if changing the property type (originally was '" . $formHandler->model->propertyType . "')."]);
                    }
                }

                return $formHandler->validate($useInputData, $fieldInfoElement, false);
            },
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use (&$message) {
                // Remember renaming
                if ($data['rememberCityAltRenaming']) {
                    if ($formHandler->model->cityAlt != $data['cityAlt'] && $formHandler->model->cityAlt != '') {
                        DataCorrection::saveCorrection('', 'cityAlt', $formHandler->model->cityAlt, $data['cityAlt'], $data['country'], $data['city']);
                        $message = "Remembering '" . $formHandler->model->cityAlt . "' -> '$data[cityAlt]'.";

                        // Also update other listings with same cityAlt
                        Listing::where('cityAlt', $formHandler->model->cityAlt)->where('city', $data['city'])->where('region', $data['region'])->where('country', $data['country'])
                            ->update(['cityAlt' => $data['cityAlt']]);
                    }
                }

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
            'listRowLink' => function ($formHandler, $row, $fieldName) {
                $defaultLink = '/' . Request::path() . '/' . $row->id;

                if ($fieldName !== 'uniqueText' || empty($formHandler->inputData['uniqeTextFor'])) {
                    return $defaultLink;
                }

                return routeURL('staff-listingSpecialText', 'edit-or-create') . "?listingID=$row->id&type=" . $formHandler->inputData['uniqeTextFor'];
            },
        ];

        $formHandler->go();

        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'searchRank':
                    $rank = $formHandler->model->updateSearchRank();
                    $message = "Search rank updated ($rank).";

                    break;

                case 'updateListing':
                    /** @var App\Models\Listing\ListingMaintenance $listing */
                    $listing = $formHandler->model->listingMaintenance();
                    $message = '<h3>Update Listing</h3><br><pre>' .
                        str_replace("\n", "\n\n", trim($listing->updateListing(false, false))) .
                        '</pre><br>';
                    $formHandler->model = $formHandler->model->fresh(); // reload it in case anything changed.

                    break;

                case 'removeAllComments':
                    /*
        			if($isAdmin) {
        				dbQuery("UPDATE comments SET status='removed',notes=CONCAT(".dbQuote($_REQUEST['removeAllCommentsNote']).",notes) WHERE hostelID=$_REQUEST[listingID] AND verified>=0");
        				echo "Comments removed.";
        			}
        			exit();
        			*/
                    break;

                case 'linkExchanged':
                    /*
        			if($_REQUEST['url'] == '' || !$_REQUEST['w']['id']) exit();
        			dbQuery("UPDATE links SET linkExchangedURL=".dbQuote($_REQUEST['url'])." WHERE hostelID=".$_REQUEST['w']['id']);
        			echo "Saved $_REQUEST[url].";
        			$d['webDisplay'] = 1;
        			$qfUpdate = true;
        			*/
                    break;

                case 'geocodedInfo':
                    $result = Geocoding::reverseGeocode($formHandler->model->latitude, $formHandler->model->longitude, Listing::LATLONG_PRECISION);
                    $message = '<h3>Geocoded Info</h3><br><pre>' . print_r($result, true) . '</pre>';

                    break;
            }
        }

        if ($getKML) {
            return Response::make($formHandler->display('staff/edit-listings-kml'))
                ->header('Content-Type', 'text/tab-separated-values')
                ->header('Content-Disposition', 'attachment; filename="listings.kml"');
        }

        return $formHandler->display('staff/edit-listings', compact('message'));
    }

    public function emailListings($pathParameters = null)
    {
        return 'not yet implemented!';

        $message = '';

        $formHandler = new FormHandler(
            'Listing',
            Listing::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models\Listing'
        );
        $formHandler->query = Listing::areNotListingCorrection();
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['verified', 'name', 'address', 'city', 'cityAlt', 'country', 'comment'];
        $formHandler->listSort['name'] = 'asc';

        $formHandler->go();

        return $formHandler->display('staff/edit-listings', compact('message'));
    }

    public function previewListing($listingID)
    {
        $listing = Listing::areNotListingCorrection()->where('id', $listingID)->first();
        if (! $listing) {
            App::abort(404);
        }

        return view('staff/previewListing', with(new ListingDisplay($listing))->getListingViewData(false));
    }

    public function listingCorrections($pathParameters = null)
    {
        $formHandler = new FormHandler(
            'Listing',
            Listing::fieldInfo('listingCorrectionStaffDisplay'),
            $pathParameters,
            'App\Models\Listing'
        );
        $formHandler->query = Listing::areListingCorrection();
        $formHandler->allowedModes = ['searchForm', 'list', 'searchAndList', 'updateForm', 'update', 'delete', 'multiDelete'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['verified', 'targetListing_name', 'comment'];
        $formHandler->listSort['id'] = 'asc';

        return $formHandler->go('staff/edit-listingCorrections');
    }

    public function listingDuplicates($pathParameters = null)
    {
        if (auth()->user()->hasPermission('admin')) {
            $response = AjaxDataQueryHandler::handleUserSearch(
                Request::input('userID_selectorIdFind'),
                Request::input('userID_selectorSearch'),
                User::havePermission('staffEditHostels')
            );
            if ($response !== null) {
                return $response;
            }
        }

        $response = AjaxDataQueryHandler::handleListingSearch(Request::input('listingID_selectorIdFind'), Request::input('listingID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleListingSearch(Request::input('otherListing_selectorIdFind'), Request::input('otherListing_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $showMergeLinks = (int) Request::input('showMergeLinks');

        $formHandler = new FormHandler(
            'ListingDuplicate',
            ListingDuplicate::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models\Listing'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display'];
        $formHandler->listPaginateItems = 15;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listDisplayFields = ['status', 'listingID', 'otherListing', 'priorityLevel', 'score', 'notes'];
        $formHandler->listSort = ['priorityLevel' => 'desc', 'score' => 'desc'];
        $formHandler->persistentValues['showMergeLinks'] = $showMergeLinks;

        if ($showMergeLinks) {
            $formHandler->callbacks['listRowLink'] = function ($formHandler, $row) {
                return routeURL('staff-mergeListings', ['showThese', $row->listingID . ',' . $row->otherListing]);
            };
        }

        return $formHandler->go('staff/edit-listingDuplicates');
    }

    public function mergeListings($mode = '', $parameter = '')
    {
        $mergeList = $originalMergeList = (Session::get('listingMergeList') ?: []);

        // Use mergeIDs input instead if provided (comes from hidden elements on submitted merge forms)
        if (Request::has('mergeIDs')) {
            $mergeList = Request::input('mergeIDs');
        }

        switch ($mode) {
            case 'add':
                $listingIDs = explode(',', $parameter);
                $mergeList = array_unique(array_merge($mergeList, $listingIDs));
                $message = "$parameter added to the merge list.";

                break;

            case 'clear':
                $mergeList = [];
                $message = 'Merge list cleared.';

                break;

            case 'showThese': // Instead of using the merge list, just show these specific listings
                $mergeList = explode(',', $parameter);

                break;
        }

        $listings = $listingDuplicates = $mergeChoices = $fieldInfo = $message = $contactEmailsString = null;
        $isListingCorrection = $isSuccess = $multipleListingsInSameImportedSystem = false;
        $notes = Request::input('notes');

        if ($mergeList) {
            sort($mergeList);
            $listings = Listing::whereIn('id', $mergeList)->get();
            $listings->load('importeds');

            // Handle some commands that don't depend on there being 2+ listings to merge
            switch (Request::input('command')) {
                case 'emailHistory':
                    $searchFor = $listings->flatMap(function ($listing) {
                        return $listing->getAllEmails();
                    })->unique()->toArray();
                    $mailMessages = null;
                    if ($searchFor) {
                        $mailMessages = MailMessage::forRecipientOrBySenderEmail($searchFor)->orderBy('transmitTime', 'desc')->limit(40)->get();
                    }

                    return view('partials/_emailHistory', compact('mailMessages'));
            }

            if ($listings->count() != count($mergeList)) {
                $message = 'Some listings in merge list no longer exist. Merge list cleared.';
                $listings = null;
                $mergeList = [];
            } elseif ($listings->count() >= 2) {
                $mergeChoices = ListingDuplicate::generateMergeChoices($listings);

                switch (Request::input('command')) {
                    case 'mergeNow':
                        // Set chosen choices
                        $chosen = [];
                        $choiceInput = Request::input('choiceData');
                        foreach ($mergeChoices as $field => $choiceData) {
                            if (count($choiceData['choices']) > 1) {
                                $chosen[$field] = $choiceInput[$field];
                            } elseif ($choiceData['choices']) {
                                $chosen[$field] = reset($choiceData['choices']);
                            }
                        }
                        $isSuccess = true;
                        $mergeListingsOutput = ListingDuplicate::mergeListings($listings, $chosen);
                        if ($mergeListingsOutput == false) {
                            return false;
                        }
                        EventLog::log('staff', 'merge', 'Listing', $mergeChoices['id']['choices'][0], implode(', ', $mergeList), $mergeListingsOutput);
                        $message = 'Merged! <a href="' . routeURL('staff-listings', $mergeChoices['id']['choices'][0]) . '">Edit Listing</a>';
                        $mergeChoices = $listings = $mergeList = null;

                        break;

                    case 'flag':
                        ListingDuplicate::insertOrUpdate(
                            $listings->pluck('id')->all(),
                            'flagged',
                            ['source' => 'listingMerge', 'notes' => Request::input('notes'), 'userID' => auth()->id()]
                        );
                        $mergeChoices = $listings = $mergeList = null;
                        $message = 'Flagged.';

                        break;

                    case 'hold':
                        ListingDuplicate::insertOrUpdate(
                            $listings->pluck('id')->all(),
                            'hold',
                            ['source' => 'listingMerge', 'notes' => Request::input('notes'), 'userID' => auth()->id()]
                        );
                        $mergeChoices = $listings = $mergeList = null;
                        $message = 'On hold.';

                        break;

                    case 'nonduplicates':
                        ListingDuplicate::insertOrUpdate(
                            $listings->pluck('id')->all(),
                            'nonduplicates',
                            ['source' => 'listingMerge', 'notes' => Request::input('notes'), 'userID' => auth()->id()]
                        );
                        EventLog::log('staff', 'nonduplicates', 'Listing', $mergeChoices['id']['choices'][0], implode(', ', $mergeList));
                        $mergeChoices = $listings = $mergeList = null;
                        $message = 'Marked as nonduplicates.';

                        break;
                }

                if ($listings) {
                    /* Prep data for display on the merge page */

                    $listingDuplicates = ListingDuplicate::forListingIDs($mergeList)->get();
                    if ($notes == '' && ! $listingDuplicates->isEmpty()) {
                        // Set notes to the first listingDuplicate record found for these listings that has notes
                        $hasNotes = $listingDuplicates->first(function ($duplicate) {
                            return $duplicate->notes != '';
                        });
                        if ($hasNotes) {
                            $notes = $hasNotes->notes;
                        }
                    }
                    $fieldInfo = Listing::fieldInfo('mergeListings');

                    // Determine if $isListingCorrection and $multipleListingsInSameImportedSystem
                    $importedSystemListingIDs = [];
                    foreach ($listings as $listing) {
                        if ($listing->isListingCorrection()) {
                            $isListingCorrection = true;

                            break;
                        } elseif (! $multipleListingsInSameImportedSystem) {
                            foreach ($listing->activeImporteds as $imported) {
                                if (isset($importedSystemListingIDs[$imported->system]) && $importedSystemListingIDs[$imported->system] != $listing->id) {
                                    $multipleListingsInSameImportedSystem = true;

                                    break;
                                }
                                $importedSystemListingIDs[$imported->system] = $listing->id;
                            }
                        }
                    }

                    $contactEmailsString = $listings->map(function ($listing) {
                        return $listing->getBestEmail('listingIssue');
                    })->toBase()->filter()->unique()->implode(', ');
                }
            }
        }

        if ($mode != 'showThese' && $mergeList != $originalMergeList) {
            Session::put('listingMergeList', $mergeList);
        }

        return view(
            'staff/mergeListings',
            compact(
                'message',
                'isSuccess',
                'listings',
                'listingDuplicates',
                'contactEmailsString',
                'mergeChoices',
                'fieldInfo',
                'isListingCorrection',
                'multipleListingsInSameImportedSystem',
                'notes'
            )
        );
    }

    public function getVideosFromSpiderResults()
    {
        $answerSubmit = Request::input('answerSubmit');
        $lastSpiderResultID = null;
        $message = null;

        if ($answerSubmit != '') {
            $lastSpiderResultID = Request::input('spiderResultID');
            $video = Request::input('video');
            $this->setSpiderResultsListingVideosDone($video);

            if ($answerSubmit == 'yes') {
                $listing = Listing::findOrFail(Request::input('listingID'));
                if ($listing->videoURL != '') {
                    $message = "This listing's video was already saved.";
                } else {
                    $listing->updateAndLogEvent(
                        ['videoURL' => $video, 'videoEmbedHTML' => base64_decode(Request::input('videoEmbedHTML'))],
                        true,
                        'getVideosFromSpiderResults',
                        'staff'
                    );
                    $message = "Video saved to listing $listing->id '$listing->name'.";
                }
            }
        }

        $videoTypes = ['YouTube', 'Vimeo', 'Metacafe', 'Viddler'];

        $urlsPreviouslyDone = $this->getSpiderResultsListingVideosDone();

        $query = DB::table('spiderResults')->where('type', 'listing');
        if ($lastSpiderResultID) {
            $query->where('id', '<=', $lastSpiderResultID);
        } // we use '<=' because there can be multiple videos in a spider result
        $spiderResults = $query->orderBy('id', 'desc')->get()->all();

        foreach ($spiderResults as $spiderResultNum => $spiderResult) {
            $results = unserialize($spiderResult->spiderResults);
            if (! $results) {
                continue;
            }

            $videos = [];
            $videoCount = 0;
            foreach ($results as $resultType => $urls) {
                if (! in_array($resultType, $videoTypes)) {
                    continue;
                }
                foreach ($urls as $videoURL => $fromPage) {
                    if (! filter_var($videoURL, FILTER_VALIDATE_URL)) {
                        continue;
                    }
                    if ($resultType == 'YouTube' && (! strpos($videoURL, 'youtube.com/embed') && ! strpos($videoURL, 'youtube.com/watch'))) {
                        continue;
                    }
                    if ($videoCount++ > 5) {
                        break;
                    } // some website have a ton of videos, ignore ones past a certain number.
                    if (in_array($videoURL, $urlsPreviouslyDone)) {
                        continue;
                    }
                    $videos[] = $videoURL;
                }
            }

            if (! $videos) {
                continue;
            }

            $listing = Listing::where('web', $spiderResult->url)->where('videoURL', '')->areLive()->where('propertyType', 'Hostel')->first();
            if (! $listing) {
                continue;
            }

            foreach ($videos as $video) {
                $videoEmbedHTML = WebsiteTools::extractEmbedCode($video);
                if ($videoEmbedHTML != '') {
                    break;
                }
                $this->setSpiderResultsListingVideosDone($video); // so we don't try it again
                echo "Couldn't get embed HTML for $video.<br>";
            }

            if ($videoEmbedHTML == '') {
                continue;
            } // couldn't get embed code for any of the videos

            $remainingToDo = count($spiderResults) - $spiderResultNum;

            return view('staff/listings-getVideosFromSpiderResults', compact('listing', 'video', 'videoEmbedHTML', 'spiderResult', 'message', 'remainingToDo'));
        }

        return 'No new spider results videos found.';
    }

    private function getSpiderResultsListingVideosDone()
    {
        return DB::table('spiderResultsListingVideosDone')->pluck('url')->all();
    }

    private function setSpiderResultsListingVideosDone($url): void
    {
        DB::table('spiderResultsListingVideosDone')->insert(['url' => $url]);
    }

    /* Used for editing listing features, pics, etc. */

    public function listingManage($listingID, $action, $extraParameter = null)
    {
        $handler = new ListingEditHandler($listingID, $action, $extraParameter, 'staff/edit-listings-manage', routeURL('staff-listings', $listingID));
        $handler->relaxedValidation = true; // So some fields aren't required, etc.

        return $handler->go();
    }
}
