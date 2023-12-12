<?php

namespace App\Http\Controllers;

use App;
use App\Booking\BookingRequest;
use App\Booking\BookingService;
use App\Booking\SearchCriteria;
use App\Enums\Listing\CategoryPage;
use App\Http\Requests\Comparison\ComparisonRequest;
use App\Models\BookingClick;
use App\Models\District;
use App\Models\Imported;
use App\Models\Listing\Listing;
use App\Services\ImportSystems\ImportSystems;
use Emailer;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Session;

class BookingsController extends Controller
{
    public function listingBookingSearch($listingID)
    {
        $listing = Listing::areLiveOrNew()->where('id', $listingID)->first();
        if (! $listing) {
            abort(404);
        }

        $searchCriteria = new SearchCriteria();
        $searchCriteria->bookingSearchFormFields(Request::all());
        $validationError = $searchCriteria->getValidationError();

        if ($validationError === null && $listing->hasImportSystemWithOnlineBooking(false)) {
            $availability = BookingService::getAvailabilityForListing($listing, $searchCriteria, true, 'single_compare');
            $rooms = BookingService::formatAvailableRoomsForDisplay($availability);
            $minimumNights = BookingService::getMinimumStay();
        } else {
            // null $rooms means the listing has no imported systems with online booking (unless $validationError is set)
            $rooms = null;
            $minimumNights = null;
        }

        $cityInfo = $listing->cityInfo;

        return setCorsHeadersToAllowOurSubdomains(Response::make(view(
            'bookings/_availableRooms',
            compact('listing', 'cityInfo', 'searchCriteria', 'validationError', 'rooms', 'minimumNights')
        )), false);
    }

    public function listingsBookingSearch(ComparisonRequest $request)
    {
        $listings = Listing::whereIn('id', $request->validated()['listingsId'])->get()->keyBy('id');

        $searchCriteria = new SearchCriteria();
        $searchCriteria->bookingSearchFormFields($request->all());
        $validationError = $searchCriteria->getValidationError();

        $rooms = $roomTypes = collect();

        if ($validationError === null) {
            $availability = BookingService::getAvailabilityForListings($listings, $searchCriteria, true, '');
            $rooms = BookingService::formatCompareListings($availability);
            $roomTypes = BookingService::getRoomTypes();
        }

        return setCorsHeadersToAllowOurSubdomains(Response::make(view(
            'comparison/comparePrices',
            compact('listings', 'searchCriteria', 'validationError', 'rooms', 'roomTypes')
        )), false);
    }

    /*
        Just display the outter page, which then calls this same URL again using AJAX and inserts that content
        (this is done so we can better handle any network timeout errors and avoid double bookings)
        We make the $listingID be part of the URL so that at least we know the listing if wehave to generate an error page if the rest of the data is bad.
        Input used: searchCriteria[], importedID, roomCode -> which is turned into a bookingRequestTrackingCode for subsequent pages.
    */

    public function bookingRequestOuterPage($listingID, $bookingRequestTrackingCode = '')
    {
        $listing = Listing::areLiveOrNew()->where('id', $listingID)->first();
        if (! $listing) {
            return $this->errorPage($listing);
        }

        if ($bookingRequestTrackingCode == '') {
            return $this->bookingRequestGenerateBookingRequest($listing);
        }

        return Response::make(view('bookings/bookingRequest', compact('listing')))->setMaxAge(60 * 60); // (outter page can be cached for a long time)
    }

    public function bookingRequestInnerContent($listingID, $bookingRequestTrackingCode = '')
    {
        $bookingRequest = BookingRequest::getFromCache($bookingRequestTrackingCode);
        if (! $bookingRequest) {
            return $this->contentErrorPage();
        }
        // echo "<pre>"; print_r($bookingRequest); echo "</pre>";

        return preventBrowserCaching(Response::make(view('bookings/_bookingRequestForm', compact(/* 'listing',*/ 'bookingRequest'))));
    }

