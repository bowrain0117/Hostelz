<?php

namespace App\Helpers;

use App\Models\ContinentInfo;
use App\Models\Languages;
use App\Models\Listing\Listing;
use Carbon\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

class LegacyWebsite
{
    /*
    // [LEGACY] (delete after the new site is up)
    public static function clearOldLiveWebsiteListingCache($listingID)
    {
        if (App::environment('local', 'testing')) return;
    	$result = file_get_contents("https://secure.hostelz.com/staff/api.php?cmd=clearListingCache&listingID=$listingID");
    	if (trim($result) != 'ok') logError("Error clearing old live website cache.");
    }

    // [LEGACY] (delete after the new site is up)
    public static function clearOldLiveWebsiteCityCache($cityID)
    {
        if (App::environment('local', 'testing')) return;
    	$result = file_get_contents("https://secure.hostelz.com/staff/api.php?cmd=clearCityCache&cityID=$cityID");
    	if (trim($result) != 'ok') logError("Error clearing old live website cache.");
    }
    */

    public static function findContinentByTranslatedURL($slug)
    {
        // (we only check the current language)
        $translatedContinents = langGet('continents');

        //  remove arrays from items
        if (is_array($translatedContinents)) {
            $translatedContinents = array_filter($translatedContinents, fn ($item) => ! is_array($item));
        }

        $continentKey = array_search($slug, str_replace([' '], '-', $translatedContinents));

        return ContinentInfo::findByBumpyCase($continentKey);
    }

    public static function checkForOldURLs($request)
    {
        $script = Languages::current()->removeUrlPrefix(Request::getPathInfo());
        $pathParts = explode('/', trim($script, '/'));
        $scriptBaseName = reset($pathParts);
        $query = Request::query();

        switch ($scriptBaseName) {
            case 'display.php': // really old listing pages
                if (! isset($pathParts[1])) {
                    break;
                }
                $temp = explode('+', $pathParts[1]);
                $listingID = intval($temp[0]);
                if (! $listingID) {
                    break;
                }
                $listing = Listing::areNotListingCorrection()->where('id', $listingID)->first();
                if (! $listing) {
                    break;
                }

                return self::redirectTo($listing->getURL());

            case 'reservations.php':
            case 'booking.php':
                $listingID = $query['listingID'] ?? null;
                if (! $listingID) {
                    $listingID = $query['hostelID'] ?? null;
                }
                if (! $listingID) {
                    $listingID = $query['id'] ?? null;
                }
                if (! $listingID) {
                    break;
                }
                $listing = Listing::areNotListingCorrection()->where('id', $listingID)->first();
                if (! $listing) {
                    break;
                }

                return self::redirectTo($listing->getURL());

            case 'view.php': // really old city pages
            case 'cities.php': // really old city pages
                // "United+States_Utah_Salt+Lake+City_Hostels"
                if (! isset($pathParts[1])) {
                    break;
                }
                $temp = explode('_', str_replace('+', '-', trim($pathParts[1], '_')));

                if (count($temp) === 4) {
                    [$country, $region, $city] = $temp;
                    if ($country === 'United-States') {
                        $country = 'USA';
                    }

                    if (empty($city)) {
                        $city = null;
                    }

                    if (empty($region)) {
                        $region = $city;
                        $city = '';
                    }

                    return redirect()->route('city', compact('country', 'region', 'city'), 301); // so we only have one redirect at most
                } elseif (count($temp) === 2 || count($temp) === 3) {
                    [$country, $region] = $temp;
                    if ($country === 'United-States') {
                        $country = 'USA';
                    }

                    if (empty($region)) {
                        $region = null;
                    }

                    return redirect()->route('cities', ['country' => $country, 'cityOrRegion' => $region], 301); // so we only have one redirect at most
                } elseif (count($temp) === 1) {
                    $country = $temp[0];
                    if (str_starts_with($country, 'listing')) {
                        // http://www.hostelz.com/view.php/listing2382+HI-Pigeon+Point+Lighthouse+Pescadero+California
                        $temp = explode('-', substr($country, 7));
                        $listingID = intval($temp[0]);
                        if (! $listingID) {
                            break;
                        }
                        $listing = Listing::areNotListingCorrection()->where('id', $listingID)->first();
                        if (! $listing) {
                            break;
                        }

                        return self::redirectTo($listing->getURL());
                    }
                    if ($country === 'United-States') {
                        $country = 'USA';
                    }

                    return redirect()->route('cities', ['country' => $country, 'cityOrRegion' => null], 301); // so we only have one redirect at most
                } else {
                    logError("Can't decode '$script'.");
                    App::abort(404);
                }
        }

        switch ($script) {
            /*
            todo:
               bookingSignup.php
                hostel-group-booking.php
                moreComments.php
                poll.php
                travel-writing.php
            */

            // Someone is still hot linking to our old map.php maps, so this script sends them a hostelz.com logo.
            case '/comment.php':
                if (Request::has('cID')) {
                    return self::redirectTo(routeURL('verifyRating', [Request::input('cID'), Request::input('code')]));
                } else {
                    logWarning('LegacyWebsite /comment.php; request data:' . json_encode($request->all()));

                    return self::redirectTo(routeURL('afterBookingRating', [Request::input('bID'), Request::input('code')]));
                }

                // no break
            case '/about.php':
                return self::redirectToRoute('about');

            case '/submitListing.php':
                return self::redirectToRoute('submitNewListing');

            case '/affiliate-program.php':
                return self::redirectToRoute('affiliateSignup');

            case '/contact.php':
            case '/precontact.php':
                return self::redirectToRoute('contact-us');

            case '/hi.php':
                return self::redirectToRoute('hi');

            case '/hi-usa.php':
                return self::redirectToRoute('hi-usa');

            case '/hostel-recommendations.php':
                return self::redirectToRoute('articles', 'hostel-owner-suggestions');

                /* (google had some links to our old search pages. best if they see these low quality content pages as not found errors)
                case '/hostels.php':
                    return self::redirectToRoute('search', [ ], $query);
                */

            case '/privacy.php':
                return self::redirectToRoute('privacy-policy');

            case '/linkToUs.php':
                return self::redirectToRoute('linkToUs');

                // Someone is still hot linking to our old map.php maps, so this script sends them a hostelz.com logo.
            case '/map.php':
                return self::redirectTo(routeURL('images', 'logo-header.png'));

            case '/newReviewer.php':
            case '/paid-reviewer.php':
                return self::redirectToRoute('paidReviewerInfo');

            case '/packing/what-to-pack.php': // really old link to the packing article
                return self::redirectToRoute('articles', 'what-to-pack');

                /*
                case '/paid-reviewer': // this was for secure.hostelz.com i think.  no longer needed?
                    return self::redirectToRoute('paidReviewerSignup');
                */

            default:
                // log the missing page (temp)
                if (isset($_SERVER['REMOTE_ADDR'])) {
                    file_put_contents(storage_path('logs/not-found-errors.log'), Carbon::now() . ' [ip ' . $_SERVER['REMOTE_ADDR'] .
                         '] "query ' . Request::getPathInfo() . '" ' . ($_SERVER['HTTP_USER_AGENT'] ?? '') . "\n", FILE_APPEND);
                }

                return null;
        }
    }

    private static function redirectToRoute($route, $parameters = [], $queryVariables = [])
    {
        $url = routeURL($route, $parameters, 'absolute');
        if ($queryVariables) {
            $url .= '?' . http_build_query($queryVariables);
        }

        return self::redirectTo($url);
    }

    private static function redirectTo($url)
    {
        return redirect($url, 301);
    }
}
