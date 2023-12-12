<?php

use App\Enums\CategorySlp;
use App\Http\Controllers\AffiliateController;
use App\Http\Controllers\AssetController;
use App\Http\Controllers\BookingsController;
use App\Http\Controllers\CheckImportController;
use App\Http\Controllers\CitiesController;
use App\Http\Controllers\City\CityController;
use App\Http\Controllers\City\CityListingsController;
use App\Http\Controllers\CodeEditorController;
use App\Http\Controllers\ComparisonController;
use App\Http\Controllers\ContinentController;
use App\Http\Controllers\ContinentsCitiesController;
use App\Http\Controllers\DataChecksController;
use App\Http\Controllers\DataMiningController;
use App\Http\Controllers\DevAccessController;
use App\Http\Controllers\DevController;
use App\Http\Controllers\Districts\StaffDistrictController;
use App\Http\Controllers\DocumentationController;
use App\Http\Controllers\ExploreSectionController;
use App\Http\Controllers\HostelsChainController;
use App\Http\Controllers\ImportSystemWebhooksController;
use App\Http\Controllers\IncomingLinksController;
use App\Http\Controllers\LanguageChangeController;
use App\Http\Controllers\Listings\ListingReviewsController;
use App\Http\Controllers\Listings\ListingsController;
use App\Http\Controllers\Listings\ListingsFilter;
use App\Http\Controllers\Listings\ListingShowController;
use App\Http\Controllers\MainPageShowController;
use App\Http\Controllers\MgmtController;
use App\Http\Controllers\MiscController;
use App\Http\Controllers\MoreHostelsController;
use App\Http\Controllers\PicFixController;
use App\Http\Controllers\QuestionsController;
use App\Http\Controllers\RedirectController;
use App\Http\Controllers\ReviewerController;
use App\Http\Controllers\SearchAutocompleteController;
use App\Http\Controllers\Slp\SlpController;
use App\Http\Controllers\Slp\StaffSlpController;
use App\Http\Controllers\Staff\AttachedTextStaffController;
use App\Http\Controllers\Staff\CheckAvailabilityListingStaffController;
use App\Http\Controllers\Staff\CityCategoryPageDescriptionStaffController;
use App\Http\Controllers\Staff\EditCityInfoStaffController;
use App\Http\Controllers\Staff\ImportsPageStaffController;
use App\Http\Controllers\Staff\UpdateImportedPicsStaffController;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\StaffListingsController;
use App\Http\Controllers\StaffMailController;
use App\Http\Controllers\SubmitRatingController;
use App\Http\Controllers\TempController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\UserPublicController;
use App\Http\Controllers\UserReservationsController;
use App\Http\Controllers\WishlistController;
use App\Models\Languages;
use Illuminate\Support\Facades\Route;

/*
** Routes
*/

// **
// ** Universal **
// **

// Used for Google Calendar importing of our AirBnb calendar (it doesn't work dirctly because of AirBnb's robots.txt)
//Route::any('airbnb-calendar-proxy', function() { return WebsiteTools::fetchPage('https://www.airbnb.com/calendar/ical/224603.ics?s=aa95f5ee66c9490a32d352955323dab5'); });

/* Paths to Static Assets in the Public Folder */

// The actual data files are actual files in the public folder.  This is here to define a named route to them.
// The controller method just returns a page not found error because the only time that really gets called is if the actual file doesn't exist.

Route::get('generated-css/{path}.css', [MiscController::class, 'pageNotFound'])->name('generated-css')->where('path', '.*'); // (to allow '/' in the path);
Route::get('images/{path}', [MiscController::class, 'pageNotFound'])->name('images')->where('path', '.*'); // (to allow '/' in the path);

Route::prefix(trim(Languages::current()->urlPrefix(), '/'))->group(function () {
    // **
    // ** Any domain, Langauge-specific, HTTP/HTTPS **
    // **

    // (js is language-specific because it needs to load auto-complete for the specific language)
    Route::get('js/{page}.js', [AssetController::class, 'js'])->name('js')->middleware('browserCache:7 days public', 'pageCache:indefinite');

    Route::get('search-autocomplete', SearchAutocompleteController::class)->name('searchAutocomplete')->middleware('browserCache:1 day public', 'pageCache:indefinite');
    Route::get('address-autocomplete', [ListingsController::class, 'addressAutocomplete'])->name('addressAutocomplete')->middleware('browserCache:1 day public', 'pageCache:indefinite');
});

