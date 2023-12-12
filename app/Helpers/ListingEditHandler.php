<?php

namespace App\Helpers;

/*

This is called from controllers to handle editing of listings.

Can be used for:

- Admin/staff editing listing details.
- Manager/owner editing own listing's details.
- User submitting a new listing.

*/

use App\Models\AttachedText;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingFeatures;
use App\Models\Rating;
use Exception;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Lib\FileListHandler;
use Lib\FileUploadHandler;
use Lib\FormHandler;
use Lib\WebsiteTools;

class ListingEditHandler
{
    public $returnToWhenDone; // route to return to after editing is complete, tab is picked based on the listing. (or can be a URL)

    public $listing;

    public $view;

    public $action;

    public $extraParameter;

    public $showGenericUpdateSuccessPage = false;

    public $relaxedValidation = false; // Set to true for admin users so some fields are required, etc.

    public function __construct($listingID, $action = null, $extraParameter = null, $view = null, $returnToWhenDone = null, $defaultListingAttributes = [])
    {
        $this->action = $action;
        $this->extraParameter = $extraParameter;
        $this->view = $view;
        $this->returnToWhenDone = $returnToWhenDone;

        if ($listingID == 'new') {
            $this->action = 'new';
            $this->listing = new Listing($defaultListingAttributes);
        } else {
            $this->listing = Listing::find($listingID);
            if (! $this->listing) {
                abort(404);
            }
        }
    }

    public function getText(string $type): Collection
    {
        return $this->listing->attachedTexts()->where('type', $type)->where('source', 'owner')
            ->orderBy(DB::raw('LENGTH(data)'), 'ASC') // if multiple for the same language, ordered shortest to longest (which means we end up displaying the longest ones)
            ->pluck('data', 'language');
    }

    /* Misc */

    public function getValidationStatusForAll()
    {
        return [
            'basicInfo' => $this->basicInfo(true, false),
            'features' => $this->features(true, false),
            'description' => $this->editAttachedText('description', true, false),
            'location' => $this->editAttachedText('location', true, false),
            'mapLocation' => $this->mapLocation(true, false),
            'pics' => $this->pics(true, false),
            'video' => $this->video(true, false),
            'backlink' => $this->backlink(true, false),
            'ratings' => $this->ratings(true, false),
            // 'bookings' => $this->bookings(true, false),
        ];
    }

    public function go()
    {
        // Note these don't actually get saved unless the listing gets saved
        if (! auth()->user()->hasPermission('staff')) {
            $this->listing->lastEditSessionID = Session::getId();
        }

        View::share('listingEditHandler', $this);

        switch ($this->action) {
            case 'basicInfo':
                return $this->basicInfo(false, true);

            case 'features':
                return $this->features(false, true);

            case 'description':
                return $this->editAttachedText('description', false, true);

            case 'location':
                return $this->editAttachedText('location', false, true);

            case 'mapLocation':
                return $this->mapLocation(false, true);

            case 'pics':
                return $this->pics(false, true);

            case 'panoramas':
                return $this->panoramas(false, true);

            case 'video':
                return $this->video(false, true);

            case 'backlink':
                return $this->backlink(false, true);

            case 'ratings':
                return $this->ratings(false, true);

            case 'reviews':
                return $this->reviews(false, true);

            case 'bookings':
                return $this->bookings(false, true);

            case 'sticker':
                return $this->sticker(false, true);

            case 'preview':
                return $this->previewListing();

            default:
                abort(404);
        }
    }

    public function getURL($listingID, $action = '', $queryVariables = null)
    {
        $parameters = Route::getCurrentRoute()->parameters();
        $parameters['listingID'] = $listingID;
        if ($action != '') {
            $parameters['listingAction'] = $action;
        } else {
            unset($parameters['listingAction']);
        }

        return routeURL(Route::currentRouteName(), $parameters) .
            ($queryVariables ? makeUrlQueryString($queryVariables) : '');
    }

    /*
    ** Actions
    */