    private function bookingRequestGenerateBookingRequest($listing)
    {
        // * Prep the Input *

        $searchCriteria = new SearchCriteria();
        $searchCriteria->bookingSearchFormFields(Request::all());
        $validationError = $searchCriteria->getValidationError();
        if ($validationError) {
            logWarning('Booking request page search criteria validation error.');

            return $this->errorPage($listing);
        }
        $importedID = Request::input('importedID');
        $roomCode = Request::input('roomCode');

        // * Check for cached BookingRequest *

        // (this puts the tracking code in a cache just so we don't generate a new booking proposal if the user clicks on the same room twice in a row)
        $bookingRequestTrackingCodeCacheKey = "bookingRequestGenerateBookingRequest:$importedID:$roomCode:" . $searchCriteria->hashValue() . ':' . Session::getId();
        $bookingRequestTrackingCode = Cache::get($bookingRequestTrackingCodeCacheKey);
        if ($bookingRequestTrackingCode) {
            $bookingRequest = BookingRequest::getFromCache($bookingRequestTrackingCode);
            // Don't use it if it expired or it was from a booking that was already submitted, or if it had errors
            // (in which case it's probably best to try to get a new proposal) ...
            if (! $bookingRequest || $bookingRequest->bookingSubmitStatus != '' || $bookingRequest->fatalErrors) {
                $bookingRequestTrackingCode = null;
            }
        }

        if (! $bookingRequestTrackingCode) {
            // * Generate a New BookingRequest *

            // We need to get the RoomAvailability information in case it's useful for getBookingRequest() (usually it just gets fetched from the cache)
            $roomAvailability = BookingService::findRoomAvailabilityByRoomCode($listing, Request::input('importedID'), $searchCriteria, Request::input('roomCode'));
            if (! $roomAvailability) {
                logWarning("Booking request page can't find matching room availability (cache expired and the room became unavailable?).");

                return $this->errorPage($listing);
            }
            if (! $roomAvailability->isValid()) {
                return $this->errorPage($listing);
            } // (isValid() reports its own warnings)

            $systemClassName = $roomAvailability->imported()->getImportSystem()->getSystemService();
            $bookingRequest = $systemClassName::getBookingRequest($roomAvailability);
            if (! $bookingRequest) {
                return $this->errorPage($listing);
            }
            $bookingRequest->fillInMissingDataAndValidate($roomAvailability);
            $bookingRequestTrackingCode = $bookingRequest->saveToCache();

            // Our cache time of the tracking code should be less than any booking system's user token expiration,
            // and really it just needs to be long enough to use the same booking proposal if they click twice in a row.
            Cache::put($bookingRequestTrackingCodeCacheKey, $bookingRequestTrackingCode, 300);
        }

        return redirect()->route('bookingRequest', ['listingID' => $listing->id, 'bookingRequestTrackingCode' => $bookingRequestTrackingCode]);
    }

    private function contentErrorPage($errorCode = 'misc', $errorMessage = '')
    {
        return view('bookings/_bookingRequestError', compact('errorCode', 'errorMessage'));
    }

    private function errorPage($listing = null, $errorCode = 'misc', $errorMessage = '')
    {
        return view('bookings/bookingRequest', compact('listing', 'errorCode', 'errorMessage'));
    }

    public function bookingSystemInvoice($system)
    {
        if (! ImportSystems::systemExists($system)) {
            abort(404);
        }
        $systemInfo = ImportSystems::findByName($system);
        if ($systemInfo->invoicePassword == '') {
            abort(404);
        }

        $viewData = [
            'systemInfo' => $systemInfo,
        ];

        if (Request::input('password') != $systemInfo->invoicePassword) {
            return view('user/bookingSystemInvoice', $viewData)->with('showInvoice', false);
        }

        Emailer::send(1, 'Hostelz.com Invoice for ' . $systemInfo->displayName, 'user/bookingSystemInvoice-email');

        return view('user/bookingSystemInvoice', $viewData)->with('showInvoice', true);
    }

    public function linkRedirect($importedID)
    {
        $imported = Imported::findOrFail($importedID);

        // (The original links are created by RoomAvailabilty::bookingPageLink().)

        $bookingLinkInfo = unobfuscateString(urldecode(Request::input('b')));
        if ($bookingLinkInfo == '') {
            return '';
        } // url was probably modified

        $importSystem = $imported->getImportSystem();

        $listing = $imported->listing()->first();
        $city = $imported->getImportSystem()->systemName === 'BookHostels' ? $imported->city : $listing->city;

        $linkLocation = $this->getLinkLocation(Session::get('availabilityLink'));

        $decodedBookingLinkInfo = $importSystem->getSystemService()::decodeBookingLinkInfo(
            $bookingLinkInfo,
            getCMPLabel($linkLocation, $city, $listing->name)
        );

        if (! $decodedBookingLinkInfo) {
            // logError("$imported->system couldn't decode \"$bookingLinkInfo\"");  (let the booking system's code report any errors instead)
            return view('error');
        }

        $trackingCode = Request::has('t') ? unobfuscateString(urldecode(Request::input('t'))) : '';
        BookingClick::recordClick($importedID, $trackingCode);

        return view(
            'bookings/linkRedirect',
            [
                'redirectURL' => $decodedBookingLinkInfo['url'],
                'importSystemName' => $importSystem->shortName(),
                'postVariables' => $decodedBookingLinkInfo['postVariables'] ?? null, // (post variables aren't actually implemented because we haven't needed them yet)
            ]
        );
    }

    public function linkStaticRedirect($importedID = null)
    {
        if ($importedID !== null) {
            $trackingCode = Request::has('t') ? unobfuscateString(urldecode(Request::input('t'))) : '';
            BookingClick::recordClick($importedID, $trackingCode);
        }

        $link = unobfuscateString(urldecode(Request::input('b')));
        $importSystemName = Request::input('system');

        return view('bookings/linkRedirect', ['redirectURL' => $link, 'importSystemName' => $importSystemName]);
    }

    public function getLinkLocation(string|null $url): string
    {
        if (is_null($url)) {
            return 'nu';
        }

        $route = app('router')
            ->getRoutes()
            ->match(app('request')->create($url));

        if ($route->getName() === 'hostel') {
            return 'single_compare';
        }

        if ($route->getName() !== 'city') {
            return 'ukn';
        }

        if ($category = CategoryPage::tryFrom($route->parameter('city'))) {
            return $category->keyValue();
        }

        $districtExists = District::query()
            ->byFullLocation(
                $route->parameter('country'),
                $route->parameter('region'),
                $route->parameter('city')
            )
            ->exists();
        if ($districtExists) {
            return 'district';
        }

        return 'city';
    }
}