Route::domain(config('custom.thisStaticDomain'))
    ->middleware('startSession')
    ->group(function () {
        // **
        // ** Static, Non-language, HTTP/HTTPS **
        // **

        Route::any('robots.txt', [MiscController::class, 'robotsTxtStaticDomain'])
            ->middleware('browserCache:1 day public', 'pageCache:indefinite')
            ->name('robots-txt');

        Route::prefix(trim(Languages::current()->urlPrefix(), '/'))->group(function () {
            // **
            // ** Static, Langauge-specific, HTTP/HTTPS **
            // **

            Route::any('/more-ratings/{id?}', function () {
                return redirect('/', 301);
            });

            /* Home */

            Route::get('/', MainPageShowController::class)->name('home')->middleware('browserCache:1 day public', 'pageCache:indefinite');

            /* Continents */

            Route::get('hostels-in', [ContinentsCitiesController::class, 'continent'])->name('allContinents')->middleware('browserCache:3 days public', 'pageCache:indefinite');
            Route::get('hostels-in/{continent}', [ContinentController::class, 'continent'])->name('continents')->middleware('browserCache:3 days public', 'pageCache:indefinite');

            /* Cities */

            Route::get('hostels-in/{country}/{cityOrRegion?}', CitiesController::class)
                ->name('cities')
                ->middleware('browserCache:3 days public', 'pageCache:indefinite');

            /* City/District/Categories */

            Route::get('hostels/{country}/{region?}/{city?}', [CityController::class, 'city'])
                ->name('city')
                ->middleware('browserCache:2 days public', 'pageCache:indefinite');

            // todo: temp, for old URLs. ends up getting redirected to /hostels
            Route::get('hotels/{country}/{region?}/{city?}', [CityController::class, 'city'])
                ->where('slug', '.*');

            Route::get('hostels-static-list/{cityID}/{mapMode}/{page}', [CityListingsController::class, 'cityListingsListStatic'])
                ->name('staticCityListingsListContent')
                ->middleware('browserCache:1 day public', 'pageCache:indefinite', 'noindexRobots');

            Route::get('hostels-city-filters/{cityID}', [ListingsFilter::class, 'show'])
                ->middleware('browserCache:1 day public', 'pageCache:indefinite', 'blockRobots');

            // Special Landing Pages

            Route::controller(SlpController::class)
                ->middleware(['web'])
                ->group(function () {
                    // show
                    Route::get('/party-hostels-in-{slug}', 'partyShow')
                        ->name('slp.show.' . CategorySlp::Party->value)
                        ->where('slug', '[a-z\-]+');

                    Route::get('/hostels-in-{slug}-with-private-rooms', 'privateShow')
                        ->name('slp.show.' . CategorySlp::Private->value)
                        ->where('slug', '[a-z\-]+');

                    Route::get('/best-hostels-{slug}', 'bestShow')
                        ->name('slp.show.' . CategorySlp::Best->value)
                        ->where('slug', '[a-z\-]+');

                    Route::get('/cheapest-hostels-in-{slug}', 'cheapShow')
                        ->name('slp.show.' . CategorySlp::Cheap->value)
                        ->where('slug', '[a-z\-]+');

                    // idex
                    Route::get('/party-hostels', 'partyIndex')
                        ->name('slp.index.' . CategorySlp::Party->value)
                        ->whereIn('category', CategorySlp::values()->toArray());

                    Route::get('/best-hostels', 'bestIndex')
                        ->name('slp.index.' . CategorySlp::Best->value)
                        ->whereIn('category', CategorySlp::values()->toArray());

                    Route::get('/hostels-with-private-rooms', 'privateIndex')
                        ->name('slp.index.' . CategorySlp::Private->value)
                        ->whereIn('category', CategorySlp::values()->toArray());

                    Route::get('/cheapest-hostels', 'cheapIndex')
                        ->name('slp.index.' . CategorySlp::Cheap->value)
                        ->whereIn('category', CategorySlp::values()->toArray());
                });

            /* Listing */

            Route::get('hostel/{slug}', ListingShowController::class)
                ->name('hostel')
                ->middleware('browserCache:5 days public', 'pageCache:indefinite');

            Route::get('hotel/{slug}', ListingShowController::class)->name('hotel');

            Route::get('listing-website/{listingID}', [ListingsController::class, 'website'])->name('listing-website')->where('listingID', '[0-9]+');

            Route::get('listing-reviews/{listing}', ListingReviewsController::class)->name('listingReviews');

            /* Mapping Dynamic (AJAX) Data */

            Route::get('city-marker', [ListingsController::class, 'cityMarkerPoints'])->name('cityMarkerPoints');
            Route::get('user/frontUserData', [UserController::class, 'frontUserData'])
                ->name('user:frontUserData')
                ->middleware('web', 'startSession', 'blockRobots');

            Route::post('user/userSearch', [UserController::class, 'searchHistory'])
                ->name('user:searchHistory')
                ->middleware('web', 'startSession');

            Route::post('/flogin', [UserController::class, 'login'])
                ->name('loginFrontend')
                ->middleware('csrf', 'throttle:8,1', 'blockCountries');

            Route::any('city/explore-section/{cityInfo}', ExploreSectionController::class)
                ->name('getExploreSection')
                ->middleware('browserCache:2 days public', 'pageCache:indefinite', 'noindexRobots');

            Route::any('listing/more-hostels/{listing}', MoreHostelsController::class)
                ->name('getMoreHostels')
                ->middleware('browserCache:2 days public', 'pageCache:indefinite', 'noindexRobots');

            Route::any('articles/getText/{article}', [MiscController::class, 'getArticleText'])
                ->name('getArticleText')
                ->middleware('web', 'startSession');

            Route::any('articles/getArticleCategoryText/{slug}', [MiscController::class, 'getArticleCategoryText'])
                ->name('getArticleCategoryText')
                ->middleware('web', 'startSession');

            /* Comparison */
            Route::
            middleware(['web', 'startSession'])
                ->controller(ComparisonController::class)
                ->prefix('compare')
                ->name('comparison')
                ->group(function () {
                    Route::get('/', 'index');
                    Route::get('/{listingsId}', 'show')->name('.show');
                    Route::post('/{listingId}', 'update')->name('.update');
                    Route::delete('/{listingId}', 'destroy')->name('.delete');
                });

            /*  Users Page  */

            Route::get('user/{user:slug}', [UserPublicController::class, 'show'])->name('userPublic:show');

            /* Misc */

            Route::any('search', [ListingsController::class, 'search'])->name('search')->middleware('browserCache:1 hour public', 'pageCache:indefinite');
            Route::any('paid-reviewer', [UserController::class, 'paidReviewerInfo'])->name('paidReviewerInfo');

            /* routes for static pages ( about, faq, contacts, ... ) */

            \App\Http\Controllers\MiscController::staticPageRouts();

            /*  Wishlists (ajax)  */

            Route::get('wishlists', [WishlistController::class, 'index'])
                ->name('wishlist:index')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'blockRobots');

            Route::get('wishlists/userLists', [WishlistController::class, 'userLists'])
                ->name('wishlist:userLists')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'blockRobots');

            Route::get('wishlists/isActive', [WishlistController::class, 'isActive'])
                ->name('wishlist:isActive')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'blockRobots');

            Route::post('wishlists/{wishlist}/listing/{listing}', [WishlistController::class, 'addListing'])
                ->name('wishlist:addListing')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'blockRobots');

            Route::delete('wishlists/listing/{listing}', [WishlistController::class, 'deleteListing'])
                ->name('wishlist:deleteListing')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'blockRobots');

            Route::get('wishlists/{wishlist}', [WishlistController::class, 'show'])
                ->name('wishlist:show')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'blockRobots');

            Route::delete('wishlists/{wishlist}', [WishlistController::class, 'destroy'])
                ->name('wishlist:destroy')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'csrf', 'blockRobots');

            Route::post('wishlists', [WishlistController::class, 'store'])
                ->name('wishlist:store')
                ->middleware('web', 'startSession', 'anyLoggedInUser', 'csrf', 'blockRobots');

            /*  Hostels Chain   */

            Route::get('hostel-chains', [HostelsChainController::class, 'index'])
                ->name('hostelChain:index');
            Route::get('hostel-chains/{hostelChain}', [HostelsChainController::class, 'show'])
                ->name('hostelChain:show');
        });
    });