    private function basicInfo($justGetValidationStatus, $showValidation)
    {
        $formHandler = new FormHandler('Listing', Listing::fieldInfo('basicInfo'), null, 'App\Models\Listing');
        $formHandler->model = $this->listing;
        $formHandler->logChangesAsCategory = 'management';

        if ($justGetValidationStatus) {
            $formHandler->mode = 'updateForm'; // it just needs some mode to be set

            return ! $formHandler->validate(false)->any();
        }

        return $formHandler->go($this->view, ['updateForm', 'update']);
    }

    private function features($justGetValidationStatus, $showValidation)
    {
        $formHandler = new FormHandler('ListingFeatures', ListingFeatures::fieldInfo(! $this->relaxedValidation, 'long'), null, 'App\Models\Listing');
        $formHandler->query = false; // tell it not to use database queries
        $formHandler->model = new ListingFeatures($this->listing);
        $formHandler->logChangesAsCategory = 'management';
        $formHandler->callbacks['logEvent'] = function ($category, $action, $subjectType, $subjectID, $subjectString, $data): void {
            EventLog::log($category, $action, 'Listing', $this->listing->id, 'listingFeatures', $data);
        };

        if ($justGetValidationStatus) {
            $formHandler->mode = 'updateForm'; // it just needs some mode to be set

            return ! $formHandler->validate(false)->any();
        }

        return $formHandler->go($this->view, ['updateForm', 'update']);
    }

    private function editAttachedText($type, $justGetValidationStatus, $showValidation)
    {
        $texts = $this->getText($type);

        $minPreferredLength = match ($type) {
            'description' => Listing::MIN_PREFERRED_DESCRIPTION_LENGTH,
            'location' => Listing::MIN_PREFERRED_LOCATION_LENGTH,
        };

        if ($justGetValidationStatus) {
            return $texts->isNotEmpty() && $texts['en'] != '' && strlen($texts['en']) >= $minPreferredLength;
        }

        if ($submittedTexts = Request::input('texts')) {
            $submittedTexts = array_map('strip_tags', $submittedTexts);
            if (array_diff(array_keys($submittedTexts), Languages::allLiveSiteCodes())) {
                throw new Exception("Submitted $type contains unknown language(s).");
            }
            AttachedText::replaceAllLanguages('hostels', $this->listing->id, null, $type, 'owner', $submittedTexts);
            EventLog::log('management', 'update', 'Listing', $this->listing->id, $type);
            $this->listing->clearRelatedPageCaches();
            $this->showGenericUpdateSuccessPage = true;
        }

        return view($this->view, compact('texts', 'minPreferredLength'));
    }

    private function mapLocation($justGetValidationStatus, $showValidation)
    {
        if ($justGetValidationStatus) {
            return $this->listing->ownerLatitude != 0.0 || $this->listing->ownerLongitude != 0.0;
        }

        if (Request::input('reset')) {
            $this->listing->updateAndLogEvent(['ownerLatitude' => 0, 'ownerLongitude' => 0], true, 'mapLocation');
        }

        if (Request::has('latitude')) {
            $this->listing->updateAndLogEvent(['ownerLatitude' => round(Request::input('latitude'), Listing::LATLONG_PRECISION),
                'ownerLongitude' => round(Request::input('longitude'), Listing::LATLONG_PRECISION), ], false, 'mapLocation', 'management');
            $this->listing->listingMaintenance()->setBestGeocoding();
            $this->listing->save();
            $this->showGenericUpdateSuccessPage = true;
        }

        if ($this->listing->ownerLatitude != 0.0 || $this->listing->ownerLongitude != 0.0) {
            $latitude = $this->listing->ownerLatitude;
            $longitude = $this->listing->ownerLongitude;
        } else {
            // Use the regular latitude/longitude (may be from imported data, etc.)
            $latitude = $this->listing->latitude;
            $longitude = $this->listing->longitude;
        }

        return view($this->view, compact('latitude', 'longitude'));
    }

