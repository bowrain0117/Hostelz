<?php

namespace App\Http\Controllers\Listings;

use App\Helpers\ListingAndCitySearch;
use App\Helpers\ListingDisplay;
use App\Http\Controllers\Controller;
use App\Models\Ad;
use App\Models\CityComment;
use App\Models\CityInfo;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Traits\Redirect as RedirectTrait;
use Exception;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Lib\Captcha;
use Lib\FormHandler;
use Lib\GeoBounds;
use Lib\PageCache;

class ListingsController extends Controller
{
    use RedirectTrait;

    public function getCityAd($cityID)
    {
        $cityInfo = CityInfo::areLive()->where('id', $cityID)->first();
        if (! $cityInfo) {
            return '';
        }
        $ad = Ad::getAdForCity($cityInfo);
        if ($ad) {
            $adText = view('partials/_sidebarAd', compact('ad'));
        } else {
            $adText = '';
        }

        return setCorsHeadersToAllowOurSubdomains(Response::make($adText), false);
    }

    public function submitCityComment($cityID)
    {
        if (! auth()->check()) {
            $captcha = new Captcha();
            if (Request::isMethod('post') && Request::has('data.name') && ! $captcha->verify()) {
                return view('captchaError');
            }
            View::share('captcha', $captcha);
        }

        $cityInfo = CityInfo::areLive()->where('id', $cityID)->first();
        if (! $cityInfo) {
            abort(404);
        }

        $fakeInsert = false; // not currently using, but could be used to make it look to spammers like their spamming is working.

        $fieldInfo = CityComment::fieldInfo('submitComment');
        if (auth()->check()) {
            $fieldInfo['name']['type'] = 'display';
        }

        $formHandler = new FormHandler('CityComment', $fieldInfo, null, 'App\Models');
        $formHandler->allowedModes = ['insertForm', 'insert'];

        $defaultName = (auth()->check() ? auth()->user()->getNicknameOrName() : '');
        $formHandler->defaultInputData = [
            'name' => $defaultName,
        ];

        $formHandler->callbacks = [
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use ($cityInfo, $fieldInfo, $defaultName) {
                $formHandler->model->status = 'new';
                $formHandler->model->name = $defaultName;
                $formHandler->model->cityID = $cityInfo->id;
                $formHandler->model->userID = auth()->id();
                $formHandler->model->originalComment = $data['comment'];
                $formHandler->model->ipAddress = Request::ip();
                $formHandler->model->sessionID = Session::getId();
                $formHandler->model->language = Languages::currentCode();
                $formHandler->model->commentDate = date('Y-m-d');

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
        ];

        $formHandler->go();

        return $formHandler->display('submitCityComment', compact('cityInfo', 'fakeInsert'));
    }

    // Used to fetch some content of the listing as javascript.  (Mostly just used to hide data from Google, or to load data later for faster page loading.)

    public function listingFetchContent()
    {
        $listingID = (int) Request::input('listingID'); // Note: We use a get variables instead of including it in the path so it looks more like just one page not multiple to Google.
        $listing = Listing::areLive()->where('id', $listingID)->first();
        if (! $listing) {
            abort(404);
        }

        PageCache::addCacheTags('listing:' . $listingID); // mark the cache is it can be later cleared by listingID

        return (new ListingDisplay($listing))->listingFetchContent();
    }

    // Data that is specific to the current user and the current browsing session.

    public function listingDynamicData($listingID)
    {
        // (We use AreNotListingCorrection() because dynamic data is loaded even for not-live listings (listingNotLive pages).
        $listing = Listing::areNotListingCorrection()->where('id', $listingID)->first();
        if (! $listing) {
            abort(404);
        }

        return with(new ListingDisplay($listing))->listingDynamicData();
    }

    public function website($listingID)
    {
        $listing = Listing::find($listingID);

        if (! $listing || ! $listing->hasValidWebsite() || ! $listing->isLive() ||
            ! filter_var($listing->web, FILTER_VALIDATE_URL)) {
            abort(404);
        }

        // Affiliate Links

        if (stripos($listing->web, '.yha.org.uk') || stripos($listing->web, '://yha.org.uk')) {
            // (see http://affiliates.affiliatefuture.com/)
            $url = 'http://scripts.affiliatefuture.com/AFClick.asp?affiliateID=340802&merchantID=6750&programmeID=20765&mediaID=0&tracking=&url=' .
                urlencode($listing->web);
        } else {
            $url = $listing->web;
        }

        return view('listingWebsite', ['listing' => $listing, 'url' => $url]);
    }

    public function listingCorrection($listingID)
    {
        if (! auth()->check()) {
            $captcha = new Captcha();
            if (Request::isMethod('post') && ! $captcha->verify()) {
                return view('captchaError');
            }
            View::share('captcha', $captcha);
        }

        // note that it works for new non-live listings so that we can ask reviewers to update listings they've submitted or reviewed.
        $listing = Listing::areLiveOrNew()->where('id', $listingID)->first();
        if (! $listing) {
            abort(404);
        }

        $fakeInsert = false; // not currently using, but could be used to make it look to spammers like their spamming is working.

        $fieldInfo = Listing::fieldInfo('listingCorrection');
        $fieldInfo['comment']['fieldLabelLangKey'] = 'listingCorrection.comment';

        $formHandler = new FormHandler('Listing', $fieldInfo, null, 'App\Models\Listing');
        $formHandler->logChangesAsCategory = 'user';
        $formHandler->allowedModes = ['insertForm', 'insert'];

        $formHandler->defaultInputData = [
            'web' => $listing->web, 'tel' => $listing->tel, 'fax' => $listing->fax,
        ];

        $formHandler->callbacks = [
            'validate' => function ($formHandler, $useInputData, $fieldInfoElement) use ($listing, $fieldInfo, $fakeInsert) {
                $foundChange = false;
                foreach ($fieldInfo as $fieldName => $info) {
                    $existingValue = $listing->$fieldName;
                    $submittedValue = $formHandler->getFieldValue($fieldName, $useInputData);
                    if ((is_array($submittedValue) && ! $submittedValue) || (! is_array($submittedValue) && $submittedValue == '')) {
                        continue; // ignore any blank data fields
                    }
                    if ((is_array($submittedValue) && ! arraysHaveEquivalentValues($existingValue, $submittedValue)) || (! is_array($submittedValue) && $existingValue != $submittedValue)) {
                        $foundChange = true;
                    }
                }
                if (! $foundChange) {
                    return new MessageBag(['_general_' => langGet('listingCorrection.NoChangesMade')]);
                }

                return $formHandler->validate($useInputData, $fieldInfoElement, false);
            },
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use ($listing, $fieldInfo) {
                $formHandler->model->verified = Listing::$statusOptions['listingCorrection'];
                $formHandler->model->targetListing = $listing->id;
                $formHandler->model->lastEditSessionID = Session::getId();

                foreach ($fieldInfo as $fieldName => $info) {
                    $existingValue = $listing->$fieldName;
                    $submittedValue = $data[$fieldName];
                    if ((is_array($submittedValue) && ! $submittedValue) || (! is_array($submittedValue) && $submittedValue == '')) {
                        unset($data[$fieldName]); // ignore any blank data fields

                        continue;
                    }
                    if ((is_array($submittedValue) && ! arraysHaveEquivalentValues($existingValue, $submittedValue)) || (! is_array($submittedValue) && $existingValue != $submittedValue)) {
                        if (! is_array($submittedValue)) {
                            $data[$fieldName] = strip_tags($submittedValue);
                        } // don't allow html
                    } else {
                        unset($data[$fieldName]); // ignore any data that hasn't changed
                    }
                }

                if (isset($data['comment']) && $data['comment'] !== '') {
                    $data['comment'] = '"' . trim($data['comment']) . '"';
                } // put their comment in quotes

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
        ];

        $formHandler->go();

        return $formHandler->display('listingCorrection', compact('listing', 'fakeInsert'));
    }

    /*
        Input: search, field, context[ 'country' => ..., 'city' => '...', etc. ]
    */

    public function addressAutocomplete()
    {
        $allowedFields = ['country', 'region', 'cityGroup', 'city', 'cityAlt'];

        $search = Request::input('search');
        $field = Request::input('field');
        $contexts = Request::input('context');
        if (! in_array($field, $allowedFields)) {
            throw new Exception("Search field '$field' is unknown.");
        }
        if ($contexts && array_diff(array_keys($contexts), $allowedFields)) {
            throw new Exception('Context requested has unknown fields.');
        }

        if ($field == 'cityAlt') {
            PageCache::addCacheTags('listing:aggregation'); // mark the cache so it can by cleared when any city is altered
            $query = Listing::areLive()->where($field, 'LIKE', $search . '%');
        } else {
            PageCache::addCacheTags('city:aggregation'); // mark the cache so it can by cleared when any listing is altered
            $query = CityInfo::areLive()->where($field, 'LIKE', $search . '%');
        }

        if ($contexts) {
            foreach ($contexts as $contextField => $contextValue) {
                if ($contextValue != '') {
                    $query->where($contextField, $contextValue);
                }
            }
        }

        $result = $query->limit(10)->groupBy($field)->pluck($field);

        return Response::json(['suggestions' => $result]);
    }

    public function search()
    {
        $queryResultLimit = 100;
        $maxResultsPerType = 20;

        $search = trim(Request::input('search'));
        if ($search == '') {
            return redirect()->route('home');
        }

        $searchResults = ListingAndCitySearch::search($search, $queryResultLimit, $maxResultsPerType);

        // Count exact matches and redirect to it if there is only one
        $exactMatches = [];
        foreach ($searchResults as $type => $items) {
            foreach ($items as $item) {
                if (! strcasecmp($item['text'], $search)) { /* not sure why it also had (doesn't work with Australia, etc.) || !strcasecmp($item['extraText'], $search) */
                    $exactMatches[] = $item;
                } else {
                    break;
                } // because of the sort order, if the first one isn't an exact match, none in this group will be.
            }
        }
        debugOutput('Exact match count: ' . count($exactMatches));
        if (count($exactMatches) == 1) {
            return redirect($exactMatches[0]['url']);
        }

        PageCache::addCacheTags('city:aggregation'); // mark the cache so it can by cleared when any city is altered
        PageCache::addCacheTags('listing:aggregation'); // mark the cache so it can by cleared when any listing is altered

        return view('searchResults', compact('search', 'searchResults'));
    }

    public function submitNewListing($pathParameters = null)
    {
        if (! auth()->check()) {
            $captcha = new Captcha();
            if (Request::isMethod('post') && ! $captcha->verify()) {
                return view('captchaError');
            }
            View::share('captcha', $captcha);
        }

        $formHandler = new FormHandler('Listing', Listing::fieldInfo('submitListing'), $pathParameters, 'App\Models\Listing');
        $formHandler->logChangesAsCategory = 'user';
        $formHandler->allowedModes = ['insertForm', 'insert'];
        $formHandler->callbacks['setModelData'] = function ($formHandler, $data, &$dataTypeEventValues) {
            $formHandler->model->verified = Listing::$statusOptions['new'];
            $formHandler->model->lastEditSessionID = Session::getId();

            return $formHandler->setModelData($data, $dataTypeEventValues, false);
        };

        return $formHandler->go('submitNewListing');
    }

    public function cityMarkerPoints()
    {
        $boxInput = Request::input('box');
        if (! $boxInput) {
            return '';
        }
        $bounds = new GeoBounds($boxInput['swLatitude'], $boxInput['swLongitude'], $boxInput['neLatitude'], $boxInput['neLongitude']);

        if (! $bounds->isValid()) {
            logWarning('Invalid boundingBox: ' . json_encode($boxInput));

            return '';
        }

        $query = CityInfo::areLive()->haveLatitudeAndLongitude();

        if ($exceptCityIDs = Request::input('hostelsOnly')) {
            $query->where('hostelCount', '>', 0);
        }
        if ($exceptCityIDs = Request::input('exceptCityIDs')) {
            $query->whereNotIn('id', explode(',', $exceptCityIDs));
        }

        if ($exceptCityGroup = Request::input('exceptCityGroup')) {
            $exceptCountry = Request::input('exceptCountry');
            $query->where(function ($query) use ($exceptCityGroup, $exceptCountry): void {
                $query->where('cityGroup', '!=', $exceptCityGroup)->orWhere('country', '!=', $exceptCountry);
            });
        } elseif ($exceptRegion = Request::input('exceptRegion')) {
            $exceptCountry = Request::input('exceptCountry');
            $query->where(function ($query) use ($exceptRegion, $exceptCountry): void {
                $query->where('region', '!=', $exceptRegion)->orWhere('country', '!=', $exceptCountry);
            });
        } else {
            if ($exceptCountry = Request::input('exceptCountry')) {
                $query->where('country', '!=', $exceptCountry);
            }
        }

        $cities = $bounds->query($query)->get();
        $points = $cities->map(function ($city) {
            return [
                'url' => $city->getURL(),
                'cityName' => $city->translation()->city,
                'latitude' => round($city->latitude, 2),
                'longitude' => round($city->longitude, 2),
            ];
        });

        return Response::json(['points' => $points]);
    }
}