Route::domain(config('custom.thisDynamicDomain'))
    ->middleware(['startSession', 'blockRobots'])
    ->group(function () {
        // todo: fixe!!
        Route::get('user/frontUserData', [UserController::class, 'frontUserData'])->middleware('web', 'startSession');

        Route::any('articles/getText/{article}', [MiscController::class, 'getArticleText'])->name('getArticleTextAdmin')->middleware('web', 'startSession');

        // **
        // ** Dynamic, Non-language, HTTP/HTTPS **
        // **

        Route::get('robots.txt', [MiscController::class, 'robotsTxtDynamicDomain'])->middleware('browserCache:1 day public', 'pageCache:indefinite');

        Route::prefix(trim(Languages::current()->urlPrefix(), '/'))
            ->group(function () {
                // **
                // ** Dynamic, Langauge-specific, HTTP/HTTPS **
                // **

                Route::any('city-comment/{cityID}', [ListingsController::class, 'submitCityComment'])->name('submitCityComment');
                Route::any('submit-rating/{listingID}', [SubmitRatingController::class, 'submitRating'])->name('submitRating')->middleware('blockCountries');
                Route::any('verify-rating/{ratingID}/{verificationCode}', [SubmitRatingController::class, 'verifyRating'])->name('verifyRating');
                Route::any('booking-rating/{bookingID}/{verificationCode}', [SubmitRatingController::class, 'afterBookingRating'])->name('afterBookingRating');
                Route::get('listing-booking-search/{listingID}', [BookingsController::class, 'listingBookingSearch'])->name('listingBookingSearch')->middleware('browserCache:10 minutes public');
                Route::get('listings-booking-search/{listingsId}', [BookingsController::class, 'listingsBookingSearch'])->name('listingsBookingSearch')->middleware('browserCache:10 minutes public');
                Route::get('booking-redirect/{importedID}', [BookingsController::class, 'linkRedirect'])->name('bookings-linkRedirect');
                Route::get('booking-static-redirect/{importedID?}', [BookingsController::class, 'linkStaticRedirect'])->name('bookings-linkStaticRedirect');
                Route::any('new-listing', [ListingsController::class, 'submitNewListing'])->name('submitNewListing')->middleware('browserCache:1 day public');
                Route::any('contact/{reason?}/{contactType?}', [MiscController::class, 'contactUs'])->name('contact-us')->middleware('csrf'); // (use contact/contact-form to go directly to the contact form)
                // (served from the dynamic server for plausible deniability about why it's blocked from robots.txt,
                // since the entire dynamic server is blocked) -> todo: move it back to the static server? Google is ok with blocking content from external sources (licensing issues, etc.)
                Route::get('listing-fetch', [ListingsController::class, 'listingFetchContent'])->name('listingFetchContent')->middleware('browserCache:5 days public', 'pageCache:indefinite');
                Route::any('listing-correction/{listingID}', [ListingsController::class, 'listingCorrection'])->name('listingCorrection');

                Route::get('listings-list/{cityID}', [CityListingsController::class, 'cityListingsListDynamic'])
                    ->name('listingsListContent')
                    ->middleware('browserCache:60 minutes public', 'startSession', 'web'); // could also use a short pageCache (15 min?), but probably not worth bothering with.

                // Ads
                // (best if the actual URL doesn't say "ad" to avoid getting ad-blocked)
                Route::get('city-alink/{cityID}', [ListingsController::class, 'getCityAd'])
                    ->name('getCityAd')->middleware('blockRobots');

                Route::get('alink-click/{adID}', [MiscController::class, 'adClick'])->name('adClick');

                Route::group([config('custom.adminGroupRoute', 'https')], function () {
                    // **
                    // ** Dynamic, Langauge-specific, HTTPS Only **
                    // **

                    Route::any('booking-request/{listingID}/{bookingRequestTrackingCode?}', [BookingsController::class, 'bookingRequestOuterPage'])->name('bookingRequest')->middleware('csrf');
                    Route::any('booking-request-content/{listingID}/{bookingRequestTrackingCode?}', [BookingsController::class, 'bookingRequestInnerContent'])->name('bookingRequestInnerContent')->middleware('csrf'); // should be csrf?
                    Route::any('article-preview/{articleID}/{verificationCode}', [MiscController::class, 'articlePrivatePreview'])->name('article-private-preview');

                    /* City/Listings Dynamic User-specific Content */

                    Route::get('listing-dynamic/{listingID}', [ListingsController::class, 'listingDynamicData'])->name('listingDynamicData');

                    /* Login */

                    Route::prefix('login')->middleware('throttle:8,1', 'blockCountries')->group(function () {
                        Route::any('/', [UserController::class, 'login'])->name('login');
                        Route::any('forgot/{emailVerificationToken?}', [UserController::class, 'forgotLogin'])->name('login-forgot')->middleware('csrf');
                    });

                    Route::get('logout', [UserController::class, 'logout'])->name('logout');

                    /* Sign-ups */

                    Route::any('signup/{emailVerificationToken?}', [UserController::class, 'userSignup'])->name('userSignup')->middleware('csrf', 'blockCountries');
                    Route::any('mgmt-signup/{emailVerificationToken?}', [UserController::class, 'listingMgmtSignup'])->name('listingMgmtSignup')->middleware('csrf', 'blockCountries');
                    // Note: There is also a 'paidReviewerInfo' page on the www site that is visible to search engines and is static.
                    Route::any('paid-reviewer-signup/{emailVerificationToken?}', [UserController::class, 'paidReviewerSignup'])->name('paidReviewerSignup')->middleware('csrf', 'blockCountries');
                    Route::any('affiliate-signup/{emailVerificationToken?}', [UserController::class, 'affiliateSignup'])->name('affiliateSignup')->middleware('csrf', 'blockCountries');

                    // For some user settings actions that don't need the user to be logged in (such as clicking on an email change confirmation email)
                    Route::any('user-settings/{userAction}', [UserController::class, 'userSettingsNonLoggedInActions'])->name('userSettingsNonLoggedIn');

                    /* User Menu */

                    Route::prefix('user')
                        ->middleware('anyLoggedInUser')
                        ->group(function () {
                            Route::any('/', [UserController::class, 'index'])->name('user:menu');

                            Route::any('settings/{userAction}', [UserController::class, 'userSettings'])->name('user:settings')->middleware('csrf');

                            Route::any('your-pay', [UserController::class, 'yourPay'])->name('user:yourPay')->middleware('csrf');

                            Route::prefix('reviewer')->middleware('userHasPermission:reviewer')->group(function () {
                                Route::any('instructions', [ReviewerController::class, 'reviewerInstructions'])->name('reviewer:instructions');
                                Route::any('find-listings/{listingID?}', [ReviewerController::class, 'findListingsToReview'])->name('reviewer:findListingsToReview');
                                Route::any('new-listing', [ReviewerController::class, 'submitNewListing'])->name('reviewer:submitNewListing');
                                Route::any('review-photos/{reviewID}', [ReviewerController::class, 'reviewPics'])->name('reviewer:reviewPics');
                                Route::any('/{pathParameters?}', [ReviewerController::class, 'reviews'])->name('reviewer:reviews');
                            });

                            Route::prefix('place-descriptions')->middleware('userHasAnyPermissionOf:placeDescriptionWriter')->group(function () {
                                Route::any('instructions', [ReviewerController::class, 'placeDescriptionInstructions'])->name('placeDescriptions:instructions');
                                Route::any('find/{listingID?}', [ReviewerController::class, 'findCities'])->name('placeDescriptions:findCities');
                                Route::any('/{pathParameters?}', [ReviewerController::class, 'placeDescriptions'])->name('placeDescriptions');
                            });

                            Route::any('city-photos-find', [ReviewerController::class, 'submitCityPicsFindCity'])->name('submitCityPicsFindCity');

                            Route::any('city-photos/{cityID}', [ReviewerController::class, 'submitCityPics'])->name('submitCityPics');

                            Route::prefix('affiliate')->middleware('userHasPermission:affiliate')->group(function () {
                                Route::any('/', [AffiliateController::class, 'index'])->name('affiliate:menu');
                                Route::any('edit-urls', [AffiliateController::class, 'editURLs'])->name('affiliate:editURLs');
                            });

                            Route::get('reservations', [UserReservationsController::class, 'index'])
                                ->name('user:reservations');
                        });

                    /* Mgmt */

                    Route::prefix('mgmt')->middleware('anyLoggedInUser')->group(function () {
                        Route::any('/', [MgmtController::class, 'index'])->name('mgmt:menu');
                        Route::any('listings/{listingID}/{listingAction}/{extraParameter?}', [MgmtController::class, 'listingManage'])->middleware('csrf')->name('mgmt-listing-manage')
                            ->where('listingID', '[0-9]+');
                        Route::any('payment-method', [MgmtController::class, 'paymentMethod'])->middleware('csrf')->name('mgmt-payment-method');
                        Route::any('featured/{listing}', [MgmtController::class, 'featureListing'])->middleware('csrf')->name('mgmt-feature-listing');
                    });

                    /* Staff */

                    Route::prefix('staff')->middleware('csrf', 'userHasPermission:staff')->group(function () {
                        Route::any('/', [StaffController::class, 'index'])->name('staff-menu');

                        Route::get('logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->name('staff-laravel-logs')->middleware('userHasPermission:admin');

                        // Database (various)

                        Route::any('language-strings/{pathParameters?}', [StaffController::class, 'languageStrings'])->name('staff-languageStrings')->middleware('userHasPermission:admin');
                        Route::any('imported/{pathParameters?}', [StaffController::class, 'imported'])->name('staff-importeds')->middleware('userHasPermission:staffEditHostels');
                        Route::get('imports', ImportsPageStaffController::class)->name('staff-imports')->middleware('userHasPermission:admin');
                        Route::any('imported-import/{systemName?}', [StaffController::class, 'importedImport'])->name('staff-importedImport')->middleware('userHasPermission:admin');
                        Route::any('imported-name-changes', [StaffController::class, 'importedNameChanges'])->name('staff-importedNameChanges')->middleware('userHasPermission:admin');
                        Route::any('bookings/{pathParameters?}', [StaffController::class, 'bookings'])->name('staff-bookings')->middleware('userHasPermission:staffBookings');

                        Route::any('attached-text/{pathParameters?}', AttachedTextStaffController::class)
                            ->name('staff-attachedTexts')
                            ->middleware('userHasPermission:staff');

                        Route::any('place-description-instructions', [StaffController::class, 'placeDescriptionEditingInstructions'])->name('staff-placeDescriptionEditingInstructions');
                        Route::any('event-log/{pathParameters?}', [StaffController::class, 'eventLog'])->name('staff-eventLogs');
                        Route::any('ratings/{pathParameters?}', [StaffController::class, 'ratings'])->name('staff-ratings')->middleware('userHasPermission:staffEditComments');
                        Route::any('city-comments/{pathParameters?}', [StaffController::class, 'cityComments'])->name('staff-cityComments')->middleware('userHasPermission:staffEditCityComments');
                        Route::any('pics/{pathParameters?}', [StaffController::class, 'pics'])->name('staff-pics')->middleware('userHasPermission:staffPicEdit');
                        Route::any('articles/{pathParameters?}', [StaffController::class, 'articles'])->name('staff-articles')->middleware('userHasPermission:admin');
                        Route::any('reviews/{pathParameters?}', [StaffController::class, 'reviews'])->name('staff-reviews')->middleware('userHasPermission:staffEditReviews');

                        Route::any('city-info/{pathParameters?}', EditCityInfoStaffController::class)
                            ->name('staff-cityInfos')
                            ->middleware('userHasPermission:staffCityInfo');

                        Route::any('city-info-pics/{cityID}', [StaffController::class, 'cityInfoPics'])->name('staff-cityInfo-pics')->middleware('userHasPermission:staffCityInfo');
                        Route::any('country-info/{pathParameters?}', [StaffController::class, 'countryInfo'])->name('staff-countryInfos')->middleware('userHasPermission:admin');

                        Route::any('question-sets/{pathParameters?}', [QuestionsController::class, 'questionSets'])->name('staff-questionSets')->middleware('userHasPermission:admin');
                        Route::any('question-results/{pathParameters?}', [QuestionsController::class, 'questionResults'])->name('staff-questionResults')->middleware('userHasPermission:admin');
                        Route::any('ads/{pathParameters?}', [StaffController::class, 'ads'])->name('staff-ads')->middleware('userHasPermission:admin');
                        Route::any('search-rank/{pathParameters?}', [StaffController::class, 'searchRank'])->name('staff-searchRank')->middleware('userHasPermission:admin');
                        Route::any('my-macros/{pathParameters?}', [StaffController::class, 'myMacros'])->name('staff-my-macros')->middleware('userHasPermission:staff');
                        Route::any('macros/{pathParameters?}', [StaffController::class, 'macros'])->name('staff-macros')->middleware('userHasPermission:admin');
                        Route::any('data-correction/{pathParameters?}', [StaffController::class, 'dataCorrection'])->name('staff-dataCorrections')->middleware('userHasPermission:admin');
                        Route::any('data-correction/{dbTable}/{dbField}', [StaffController::class, 'dataCorrectionMass'])->name('staff-dataCorrection-mass')->middleware('userHasPermission:admin');
                        Route::any('pay-all-users', [StaffController::class, 'payAllUsers'])->name('staff-payAllUsers')->middleware('userHasPermission:admin');

                        Route::any('hostelgeeks/{pathParameters?}', [StaffController::class, 'hostelgeeks'])->name('staff-hostelgeeks')->middleware('userHasPermission:admin');

                        // Listings

                        Route::any('listings-instructions', [StaffListingsController::class, 'instructions'])->name('staff-listingsInstructions');

                        Route::get('listings/{listing}/checkAvailability', CheckAvailabilityListingStaffController::class)->name('staff-listings-checkAvailability')->middleware('userHasPermission:admin');

                        Route::any('listings/{pathParameters?}', [StaffListingsController::class, 'listings'])->name('staff-listings')->middleware('userHasPermission:staffEditHostels');

                        Route::any('listings/{listingID}/manage/{listingAction}/{extraParameter?}', [StaffListingsController::class, 'listingManage'])->name('staff-listing-manage')->middleware('userHasPermission:staffEditHostels')
                            ->where('listingID', '[0-9]+');
                        Route::any('listing-preview/{listingID}', [StaffListingsController::class, 'previewListing'])->name('staff-previewListing')->middleware('userHasPermission:staffEditHostels');
                        Route::any('email-listings/{pathParameters?}', [StaffListingsController::class, 'emailListings'])->name('staff-emailListings')->middleware('userHasPermission:admin');
                        Route::any('listing-corrections/{pathParameters?}', [StaffListingsController::class, 'listingCorrections'])->name('staff-listingCorrections')->middleware('userHasPermission:staffEditHostels');
                        Route::any('listing-duplicates/{pathParameters?}', [StaffListingsController::class, 'listingDuplicates'])->name('staff-listingDuplicates')->middleware('userHasPermission:staffEditHostels');
                        Route::any('merge-listings/{mode?}/{parameter?}', [StaffListingsController::class, 'mergeListings'])->name('staff-mergeListings')->middleware('userHasPermission:staffEditHostels');
                        Route::any('listing-spider-videos', [StaffListingsController::class, 'getVideosFromSpiderResults'])->name('staff-listingSpiderVideos')->middleware('userHasPermission:staffEditHostels');

                        // Incoming Links

                        Route::any('link-instructions', [IncomingLinksController::class, 'instructions'])->name('staff-incomingLinksInstructions');
                        Route::any('incoming-links-new', [IncomingLinksController::class, 'createNew'])->name('staff-incomingLinks-new')->middleware('userHasPermission:staffMarketing');
                        Route::any('incoming-links-edit/{pathParameters?}', [IncomingLinksController::class, 'incomingLinksEdit'])->name('staff-incomingLinksEdit')->middleware('userHasPermission:admin');
                        Route::any('incoming-links/{pathParameters?}', [IncomingLinksController::class, 'incomingLinks'])->name('staff-incomingLinks')->middleware('userHasPermission:staffMarketing');
                        Route::any('incoming-links-follow-up/{pathParameters?}', [IncomingLinksController::class, 'followUp'])->name('staff-incomingLinksFollowUp')->middleware('userHasPermission:staffMarketing');
                        Route::any('import-incomingLinks', [IncomingLinksController::class, 'importFromFile'])->name('admin-importIncomingLinks');
                        Route::any('incoming-link-ads/{incomingLinkID}/{pathParameters?}', [IncomingLinksController::class, 'ads'])->name('staff-incomingLinkAds')->middleware('userHasPermission:staffMarketingLevel2');

                        // Mail

                        Route::any('mail/{pathParameters?}', [StaffMailController::class, 'searchAndDisplay'])->name('staff-mailMessages')->middleware('userHasPermission:staffEmail');

                        Route::any('mail-attachments-edit/{mailID}', [StaffMailController::class, 'editAttachments'])->name('staff-mail-editAttachments')->middleware('userHasPermission:staffEmail')->where('mailID', '[0-9]+');   //todo: maybe not use

                        Route::any('mail-attachment/{attachmentID}/{attachmentFilename?}', [StaffMailController::class, 'viewAttachment'])->name('staff-mailAttachment')->middleware('userHasPermission:staffEmail')->where('attachmentID', '[0-9]+');
                        Route::any('email-autocomplete', [StaffMailController::class, 'autocompleteEmail'])->name('staff.autocompleteEmail')->middleware('browserCache:1 day private', 'pageCache:1 day', 'userHasPermission:staffEmail');
                        Route::any('mail-listing-add-contact', [StaffMailController::class, 'addListingContact'])->name('staff-listingAddContact')->middleware('userHasPermission:staffMarketing');
                        Route::any('mail-incomingLink-add-contact', [StaffMailController::class, 'addIncomingLinkContact'])->name('staff-incomingLinkAddContact')->middleware('userHasPermission:staffMarketing');

                        Route::prefix('users')->group(function () {
                            Route::any('{userID}/pay', [StaffController::class, 'userPay'])->name('staff-user-pay')->middleware('userHasPermission:admin')->where('userID', '[0-9]+');
                            Route::get('{userID}/auto-login', [StaffController::class, 'userAutoLogin'])->name('staff-user-autoLogin')->middleware('userHasPermission:admin')->where('userID', '[0-9]+');
                            Route::any('{pathParameters?}', [StaffController::class, 'users'])->name('staff-users')->middleware('userHasPermission:staffEditUsers');
                            Route::any('{userID}/settings/{listingAction}', [StaffController::class, 'userSettings'])->name('staff-user-settings')->middleware('userHasPermission:staffEditUsers')->where('userID', '[0-9]+');
                        });

                        // SEO

                        Route::any('city-category-page-description/{pathParameters?}', CityCategoryPageDescriptionStaffController::class)
                            ->name('staff-cityCategoryPageDescription')
                            ->middleware('userHasPermission:staff');

                        // todo: check is this used
                        Route::any('city-special-text/{pathParameters?}', [StaffController::class, 'citySpecialText'])
                            ->name('staff-citySpecialText')
                            ->middleware('userHasPermission:staff');

                        Route::any('listing-special-text/{pathParameters?}', [StaffController::class, 'listingSpecialText'])
                            ->name('staff-listingSpecialText')
                            ->middleware('userHasPermission:staff');

                        // Special Landing Pages

                        Route::prefix('slp')
                            ->controller(StaffSlpController::class)
                            ->middleware(['userHasPermission:admin'])
                            ->group(function () {
                                Route::get('/edit/{slp}', 'edit')
                                    ->name('slpStaff:edit')
                                    ->where('slug', '[a-z\-]+')
                                    ->whereIn('category', CategorySlp::values()->toArray());

                                Route::get('/create/{city?}', 'create')
                                    ->name('slpStaff:create');

                                Route::get('/{pathParameters?}', 'index')
                                    ->name('slpStaff:index');
                            });

                        /*  Districts */

                        Route::get(
                            '/district',
                            [StaffDistrictController::class, 'index'])
                            ->name('staff:district:index')
                            ->middleware('userHasPermission:admin');

                        Route::get(
                            '/district/edit/{district?}',
                            [StaffDistrictController::class, 'edit'])
                            ->name('staff:district:edit')
                            ->middleware('userHasPermission:admin');

                        // Misc

                        Route::any('data-checks', [DataChecksController::class, 'runChecks'])->name('staff-dataChecks')->middleware('userHasPermission:admin');
                        Route::any('use-geocoding', [StaffController::class, 'useGeocodingInfo'])->name('staff-useGeocodingInfo')->middleware('userHasPermission:staffEditHostels');
                        Route::any('translation/{language}/{group?}', [StaffController::class, 'translation'])->name('staff-translation')->middleware('userHasPermission:staffTranslation');
                        Route::any('translate-text', [StaffController::class, 'translateText'])->name('staff-translateText')->middleware('userHasPermission:staff'); // email translation
                        Route::any('data-mining/{function}', [DataMiningController::class, 'go'])->middleware('userHasPermission:admin');
                        Route::any('tax-reports', [StaffController::class, 'taxReports'])->name('taxReports')->middleware('userHasPermission:admin');
                        Route::any('pic-fix/{picType}', [PicFixController::class, 'picFix'])->name('staff-picFix')->middleware('userHasPermission:staffPicEdit');

                        // Development

                        Route::any('dev-sync/{fileSetName}', [DevController::class, 'devSync'])->name('staff-devSync')->middleware('userHasPermission:developer');
                        Route::any('generated-images', [DevController::class, 'regenerateGeneratedImages'])->name('staff-regenerateGeneratedImages')->middleware('userHasPermission:developer');
                        Route::any('temp/{function}', [TempController::class, 'temp'])->middleware('userHasPermission:developer');
                        Route::any('geonames/{pathParameters?}', [StaffController::class, 'geonames'])->name('staff-geonames')->middleware('userHasPermission:developer');

                        Route::any('seo/redirect/{pathParameters?}', [RedirectController::class, 'index'])->name('seo:redirect')->middleware('userHasPermission:admin');

                        Route::any('seo/prettylink/{parameters?}', [RedirectController::class, 'prettylink'])->name('seo:prettylink')->middleware('userHasPermission:admin');

                        //  Hostels Chains

                        Route::any('hostels-chains/{hostelChain}/image', [HostelsChainController::class, 'imageCreate'])->name('staff-hostelsChain:imageCreate')->middleware('userHasPermission:admin');
                        Route::any('hostels-chains/{pathParameters?}', [HostelsChainController::class, 'dashboard'])->name('staff-hostelsChain')->middleware('userHasPermission:admin');

                        //  other

                        Route::get('documentation', [DocumentationController::class, 'index'])->name('documentation')->middleware('userHasPermission:admin');
                        Route::get('documentation/edit', [DocumentationController::class, 'edit'])->name('documentation:edit')->middleware('userHasPermission:admin');
                        Route::put('documentation/update', [DocumentationController::class, 'update'])->name('documentation:update')->middleware('userHasPermission:admin');
                    });

                    //  Translation

                    Route::post('translation', ['csrf']);

                    // (This one is outside of the 'staff' group because we can't use the 'csrf' middleware with it.)
                    Route::any('staff/code-editor', [CodeEditorController::class, 'codeEditor'])->name('staff-codeEditor')->middleware('userHasPermission:developer');

                    /* Misc */

                    Route::any('questions-ask/{questionSetID}/{referenceCode}/{verificationCode}', [QuestionsController::class, 'ask'])->name('questions-ask');
                    Route::any('dev-sync-remote-command/{command}', [DevController::class, 'devSyncRemoteCommand'])->name('devSync-remote-command');
                    Route::any('booking-system-invoice/{system}', [BookingsController::class, 'bookingSystemInvoice'])->name('bookingSystemInvoice');
                    Route::any('cloud-pics/{pic}/{sizeType}', [MiscController::class, 'cloudStreamedPic'])->name('cloudStreamedPic');

                    Route::any('booking-notification-webhook/{system}', [ImportSystemWebhooksController::class, 'bookingNoticeWebhook'])->name('bookingNoticeWebhook');

                    Route::get('forceUpdateImportedPics/{imported}', UpdateImportedPicsStaffController::class)
                        ->name('forceUpdateImportedPics')
                        ->middleware('userHasPermission:admin');

                    // Check Import

                    Route::get('checkImport/{imported}', [CheckImportController::class, 'getForListing'])
                        ->name('checkImport')
                        ->middleware('userHasPermission:admin');
                });
            });
    });

Route::withoutMiddleware('devAccess')->group(function () {
    Route::view('/dev-access', 'devlogin')->name('devAccess');
    Route::post('/dev-access', [DevAccessController::class, 'checkDevAccess']);
});

Route::get('/lang/{langCode}', [LanguageChangeController::class, 'setLanguage'])->name('change-language');