    private function pics($justGetValidationStatus, $showValidation)
    {
        $existingPics = $this->listing->ownerPics;

        if ($justGetValidationStatus) {
            return ! $existingPics->isEmpty();
        } /* (or require at least a minimum number of pics?) */

        // FileList

        $fileList = new FileListHandler($existingPics, ['caption'], ['caption'], true);
        $fileList->useIsPrimary = true;
        $fileList->makeSortableUsingNumberField = 'picNum';
        $fileList->picListSizeTypeNames = ['big'];
        if (auth()->user()->hasPermission('staffPicEdit')) {
            $fileList->viewLinkClosure = function ($row) {
                return routeURL('staff-pics', [$row->id, 'pics']);
            };
        }
        $response = $fileList->go();
        if ($fileList->filesModified) {
            $this->listing->load('ownerPics');
        }
        if ($response !== null) {
            return $response;
        }

        // FileUpload
        $fileUpload = new FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], 20, Listing::MAX_PIC_COUNT, $existingPics->count());
        $fileUpload->minImageWidth = Listing::NEW_PIC_MIN_WIDTH;
        $fileUpload->minImageHeight = Listing::NEW_PIC_MIN_HEIGHT;
        $listing = $this->listing;
        $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($listing): void {
            $listing->addOwnerPic($filePath);
            $listing->clearRelatedPageCaches();
        });
        if ($response !== null) {
            return $response;
        }

        return view($this->view, compact('fileList', 'fileUpload', 'showValidation'));
    }

    private function panoramas($justGetValidationStatus, $showValidation)
    {
        $existingPics = $this->listing->panoramas;

        if ($justGetValidationStatus) {
            return ! $existingPics->isEmpty();
        } /* (or require at least a minimum number of pics?) */

        // FileList

        $fileList = new FileListHandler($existingPics, ['caption'], ['caption'], true);
        $fileList->useIsPrimary = true;
        $fileList->makeSortableUsingNumberField = 'picNum';
        $fileList->picListSizeTypeNames = [''];
        if (auth()->user()->hasPermission('staffPicEdit')) {
            $fileList->viewLinkClosure = function ($row) {
                return routeURL('staff-pics', [$row->id, 'pics']);
            };
        }
        $response = $fileList->go();
        if ($fileList->filesModified) {
            $this->listing->load('panoramas');
        }
        if ($response !== null) {
            return $response;
        }

        // FileUpload
        $fileUpload = new FileUploadHandler(['jpg', 'jpeg'], 20, 4, $existingPics->count());
        $fileUpload->minImageWidth = Listing::NEW_PANORAMA_MIN_WIDTH;
        $fileUpload->minImageHeight = Listing::NEW_PANORAMA_MIN_HEIGHT;
        $listing = $this->listing;
        $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($listing): void {
            $listing->addPanorama($filePath);
            $listing->clearRelatedPageCaches();
        });
        if ($response !== null) {
            return $response;
        }

        return view($this->view, compact('fileList', 'fileUpload', 'showValidation'));
    }

    private function video($justGetValidationStatus, $showValidation)
    {
        if ($justGetValidationStatus) {
            return true;
        }

        $status = '';
        $videoURL = '';

        if (Request::has('submitVideoURL')) {
            $videoURL = Request::input('videoURL');
            if ($videoURL == '') {
                if ($this->listing->videoURL != '') {
                    // Special case, setting the video to '' (to remove it)
                    $this->listing->updateAndLogEvent(['videoURL' => '', 'videoEmbedHTML' => '', 'videoSchema' => ''], true, 'video', 'management');
                    $status = 'videoRemoved';
                }
            } elseif (! filter_var($videoURL, FILTER_VALIDATE_URL)) {
                $status = 'extractionError';
            } else {
                $videoEmbedHTML = WebsiteTools::extractEmbedCode($videoURL);
                if ($videoEmbedHTML == '') {
                    $status = 'extractionError';
                } else {
                    $this->listing->updateAndLogEvent(['videoURL' => $videoURL, 'videoEmbedHTML' => $videoEmbedHTML], true, 'video', 'management');
                    $status = 'success';
                }

                $videoID = getYoutubeIDFromURL($videoURL);
                if ($videoID) {
                    $schema = WebsiteTools::getVideoSchema($videoID);
                    if ($schema) {
                        $this->listing->updateAndLogEvent(['videoSchema' => $schema], true, 'video', 'management');
                        $status = 'schemaSuccess';
                    } else {
                        $status = 'getSchemaError';
                    }
                }
            }
        } elseif (Request::input('removeVideoURL')) {
            $this->listing->updateAndLogEvent(['videoURL' => '', 'videoEmbedHTML' => '', 'videoSchema' => ''], true, 'video', 'management');
            $status = 'videoRemoved';
        } elseif (Request::has('submitVideoSchema')) {
            $videoID = Request::input('videoID');
            if ($videoID !== '') {
                $schema = WebsiteTools::getVideoSchema($videoID);
                if ($schema) {
                    $this->listing->updateAndLogEvent(['videoSchema' => $schema], true, 'video', 'management');
                    $status = 'schemaSuccess';
                } else {
                    $status = 'getSchemaError';
                }
            }
        }

        $listing = $this->listing;

        return view($this->view, compact('videoURL', 'status', 'listing'));
    }

    private function sticker($justGetValidationStatus, $showValidation)
    {
        $fieldInfo = [
            'mailingAddress' => ['type' => 'textarea', 'rows' => 5],
            'adhesiveType' => [
                'type' => 'radio', 'options' => ['outside', 'inside'], 'optionsDisplay' => 'translate',
                'fieldLabelLangKey' => 'ListingEditHandler.sticker.adhesiveType',
                'getValue' => function ($formHandler, $model) {
                    return strpos($model->stickerPlacement, 'inside') !== false ? 'inside' : 'outside';
                },
                'setValue' => function ($formHandler, $model, $value): void {
                    if ($value == 'inside') {
                        $model->stickerPlacement = (strpos($model->stickerPlacement, 'Small') !== false ? 'insideSmall' : 'insideLarge');
                    } else {
                        $model->stickerPlacement = (strpos($model->stickerPlacement, 'Small') !== false ? 'outsideSmall' : 'outsideLarge');
                    }
                },
            ],
        ];

        /*
        if ($this->listing->qualifiesForSticker()) {
            $fieldInfo = array_merge($fieldInfo, [

            ]);
        }
        */

        $formHandler = new FormHandler('Listing', $fieldInfo, null, 'App\Models\Listing');
        $formHandler->model = $this->listing;
        $formHandler->logChangesAsCategory = 'management';

        if ($justGetValidationStatus) {
            $formHandler->mode = 'updateForm'; // it just needs some mode to be set

            return ! $formHandler->validate(false)->any();
        }

        $formHandler->go(null, ['updateForm', 'update']);

        if ($formHandler->mode == 'updateForm' && $this->listing->mailingAddress == '') {
            // Create a default mailing address from the database info...
            $mailingAddress = $this->listing->name;
            if ($this->listing->ownerName != '') {
                $mailingAddress .= "\n" . $this->listing->ownerName;
            }
            if ($this->listing->poBox != '') {
                $mailingAddress .= "\n" . $this->listing->poBox;
            } else {
                $mailingAddress .= "\n" . $this->listing->address;
            }
            $mailingAddress .= "\n" . $this->listing->city;
            if ($this->listing->country == 'USA' && $this->listing->region != '') {
                $mailingAddress .= ", $listing[region]";
            }
            if ($this->listing->zipcode != '') {
                $mailingAddress .= '  ' . $this->listing->zipcode;
            }
            if ($this->listing->country != 'USA') {
                $mailingAddress .= "\n" . strtoupper($this->listing->country);
            }
            $this->listing->mailingAddress = $mailingAddress . "\n";
        }

        return $formHandler->display($this->view);
    }

    private function backlink($justGetValidationStatus, $showValidation)
    {
        if ($justGetValidationStatus) {
            return $this->listing->mgmtBacklink != '';
        }

        $status = $errors = '';

        if (($url = Request::input('backlinkURL')) != '') {
            if (stripos($url, 'http://') !== 0 && stripos($url, 'https://') !== 0) {
                $url = 'http://' . $url;
            } // sometimes they forget to add that part

            if (! filter_var($url, FILTER_VALIDATE_URL)) {
                $status = 'invalidURL';
            } elseif (strcasecmp('hostelz.com', parse_url($url, PHP_URL_HOST)) == 0) {
                $status = 'ourURL';
            } else {
                $contents = $url ? file_get_contents($url, false, stream_context_create(['http' => ['ignore_errors' => true, 'user_agent' => 'Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/92.0.4515.131 Safari/537.36']])) : false;

                if ($contents === false || $contents == '') {
                    $status = 'pageError';
                } elseif (! strpos($contents, 'http://www.hostelz.com') && ! strpos($contents, 'http://hostelz.com') &&
                    ! strpos($contents, 'https://www.hostelz.com') && ! strpos($contents, 'https://hostelz.com')) {
                    $status = 'linkNotFound';
                } else {
                    $this->listing->updateAndLogEvent(['mgmtBacklink' => $url], true, 'backlink', 'management');
                    $status = 'success';
                }
            }
        }

        $backlinkURL = $url;

        return view($this->view, compact('backlinkURL', 'status'));

        /*
    	switch(rand(1,2)) {
    		case 1: $dotted = ' solid'; break;
    		case 2: $dotted = ''; break;
    	}
    	$borderWidth = rand(0,3).'px';
    	$fontSizes = array('10.5pt', '11pt', '12px', '13px', '14px');
    	$fontSize = $fontSizes[rand(0,count($fontSizes)-1)];
    	$fonts = array('','serif','sans-serif', 'Georgia', 'Verdana', 'Arial', 'Palatino', 'Tshoma');
    	$font = $fonts[rand(0,count($fonts)-1)];
    	$margin = rand(4,7);
    	$padding = rand(3,6);
    	$background = sprintf("%2x%2x%2x",0xE7+rand(0,4),0xE7+rand(0,8),0xF7+rand(3,8));
    	$border = sprintf("%2x%2x%2x",0xD0+rand(0,31),0xD8+rand(0,31),0xE0+rand(0,31));
    	$color =  sprintf("%2x%2x%2x",0x20+rand(0,31),0x20+rand(0,31),0x40+rand(0,31));

        $listingLink = $isLive ? "<a href=\"$listingURL\">$listing[name]</a>" : $listing['name'];

    	if($isHostel)
    		$listingText = array(
    			"$listingLink &mdash; a <a href=\"$WEB_URL\">Hostelz.com</a> listed Hostel",
    			"$listingLink is listed on <a href=\"$WEB_URL\">Hostelz.com</a>",
    			"See $listingLink in the $listing[city] Hostels list at <a href=\"$WEB_URL\">Hostelz.com</a>.",
    			"See $listingLink and other great <a href=\"$WEB_URL\">Hostels at Hostelz.com</a>.",
    			"$listingLink &mdash; a <a href=\"$WEB_URL\">Hostelz.com Hostel</a>",
    			"$listingLink is a <a href=\"$WEB_URL\">Hostelz.com</a> listed $listing[city] Hostel.",
    			"<a href=\"$WEB_URL\">Hostelz.com</a> lists $listingLink",
    			"$listingLink is a <a href=\"$WEB_URL\">Hostelz.com</a> $listing[city] Hostel.",
    			"<b>$listingLink</b> is a <a href=\"$WEB_URL\"><b>Hostelz.com</b></a> $listing[city] Hostel.</div>",
    			"<b>$listingLink is listed in <a href=\"$WEB_URL\">Hostelz.com's</a> $listing[city] Hostels</b>.",
    			"<a href=\"$WEB_URL\">Hostelz.com</a> lists $listingLink as a $listing[city] Hostel.",
    			"<b>$listingLink</b> is a <b>Hostelz.com</b> $listing[city] <a href=\"$WEB_URL\">Backpackers Hostel</a>.</div>",
        		"<b>$listingLink is listed on <a href=\"$WEB_URL\">Hostelz.com's Backpacker Hostels Guide</a></b>.",
        		"<b>$listingLink is listed on <a href=\"$WEB_URL\">Hostelz.com's Hostel Review Guide</a></b>.",
    			"Hostelz.com lists $listingLink as a $listing[city] <a href=\"$WEB_URL\">Backpackers Hostel</a>.",
    			"<b>Visit $listingLink in the <a href=\"$WEB_URL\">Hostelz.com Hostels Directory</a></b>.",
    			"Visit $listingLink in the <a href=\"$WEB_URL\">Hostelz.com Hostels Review Guide.",
    			"See $listingLink in Hostelz.com's $listing[city] <a href=\"$WEB_URL\">Hostels Directory</a>.",
    			"Visit $listingLink at <a href=\"$WEB_URL\">Hostelz.com</a>",
    			"See $listingLink in <a href=\"$WEB_URL\">Hostelz.com's Hostels Guide</a>."
    		);
    	else
    		$listingText = array(
        		"$listingLink is listed on <a href=\"$WEB_URL\">Hostelz.com</a>",
    			"<b><a href=\"$WEB_URL\">Hostelz.com</a> lists $listingLink in the $listing[city]</b> hotel/hostel guide.",
    			"Visit $listingLink on <a href=\"$WEB_URL\">Hostelz.com</a> in their $listing[city] hotel &amp; hostel guide.",
    			"See $listingLink in the <a href=\"$WEB_URL\">Hostelz.com Hostels and Hotels Guide</a>.",
    			"$listingLink is in the <a href=\"$WEB_URL\">Hostelz.com</a> $listing[city] Hostel/Hotel review database.",
    			"$listingLink is listed in the $listing[city] Hostel and Hotel guide on <a href=\"$WEB_URL\">Hostelz.com</a>.",
    			"$listingLink is now listed on <a href=\"$WEB_URL\">Hostelz.com</a> in the $listing[city] Hostels &amp; Hotels Guide.",
    			"$listingLink appears in <a href=\"$WEB_URL\">Hostelz.com's Hostels, Backpackers, and Hotels Directory</a>.",
    			"$listingLink is proudly listed in the <a href=\"$WEB_URL\">Hostelz.com Hostels / Hotels Guide</a>.",
    			"$listingLink is proud to appear in the <a href=\"$WEB_URL\">Hostelz.com</a> Hostel &amp; Hotel website.",
    			"<a href=\"$WEB_URL\">Hostelz.com</a> proudly lists $listingLink",
    		);

    	$text = $listingText[rand(0,count($listingText)-1)];

        if (rand(1,4) == 1) {
            $logo = "<a href=\"$WEB_URL\"><img src=\"$WEB_URL/images/hostelz-small.png\" height=".rand(17,21)." style=\"padding: 4px\"></a>".(rand(0,1) ? '<br>':'');
            $text = "$logo $text";
        }

    	switch(rand(0,10)) {
    		case 0:
    			$text = "<div style=\"border:$borderWidth$dotted #$border;font: $fontSize ${font}; color: #$color;margin:${margin}px;padding:${padding}px;background:#$background\">$text</div>";
    		break;
    		case 1:
    			$text = "<div style=\"font: $fontSize ${font}; color: #$color;margin:${margin}px; padding:${padding}px;background:#$background;border:$borderWidth$dotted #$border;\">$text</div>";
    		break;
    		case 2:
    			$text = "<span style=\"display:block;whitespace:nowrap color:#$color;margin:${margin}px; padding:${padding}px;\"><div style=\"font-size:$fontSize\">$text</div></span>";
    		break;
    		case 3:
    			$text = "<span style=\"display:block;whitespace:nowrap margin:${margin}px; padding:${padding}px;background:#$background; color:#$color;\"><font style=\"font-size:$fontSize\">$text</font></span>";
    		break;
    		case 4:
    			$text = "<table><tr><td style=\"border:$borderWidth$dotted #$border;font: $fontSize ${font}; color: #$color;margin:${margin}px;padding:${padding}px;background:#$background\">$text</table>";
    		break;
    		case 5:
    			$text = "<table style=\"color: #$color; border:$borderWidth$dotted #$border;font: $fontSize ${font}; margin:${margin}px; padding:${padding}px; background:#$background\"><tr><td>$text</table>";
    		break;
    		case 6:
    			$text = "<span style=\"display:block;border:$borderWidth$dotted #$border; margin:${margin}px; padding:${padding}px; \"><b><font style=\"font-size:$fontSize\">$text</font></b></span>";
    		break;
    		case 7:
    			$text = "<div style=\"border:$borderWidth$dotted #$border; font: $fontSize ${font}; color: #$color; margin:${margin}px; padding:${padding}px; background:#$background\">$text</div>";
    		break;
    		case 8:
    			$text = "<div style=\"border:$borderWidth$dotted #$border;margin:${margin}px;background:#$background\"><span style=\"display:block;padding:${padding}px;font: $fontSize ${font}; color: #$color;\">$text</span></div>";
    		break;
    		case 9:
    			$text = "<div style=\"border:$borderWidth$dotted #$border;font: $fontSize ${font}; font-weight:700;color: #$color;margin:${margin}px;padding:${padding}px;background:#$background\">$text</div>";
    		break;
    		case 10:
    			$text = "<div style=\"border:$borderWidth$dotted #$border;font: $fontSize ${font}; color: #$color;margin:${margin}px;padding:${padding}px;background:#$background\">$text</div>";
    		break;
    	}
        */
    }

    private function ratings($justGetValidationStatus, $showValidation)
    {
        if ($justGetValidationStatus) {
            return true;
        }

        $formHandler = new FormHandler('Rating', Rating::fieldInfo('management'), $this->extraParameter, 'App\Models');
        $formHandler->query = Rating::areLive()->where('hostelID', $this->listing->id);
        $formHandler->logChangesAsCategory = 'management';
        $formHandler->listPaginateItems = 15;
        $formHandler->listSelectFields = ['name', 'rating', 'summary', 'comment', 'ownerResponse'];
        $formHandler->listSort['commentDate'] = 'desc';

        $formHandler->go(null, ['list', 'updateForm', 'update']);
        if ($formHandler->mode == 'update') {
            $this->showGenericUpdateSuccessPage = true;
        }

        return $formHandler->display($this->view);
    }

    private function reviews($justGetValidationStatus, $showValidation)
    {
        if ($justGetValidationStatus) {
            return true;
        }

        $formHandler = new FormHandler('Review', Review::fieldInfo('management'), $this->extraParameter, 'App\Models');
        $formHandler->query = Review::whereIn('status', ['publishedReview', 'postAsRating'])->where('hostelID', $this->listing->id);
        $formHandler->logChangesAsCategory = 'management';
        $formHandler->listPaginateItems = 15;
        $formHandler->listSelectFields = ['reviewDate', 'editedReview', 'ownerResponse'];
        $formHandler->listSort['reviewDate'] = 'desc';

        $formHandler->go(null, ['list', 'updateForm', 'update']);
        if ($formHandler->mode == 'update') {
            $this->showGenericUpdateSuccessPage = true;
        }

        return $formHandler->display($this->view);
    }

    private function bookings($justGetValidationStatus, $showValidation): void
    {
    }

    private function previewListing()
    {
        return view($this->view, with(new ListingDisplay($this->listing))->getListingViewData(false));
    }
}
