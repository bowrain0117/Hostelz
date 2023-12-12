<?php

namespace App\Http\Controllers;

use App;
use App\Booking\BookingService;
use App\Booking\RoomAvailability;
use App\Booking\RoomInfo;
use App\Booking\SearchCriteria;
use App\Helpers\EventLog;
use App\ImportSystems\BookingDotCom;
use App\Models\Ad;
use App\Models\AttachedText;
use App\Models\Booking;
use App\Models\BookingClick;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Geonames;
use App\Models\Imported;
use App\Models\IncomingLink;
use App\Models\Languages;
use App\Models\LanguageString;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingDuplicate;
use App\Models\Listing\ListingFeatures;
use App\Models\Listing\ListingMaintenance;
use App\Models\Listing\ListingSubscription;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Models\Pic;
use App\Models\PriceHistory;
use App\Models\QuestionResult;
use App\Models\QuestionSet;
use App\Models\Rating;
use App\Models\Review;
use App\Models\SearchRank;
use App\Models\User;
use App\Services\ImportSystems\Hostelsclub\HostelsclubService;
use App\Services\ImportSystems\ImportSystems;
use App\Services\WebsiteStatusChecker;
use Artisan;
use Carbon\Carbon;
use Config;
use DB;
use Emailer;
use Exception;
use File;
use Illuminate\Support\Collection;
use Lib\Currencies;
use Lib\DataCorrection;
use Lib\GeoBounds;
use Lib\Geocoding;
use Lib\GeoMath;
use Lib\GeoPoint;
use Lib\ImageProcessor;
use Lib\ImageSearch;
use Lib\LanguageDetection;
use Lib\MinifyCSS;
use Lib\MinifyJavascript;
use Lib\PageCache;
use Lib\SimpleAWS;
use Lib\Spider;
use Lib\WebSearch;
use Lib\WebsiteInfo;
use Lib\WebsiteTools;
use PDO;
use Queue;
use Request;
use Response;
use Storage;
use Twitter;
use URL;
use Validator;

/*

Just define a method of this class and it can be accessed with the URL https://.../staff/temp/{function}

*/

class TempController extends Controller
{
    public function temp($function)
    {
        return $this->$function();
    }

    /*
    // can be used for writing to a debug log for testing something
    public function writeToDebugLog()
    {
        file_put_contents(storage_path('logs/writeToDebugLog.log'), Carbon::now() . ' ' . $_SERVER['REMOTE_ADDR'] .
            ' "' . Request::input('s') . '" ' .
            ' "' . Request::getPathInfo() . '" ' . (string) @$_SERVER['HTTP_USER_AGENT'] . "\n", FILE_APPEND);
    }
    */

    /* Temp Functions */

    private function listingCounts()
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $count = Listing::
        //where('continent', 'North America')
        //->where('country', 'USA')
        // ->where('region', 'Texas')
        where('propertyType', '!=', 'hostel')
            ->where(function ($query): void {
                $query->where('supportEmail', '!=', '')
                    ->orWhere('managerEmail', '!=', '')
                    ->orWhere('bookingsEmail', '!=', '')
                    ->orWhere('tel', '!=', '');
            })
            ->whereDoesntHave('importeds', function ($query): void {
                $query->where('system', 'BookingDotCom');
            })
            ->areLive()->count();

        return $count;
    }

    private function outputListingsCsv(): void
    {
        set_time_limit(24 * 60 * 60);

        $listingIDs = Listing::where('country', 'USA')
            // ->whereIn('city', [ 'New York City', 'Los Angeles', 'San Francisco', 'Miami Beach' ])
            ->whereIn('city', ['Chicago', 'Washington, DC', 'San Diego', 'Boston', 'New Orleans', 'Seattle', 'Philadelphia', 'Portland', 'Austin', 'Denver', 'Oahu'])
            // ->where('region', 'Texas')
            ->where('propertyType', 'hostel')
            ->where(function ($query): void {
                $query->where('supportEmail', '!=', '')
                    ->orWhere('managerEmail', '!=', '')
                    ->orWhere('bookingsEmail', '!=', '')
                    ->orWhere('tel', '!=', '');
            })
            ->whereDoesntHave('importeds', function ($query): void {
                $query->where('system', 'BookingDotCom');
            })
            // ->where('verified', '>=', 0)
            ->areLive()
            // ->inRandomOrder()->limit(1500)
            ->orderBy('city')
            ->pluck('id');
        // dd($listingIDs);

        $out = fopen('php://output', 'w');
        fputcsv($out, ['Property Type', 'Name', 'Address', 'Neighborhood', 'City', 'Region', 'Country', 'Postal Code',
            'Latitude', 'Longitude',
            'Phone', 'Website', 'Emails', ]);

        foreach ($listingIDs as $id) {
            $listing = Listing::findOrFail($id);

            // $listing->listingMaintenance()->updateListing();

            // $imported = $listing->importeds()->where('system', 'BookingDotCom')->first();
            // if ($imported) continue;
            fputcsv($out, [
                $listing->propertyType, $listing->name,
                $listing->address, $listing->cityAlt, $listing->city, $listing->region, $listing->country, $listing->zipcode,
                $listing->latitude, $listing->longitude,
                $listing->tel, $listing->webStatus == 1 ? $listing->web : '',
                implode(', ', $listing->getAllEmails(['supportEmail', 'managerEmail', 'bookingsEmail'])),
                // $imported ? $imported->intCode : ''
            ]);
        }
        fclose($out);
    }

    private function emailReviewersAboutHiatus(): void
    {
        $userIDs = Review::where('expirationDate', '>', '2019-07-01')->orWhere('reviewDate', '>', '2019-07-01')->groupBy('reviewerID')->pluck('reviewerID');
        // $userIDs = [ 20807 ];

        foreach ($userIDs as $userID) {
            $user = User::findOrFail($userID);
            echo "$user->id $user->username";
            if (! $user->hasPermission('reviewer')) {
                echo ' not reviewer<br>';

                continue;
            }
            if ($user->status != 'ok') {
                echo ' not ok<br>';

                continue;
            }
            Emailer::send($user, 'Hostelz.com Paid Reviewer Program Hiatus from Most Countries', 'generic-email', ['text' => "Hi Hostelz.com Reviewers.  Unfortunately due to declining revenue from Hostelz.com bookings, we have had to make the difficult decision to no longer offer payment for reviews for hostels in many countries starting November 1st.  After November 1st, we will only pay for reviews for hostels located in either North America or Europe.\n\n" .
                "If there are hostels in other countries that you just want to review anyway even if it isn't for pay, we will absolutely still gladly accept and publish reviews for hostels in other countries as non-paid reviews.  We certainly understand if you are not interested in submitting reviews that aren't for payment.  I know many of you were wanting to continue writing reviews while you travel to help earn money for your trips, and we do very much hope to eventually resume the paid reviews program for all countries.  As soon as we can do that, we'll send you a notice by email to let you know.",
            ], Config::get('custom.adminEmail'));
            echo ' - emailed<br>';
        }
    }

    private function removeImportedPicsOriginals(): void
    {
        set_time_limit(100 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $pics = Pic::where('subjectType', 'imported')
            ->where('storageTypes', 'like', '%originals%')
            ->where('originalWidth', '>', Listing::BIG_PIC_MAX_WIDTH)
            ->limit(50)->get();

        foreach ($pics as $pic) {
            echo "$pic->id ";

            if (! $pic->hasSizeType('originals')) {
                echo 'no originals. ';

                continue;
            }

            $url = $pic->url(['originals'], 'absolute');

            echo "$url ";

            $image = ImageProcessor::makeFromFile($url);
            if (! $image) {
                logWarning("Couldn't load pic $pic->id.");

                return;
            }

            $pic->deletePicFiles(); // delete the old pic data first
            $pic->save();

            $pic->saveImageFiles($image, [
                // 'originals' => [ ], (takes up too much space, not worth saving the originals)
                'big' => ['saveAsFormat' => 'jpg', 'outputQuality' => 80,
                    'maxWidth' => Listing::BIG_PIC_MAX_WIDTH, 'maxHeight' => Listing::BIG_PIC_MAX_HEIGHT, /* 'skipIfUnmodified' => true */],
            ]);
        }
    }

    private function creditCard(): void
    {
        $proc = new App\PaymentProcessors\Stripe\PaymentProcessor();
    }

    private function storageTest(): void
    {
        $result = Storage::disk('spaces1')->delete('foo');
        dd($result);
    }

    private function picStorageTypes()
    {
        $pic = Pic::find(2764446);

        $pic->storageTypes = [

        ];
        $pic->save();

        return 'done';
    }

    private function hostelsScraping(): void
    {
        $imported = Imported::find(2659176);
        $url = 'http://www.hostels.com/Reviews/' . urlencode(str_replace([' ', '/'], '-', $imported->name)) . '/' . $imported->intCode;
        //echo $url;
        //echo htmlentities(WebsiteTools::fetchPage($url)); exit();
        $xpath = WebsiteTools::fetchXPath($url, false, [
            'misc' => "//div[@class='overview rounded']//h3",
            'ratings' => '//div[@class="ratings"]/ul/li/span',
        ]);
        echo "$url\n";
        dd($xpath);
    }

    private function mailHeadersTest()
    {
        return imap_mime_header_decode('=?UTF-8?B?TmV3IFJlc2VydmF0aW9uIGZvciBZb2hvIEludGVybmF0aW9uYWwgWW91dGggSG9zdGVsIA==?=');

        return imap_utf8('=?UTF-8?B?TmV3IFJlc2VydmF0aW9uIGZvciBZb2hvIEludGVybmF0aW9uYWwgWW91dGggSG9zdGVsIA==?=');
    }

    private function testEmailFilter(): void
    {
        $mails = MailMessage::where('status', 'new')
            ->where('subject', 'like', 'New Reservation for %')
            ->where('senderAddress', 'reservations@hostelsclub.com')->get();

        foreach ($mails as $mail) {
            echo "$mail->id ";
            $result = HostelsclubService::emailFilter($mail);
            if ($result) {
                echo 'ok<br>';
                $mail->status = 'archived';
                $mail->save();
            } else {
                echo 'failed<br>';
            }
        }
    }

    private function findDuplicateEditedReviews()
    {
        set_time_limit(100 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $reviews = Review::where('status', 'publishedReview')->where('editedReview', '!=', '')
            /* ->where('id', '>', 27835) */
            ->orderBy('id')->get();
        $totalCount = $reviews->count();
        $count = 0;
        $usersDone = [];

        foreach ($reviews as $review) {
            $count++;

            $userID = $review->reviewerID;

            $otherReviews = Review::where('id', '!=', $review->id)
                ->where('editedReview', '!=', '')
                ->whereNotIn('status', ['newHostel', 'deniedReview', 'returnedReview'])
                ->where(function ($query) use ($review): void {
                    $query->where('reviewerID', $review->reviewerID) // compare to all reviews of this reviewer
                    ->orWhere('hostelID', $review->hostelID); // compare to existing reviews of the same listing
                })
                ->get();

            foreach ($otherReviews as $otherReview) {
                $similar = similar_text($review->editedReview, $otherReview->editedReview, $percent);
                $percent = round($percent);
                // This "percent" sounds high, but reviews at that level tend to be fairly different.
                if ($percent > 49) {
                    $subjectName = $otherReview->listing->name;
                    echo "($count/$totalCount) user $userID review <a href=\"" . routeURL('staff-reviews', $review->id) . "\">$review->id</a> $review->payStatus is $percent% similar to <a href=\"" . routeURL('staff-reviews', $otherReview->id) . "\">$otherReview->id</a> '$subjectName'<br>";
                    // return 'ok';
                }
            }
        }

        return 'done.';
    }

    private function findDuplicateReviews(): void
    {
        set_time_limit(100 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        /*
        $reviews = Review::where('status', 'markedForEditing')->get();

        foreach ($reviews as $review) {
            $review->doPlagiarismChecks();
        }

        return 'done.';
        */

        $reviews = Review::whereIn('status', ['newReview'])->where('review', '!=', '')
            ->where('id', '>', 28119)
            ->orderBy('id')->get();
        $totalCount = $reviews->count();
        $count = 0;
        $usersDone = [];

        foreach ($reviews as $review) {
            $count++;

            $userID = $review->reviewerID;

            $review->doPlagiarismChecks();

            if ($review->status == 'returnedReview') {
                echo "($count/$totalCount) user $userID review $review->id $review->payStatus<br>";
                // return 'ok';

                if ($review->payStatus == 'paid') {
                    $review->payStatus = '';
                    $review->save();
                    $review->user->makeBalanceAdjustment(-Review::PAY_AMOUNT, "Duplicate content found in already paid review $review->id.");
                }

                // return 'ok';
            }
        }
    }

    private function removeOnlyImportedAndInactive(): void
    {
        set_time_limit(2 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listings = Listing::where('verified', Listing::$statusOptions['imported'])->
        whereHas('importeds', function ($query): void {
            $query->where('status', 'inactive');
        })->whereDoesntHave('importeds', function ($query): void {
            $query->where('status', 'active');
        })->doesntHave('reviews')->get();

        foreach ($listings as $listing) {
            echo '<a href="' . routeURL('staff-listings', $listing->id) . "\">$listing->name</a><br>";
            $listing->delete();
        }
    }

    private function useBookingDotComMatches()
    {
        $file = $GLOBALS['DATA_STORAGE'] . '/booking.com-their-matching-data.csv';
        $needToImportList = $GLOBALS['DATA_STORAGE'] . '/booking.com-need-to-import.txt';

        set_time_limit(6 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $fp = fopen($file, 'r');
        fgetcsv($fp);

        $needToImport = [];

        while ($row = fgetcsv($fp)) {
            $ourID = $row[0];
            $theirID = $row[8];
            if (! $theirID) {
                continue;
            }

            echo '<br><a href="' . routeURL('staff-listings', $ourID) . "\">$ourID</a> $theirID - ";

            $listing = Listing::find($ourID);
            if (! $listing) {
                echo 'LISTING NOT FOUND!';

                continue;
            }

            echo "$listing->name ($listing->city, $listing->country) - ";

            $imported = $listing->importeds->where('system', 'BookingDotCom')->where('intCode', $theirID)->first();
            if ($imported) {
                echo 'already.';

                continue;
            }

            $imported = Imported::where('system', 'BookingDotCom')->where('intCode', $theirID)->first();
            if ($imported) {
                if ($imported->hostelID) {
                    // This may automatically merge them if $attemptAutoMerging, otherwise just computes $maxChoiceDifficulty
                    $autoMergeOutput = $maxChoiceDifficulty = '';
                    $didMerge = ListingDuplicate::automaticMerging([$listing, $imported->listing], true, $autoMergeOutput, $maxChoiceDifficulty);
                    echo $autoMergeOutput;
                    if ($didMerge) {
                        echo ' MERGED!';

                        continue;
                    } else {
                        ListingDuplicate::insertOrUpdate(
                            [$listing->id, $imported->hostelID],
                            'suspected',
                            ['source' => 'useBookingDotComMatches', 'score' => 90, 'maxChoiceDifficulty' => $maxChoiceDifficulty]
                        );
                        echo ' Added to merge list with <a href="' . routeURL('staff-listings', $imported->hostelID) . "\">$imported->hostelID</a>";

                        continue;
                    }
                } else {
                    echo "No listingID ($imported->name ($imported->city, $imported->country)) ";

                    continue;
                }
            }

            echo 'Need to import.';
            $needToImport[] = $theirID;
        }

        fclose($fp);

        file_put_contents($needToImportList, implode("\n", $needToImport));

        return '[done]';
    }

    private function testBookingAPI(): void
    {
        return;

        ListingMaintenance::updateListings(
            [0 => 259975, 1 => 229117, 2 => 245770, 3 => 86823, 4 => 242565, 5 => 359444, 6 => 83420, 7 => 197884, 8 => 244447, 9 => 189414]
        );

//        DB::enableQueryLog();

        /*       $listings = DB::table( 'listings' )->join( 'imported', function ( $join ) {
                   $join->on( 'listings.id', '=', 'imported.hostelID' )
                        ->where( [
                            [ 'system', '=', 'BookingDotCom' ],
                            [ 'version', '=', '72' ],
                        ] );
               } )
                 ->select( 'listings.id', 'compiledFeatures', 'system', 'intCode', 'hostelID' )
                 ->where( [
                     [ 'verified', '>=', Listing::$statusOptions['ok'] ],
                     [ 'compiledFeatures', 'like', '%partying%' ],
                     [ 'compiledFeatures', 'like', '%families%' ],
                 ] )
                 ->orderBy( 'lastUpdate' )
//                ->count()

                 ->limit( 10 )
                 ->get()
               ;

//        dd(DB::getQueryLog());

               $hostelIDs = $listings->map(function ($item, $key) {
                   return $item->intCode;
               });


               var_export($listings->map(function ($item, $key) {
                   return $item->hostelID;
               })->toArray());*/

//        dd( $listings );
//        dd( $hostelIDs );

//        BookingDotCom::testImport($hostelIDs);

//        $for_updates = [365211];

//        return ListingMaintenance::updateListings($for_updates);
    }

    private function reEditReviews()
    {
        $ids = EventLog::where('userID', 46148)->where('action', 'update')->where('subjectType', 'Review')->pluck('subjectID');
        echo '(' . $ids->count() . ')';
        $reviews = Review::whereIn('status', ['staffEdited', 'publishedReview'])
            ->whereIn('id', $ids)->get();

        return $reviews->count();
        //Review::whereIn('status', [ 'staffEdited', 'publishedReview' ])
        //    ->whereIn('id', $ids)->update([ 'status' => 'markedForEditing' ]);
    }

    public function formHandlerMethods(): void
    {
        echo implode('()<br>', (get_class_methods(\Lib\FormHandler::class)));
    }

    // For booking.com. See https://secure.hostelz.com/staff/mail-attachment/568623/untitled
    private function exportAllLiveListingsAsTSV(): void
    {
        set_time_limit(6 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listingIDs = Listing::areLive()->orderBy('id')->pluck('id');

        foreach ($listingIDs as $listingID) {
            $listing = Listing::find($listingID);
            echo "$listing->id\t$listing->name\t$listing->address\t$listing->zipcode\t$listing->city\t$listing->country\t$listing->longitude\t$listing->latitude\n";
        }
    }

    private function updateSnippets(): void
    {
        set_time_limit(6 * 60 * 60);
        DB::disableQueryLog(); // to save memory
        $listingIDs = Listing::areLive()->arePrimaryPropertyType()
            ->orderBy('id', 'desc')->pluck('id');

        foreach ($listingIDs as $listingID) {
            echo "$listingID ";
            $listing = Listing::find($listingID);
            $listing->listingMaintenance()->updateSnippets();
        }
    }

    private function testUpdateSnippets(): void
    {
        DB::enableQueryLog();
        $ratings = Rating::spliceRatingsForPage(Rating::getRatingsForListing(Listing::find(170250), 'en'), 0);
        $ratingsScore = 0;
        foreach ($ratings as $rating) {
            _d($rating->comment);
            _d(strlen($rating->comment));
            $ratingsScore += strlen($rating->summary) + strlen($rating->comment);
        }

        _d(DB::getQueryLog());

        dd(Listing::find(170250)->listingMaintenance()->calculateContentScores());

        return;
        //  snippet update
        $listing_id = 170250;
        dd(Listing::find($listing_id)->listingMaintenance()->updateSnippets());
    }

    private function updateOverallContentScore(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory
        $listings = Listing::where('overallContentScore', 0)->where('contentScores', '!=', '')
            ->orderBy('id', 'asc')->pluck('contentScores', 'id');

        foreach ($listings as $listingID => $contentScores) {
            $score = $contentScores['en'] ?? null;
            echo "$listingID $score ";
            Listing::where('id', $listingID)->update(['overallContentScore' => $score]);
        }
    }

    private function testResizeGamma(): void
    {
        /*
        http://www.imagemagick.org/discourse-server/viewtopic.php?t=15955
        http://www.4p8.com/eric.brasseur/gamma.html
         */
        $image = ImageProcessor::makeFromFile('https://i.stack.imgur.com/p6jYl.jpg'); // http://www.4p8.com/eric.brasseur/gamma_dalai_lama_gray.jpg');
        $image->applyEdits(['absoluteHeight' => 111 /* , 'absoluteWidth' => 129 */]);
        header('Content-Type:image/jpeg');
        echo $image->getImageData();
        exit();
    }

    private function recreatePics(): void
    {
        $subjectType = 'reviews';
        $status = 'ok';

        set_time_limit(10 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $pics = Pic::where('subjectType', $subjectType)->where('status', $status)
            // ->where('subjectID', 1378)
            // ->orderBy(DB::raw('rand()'))->limit(1)
            // ->where('id', '>', 3188316)
            ->orderBy('id')
            //>limit(1)
            ->get();

        foreach ($pics as $pic) {
            echo '<a href="' . routeURL('staff-pics', $pic->id) . '"><img src="' . $pic->url(['']) . "\">$pic->id</a><br>";

            /*
            (this was causing it to run out of memory, so we call an artisan command instead)

            $image = ImageProcessor::makeFromString($pic->getImageData('originals'));

            if (!$image) throw new Exception("Couldn't load image for $pic->id.");

            switch ($subjectType) {
                case 'cityInfo':
                    $picOutputTypes = CityInfo::picFixPicOutputTypes();
                    break;

                case 'reviews':
                    $picOutputTypes = Review::picFixPicOutputTypes();
                    break;

                default:
                    throw new Exception("Unknown subjectType $subjectType.");
            }

            $edits = $pic->edits;
            if ($edits) {
                foreach ($picOutputTypes as &$picOutputType) {
                    $picOutputType = $picOutputType + $edits;
                }
            }

            $pic->saveImageFiles($image, $picOutputTypes);
            $image->releaseResources();
            //header('Content-Type:image/jpeg');
            //echo $image->getImageData();
            */

            /*
            this didn't work either because the artisan call is still using this script's memory
            echo Artisan::call('hostelz:runTempFunction', [ 'arguments' => [ 'recreatePic', $pic->id] ]);
            */

            echo shell_exec(PHP_BINDIR . '/php ' . base_path() . '/artisan hostelz:runTempFunction recreatePic ' . $pic->id);
        }
    }

    private function testWebsites(): void
    {
        set_time_limit(24 * 60 * 60);

        $curl = curl_init();

        $listings = Listing::where('webStatus', WebsiteStatusChecker::$websiteStatusOptions['pageError'])
            ->where('verified', Listing::$statusOptions['ok'])->limit(1000)->get();

        foreach ($listings as $listing) {
            echo "<br>$listing->web: ";
            curl_setopt_array($curl, [CURLOPT_URL => WebsiteTools::removeUrlFragments($listing->web), CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 7, CURLOPT_CONNECTTIMEOUT => 20, CURLOPT_TIMEOUT => 20,
                /* CURLOPT_BUFFERSIZE => 16000, (supposed to limit the amount of data we download, but was causing timeouts) */
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
                CURLOPT_IPRESOLVE => CURL_IPRESOLVE_V4, // was having an issue with www.bananabarracks.com failing with IPv6, so making it always use IPv4 for now
                CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, ]);
            $contentsData = curl_exec($curl);

            $errno = curl_errno($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $effectiveURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            if (! $errno) {
                $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
            }

            if ($errno || $contentsData == '') {
                continue;
            }

            if (substr($code, 0, 1) != '2') {
                continue;
            }

            echo 'OK!';
        }
    }

    private function websiteStatus(): void
    {
        $result = WebsiteStatusChecker::getWebsiteStatus('http://www.bananabarracks.com/', true, true);
        dd($result);
    }

    private function validation(): void
    {
        $validator = Validator::make(['thing' => 'foo http://yahoo.com sdfsd'], ['thing' => 'string_length:5']);
        if ($validator->fails()) {
            $messageBag = $validator->messages();
        }
        dd($messageBag);
    }

    private function substrCount()
    {
        $row = AttachedText::find(18360231);

        return substr_count(strtolower($row->data), 'hostel');
    }

    private function pregSplit(): void
    {
        // $result = preg_replace('/[^\da-z]/i', '', 'sdf.d.d$.dff');

        $text = '説明 シドニーを訪れるならお泊まりはYHAのシドニーバックパッカーズ向けホ';
        $text = ' 가장 큰 불교사원 문수사원 인근에 위치하고 있';
        $text = 'sdf dfid, df!! sdf ?F?' . 'シドニーセントラyhaはセントラル駅の真向かいの遺産指定された建物を用いた五つ星のホステル';
        $text = 'ベイルートの賑やかなエネルギーの中心部からわずか数秒離れて静かなエリアにたたずむ、ホテルドゥヴィレは4つ星の豪華な新しい意味を与えます。私たちは、細部と卓越を重視する個性、関心を具体化しています。 ゴールデンチューリップホテルデビルの客室は、温かみのあるスタイリッシュな装飾が施されています。それぞれは、ケーブルテレビ、DVDプレーヤー、バスタブとドライヤー付きの専用浴室が装備されています。 ご滞在中はホテルのレストランでは、ジ でワークアウト、そしてバーでくつろぐおいしい高級料理を楽しむことができます。 あなたがカフェ通り75\'＆\'寿司デビル\'であなたの国際的な好みの食事を楽しむことができるヨーロッパのバリア朗 。 また、ビジネスでお越しのお客様は3カンファレンスがよくお部屋を搭載したこの豪華なホテルで扱われている、すべて提供する、自然採光を確保することができます。 間違いなくゴールデンチューリップドゥヴィルでのご滞在が思い出になります。';

        $result = preg_split("/[^\p{L}\p{N}]/u", $text, -1, PREG_SPLIT_NO_EMPTY);
        // $result = preg_split('/(?<!^)(?!$)/u', 'this is f d dsfi! sdf. , fdsf,df.s', -1, PREG_SPLIT_NO_EMPTY);
        // $result = preg_split('/(?<!^)(?!$)/u', , -1, PREG_SPLIT_NO_EMPTY);
        $result = preg_split('/\B(?=\p{Han})|\W/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        // $result = preg_split('/(^\W+|\W*\s\W*|\p{Cc}|\p{Cf}|\p{Cn}|\p{Co}|\p{Z}|\p{Han}|;|!|\W+$)/u', $text, -1, PREG_SPLIT_NO_EMPTY);
        dd($result);
    }

    private function testLanguageDetection(): void
    {
        foreach (Languages::allCodes() as $languageCode) {
            $texts = AttachedText::where('type', 'description')->where('language', $languageCode)
                ->limit(30)->select('id', 'data')->get();

            foreach ($texts as $text) {
                $result = LanguageDetection::verify($text->data, $languageCode);
                echo "<br><br>$text->id - $languageCode. ";
                if ($result != $languageCode) {
                    echo json_encode($result) . "$text->data";
                }
            }
        }
    }

    private function languageDetectTrain(): void
    {
        set_time_limit(30 * 60);
        DB::disableQueryLog(); // to save memory

        LanguageDetection::resetTrainingData();

        $textsPerLanguage = 50;

        foreach (Languages::allCodes() as $languageCode) {
            // if (in_array($languageCode, [ 'en', 'fr', 'es', 'it' ])) continue;
            $texts = AttachedText::where('type', 'description')->where('language', $languageCode)
                ->limit($textsPerLanguage)->select('id', 'data')->get();

            if ($texts->count() < $textsPerLanguage) {
                logError("Not enough texts for $languageCode.");

                continue;
            }

            foreach ($texts as $text) {
                $strippedText = str_replace("\n", ' ', strip_tags($text->data));
                echo '<br><br><a href="' . routeURL('staff-attachedTexts', $text->id) . "\">$text->id</a> $languageCode: " . $strippedText;
                LanguageDetection::train($strippedText, $languageCode);
            }
        }
    }

    private function removeSpiderResultsDuplicates(): void
    {
        $spiders = DB::table('spiderResults')->groupBy('type', 'url')->havingRaw('count(*) > 1')->select('type', 'url')->get()->all();

        foreach ($spiders as $spider) {
            $duplicates = DB::table('spiderResults')->where('type', $spider->type)->where('url', $spider->url)->orderBy('lastUpdateDate', 'asc')->get()->all();
            $keeping = array_pop($duplicates);
            echo "<br>Keeping $keeping->id ($keeping->lastUpdateDate). ";
            foreach ($duplicates as $duplicate) {
                //DB::table('spiderResults')->where('id', $duplicate->id)->delete();
                echo "Removing $duplicate->id ($duplicate->lastUpdateDate). ";
            }
        }
    }

    private function getVideosFromSpiderResults(): void
    {
        $videoTypes = ['YouTube', 'Vimeo', 'Metacafe', 'Viddler'];

        $spiders = DB::table('spiderResults')->where('type', 'listing')->orderBy('url')->get()->all();

        foreach ($spiders as $spider) {
            $results = unserialize($spider->spiderResults);
            if (! $results) {
                continue;
            }

            $videos = [];
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
                    $videos[] = $videoURL;
                }
            }

            if (! $videos) {
                continue;
            }

            $listing = Listing::where('web', $spider->url)->where('videoURL', '')->areLive()->where('propertyType', 'Hostel')->first();
            if (! $listing) {
                continue;
            }

            echo "$listing->name: $spider->url -> " . implode(', ', $videos) . '<br>';
        }
    }

    private function twitterFollowIncomingLinks()
    {
        $TEST_MODE = true;

        $twitterConfig = Config::get('twitter.test');

        $incomingLinks = IncomingLink::where('spiderResults', 'LIKE', '%twitter.com%')->get();

        $twitterUsers = [];
        foreach ($incomingLinks as $link) {
            $spiderResults = $link->spiderResults;
            if (! isset($spiderResults['Twitter'])) {
                continue;
            }
            foreach ($spiderResults['Twitter'] as $twitterLink => $onPage) {
                $result = preg_match("|https?://(www\.)?twitter\.com/(#!/)?@?([^/\?]*)|", $twitterLink, $matches);
                if (! $result) {
                    continue;
                }
                $twitterUser = $matches[3];
                if (in_array($twitterUser, $twitterUsers) || in_array($twitterUser, ['', 'share', 'home', 'intent'])) {
                    continue;
                }
                // echo "$twitterLink -> $twitterUser<br>";
                $twitterUsers[] = $twitterUser;

                continue;
            }
        }

        print_r($twitterUsers);

        return 'ok';

        $twitter = new Twitter($twitterConfig['consumerKey'], $twitterConfig['consumerSecret'], $twitterConfig['accessToken'], $twitterConfig['tokenSecret']);

        foreach ($twitterUsers as $twitterUser) {
            $result = $twitter->request('friendships/create', 'POST', ['screen_name' => 'briggsb', 'follow' => true]);
            if ($result && $result->following) {
                return 'ok';
            } else {
                dd($result);
            }
        }
    }

    private function videoSchema(): void
    {
        $list = Listing::where('videoSchema', '=', '')->where('videoEmbedHTML', '!=', '')->areLive();

        _d($list->count());

        $list->each(function ($item, $key): void {
            $videoID = getYoutubeIDFromURL($item->videoEmbedHTML);

            _d([$item->id, '', $item->videoEmbedHTML, $videoID]);

//            _d([$item->videoURL, $videoID]);

//            _d(['no ID', $item->id, $item->videoEmbedHTML, $item->videoURL, $videoID]);

            /*            if ($videoID === null) {
                        _d(['no ID', $item->id, $item->videoEmbedHTML, $item->videoURL, $videoID]);
                            return true;
                        }


                        $schema = WebsiteTools::getVideoSchema($videoID);
                        if (!$schema) {
                        _d(['no schema', $item->id, $item->videoEmbedHTML, $videoID]);
                            return true;
                        }

                        _d([$item->id, $schema]);


                        $item->videoSchema = $schema;*/

//            $item->save();
        });
    }

    private function youtubeExtract(): void
    {
        $listings = Listing::where('videoEmbedHTML', '!=', '')->where('videoURL', '')->get();

        foreach ($listings as $listing) {
            $text = $listing->videoEmbedHTML;
            $videoURL = '';
            if (strpos($text, 'embedly')) {
                preg_match('|src="(.*)"|U', $text, $matches);
                if (! $matches) {
                    echo 'no matches for: ' . htmlentities($listing->videoEmbedHTML) . '<br>';
                    exit();
                }
                $parsedURL = parse_url($matches[1]);
                if ($parsedURL['query'] == '') {
                    echo 'no query for: ' . htmlentities($matches[1]) . '<br>';
                    exit();
                }
                parse_str($parsedURL['query'], $query);
                $videoURL = $query['url'];
            } elseif (strpos($text, 'iframe') !== false || strpos($text, '<object') !== false || strpos($text, '<embed') !== false) {
                preg_match('`src=["\'](.*)["\']`U', $text, $matches);
                if (! $matches) {
                    echo 'no matches for: ' . htmlentities($listing->videoEmbedHTML) . '<br>';
                    exit();
                }
                $videoURL = $matches[1];
            } elseif (strpos($text, 'data-href="https://www.facebook.com/') !== false) {
                preg_match('`data-href=["\'](.*)["\']`U', $text, $matches);
                if (! $matches) {
                    echo 'no matches for: ' . htmlentities($listing->videoEmbedHTML) . '<br>';
                    exit();
                }
                $videoURL = $matches[1];
            } elseif (strpos($text, '<embed') !== false) {
            } elseif (strpos($text, 'www.facebook.com/video.php?') !== false) {
            } else {
                echo 'unknown: ' . htmlentities($listing->videoEmbedHTML) . '<br>';

                continue;
            }

            if (strpos($text, 'https://www.youtube.com/embed/') !== false) {
                preg_match('`https://www.youtube.com/embed/(.*)[\?\&$]`U', $text, $matches);
                if (! $matches) {
                    echo 'no matches for: ' . htmlentities($listing->videoEmbedHTML) . '<br>';
                    exit();
                }
                $videoURL = 'http://www.youtube.com/watch?v=' . $matches[1];
            }

            $videoURL = str_replace('?feature=oembed', '', $videoURL);

            if ($videoURL != '') {
                echo "Video URL: '$videoURL'<br>";
                $listing->videoURL = $videoURL;
                $listing->save();
            } else {
                echo htmlentities($listing->videoEmbedHTML) . '<br>';
            }
        }
    }

    private function moveSearchRanksFromCountryInfo(): void
    {
        set_time_limit(30 * 60);

        $countryInfos = CountryInfo::where('notes', 'like', '%Google Rank:%')->get();

        foreach ($countryInfos as $countryInfo) {
            $lines = explode("\n", trim($countryInfo->notes));

            $newNotes = '';

            foreach ($lines as $line) {
                if (! strpos($line, 'Google Rank: ')) {
                    $newNotes .= ($line . "\n");

                    continue;
                }
                $parts = explode(' ', trim($line));

                /*
                if (count($parts) == 6) {
                    $asURL = $parts[5];
                    if (!strpos($asURL, '/hostels/')) {
                        echo "Unknown url $asURL.";
                        continue;
                    }
                }
                */

                $new = [
                    'checkDate' => $parts[0], 'source' => 'Google', 'searchPhrase' => "$countryInfo->country hostels", 'rank' => $parts[3],
                    'placeType' => 'CountryInfo', 'placeID' => $countryInfo->id,
                ];
                print_r($new);

                $new = new SearchRank($new);
                $new->save();
            }

            $countryInfo->notes = $newNotes;
            $countryInfo->save();
            echo "newNotes: $newNotes\n\n";
        }
    }

    private function moveSearchRanksFromListing(): void
    {
        set_time_limit(30 * 60);

        $listings = Listing::where('comment', 'like', '%Google Rank:%')->get();

        foreach ($listings as $listing) {
            $lines = explode("\n", trim($listing->comment));

            $newNotes = '';

            foreach ($lines as $line) {
                if (! strpos($line, 'Google Rank: ')) {
                    $newNotes .= ($line . "\n");

                    continue;
                }
                $parts = explode(' ', trim($line));

                /*
                if (count($parts) == 6) {
                    $asURL = $parts[5];
                    if (!strpos($asURL, '/hostels/')) {
                        echo "Unknown url $asURL.";
                        continue;
                    }
                }
                */

                $new = [
                    'checkDate' => $parts[0], 'source' => 'Google', 'searchPhrase' => "$listing->name", 'rank' => $parts[3],
                    'placeType' => 'Listing', 'placeID' => $listing->id,
                ];
                print_r($new);

                $new = new SearchRank($new);
                $new->save();
            }

            $listing->comment = $newNotes;
            $listing->save();
            echo "newNotes: $newNotes\n\n";
        }
    }

    private function moveSearchRanksFromCityInfo(): void
    {
        set_time_limit(30 * 60);

        $cityInfos = CityInfo::where('staffNotes', 'like', '%Google Rank:%')->get();

        foreach ($cityInfos as $cityInfo) {
            $lines = explode("\n", trim($cityInfo->staffNotes));

            $newNotes = '';

            foreach ($lines as $line) {
                if (! strpos($line, 'Google Rank: ')) {
                    $newNotes .= ($line . "\n");

                    continue;
                }
                $parts = explode(' ', trim($line));

                /*
                if (count($parts) == 6) {
                    $asURL = $parts[5];
                    if (!strpos($asURL, '/hostels/')) {
                        echo "Unknown url $asURL.";
                        continue;
                    }
                }
                */

                $new = [
                    'checkDate' => $parts[0], 'source' => 'Google', 'searchPhrase' => "$cityInfo->city hostels", 'rank' => $parts[3],
                    'placeType' => 'CityInfo', 'placeID' => $cityInfo->id,
                ];
                print_r($new);

                //$new = new SearchRank($new);
                //$new->save();
            }

            //$cityInfo->staffNotes = $newNotes;
            //$cityInfo->save();
            echo "newNotes: $newNotes\n\n";
        }
    }

    private function moveRankingsToSearchRanks(): void
    {
        $rankings = DB::table('rankings')->where('search', 'like', '% hostels')->where('url', 'like', 'http://www.hostelz.com/hostels/%')->get()->all();

        foreach ($rankings as $rank) {
            $slugParts = explode('/', str_replace('http://www.hostelz.com/hostels/', '', $rank->url));

            switch (count($slugParts)) {
                case 2: // without region
                    list($country, $city) = $slugParts;
                    $region = '';

                    break;
                case 3: // with region
                    list($country, $region, $city) = $slugParts;

                    break;
                default:
                    App::abort(404);
            }

            $cityInfo = CityInfo::areLive()->fromUrlParts($country, $region, $city)->first();
            if (! $cityInfo) {
                echo "unknown for $rank->url.";

                continue;
            }

            $new = new SearchRank([
                'checkDate' => $rank->searchTime, 'source' => $rank->source, 'searchPhrase' => $rank->search, 'rank' => $rank->rank,
                'placeType' => 'CityInfo', 'placeID' => $cityInfo->id,
            ]);
            $new->save();
        }
    }

    private function reviewPlagiarismChecks(): void
    {
        set_time_limit(30 * 60);
        $reviews = Review::where('status', 'newReview')->has('pics')->get();
        foreach ($reviews as $review) {
            echo "[$review->id] ";
            $review->doPlagiarismChecks();
        }
    }

    private function imageSearch(): void
    {
        // $result = ImageSearch::searchByURL('https://www.hostelz.com/pics/reviews/big/55/1692855.jpg');
        $result = ImageSearch::searchByURL('https://upload.wikimedia.org/wikipedia/commons/thumb/d/d9/First_Student_IC_school_bus_202076.jpg/220px-First_Student_IC_school_bus_202076.jpg');
        dd($result);
    }

    // delete error/todo incoming links if another of same domain exists

    private function deleteErrorIncomingLinks(): void
    {
        set_time_limit(8 * 60 * 60); // Note: This also resets the timeout timer.
        DB::disableQueryLog(); // to save memory

        $links = IncomingLink::where('contactStatus', 'todo')->where('checkStatus', 'error')->get();

        foreach ($links as $link) {
            $otherLinks = IncomingLink::where('domain', $link->domain)->where('id', '!=', $link->id)->count();
            if (! $otherLinks) {
                continue;
            }
            echo "$link->id $link->url<br>";
            $link->delete();
        }
    }

    private function mutex(): void
    {
        $mutex = \Mutex::create();
        var_dump($mutex);
        Mutex::destroy($mutex);
    }

    private function reviewSummaries(): void
    {
        $reviews = Review::where('status', 'publishedReview')->orderBy(DB::raw('rand()'))->limit(100)->get();

        foreach ($reviews as $review) {
            echo '<a href="' . routeURL('staff-reviews', $review->id) . "\">$review->id</a> " . $review->getSummary(200) . '<br>';
        }
    }

    private function simpleAWS(): void
    {
        $result = SimpleAWS::makeSignatureVersion2Request('awis.amazonaws.com', '/', [
            'Action' => 'aaa',
            //'Url' => 'http://www.yahoo.com',
            //'ResponseGroup' => 'Rank'
        ]);

        dd($result);

        /*
        http://awis.amazonaws.com/?
            Action=UrlInfo
            &AWSAccessKeyId=[Your AWS Access Key ID]
            &Signature=[signature calculated from request]
            &SignatureMethod=[HmacSha1 or HmacSha256]
            &SignatureVersion=2
            &Timestamp=[timestamp used in signature]
            &Url=[Valid URL]
            &ResponseGroup=[Valid Response Group]

        */
    }

    private function updateTrafficRanks()
    {
        $links = IncomingLink::whereNull('trafficRank')->where('checkStatus', 'ok')->orderBy('id', 'desc')->limit(100)->get();
        set_time_limit(300 + 1 * $links->count()); // we currently limit our fetching rate to 1 per second
        $output = IncomingLink::updateTrafficRanks($links) . "\n";

        return $output;
    }

    private function getTrafficStats(): void
    {
        $result = WebsiteInfo::getTrafficStats('http://www.hellogwu.com/home.php?mod=space&uid=506&do=wall&from=space');
        dd($result);
    }

    private function getAllEmails()
    {
        $listing = Listing::find(1);

        return $listing->getAllEmails();
        //return $listing->hasAnyMatchingEmail('info@frankfurt-hostel.com') ? 'yes' : 'no';
    }

    private function getAuthorityStats(): void
    {
        //return round(1.0);
        $results = WebsiteInfo::getAuthorityStats(['http://sdfsdf3f33']);
        dd($results);
    }

    private function setAuthorityScores()
    {
        if (Request::input('file') == '') {
            echo '<form>file: <select name="file">';
            foreach (glob(config('custom.userRoot') . '/data/incomingLinks/*/*') as $filename) {
                echo "<option value=\"$filename\" " . (Request::input('file') == $filename ? 'SELECTED' : '') . ">$filename</option>";
            }
            echo '</select>
                <div>source: <input name="source"> semrush, seomoz, majestic</div>
                <button type="submit">Submit</button>
                </form>';

            return '';
        }

        set_time_limit(60 * 60);

        $fp = fopen(Request::input('file'), 'r');
        if (! $fp) {
            throw new Exception("Couldn't open $filePath.");
        }

        $lineNumber = 0;
        $lastDomain = '';
        $lastURL = '';
        while (($line = fgets($fp)) !== false) {
            $lineNumber++;
            $attributes = null;

            switch ($source = Request::input('source')) {
                case 'seomoz':
                    if ($lineNumber < 7) {
                        echo "<p>Skipping '$line'.</p>";

                        continue 2;
                    }
                    $parts = str_getcsv($line, ',');
                    // URL,Title,Anchor Text,Spam Score,Page Authority,Domain Authority,Number of Domains Linking to this Page,Number of Domains Linking to Domain,Origin,Target URL,Link Equity,No Link Equity,Only rel=nofollow,Only follow,301
                    if (count($parts) != 15) {
                        throw new Exception('Unexpected number of columns (' . count($parts) . ") for '$line'.");
                    }
                    $attributes = ['pageTitle' => $parts[1], 'url' => $parts[0], 'linksTo' => $parts[9], 'anchorText' => $parts[2],
                        'pageAuthority' => $parts[4], 'domainAuthority' => $parts[5], 'followable' => ($parts[12] == 'Yes' ? 'n' : 'y'),
                        'source' => 'seomoz',
                    ];

                    break;

                default:
                    throw new Exception("Unknown source '$source'.");
            }

            // Seomoz has many of the same URL in a row (multiple links I guess?)
            if ($attributes['url'] == $lastURL) {
                echo "<p>Skipping '" . htmlspecialchars($attributes['url']) . "' because is same URL again.</p>";

                continue;
            }
            $lastURL = $attributes['url'];

            $domain = WebsiteTools::getRootDomainName($attributes['url']);

            /*
            if ($domain == $lastDomain) {
                echo "<p>Skipping '".htmlspecialchars($attributes['url'])."' because is same domain again.</p>";
                continue;
            }
            $lastDomain = $domain;
            */

            $output = "pageAuthority: $attributes[pageAuthority], domainAuthority: $attributes[domainAuthority]";

            IncomingLink::where('url', $attributes['url'])->where(function ($query) use ($attributes): void {
                $query->where('pageAuthority', '!=', $attributes['pageAuthority'])->orWhereNull('pageAuthority');
            })->update(['pageAuthority' => $attributes['pageAuthority'], 'domainAuthority' => $attributes['domainAuthority']]);
            IncomingLink::where('domain', $domain)->where(function ($query) use ($attributes): void {
                $query->where('domainAuthority', '!=', $attributes['domainAuthority'])->orWhereNull('domainAuthority');
            })
                ->update(['domainAuthority' => $attributes['domainAuthority']]);

            echo '<p><div><a href="' . htmlspecialchars($attributes['url']) . '">' . htmlspecialchars($attributes['url']) . '</a></div>' . htmlspecialchars($output) . '</p>';
            // if ($lineNumber > 4000) exit();
        }

        return 'done';
    }

    private function symlink()
    {
        //@unlink('/home/hostelz/dev/public/pics/imported/listings/big/1/335201.jpg');
        return (string) symlink('../../originals/1/335201.jpg', '/home/hostelz/dev/public/pics/imported/listings/big/1/335201.jpg');
    }

    private function setTimeLimit(): void
    {
        echo '<br>request_terminate_timeout:' . (string) ini_get('request_terminate_timeout') . ' max_execution_time: ' . ini_get('max_execution_time');
        set_time_limit(4);
        echo '<br>request_terminate_timeout:' . (string) ini_get('request_terminate_timeout') . ' max_execution_time: ' . ini_get('max_execution_time');
        echo 'ok';
    }

    private function filterCityComments(): void
    {
    }

    private function fixOldLinkDomains(): void
    {
        set_time_limit(1 * 60 * 60); // Note: This also resets the timeout timer.
        DB::disableQueryLog(); // to save memory

        $links = DB::table('links')->select('id', 'domain', 'url')->where('domain', 'like', '%?%')->get()->all();

        foreach ($links as $link) {
            $domain = WebsiteTools::getRootDomainName($link->url, true);
            if ($domain != $link->domain) {
                echo "$link->id $link->url: $link->domain -> $domain<br>";
                DB::table('links')->where('id', $link->id)->update(['domain' => $domain]);
            }
        }
    }

    private function fixListingDomains(): void
    {
        $listings = Listing::where('websiteDomain', '!=', '')->where('websiteDomain', 'like', '%?%')->select(['id', 'web', 'websiteDomain'])->get();

        foreach ($listings as $listing) {
            $domain = WebsiteTools::getRootDomainName($listing->web, true);
            if ($domain != $listing->websiteDomain) {
                echo "$listing->id $listing->web: $listing->websiteDomain -> $domain<br>";
                Listing::where('id', $listing->id)->update(['websiteDomain' => $domain]);
            }
        }
    }

    private function fixIncomingLinkDomains(): void
    {
        set_time_limit(8 * 60 * 60); // Note: This also resets the timeout timer.
        DB::disableQueryLog(); // to save memory

        $links = IncomingLink::where('domain', '!=', '')->where('domain', 'like', '%?%')->select(['id', 'url', 'domain'])->get();

        foreach ($links as $link) {
            $domain = WebsiteTools::getRootDomainName($link->url, true);
            if ($domain != $link->domain) {
                echo "$link->id $link->url: $link->domain -> $domain<br>";
                IncomingLink::where('id', $link->id)->update(['domain' => $domain]);
            }
        }
    }

    private function showIncomingLinkDomains(): void
    {
        $domains = IncomingLink::where('source', 'majestic')->where('contactStatus', 'todo')->orderBy('domain')->pluck('domain');
        foreach ($domains as $domain) {
            echo "$domain<br>";
        }
    }

    private function assignTodoLinksToMarketingUsers()
    {
        return IncomingLink::assignTodoLinksToMarketingUsers();
    }

    private function pageCacheMaintenanceTasks()
    {
        return PageCache::maintenanceTasks();
    }

    private function getRequestUri()
    {
        return Request::getRequestUri();
    }

    private function minifyJavascript()
    {
        $name = 'city';

        try {
            try {
                $contents = view('js/' . $name); // regular js path
            } catch (InvalidArgumentException $e) { // template not found
                $contents = view('Lib/js/' . $name); // also try from the Lib/js path
            }
        } catch (InvalidArgumentException $e) { // template not found
            App::abort(404);
        }

        // echo $contents;

        $contents = "alert();\nangle = (i - 3) * (Math.PI * 2) / 12; // THE ANGLE TO MARK\n// single-line alone\nalert()\n";

        return MinifyJavascript::minifyString($contents);
    }

    private function testSendMail(): void
    {
        $from = 'david@hostelz.com';
        ini_set('sendmail_from', $from);
        $result = mail('orrd101@yahoo.com', 'subject here', 'message here', 'From: ' . $from, '-f' . $from);
        dd($result);
    }

    private function orphanListingDuplicates()
    {
        $listingIDs = Listing::pluck('id')->toArray();
        $dupListingIDs = ListingDuplicate::pluck('listingID')->unique()->toArray();
        // $dupListingIDs = ListingDuplicate::pluck('id')->unique()->toArray();
        $orphanListingIDs = array_diff($dupListingIDs, $listingIDs);

        foreach ($orphanListingIDs as $orphanListingID) {
            echo "$orphanListingID<br>";
        }

        return 'ok';
    }

    private function checkOldLinks()
    {
        $oldLinks = DB::table('links')->where('status', '!=', 'already')->where('status', '!=', 'new')->where('status', '!=', 'hostelSpidering')->pluck('domain')->all();
        $incomingLinks = IncomingLink::readyTodo()->pluck('domain')->toArray();
        $matchedLinks = array_unique(array_intersect($oldLinks, $incomingLinks));
        //return count($matchedLinks);

        $count = 0;
        foreach ($matchedLinks as $domain) {
            $oldLink = DB::table('links')->where('domain', $domain)->first();
            $incomingLink = IncomingLink::where('domain', $domain)->where('contactStatus', 'todo')->first();

            if ($count++ > 1000) {
                return $count;
            }
            echo "$oldLink->url ($oldLink->status) -> $incomingLink->url ($incomingLink->contactStatus)<br>";
        }
    }

    private function searchForContactEmails(): void
    {
        dd(WebsiteTools::searchForContactEmails('hostelz.com', 'david orr'));
    }

    private function setSpecialListings()
    {
        $cityInfo = CityInfo::find(30454);
//        if ($cityInfo->setSpecialListings()) {
//            $cityInfo->save();
//        }

        return $cityInfo;
    }

    private function tldTest()
    {
        return WebsiteTools::getRootDomainName('http://www.org.gov.co.uk?jhkjh');
    }

    private function updateIncomingLinks(): void
    {
        set_time_limit(8 * 60 * 60); // Note: This also resets the timeout timer.
        DB::disableQueryLog(); // to save memory
        echo 'Update Link Information: ';
        $incomingLinks = IncomingLink::where('checkStatus', '')->orderBy('id', 'asc')->limit(4000)->get();
        foreach ($incomingLinks as $incomingLink) {
            echo "[$incomingLink->id] ";
            $incomingLink->updateLinkInformation();
            $incomingLink->save();
        }
    }

    private static function testAdPlace()
    {
        $cityInfo = CityInfo::find(18561);

        return Ad::getAdForCity($cityInfo);

        $ad = Ad::find(1);
        $ad->placeType = 'CityInfo';
        $ad->placeID = 18561;

        //$ad->placeType = 'ContinentInfo'; $ad->placeString = 'North America';
        //$ad->placeType = 'Region'; $ad->placeID = 147; $ad->placeString = 'Florida';
        //dd($ad->getPlaceObject());
        return $ad->placeFullDisplayName(true);
    }

    private static function scrapePriceOfTravelTours(): void
    {
        $scrapeURL = 'http://www.priceoftravel.com/1835/list-of-free-walking-tours-around-the-world/';
        $data = WebsiteTools::fetchXPath($scrapeURL, [], [
            'urls' => '//article/div[@class="content"]/ul/li/a/@href',
        ]);

        foreach ($data['urls'] as $url) {
            echo "$url<br>";
            $result = IncomingLink::addNewLink(['url' => $url, 'source' => $scrapeURL, 'contactStatus' => 'todo', 'category' => 'tour',
            ], false);
        }
    }

    private static function dmozScanTopics(): void
    {
        /*
        To get the categories list:

            wget http://rdf.dmoz.org/rdf/structure.rdf.u8.gz
            gunzip structure.rdf.u8.gz
            grep "<Topic r:id=" structure.rdf.u8 > topics.txt
        */

        set_time_limit(5 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $fp = fopen(config('custom.userRoot') . '/data/dmoz/topics.txt', 'r');
        while (($line = fgets($fp)) !== false) {
            $topic = trim(strstr($line, '"'), '"');
            $topic = substr($topic, 0, strpos($topic, '"'));
            if (stripos($topic, 'Top/Regional') === false && stripos($topic, 'Top/Recreation') === false) {
                continue;
            }
            if (stripos($topic, 'Tour_Operators') === false && stripos($topic, 'Pub_Crawl') === false && stripos($topic, 'Walking_Tour') === false) {
                continue;
            }

            echo "<h3><a href=https://www.dmoz.org/$topic>$topic</a></h3>";

            $name = $placeType = $placeString = '';
            $placeID = 0;
            if (stripos($topic, 'Top/Regional') !== false) {
                $parts = explode('/', str_replace('United Kingdom/', '', str_replace('_', ' ', $topic)));
                if ($parts[2] == 'Africa') {
                    continue;
                } // not worth doing all the African safaris
                if (in_array('Outbound', $parts)) {
                    continue;
                }
                $end = array_search('Travel and Tourism', $parts);
                if (! $end) {
                    $end = array_search('Business and Economy', $parts);
                }
                if (count($parts) > 3 && $end > 3) {
                    $country = $parts[3];
                    $city = '';
                    if ($end > 4) {
                        $city = $parts[$end - 1];
                    }
                    if ($country == 'United States') {
                        $country = 'USA';
                    }
                    if ($city == 'Saint Petersburg') {
                        $city = 'St. Petersburg';
                    }
                    if ($city == 'Miami') {
                        $city = 'Miami Beach';
                    }
                    if ($city == 'Brisbane CBD') {
                        $city = 'Brisbane';
                    }
                    if ($city == 'Melbourne CBD') {
                        $city = 'Melbourne';
                    }
                    if ($city == 'Sydney CBD') {
                        $city = 'Sydney';
                    }
                    if ($city == 'Greater Sydney') {
                        $city = 'Sydney';
                    }
                    if ($city == 'Manhattan') {
                        $city = 'New York City';
                    }
                    $name = "$country, $city";
                }
                if ($country != '') {
                    $countryInfo = CountryInfo::where('country', $country)->first();
                    if ($countryInfo) {
                        if ($city == '') {
                            $placeType = 'CountryInfo';
                            $placeID = $countryInfo->id;
                        } else {
                            $cityInfo = CityInfo::where('country', $country)->where('city', $city)->where('hostelCount', '>', 0)->first();
                            if (! $cityInfo && strpos($city, ' City')) {
                                $city = str_replace(' City', '', $city);
                                $cityInfo = CityInfo::where('country', $country)->where('city', $city)->where('hostelCount', '>', 0)->first();
                            }
                            if (! $cityInfo && strpos($city, ' Metro')) {
                                $city = str_replace(' Metro', '', $city);
                                $cityInfo = CityInfo::where('country', $country)->where('city', $city)->where('hostelCount', '>', 0)->first();
                            }
                            if ($cityInfo) {
                                $placeType = 'CityInfo';
                                $placeID = $cityInfo->id;
                            } else {
                                $cityInfo = CityInfo::where('country', $country)->where('region', $city)->where('hostelCount', '>', 0)->first();
                                if ($cityInfo) {
                                    $placeType = 'Region';
                                    $placeID = $countryInfo->id;
                                    $placeString = $cityInfo->region;
                                }
                            }
                        }
                    }
                }
                if ($placeType == '') {
                    echo 'SKIPPING.';

                    continue;
                }
            }

            $topic = substr($topic, 4);
            echo "($name) [$placeType:$placeString:$placeID]<br>";
            $urls = self::scrapeDMOZ($topic);
            if (! $urls) {
                continue;
            }

            foreach ($urls as $url) {
                echo "<a href=$url[url]>$url[url]</a> - $url[title] - $url[description]: ";

                $text = "$topic - $url[title] - $url[description]";
                if (! containsWord($text, ['walk', 'walking', 'bike', 'ghost', 'haunted', 'crawl'])) {
                    // If not in a certain city, skip
                    if ($placeType != 'CityInfo') {
                        echo 'SKIPPING<br>';

                        continue;
                    }
                    if (containsWord($text, ['luxury', 'limousine', 'custom', 'customized', 'wedding', 'cruise', 'hotels', 'hotel', 'golf', 'chartered', 'charters']) || stripos($text, 'package') !== false) {
                        echo 'SKIPPING<br>';

                        continue;
                    }
                }

                $result = IncomingLink::addNewLink([
                    'url' => $url['url'], 'source' => 'https://www.dmoz.org/' . $topic, 'contactStatus' => 'todo', 'category' => 'tour',
                    'placeType' => $placeType, 'placeID' => $placeID, 'placeString' => $placeString, 'notes' => "\"$url[description]\"\n",
                    'pageTitle' => $url['title'],
                ], false);
                if ($result == 'exists') {
                    echo '[exists]<br>';
                } elseif (isset($result->id)) {
                    echo '[<a href=/staff/incoming-links-contact/' . $result->id . '>ADDED</a>]<br>';
                } else {
                    print_r($result);
                    echo '!!! <br>';
                }
            }
        }
        fclose($fp);
    }

    private static function scrapeDMOZ($topic)
    {
        $scrapeURL = 'https://www.dmoz.org/' . $topic;
        $data = WebsiteTools::fetchXPath($scrapeURL, [], [
            'urls' => '//ul[@class="directory-url"]/li/a[@class="listinglink"]/@href',
            'titles' => '//ul[@class="directory-url"]/li/a[@class="listinglink"]/text()',
            'descriptions' => '//ul[@class="directory-url"]/li/text()[2]',
        ]);
        if (! $data || ! isset($data['urls'])) {
            return null;
        }
        if (! is_array($data['urls'])) { // was a single result, make it into an array
            $data = [
                'urls' => [$data['urls']],
                'titles' => [$data['titles']],
                'descriptions' => [$data['descriptions']],
            ];
        }
        $return = [];
        foreach ($data['urls'] as $key => $url) {
            $return[] = [
                'url' => $url,
                'title' => $data['titles'][$key],
                'description' => substr($data['descriptions'][$key], 2),
            ];
        }

        return $return;
    }

    private static function moveRatingsBackToReviews(): void
    {
        // $ratings = Rating::where('notes', 'like', 'Copied from review %')->where('verified', Rating::$statusOptions['approved'])->get();
        $reviews = Review::whereIn('status', ['deniedReview', 'removedReview'])->get();

        // foreach ($ratings as $rating) {
        foreach ($reviews as $review) {
            //$reviewNum = filter_var($rating->notes, FILTER_SANITIZE_NUMBER_INT);
            //$review = Review::find($reviewNum);
            //if (!$review) {
            //    echo 'no review!';
            //    return;
            //}
            $rating = Rating::where('hostelID', $review->hostelID)->where('comment', $review->editedReview ? $review->editedReview : $review->review)->first();
            if (! $rating) {
                echo 'not found.';

                continue;
            }

            /*
            if ($rating->hostelID != $review->hostelID) {
                echo "diff listing! $rating->hostelID != $review->hostelID";
                return;
            }*/

            echo "<br>$rating->hostelID $rating->id ($rating->rating) = $review->id ($review->status) ";

            continue;
            if ($rating->comment == $review->editedReview || $rating->comment == $review->review) {
                echo 'same!';
            } else {
                echo 'diff!';
            }

            if (trim($review->notes) != '' && trim($review->notes) != 'Copied to comment.') {
                echo "notes: $review->notes";
                //continue;
            }

            $review->editedReview = $rating->comment;
            $review->rating = $rating->rating;
            $review->notes = '';
            $review->publishAsARating();

            $rating->delete();
        }
    }

    private static function recordPrice()
    {
        // 10548 2016-05-31 n:1 private p:2  en 1
        $listingID = 10548;

        $existingMatches = PriceHistory::where('listingID', $listingID)->where('roomType', 'private')->where('month', '2016-05-01')->get(); // roomType, month
        $existing = $existingMatches->where('peoplePerRoom', 2);
        print_r($existingMatches);
        //print_r($existing);
        $new = new PriceHistory(['listingID' => $listingID]);
        $existingMatches->push($new);
        print_r($existingMatches);

        return 'ok';
        foreach ($roomAvailabilities as $roomAvailability) {
            $pricePerNight = (float) $roomAvailability->averagePricePerBlockPerNight(false, 'USD');

            if ($searchCriteria->roomType == 'private') {
                if ($pricePerNight > self::MAX_PRIVATE_PRICE_USD) {
                    // logError("Private room price $pricePerNight for $listingID is too high.");
                    continue;
                }
                $existing = $existingMatches->where('peoplePerRoom', $roomAvailability->roomInfo->peoplePerRoom);
            } else {
                if ($pricePerNight > self::MAX_DORM_PRICE_USD) {
                    // logError("Dorm room price $pricePerNight for $listingID is too high.");
                    continue;
                }
                $existing = $existingMatches; // we ignore peoplePerRoom for dorm rooms
            }

            if ($existing->count() > 1) {
                logError("Multiple duplicate price histories for $listingID " . $searchCriteria->summaryForDebugOutput());
            }
            $existing = $existing->first();

            if ($existing) {
                $existing->averageInAnotherPrice($pricePerNight);
                $existing->save();
            } else {
                $new = new self(['listingID' => $listingID, 'month' => $searchCriteria->startDate->format('Y-m') . '-01',
                    'roomType' => $searchCriteria->roomType,
                    'peoplePerRoom' => $searchCriteria->roomType == 'private' ? $roomAvailability->roomInfo->peoplePerRoom : 0,
                    'dataPointsInAverage' => 1, 'averagePricePerNight' => $pricePerNight, ]);
                $new->save();
                $existingMatches->push($new);
            }
        }
    }

    private function emailPanoramaStatus(): void
    {
        set_time_limit(2 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $sendingUser = User::find(1);

        $hostels = User::areAllowedToLogin()->havePermission('affiliate')->doesntHavePermission('staff')->where('affiliateURLs', '')->limit(100)->get();

        foreach ($users as $user) {
            echo "<br>$user->username: ";
            $emails = MailMessage::forRecipientOrBySenderEmail($user->username)->where('userID', $sendingUser->id)->count();
            if ($emails) {
                echo 'has emails';

                continue;
            }

            //if ($user->affiliateURLs)
            //    $messageText = "Hi.  I noticed that you signed up for our affiliate program awhile back.  Let me know if you have any questions to need any assistance with setting up your links.";
            //else
            $messageText = "Hi.  I noticed that you signed up for our affiliate program awhile back, but haven't yet set your website URLs so you can get paid for bookings.\n\nThe first step is to add a link to any Hostelz.com page from your website. Once you've done that, all you have to do is enter the URLs of those pages into our affiliate system so that we know to give you a commission for any bookings that come from people who used the link from your website.  You can do that from the \"Affiliate Program\" link on your Hostelz.com user menu, or by going directly to this page: \n\n " . routeURL('affiliate:menu', [], 'publicSite') .
                "\n\nLet me know if you have any questions.";

            $mail = MailMessage::createOutgoing([
                'recipient' => $user->getEmailAddress(),
                'subject' => 'affiliate program',
                'bodyText' => $messageText . "\n" . $sendingUser->getEmailSignature(),
            ], $sendingUser);

            echo 'emailed!';
        }
    }

    private function emailAffiliates(): void
    {
        set_time_limit(10 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $sendingUser = User::find(1);

        // $logs = EventLog::where('subjectString', 'becomeAffiliate')->groupBy('subjectID')->get();
        $users = User::areAllowedToLogin()->havePermission('affiliate')->doesntHavePermission('staff')->where('affiliateURLs', '')->get();

        foreach ($users as $user) {
            echo "<br>$user->username: ";

            $listing = Listing::anyMatchingEmail($user->username)->first();
            if ($listing) {
                echo '(listing)';

                continue;
            }

            $emails = MailMessage::forRecipientOrBySenderEmail($user->username)->where('userID', $sendingUser->id)->count();
            if ($emails) {
                echo 'has emails';

                continue;
            }

            //if ($user->affiliateURLs)
            //    $messageText = "Hi.  I noticed that you signed up for our affiliate program awhile back.  Let me know if you have any questions to need any assistance with setting up your links.";
            //else
            $messageText = "Hi.  I noticed that you signed up for our affiliate program awhile back, but haven't yet set your website URLs so you can get paid for bookings.\n\nThe first step is to add a link to any Hostelz.com page from your website. Once you've done that, all you have to do is enter the URLs of those pages into our affiliate system so that we know to give you a commission for any bookings that come from people who used the link from your website.  You can do that from the \"Affiliate Program\" link on your Hostelz.com user menu, or by going directly to this page: \n\n " . routeURL('affiliate:menu', [], 'publicSite') .
                "\n\nLet me know if you have any questions.";

            $mail = MailMessage::createOutgoing([
                'recipient' => $user->getEmailAddress(),
                'subject' => 'affiliate program',
                'bodyText' => $messageText . "\n" . $sendingUser->getEmailSignature(),
            ], $sendingUser);

            echo 'emailed!';
        }
    }

    private function findMissingPics(): void
    {
        set_time_limit(5 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $pics = Pic::where('subjectType', 'imported')->where('status', 'ok')->where('originalFileType', '')->get();

        foreach ($pics as $pic) {
            if (! file_exists($pic->filePath(''))) {
                echo "$pic->id missing";

                return;
            }

            $originalFile = $pic->filePath('originals');

            if ($pic->originalFiletype == '') {
                if (file_exists($originalFile)) {
                    echo "$pic->id original exists!";

                    return;
                    $image = ImageProcessor::makeFromFile($originalFile);
                    if (! $image) {
                        logWarning("Couldn't load $originalFile pic from $pic->id.");

                        continue;
                    }
                    $pic->setAttributesFromImage($image);
                    if ($pic->originalFiletype == '') {
                        logWarning("originalFiletype unknown for $pic->id.");

                        continue;
                    }
                    rename($originalFile, $originalFile . '.' . $pic->originalFiletype);
                    if ($pic->status == 'ok') {
                        $pic->status = 'markedForEditing';
                    }
                    $pic->save();

                    continue;
                }
            }

            if (file_exists($originalFile)) {
                if (! file_exists($pic->filePath('big'))) {
                    logWarning("Couldn't find big pic from $pic->id.");

                    continue;
                }
                echo '.';

                continue;
            }

            echo "(copying $pic->id) ";

            $sourcePic = $pic->filePath('');
            if (file_exists($pic->filePath('big'))) {
                logWarning("Big pic exists for imported $pic->id!");
                $sourcePic = $pic->filePath('big');
            }

            $image = ImageProcessor::makeFromFile($sourcePic);
            if (! $image) {
                logWarning("Couldn't load pic from $pic->id.");

                continue;
            }
            $pic->setAttributesFromImage($image);
            if ($pic->originalFiletype == '') {
                logWarning("originalFiletype unknown for $pic->id.");

                continue;
            }
            copy($sourcePic, $pic->filePath('originals')); // (have to get new 'originals' path now that we have the originalFiletype)
            //if ($sourcePic != $pic->filePath('big')) copy($sourcePic, $pic->filePath('big'));

            $pic->save();
        }
    }

    private function incomingLinkMultipleSameDomain(): void
    {
        $urls = IncomingLink::groupBy('domain')->havingRaw('count(*) > 1')->pluck('domain');

        foreach ($urls as $domain) {
            // Remove todo ones if there are matching non-todo ones
            $links = IncomingLink::where('domain', $domain)->get();
            $todoCount = 0;
            $hasNotTodo = false;
            foreach ($links as $link) {
                if ($link->contactStatus == 'todo') {
                    $todoCount++;
                } else {
                    $hasNotTodo = true;
                }
            }
            if ($todoCount != 0 && $hasNotTodo && ! $link->allowingMultipleURLsToThisDomain()) {
                foreach ($links as $link) {
                    echo "<br>$link->createDate <a href=\"" . routeURL('staff-incomingLinks', $link->id) . "\">$link->url</a> $link->contactStatus/$link->contactStatusSpecific $link->notes ";
                    if ($link->contactStatus == 'todo') {
                        $link->delete();
                        echo '<b>TODO deleted because non-todo</b>';
                    }
                }
            }

            // Check for multiple todos
            if ($todoCount > 1) {
                // Fetch them again (some might have been deleted above).
                $links = IncomingLink::where('domain', $domain)->where('contactStatus', 'todo')->get();
                $isFirstOne = true;
                foreach ($links as $link) {
                    echo "<br>$link->createDate <a href=\"" . routeURL('staff-incomingLinks', $link->id) . "\">$link->url</a> $link->contactStatus/$link->contactStatusSpecific $link->notes ";
                    if ($isFirstOne) {
                        echo '(keeping)';
                        $isFirstOne = false;

                        continue;
                    }
                    $link->delete();
                    echo '<b>TODO deleted because multiple</b>';
                }
            }
        }
    }

    private function databaseFieldValuesToLowercase(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $table = 'comments';
        $field = 'email';
        $items = DB::table($table)->where($field, '!=', '')->whereRaw("$field != BINARY LOWER($field)")
            ->select('id', $field)->limit(10000)->get()->all();
        foreach ($items as $item) {
            $lowercase = mb_strtolower($item->$field);
            if ($item->$field != $lowercase) {
                echo "$item->id " . $item->$field . " != $lowercase<br>";
                DB::table($table)->where('id', $item->id)->update([$field => $lowercase]);
            }
        }
    }

    private function incomingLinkListingWebsites(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listingDomains = Listing::where('web', '!=', '')->groupBy('web')->pluck('web')->toArray();
        foreach ($listingDomains as &$url) {
            $url = WebsiteTools::getRootDomainName($url);
        }
        $listingDomains = array_unique(array_map('strtolower', $listingDomains));

        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $links = IncomingLink::where('domain', '!=', '')->where('associatedModelID', 0)->select('id', 'domain')->get();
        foreach ($links as $link) {
            if (! in_array($link->domain, $listingDomains)) {
                continue;
            }
            echo "<br>$link->id $link->domain ";
            $link = IncomingLink::find($link->id);
            $listings = Listing::where('web', 'like', '%' . $link->domain . '%')->areLive()->get();
            if ($listings->isEmpty()) {
                echo 'listing not found!';

                continue;
            }
            if ($listings->count() > 1) {
                echo 'multiple listings!';

                continue;
            }
            $listing = $listings->first();
            $link->associatedModelID = $listing->id;
            $link->associatedModelType = 'Listing';
            $link->save();
        }
    }

    private function trimIncomingLinkEmails(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $ids = IncomingLink::where('contactEmails', 'like', ' %')->pluck('id');
        foreach ($ids as $id) {
            $link = IncomingLink::find($id);
            $contactEmails = implode(',', $link->contactEmails);
            echo "<br>[$id] '$contactEmails' -> ";
            $contactEmails = trim($contactEmails);
            $link->contactEmails = $contactEmails;
            echo "'$contactEmails'";
            $link->save();
        }
    }

    private function fixIncomingLinkContactTopics(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $ids = IncomingLink::where('contactTopics', '!=', '')->pluck('id');
        foreach ($ids as $id) {
            echo "[$id] ";
            $link = IncomingLink::find($id);
            if (array_diff($link->contactTopics, IncomingLink::$contactTopicOptions)) {
                echo "<br>$link->id: " . implode(',', $link->contactTopics) . ' -> ';
                $link->contactTopics = array_intersect($link->contactTopics, IncomingLink::$contactTopicOptions);
                echo implode(',', $link->contactTopics);
                $link->save();
            }
        }
    }

    private function validateAndSaveBooking(): void
    {
        $booking = Booking::find(384405);
        $booking->validateAndSave();
    }

    private function duplicateLinks(): void
    {
        $urls = IncomingLink::groupBy('url')->havingRaw('count(*) > 1')->pluck('url');

        foreach ($urls as $url) {
            echo '<br>';
            $links = IncomingLink::where('url', $url)->get();
            $hasTodo = $hasNotTodo = false;
            foreach ($links as $link) {
                if ($link->contactStatus == 'todo') {
                    $hasTodo = true;
                } else {
                    $hasNotTodo = true;
                }
            }
            foreach ($links as $link) {
                echo "<br>$link->createDate <a href=\"" . routeURL('staff-incomingLinks', $link->id) . "\">$link->url</a> $link->contactStatus/$link->contactStatusSpecific $link->notes ";
                if ($link->contactStatus == 'todo' && $hasNotTodo) {
                    $link->delete();
                    echo '<b>TODO deleted</b>';
                }
            }
        }
    }

    private function changeIncomingLinkStatuses(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $ids = IncomingLink::where('contactStatus', 'open')->pluck('id');
        foreach ($ids as $id) {
            echo "[$id] ";
            $incomingLink = IncomingLink::find($id);
            $contactAddresses = $incomingLink->allContactAddresses();
            if (! $contactAddresses) {
                continue;
            }
            $emails = MailMessage::forRecipientOrBySenderEmail($contactAddresses)->where('status', '!=', 'outgoing')->get();
            if ($emails->isEmpty()) {
                $incomingLink->contactStatus = 'initialContact';
            } else {
                $incomingLink->contactStatus = 'discussing';
            }
            echo "($incomingLink->contactStatus) ";
            $incomingLink->save();
        }
    }

    private function phpinfo(): void
    {
        phpinfo();
    }

    private function determineCountryNameFromCountryCode()
    {
        return CountryInfo::determineCountryNameFromCountryCode('us');
    }

    private function contactHostelsAboutGoodReview(): void
    {
        set_time_limit(5 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $reviews = Review::where('rating', '>=', 4)->where('status', 'publishedReview')->get();

        foreach ($reviews as $review) {
            echo "[review $review->id] ";
            $listing = $review->listing;
            if (! $listing->isLive()) {
                continue;
            }
            if ($listing->getLiveReview()->id != $review->id) {
                continue;
            }
            $mail = $listing->sendEmail(['subject' => langGet('Listing.emails.positiveReviewNotice.subject'),
                'bodyText' => langGet('Listing.emails.positiveReviewNotice.bodyText', ['listingURL' => $listing->getURL('publicSite', 'en', true)]), ]);
            echo 'sent! ';
        }
    }

    private function makeDirs(): void
    {
        Pic::make100Dirs('hostels/panorama/originals');
    }

    private function fillInBookingFieldsFromMatchingClick(): void
    {
        BookingClick::fillInBookingFieldsFromMatchingClick(Booking::find(380220), '');
    }

    private function problem(): void
    {
        $listing = Listing::find(164435);
        $listing->listingMaintenance()->updateListing();
        $onlineReservations = (! $listing->activeImporteds->where('availability', true)->isEmpty());
        echo 'here2';
        exit();
    }

    private function testAutoRotateDimensions(): void
    {
        $pic = Pic::find(1546137);
        $image = ImageProcessor::makeFromString($pic->getImageData('originals'));
        $size = $image->getImageDimensions();
        print_r($size);
        echo '<br>';
        $didAutoRotate = $image->autoRotateImage();
        echo "(didAutoRotate:$didAutoRotate)<br>";
        $size = $image->getImageDimensions();
        print_r($size);
        echo '<br>';
    }

    private function updateListings2()
    {
        set_time_limit(5 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $startTime = time();

        $output = ListingMaintenance::updateListings(Listing::where('verified', Listing::$statusOptions['db2']) // ->arePrimaryPropertyType()
        ->where(function ($query): void {
            $query->whereNull('lastUpdate');
            // ->orWhere('lastUpdate', '<', Carbon::now()->subDays(30)->format('Y-m-d'));
        })
            ->orderBy('lastUpdate')->limit(10000)->pluck('id')->all(), true);

        $output .= '<h1>Total time: ' . ((time() - $startTime) / 60) . ' minutes</h1>';

        return $output;
    }

    private function setQuestionSetIDs()
    {
        $results = QuestionResult::all();

        foreach ($results as $result) {
            $questionSet = QuestionSet::where('setName', $result->setName)->first();
            if (! $questionSet) {
                return 'not found.';
            }
            $result->questionSetID = $questionSet->id;
            $result->save();
        }
    }

    private function questionResults()
    {
        $result = QuestionResult::find(164);

        return $result->prepResultsForDisplay();
    }

    private function testDates(): void
    {
        $listing = Listing::find(1);
        $listing->lastUpdated = Carbon::now();
        var_dump($listing->lastUpdated);
        echo $listing->lastUpdated;
    }

    private function deleteInactiveUnapprovedPriceline()
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        /* nevermind, weren't that many to bother with */
        $listings = Listing::where('verified', '=', Listing::$statusOptions['db2'])->where('source', 'like', '%Priceline%')->
        where('onlineReservations', 0)->with('importeds')->get();
        echo $listings->count();
        foreach ($listings as $listing) {
            echo "<br>$listing->id ";
            if ($listing->importeds->isEmpty()) {
                echo 'no importeds.';

                continue;
            }
            if (! $listing->reviews->isEmpty()) {
                echo 'has reviews.';

                continue;
            }
            foreach ($listing->importeds as $imported) {
                if ($imported->system != 'Priceline') {
                    echo "system is '$imported->system'";

                    continue 2;
                }
            }
            echo 'delete! ';
            foreach ($listing->importeds as $imported) {
                $imported->delete();
            }
            $listing->delete();
        }

        return 'done';
    }

    private function restoreHostelbookersActiveImporteds(): void
    {
        $ids = [
            // (copy and past IDs from /home/hostelz/dev/hostelbookers-last-active-imported-ids)
        ];

        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        foreach ($ids as $id) {
            $imported = Imported::find($id);
            $imported->status = 'active';
            $imported->save();
            echo "$imported->id $imported->system ";
        }
    }

    private function deleteHostelbookersPics(): void
    {
        set_time_limit(8 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $ids = Imported::where('system', 'Hostelbookers')->pluck('id');

        foreach ($ids as $id) {
            $imported = Imported::find($id);
            $listing = $imported->listing;
            echo "<br>$imported->id: ";
            if (! $listing) {
                echo 'no listing!';

                continue;
            }
            // if ($listing->propertyType == 'Hostel') { echo "hostel."; continue; }
            $bestPics = $listing->getBestPics();
            if (! $bestPics || $bestPics->isEmpty()) {
                echo 'no pics!';

                continue;
            }
            if ($bestPics->first()->subjectType == 'imported' && $bestPics->first()->subjectID == $imported->id) {
                echo "uses this imported's pics, skipping.";

                continue;
            }
            echo 'delete! ';
            foreach ($imported->picsObjects as $pic) {
                echo "($pic->id) ";
                $pic->delete();
            }
        }
    }

    private function outputActiveHostelbookersImporteds()
    {
        return Imported::where('system', 'Hostelbookers')->where('status', 'active')->pluck('id')->implode(', ');
    }

    private function grantPlaceDescriptionAccess(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $userIDs = User::havePermission(['reviewer', 'staffWriter'])->pluck('id');

        foreach ($userIDs as $userID) {
            $user = User::find($userID);
            $user->grantPermissions('placeDescriptionWriter');
            $user->save();
            echo "$user->id ";
            //print_r($user->payAmounts);
        }
    }

    private function testLaravelDateMutators(): void
    {
        $ad = new Ad();
        var_dump($ad->startDate);
        $ad->startDate = null;
        var_dump($ad->startDate);
        $ad->startDate = '2015-01-01';
        var_dump($ad->startDate);
    }

    private function sendAfterStayEmails(): void
    {
        return;

        set_time_limit(3 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $bookings = Booking::where('email', '!=', '')
            ->where('startDate', '>=', '2018-01-01')->whereRaw("DATE_ADD(startDate, INTERVAL nights DAY) < '2019-01-01'")
            ->where('listingID', '!=', 0)
            ->where('system', 'bookingdotcom')
            // ->where('bookingDetails', '=', '') // ignore ones that went through our own booking system (already sent those emails)
            ->groupBy('email', 'listingID')->orderBy('startDate')
            ->with('listing')->get();
        foreach ($bookings as $booking) {
            echo "$booking->bookingTime ($booking->startDate for $booking->nights) $booking->email<br>";
            $booking->sendAfterStayEmail();
        }
    }

    private function importHostelsclubBookings()
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $result = WebsiteTools::fetchPage('https://www.hostelspoint.com/affiliate/login.php', ['username' => 'hostelz', 'password' => 'h625fbxk']);
        if (! $result) {
            return;
        }

        for ($page = 1; $page <= 1; $page++) {
            $url = 'https://www.hostelspoint.com/affiliate/res_affiliates_unpaid.php?page=' . $page;
            //$url = 'https://www.hostelspoint.com/affiliate/res_affiliates_paid.php?page='.$page;
            echo "<br>$url<br>";
            $result = WebsiteTools::fetchXPath($url, [], ['table' => "//table[@class='FINN_BO_maincont_table']//tr/td"]);
            //dd($result);
            if (! $result || ! $result['table']) {
                return "Couldn't fetch bookings from '$url'.";
            }
            $bookingDataRows = array_chunk(array_slice($result['table'], 11), 8);

            $bookings = new Collection();

            foreach ($bookingDataRows as $bookingData) {
                if (count($bookingData) != 8) {
                    return "Count wrong for row in '$url'.";
                }
                $bookingID = $bookingData[0];
                if (Booking::where('bookingID', $bookingID)->where('system', 'Hostelsclub')->exists()) {
                    echo "($bookingID exists)<br>";

                    continue;
                }
                $url = 'https://www.hostelspoint.com/affiliate/hotel_reservation.php?mod=preview&id=' . $bookingID;
                $detailsPage = WebsiteTools::fetchXPath($url, [], [
                    'table' => "//td[@id='FINN_BO_tdmaincont']/p", 'details' => "//table[@class='lista']", 'people' => "//table[@class='lista']/tr[2]/td[4]",
                ]);
                if (! $detailsPage || ! $detailsPage['table']) {
                    return "Couldn't fetch bookings from '$url'.";
                }

                $booking = new Booking(['bookingID' => $bookingID, 'system' => 'Hostelsclub',
                    'people' => (int) ($detailsPage['people'] ?? null), 'messageText' => (string) ($detailsPage['details'] ?? null), ]); // details and people may not be available

                $mapToField = ['Nationality' => 'nationality', 'Gender' => 'gender', 'Email' => 'email', 'Telephone' => 'phone', 'Estimated arrival time' => 'arrivalTime'];
                $values = [];
                foreach ($detailsPage['table'] as $bookingDetailRow) {
                    $parts = explode(': ', $bookingDetailRow);
                    $label = $parts[0];
                    $data = implode(': ', array_slice($parts, 1));
                    if (array_key_exists($label, $mapToField)) {
                        $ourField = $mapToField[$label];
                        $booking->$ourField = $data;
                    } else {
                        $values[$label] = $data;
                    }
                }

                $imported = Imported::where('system', 'Hostelsclub')->where('name', $values['Structure name'])->where('city', $values['City'])->first();
                if (! $imported) {
                    echo "Couldn't find '" . $values['Structure name'] . "'";

                    continue;
                }

                $nameParts = explode(' ', $values['Name']);
                $booking->lastName = array_pop($nameParts);
                $booking->firstName = implode(' ', $nameParts);
                $booking->bookingTime = Carbon::createFromFormat('d M Y H:i:s', $values['Made on']);
                $dates = explode(' To: ', $values['From']);
                $booking->startDate = Carbon::createFromFormat('M d, y', $dates[0]);
                $booking->nights = Carbon::createFromFormat('M d, y', $dates[1])->diffInDays($booking->startDate) + 1;
                $booking->depositUSD = Currencies::convert(intval($values['Deposit paid to Hostelsclub']), 'EUR', 'USD');
                $booking->commission = round($booking->depositUSD * 0.50, 2);
                $booking->importedID = $imported->id;
                $booking->listingID = $imported->hostelID;
                $booking->validateAndSave();
                echo 'Saved booking <a href="' . routeURL('staff-bookings', $booking->id) . "\">$booking->id</a><br>";
            }
        }

        return 'done.';
    }

    private function setCampingAllowedForAllCampgrounds(): void
    {
        $listings = Listing::where('propertyType', 'campsite')->get();

        foreach ($listings as $listing) {
            $features = $listing->mgmtFeatures;
            print_r($features);

            continue;
            if (isset($features['extras'])) {
                if (! in_array('camping', $features['extras'])) {
                    $features['extras'][] = 'camping';
                }
            } else {
                $features['extras'] = ['camping'];
            }
            $listing->mgmtFeatures = $features;
            print_r($features);
            $listing->listingMaintenance()->compileFeatures();
            $listing->save();
        }
    }

    private function awardPoints(): void
    {
        auth()->user()->awardPoints('listingRating', 1, false);
    }

    private function recalculatePoints(): void
    {
        $users = User::where('points', '!=', 0)->get();

        foreach ($users as $user) {
            echo "$user->id: has $user->points. ";
            $user->recalculatePoints();
            echo "Recalculated: $user->points.<br>";
        }
    }

    private function bookingsAssignUserIDs(): void
    {
        set_time_limit(1 * 60 * 60);

        $bookings = Booking::where('email', '!=', '')->where('userID', 0)->orderBy('id', 'desc')->limit(10000)->get();

        foreach ($bookings as $booking) {
            $booking->automaticallyAssignToUserWithMatchingEmail();
            if (! $booking->userID) {
                continue;
            }
            $booking->save();
            $booking->awardPoints();
            echo "$booking->id assigned to $booking->userID<br>";
        }
    }

    // export to Excel -> save as CSV -> open in editplus, copy / paste to:
    private function importHostelbookersBookings()
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $data = Request::input('data');
        if (! $data) {
            return '<form method=post><textarea name=data></textarea><button type=submit>submit</button>' . csrf_field() . '</form>';
        }

        /*
        // cookies needed: CFID, CFTOKEN, LOGGED, persist_hostel
        $result = WebsiteTools::fetchXPath('https://admin.hostelbookers.com/login/index.cfm', [ 'strLogin' => 'hostelz', 'strPassword' => 'tno0dw0D4CVA',
            'fuseaction' => 'auth', 'area' => 'affiliates', 'lastpage' => '/affiliates/main/' ],
            [ 'nextPage' => "//a[text()='click here']/@href" ]
        );
        if (!$result['nextPage']) return $result;
        echo $result['nextPage'];
        $result = WebsiteTools::fetchPage($result['nextPage']);
        */

        $data = explode("\n", $data);
        foreach ($data as $key => $line) {
            if (! $key || trim($line) == '') {
                continue;
            }
            $items = str_getcsv($line, "\t");

            list($bookingID, $date, $time, $propName, $location, $name, $startDate, $commission, $status, $nights, $people, $room) = $items;
            if ($status != 'Completed' && $status != 'Confirmed') {
                return "Don't know what to do with status '$status'.";
            }

            $existing = Booking::where('bookingID', $bookingID)->where('system', 'Hostelbookers')->first();
            if ($existing) {
                echo "($bookingID exists)<br>";

                continue;
            }

            /*
                $table->string('email', 255)->index();
            */

            $nameParts = explode(' ', $name);
            $lastName = array_pop($nameParts);
            $firstName = implode(' ', $nameParts);

            $temp = explode('-', $bookingID);
            $bookingIDNumber = array_pop($temp);
            $theirPropertyID = array_pop($temp);

            $imported = Imported::where('system', 'Hostelbookers')->where('intCode', $theirPropertyID)->first();
            if (! $imported) {
                return "'$propName' not found ($location)";
            }

            $listing = Listing::find($imported->hostelID);
            if (! $listing) {
                return "Listing for $imported->id not found.";
            }

            /*
            $result = WebsiteTools::fetchXPath('https://admin.hostelbookers.com/affiliates/reports/index.cfm?fuseaction=viewbooking&booking='.$bookingIDNumber, [ ], [
                'customerEmail' => "/html/body[@id='main.html']/table[@id='main-table']/tbody/tr/td[2]/div[@id='content']/form/div[@class='yellow-box'][1]/p[2]/a",
                'listingEmail' => "/html/body[@id='main.html']/table[@id='main-table']/tbody/tr/td[2]/div[@id='content']/form/div[@class='yellow-box'][2]/p[2]/a",
                'details' => "/html/body[@id='main.html']/table[@id='main-table']/tbody/tr/td[2]/div[@id='content']/form/div[@class='yellow-box'][3]/table"
            ]);
            if (!$result) return "fetch failed for $bookingID.";
            if (!filter_var($result['customerEmail'], FILTER_VALIDATE_EMAIL)) return "invalid customer email '$result[customerEmail]' for $bookingID.";

            if (!$listing->importedEmail) {
                if (!filter_var($result['listingEmail'], FILTER_VALIDATE_EMAIL)) return "invalid listingEmail '$result[listingEmail]' for $bookingID.";
                $listing->importedEmail = [ $result['listingEmail'] ];
                $listing->save();
                echo "[$listing->id importedEmail set to '$result[listingEmail]']<br>";
            }
            */

            echo "$propName is $imported->name<br>";

            $attributes = ['bookingTime' => with(new Carbon("$date $time"))->format('Y-m-d H:i:s'), 'startDate' => Carbon::createFromFormat('j-M-y', $startDate)->format('Y-m-d'),
                'nights' => $nights, 'people' => $people, 'system' => 'Hostelbookers', 'firstName' => $firstName, 'lastName' => $lastName,
                'bookingID' => $bookingID, 'commission' => $commission, 'depositUSD' => round($commission / 0.365, 2), 'importedID' => $imported->id,
                'listingID' => $imported->hostelID, /* 'email' => $result['customerEmail'], 'messageText' => $result['details'] */
                'messageText' => $room, ];
            echo '<pre>';
            print_r($attributes);
            echo '</pre>';

            $booking = new Booking($attributes);
            $booking->validateAndSave();
        }

        return 'done';
    }

    private function redownloadImportedPics(): void
    {
        set_time_limit(12 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $importeds = Imported::where('status', 'active')->where('system', 'BookHostels')->where('pics', 'like', '%,%')
            ->where('propertyType', 'Hostel')->get();

        foreach ($importeds as $imported) {
            $listing = $imported->listing;
            if (! $listing) {
                continue;
            }
            $existingPics = $listing->getBestPics();
            if (! $existingPics) {
                $count = 0;
            } else {
                $count = count($existingPics);
            }
            echo "[$imported->id ($count)] ";
            if ($count <= 1) {
                $imported->downloadPics();
                echo 'dl. ';
            }
        }

        $importeds = Imported::where('status', 'active')->where('system', 'BookHostels')->where('pics', 'like', '%,%')
            ->where('propertyType', '!=', 'Hostel')->get();

        foreach ($importeds as $imported) {
            $listing = $imported->listing;
            if (! $listing) {
                continue;
            }
            $existingPics = $listing->getBestPics();
            if (! $existingPics) {
                $count = 0;
            } else {
                $count = count($existingPics);
            }
            echo "[$imported->id ($count)] ";
            if ($count <= 1) {
                $imported->downloadPics();
                echo 'dl. ';
            }
        }
    }

    private function ignoreIncomingLinksIfLinkingToUs(): void
    {
        set_time_limit(8 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $ids = IncomingLink::where('spiderResults', 'like', '%hostelz%')->where('contactStatus', 'todo')->pluck('id');

        foreach ($ids as $id) {
            $link = IncomingLink::findOrFail($id);
            if (isset($link->spiderResults['Hostelz']) && $link->contactStatus == 'todo') {
                $link->contactStatus = 'ignored';
                $link->contactStatusSpecific = 'already';
                echo "[$link->id: $link->url -> " . json_encode($link->spiderResults['Hostelz']) . ']<br>';
                $link->save();
            }
        }
    }

    private function checkIncomingLinksForListingDomains(): void
    {
        set_time_limit(8 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $ids = IncomingLink::where('associatedModelType', '')->pluck('id');

        foreach ($ids as $id) {
            $link = IncomingLink::findOrFail($id);
            $listing = Listing::where('websiteDomain', $link->domain)->areLiveOrNew()->first();
            if ($listing) {
                echo "[$link->id: $link->url -> ($listing->id) '$listing->web'] ";
                $link->associatedModelType = 'Listing';
                $link->associatedModelID = $listing->id;
                if ($link->category == '') {
                    $link->category = 'accommodation';
                }
                $link->save();
                // exit();
            }
        }
    }

    private function updateMailHostelIDs(): void
    {
        set_time_limit(12 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        // $ids = MailMessage::where('listingID', 1)->pluck('id');
        $ids = MailMessage::where('id', 630761)->pluck('id');

        foreach ($ids as $id) {
            $mail = MailMessage::findOrFail($id);
            $listingID = $mail->determineListingIDs(true);
            if ($listingID == $mail->listingID) {
                continue;
            }
            echo "[$mail->id: $listingID] ";

            break;
            $mail->listingID = $listingID;
            $mail->save();
        }
    }

    private function removeDuplicateEmaailAddressesInListings(): void
    {
        set_time_limit(6 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $emailFields = ['supportEmail', 'managerEmail', 'bookingsEmail', 'importedEmail', 'invalidEmails'];
        $listingIDs = Listing::where(function ($query) use ($emailFields): void {
            $first = true;
            foreach ($emailFields as $field) {
                if ($first) {
                    $query->where($field, 'LIKE', '%,%');
                } else {
                    $query->orWhere($field, 'LIKE', '%,%');
                }
                $first = false;
            }
        })->pluck('id');

        foreach ($listingIDs as $listingID) {
            $listing = Listing::findOrFail($listingID);
            foreach ($emailFields as $field) {
                if (! $listing->$field) {
                    continue;
                }
                $original = $listing->$field;
                $listing->$field = array_unique($original);
                if ($listing->$field != $original) {
                    echo "[$listing->id: " . implode(',', $original) . ' -> ' . implode(',', $listing->$field) . '] ';
                    $listing->save();
                }
            }
        }
    }

    private function updateWebsiteStatuses(): void
    {
        set_time_limit(6 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listingIDs = Listing::where('web', '!=', '')->where('webStatus', WebsiteStatusChecker::$websiteStatusOptions['unknown'])->pluck('id');

        foreach ($listingIDs as $listingID) {
            $listing = Listing::findOrFail($listingID);
            $listing->webStatus = WebsiteStatusChecker::getWebsiteStatus($listing->web, true, true);
            echo "[$listing->id: $listing->web -> $listing->webStatus] ";
            $listing->save();
        }
    }

    private function setListingWebsiteDomains(): void
    {
        // Note: didn't realize this also resets webStatus for all the listings.  Oops.

        set_time_limit(3 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listingIDs = Listing::where('web', '!=', '')->where('websiteDomain', '')->pluck('id');

        foreach ($listingIDs as $listingID) {
            $listing = Listing::findOrFail($listingID);
            $temp = $listing->web;
            $listing->web = '';
            $listing->web = $temp;
            echo "[$listing->id: $listing->web -> $listing->websiteDomain] ";
            $listing->save();
        }
    }

    private function mergeDuplicatePriceHistories(): void
    {
        set_time_limit(1 * 60 * 60);

        $prices = PriceHistory::groupBy('listingID', 'month', 'roomType', 'peoplePerRoom')->havingRaw('count(*) > 1')->get();

        foreach ($prices as $price) {
            $duplicates = PriceHistory::where('listingID', $price->listingID)
                ->where('month', $price->month)->where('roomType', $price->roomType)
                ->where('peoplePerRoom', $price->peoplePerRoom)->get();
            $primary = $duplicates->pop();
            echo "$primary->month $primary->id: $primary->averagePricePerNight ($primary->dataPointsInAverage) -> ";
            foreach ($duplicates as $duplicate) {
                echo "($duplicate->id) $duplicate->averagePricePerNight ($duplicate->dataPointsInAverage) + ";
                for ($i = 0; $i < $duplicate->dataPointsInAverage; $i++) {
                    $primary->averageInAnotherPrice($duplicate->averagePricePerNight);
                }
                $duplicate->delete();
            }
            echo " = $primary->averagePricePerNight ($primary->dataPointsInAverage)<br>";
            $primary->save();
        }
    }

    private function incomingLinkLanguages(): void
    {
        $langs = IncomingLink::where('language', '!=', '')->groupBy('language')->pluck('language');
        foreach ($langs as $lang) {
            if (! Languages::isKnownLanguageCode($lang)) {
                echo IncomingLink::where('language', $lang)->count() . " $lang<br>";
                IncomingLink::where('language', $lang)->update(['language' => '']);
            }
        }
    }

    private function removeDuplicateEmails(): void
    {
        //select GROUP_CONCAT(id),count(*) as count from mail group by senderAddress,recipientAddresses,bodyText,subject,userID,comment,headers having count > 2
        $idSets = MailMessage::select(DB::raw('GROUP_CONCAT(id) as ids, count(*) as count'))
            // ->where('status', '!=', 'outgoing')
            ->groupBy(
                'subject',
                'userID',
                'senderAddress',
                'recipientAddresses',
                'bodyText',
                'comment',
                'headers',
                'status',
                DB::raw('YEAR(transmitTime)'),
                DB::raw('MONTH(transmitTime)'),
                DB::raw('DAY(transmitTime)')
            )
            ->having('count', '>', 1)->pluck('ids');

        $count = 0;
        foreach ($idSets as $idSet) {
            $ids = explode(',', $idSet);
            sort($ids);
            $mail = MailMessage::findOrFail($ids[0]);
            echo '<br>' . $mail->subject . ' - ';
            foreach ($ids as $num => $id) {
                echo '<a href="' . routeURL('staff-mailMessages', $id) . "\">$id</a> ";
                if (! $num) {
                    echo '(keep). ';
                } else {
                    $m = MailMessage::findOrFail($id);
                    echo "[$m->recipient] ";
                    $m->delete();
                    echo '(delete)(#' . ++$count . '). ';
                    //exit();
                }
            }
        }
    }

    private function removeDuplicateContactFormEmails(): void
    {
        $mailIDs = MailMessage::where('recipient', 'like', 'http%')->pluck('id');

        foreach ($mailIDs as $mailID) {
            $mail = MailMessage::find($mailID);
            if (! $mail) {
                continue;
            }

            $otherMails = MailMessage::where('recipient', $mail->recipient)->where('id', '!=', $mail->id)->get();
            if ($otherMails->count()) {
                echo "$mail->id: ";
                foreach ($otherMails as $otherMail) {
                    echo "$otherMail->id ";
                }
                echo '<br>';
            }
        }
    }

    private function moveIncomingLinkMessagesToMail()
    {
        set_time_limit(1 * 60 * 60);

        $incomingLinkIDs = IncomingLink::where('contactMessage', '!=', '')->pluck('id');

        /*
            $table->text('recipient'); // Can be multiple (comma separated)
            $table->string('recipientAddresses', 255); // Can be multiple (comma separated) from To and CC addresses.
        */

        foreach ($incomingLinkIDs as $incomingLinkID) {
            $incomingLink = IncomingLink::find($incomingLinkID);
            $user = User::find($incomingLink->userID ? $incomingLink->userID : 1);

            $transmitTime = Carbon::createFromFormat('Y-m-d', $incomingLink->lastContact);
            $mail = MailMessage::createOutgoing(['status' => 'outgoing', 'transmitTime' => $transmitTime,
                'bodyText' => $incomingLink->contactMessage, 'subject' => 'hostel information',
                'recipient' => $incomingLink->contactFormURL, 'recipientAddresses' => $incomingLink->contactFormURL,
            ], $user);
            //dd($mail);
            //$mail->save();
            echo $incomingLink->contactFormURL . '<br>';
        }

        return 'ok';
    }

    private function testSpider(): void
    {
        $spider = new Spider();
        $spider->maxTotalPages = 5;
        $spider->maxLinksToFollowPerPage = 5;
        $spider->maxPageDataRead = 15000;

        $spiderResults = $spider->spiderSite(
            'http://www.hostelz.com',
            2 /* spider depth */,
            [
                /* Old ones, replaced with the ones below (note some of the old ones had different capitalization for the array keys)
    			'Hostelsclub'=>'`https?\:\/\/(^|.+\.)hostelsclub\.com(.+?)$`i',
    			'Hostelbookers'=>'`https?\:\/\/(^|.+\.)hostelbookers\.com(.+?)$`i',
        		'hb-247.com'=>'`https?\:\/\/(^|.+\.)hb-247\.com(.+?)$`i', // hostelbookers affiliate site
    			'BookHostels'=>'`https?\:\/\/(^|.+\.)bookhostels\.com(.+?)$`i', // hostelworld affiliate site
                */
                'mailto' => '`mailto\:(.+?)$`i',
                'Hostelz' => '`https?\:\/\/(^|.+\.)hostelz\.com(.+?)$`i',
                'Hostelworld' => '`https?\:\/\/(^|.+\.)hostelworld\.com(.+?)$`i',
                'Hostelworld Affiliate' => '`https?\:\/\/(^|.+\.)(bookhostels\.com|hostelworld\.com(.+?)\?affiliate=)(.+?)$`i',
                'HostelBookers' => '`https?\:\/\/(^|.+\.)hostelbookers\.com(.+?)$`i',
                'HostelBookers Affiliate' => '`https?\:\/\/(^|.+\.)(hb-247\.com|hostelbookers\.com(.+?)\?affiliate=)(.+?)$`i',
                'Hostels.com' => '`https?\:\/\/(^|.+\.)hostels\.com(.+?)$`i',
                'HostelsClub' => '`https?\:\/\/(^|.+\.)hostelsclub\.com(.+?)$`i',
                // Possible contacts:
                'Facebook' => '`https?\:\/\/(^|.+\.)facebook\.com(.+?)$`i',
                'Twitter' => '`https?\:\/\/(^|.+\.)twitter\.com(.+?)$`i',
                'LinkedIn' => '`https?\:\/\/(^|.+\.)linkedin\.com(.+?)$`i',
                // Videos:
                'YouTube' => '`https?\:\/\/(^|.+\.)youtube\.com(.+?)$`i',
                'Vimeo' => '`https?\:\/\/(^|.+\.)vimeo\.com(.+?)$`i',
                'Metacafe' => '`https?\:\/\/(^|.+\.)metacafe\.com(.+?)$`i',
                'Expedia' => '`https?\:\/\/(^|.+\.)expedia\.com(.+?)$`i',
            ],
            'domain'
        );

        print_r($spiderResults);
    }

    private function getRootDomain()
    {
        $url = 'foo.reddit.com.uk';
        preg_match("`(?:www\.){0,1}(.+?)(\/|$)`i", str_replace('http://', '', $url), $matches);

        return $matches[1];
    }

    private function testListingWeb()
    {
        $listing = Listing::find(280466);
        $listing->web = 'http://nan';
        $listing->save();

        return 'ok';
    }

    private function fixIncomingLinks(): void
    {
        $ids = IncomingLink::where('domain', '')->pluck('id');
        foreach ($ids as $id) {
            echo "[$id] ";
            $link = IncomingLink::find($id);
            $error = $link->validateAndFillInMissingValues();
            if ($error != '') {
                echo "($error) ";
            } else {
                $link->save();
            }
        }
    }

    private function resetWithoutOverlappingFiles(): void
    {
        foreach (glob(storage_path('framework/schedule-*')) as $filename) {
            if (Carbon::createFromTimestamp(filemtime($filename))->diffInDays() >= 1) {
                unlink($filename);
            }
        }
    }

    private function setRatingDateToApprovalDate(): void
    {
        $logs = EventLog::where('subjectType', 'Rating')->where('data', 'like', '%verified: "-10" -> "10"%')->where('action', 'update')
            ->where('eventTime', '>', '2015-10-01')->where('userID', 7029)->get();

        foreach ($logs as $log) {
            $rating = Rating::find($log->subjectID);
            echo $rating->commentDate . '->' . $log->eventTime->format('Y-m-d') . '<br>';
        }
    }

    private function updateContentScores(): void
    {
        set_time_limit(3 * 60 * 60);
        $listingIDs = Listing::areLive()->pluck('id');

        foreach ($listingIDs as $listingID) {
            $listing = Listing::find($listingID);
            $isPoorContentPage = $listing->isPoorContentPage();
            $before = $listing->contentScores['en'] ?? null;
            $listing->listingMaintenance()->calculateContentScores();
            $after = $listing->contentScores['en'] ?? null;
            if ($isPoorContentPage == $listing->isPoorContentPage()) {
                echo '<br>same';
            } else {
                echo "<br>$listingID: $before -> $after";
                $listing->save();
            }
        }
    }

    private function fixReviewerPermissionsNotSet(): void
    {
        $logs = EventLog::where('subjectString', 'becomePaidReviewer')->groupBy('subjectID')->get();

        foreach ($logs as $log) {
            $user = User::findOrFail($log->subjectID);
            echo "$log->eventTime $user->id $user->username";
            if ($user->hasPermission('reviewer')) {
                echo ' ALREADY!<br>';

                continue;
            }
            Emailer::send($user, 'Hostelz.com Paid Reviewer Update', 'generic-email', ['text' => 'Hi.  We found that a bug on our website caused some users who signed up to be paid reviewers to not receive the proper settings on their accounts to be able to review hostels.  ' .
                'You account settings have now been corrected, and you can now start reviewing hostels.  Just login to the website and click on your email address at the top of the page.',
            ], Config::get('custom.adminEmail'));
            $user->grantPermissions('reviewer');
            echo '<br>';
        }
    }

    private function listingGoogleRanking()
    {
        $listings = Listing::areLive()->where('comment', 'LIKE', '%2015-11-10 Google Rank%')->get()->sort(function ($a, $b) {
            $phrase = 'Google Rank: ';
            $a->googleSearchRank = (int) substr($a->comment, strrpos($a->comment, $phrase) + strlen($phrase), 2);
            $b->googleSearchRank = (int) substr($b->comment, strrpos($b->comment, $phrase) + strlen($phrase), 2);
            if (! $a->googleSearchRank) {
                return 1;
            }
            if (! $b->googleSearchRank) {
                return -1;
            }

            return $a->googleSearchRank > $b->googleSearchRank;
        });

        $output = '<table border=1><tr><th>Listing</th><th>Search Rank</th><th>Content Score</th><th>Review</th><th>Description</th><th>Location</th><th>Imported Reviews</th><th>Backlink</th><th>Booking Sites</th></tr>';
        foreach ($listings as $listing) {
            if ($listing->isPoorContentPage('en')) {
                continue;
            }
            $output .= '<tr>';
            $output .= '<td><a href="' . $listing->getURL() . "\">$listing->name</a></td>";
            $output .= "<td>$listing->googleSearchRank</td>";
            $output .= '<td>' . $listing->contentScores['en'] . '</td>';
            $review = $listing->getLiveReview();
            $output .= '<td>' . ($review ? $review->reviewDate : '-') . '</td>';
            foreach (['description', 'location'] as $textType) {
                $text = $listing->getText($textType);
                if ($text) {
                    // (If it isn't original enough, we include it later with listingFetchContent so Google doesn't see it as duplicate content)
                    if (! $listing->isTextOriginalEnough($text)) {
                        $output .= '<td>fetched</td>';
                    } else {
                        $output .= '<td>not fetched</td>';
                    }
                }
            }
            $importedReviews = $listing->getImportedReviewsAsRatings(1, 'en');
            $output .= '<td>' . ($importedReviews && ! $importedReviews->isEmpty() ? 'yes' : 'no') . '</td>';
            $output .= '<td>' . $listing->mgmtBacklink . '</td>';
            $output .= '<td>' . $listing->activeImporteds->pluck('system')->implode(', ') . '</td>';
            $output .= '</tr>';
        }
        $output .= '</table>';

        return $output;
    }

    private function currencies()
    {
        // return Currencies::getRateFromXE('JMD', 'USD');
        return Currencies::exchangeRate('USD', 'VND', true);
        // return Currencies::exchangeRate('USD', 'VND', true);
    }

    private function removeUnusedPayAmounts(): void
    {
        $userIDs = User::havePermission(['reviewer', 'staffWriter'])->pluck('id');

        foreach ($userIDs as $userID) {
            $user = User::find($userID);
            $payAmounts = $user->payAmounts;
            //print_r($user->payAmounts);
            foreach (['listingReview', 'cityInfoDescription'] as $payAmount) {
                unset($payAmounts[$payAmount]);
            }
            $user->payAmounts = $payAmounts;
            $user->save();
            echo "$user->id ";
            //print_r($user->payAmounts);
        }
    }

    private function castArray(): void
    {
        $string = 'foo';
        dd((array) $string);
    }

    private function updateAllCombinedScores(): void
    {
        set_time_limit(3 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listings = Listing::areLive()->pluck('id');

        foreach ($listings as $listingID) {
            $listing = Listing::find($listingID);
            with(new ListingMaintenance($listing))->calculateCombinedRating();
            echo $listing->id . ' (' . $listing->combinedRatingCount . ')<br>';
            $listing->save();
        }
    }

    private function getAvailability()
    {
        /*
        $bookings = Booking::where('system', 'Priceline')->groupBy('importedID')->orderBy(DB::raw('rand()'))->limit(10)->get();
        $importeds = new Collection;
        foreach ($bookings as $booking) $importeds->push($booking->imported);
        */

        //$importeds = Imported::where('status', 'active')->where('system', 'Priceline')->where('id', 2798846)->orderBy(DB::raw('rand()'))->limit(50)->get();
        //$importeds = Imported::where('hostelID', 28760)->where('system', 'Gomio')->get();
        $searchCriteria = new SearchCriteria();
        /*
        $importeds = Imported::where('city', 'Austin')->where('system', 'BookHostels')->where('status', 'active')->where('hostelID', 218274)->get();
        $searchCriteria->bookingSearchFormFields([ 'startDate' => Carbon::now()->addDays(30)->format('Y-m-d'), 'nights' => 2, 'roomType' => 'private', 'people' => 3,
            'rooms' => 2, 'groupType' => '', 'groupAgeRanges' => [ ], 'currency' => 'USD' ]);
        */
        /*
        $importeds = Imported::where('city', 'Austin')->where('system', 'BookHostels')->where('status', 'active')->where('hostelID', 276766)->get();
        $searchCriteria->bookingSearchFormFields([ 'startDate' => '2015-10-18', 'nights' => 2, 'roomType' => 'dorm', 'people' => 9,
            'rooms' => 1, 'groupType' => 'friends', 'groupAgeRanges' => [ '18to21' ], 'currency' => 'USD' ]);
        */

        $importeds = Imported::where('city', 'Venice')->where('system', 'Hostelsclub')->where('status', 'active')->get();
        $searchCriteria->bookingSearchFormFields(['startDate' => Carbon::now()->addDays(30)->format('Y-m-d'), 'nights' => 2, 'roomType' => 'private', 'people' => 3,
            'rooms' => 2, 'groupType' => '', 'groupAgeRanges' => [], 'currency' => 'USD', ]);

        print_r($searchCriteria);
        $systemClassName = $importeds->first()->getImportSystem()->getSystemService();
        $avail = $systemClassName::getAvailability($importeds, $searchCriteria, false);
        // dd($avail);
        foreach ($avail as $a) {
            $a->isValid();
            $a->maxBlocksAvailableAllNights();
        }
        dd($avail);

        return 'ok';
    }

    private function regenerateAllListingThumbnails(): void
    {
        set_time_limit(8 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listings = Listing::areLive()->pluck('id');

        foreach ($listings as $listingID) {
            $listing = Listing::find($listingID);
            echo $listing->id . ' ';
            with(new ListingMaintenance($listing))->updateThumbnail();
        }
    }

    private function citiesComments()
    {
        $results = CityInfo::areLive()->fromUrlParts('usa', 'texas')->join('cityComments', function ($join): void {
            $join->on('cityInfo.id', '=', 'cityComments.cityID')->where('cityComments.verified', '>', 0);
            $join->on(DB::raw('cityComments.comment LIKE CONCAT("%",cityInfo.city,"%")'), DB::raw(''), DB::raw(''));
        })->get();
        dd($results);

        return 'ok';
    }

    private function sendAfterStayEmail(): void
    {
        $booking = Booking::find(335250);
        $booking->sendAfterStayEmail();
    }

    private function googleCustomSearch(): void
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_FAILONERROR => true,
            CURLOPT_CONNECTTIMEOUT => 35,
            CURLOPT_TIMEOUT => 40,
            CURLOPT_REFERER => 'http://www.hostelz.com', // Google requires a referer set to some page on our domain
            // Turn off the server and peer verification (TrustManager Concept).
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        curl_setopt($curl, CURLOPT_URL, 'https://www.googleapis.com/customsearch/v1?q=' . urlencode('austin hostels') . '&cx=' . urlencode('006373179204573247532:atvit5io8xg') .
            '&key=' . urlencode(config('custom.googleApiKey.serverSide')));
        $data = curl_exec($curl);
        $data = json_decode($data);
        dd($data);
    }

    private function inboundLinkSearch(): void
    {
        dd(WebSearch::inboundLinks('http://www.hotels.com'));
    }

    private function updateHiWebsites(): void
    {
        set_time_limit(8 * 60 * 60);

        $curl = curl_init();
        $listings = Listing::where('web', 'like', 'https://www.hihostels.com/hostels/%')->get();

        foreach ($listings as $listing) {
            echo "$listing->id $listing->web -> ";

            $urlParts = explode('/', $listing->web);
            $lastPart = end($urlParts);
            if (! intval($lastPart)) {
                echo 'already did.<br>';

                continue;
            }

            curl_setopt_array($curl, [CURLOPT_URL => WebsiteTools::removeUrlFragments($listing->web), CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 7, CURLOPT_CONNECTTIMEOUT => 15, CURLOPT_TIMEOUT => 15,
                //CURLOPT_BUFFERSIZE => 8000, CURLOPT_NOPROGRESS => false,
                CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
                CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, ]);
            $contentsData = curl_exec($curl);
            $errno = curl_errno($curl);
            $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            $effectiveURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
            if ($errno) {
                echo "error $errno<br>";

                continue;
            }
            if ($effectiveURL == $listing->web) {
                echo 'same!<br>';

                continue;
            }
            if ($effectiveURL == '') {
                echo 'empty!<br>';

                continue;
            }

            $listing->web = $effectiveURL;
            $listing->webStatus = WebsiteStatusChecker::$websiteStatusOptions['ok'];
            $listing->save();
            echo "$effectiveURL<br>";
        }
    }

    private function curlTest(): void
    {
        $url = 'https://www.hihostels.com/hostels/hi-cape-breton';

        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => WebsiteTools::removeUrlFragments($url), CURLOPT_HEADER => false, CURLOPT_RETURNTRANSFER => true,
            //CURLOPT_FOLLOWLOCATION => true, CURLOPT_MAXREDIRS => 7, CURLOPT_CONNECTTIMEOUT => 50, CURLOPT_TIMEOUT => 50,
            //CURLOPT_VERBOSE => true, CURLOPT_STDERR => fopen('php://stdout', 'w'),
            CURLOPT_BUFFERSIZE => 8000, CURLOPT_NOPROGRESS => false, // CURLOPT_USERAGENT => 'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
            CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false, ]);
        $contentsData = curl_exec($curl);
        $errno = curl_errno($curl);
        $code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $effectiveURL = curl_getinfo($curl, CURLINFO_EFFECTIVE_URL);
        if (! $errno) {
            $contentType = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
        }
        curl_close($curl);
        echo "errno:$errno, code:$code, effectiveURL:$effectiveURL, contentsData:$contentsData";
    }

    private function bounds()
    {
        $poi = Geonames::findNearby(new GeoPoint(30, -97), 160, 100, null, ['Airport' => 10, 'Bus Station' => 5, 'Train Station' => 75]);

        return $poi;

        $bounds = GeoBounds::makeFromApproximateDistanceFromPoint(new GeoPoint(1, 5), 20, 'miles');

        $expected = sqrt(pow(20 * 2, 2) + pow(20 * 2, 2));

        return $expected;
        echo $bounds->swPoint->distanceToPoint($bounds->nePoint);
    }

    private function randomSentence()
    {
        $text = "This is a test... so is this.  Here is another\nsentence.  Sentence here. One. Two. Three. ... Something! Here.";

        $sentences = array_values(array_filter(array_map(function ($sentence) {
            $sentence = trim($sentence);

            return $sentence == '' ? '' : "$sentence.";
        }, preg_split('/[\n\.]+/s', $text))));
        $randomSentence = array_rand($sentences);
        // Make sure it isn't too close to the last end.
        if ($randomSentence > count($sentences) - 4) {
            $randomSentence = max(0, $randomSentence - 3);
        }

        return substr($text, strpos($text, $sentences[$randomSentence]));
    }

    private function minifyCSS()
    {
        $text = file_get_contents(public_path() . '/generated-css/staff.css');

        return MinifyCSS::minifyString($text);
    }

    private function serialization(): void
    {
        $roomInfo = new RoomInfo(['code' => 'room code', 'name' => 'room name']);
        $roomAvailability = new RoomAvailability();
        $roomAvailability->roomInfo = $roomInfo;
        $roomAvailability->availabilityEachNight = [
            ['roomInfo' => $roomInfo],
            ['roomInfo' => $roomInfo],
        ];
        $s = serialize($roomAvailability);
        echo $s;
        print_r(unserialize($s));
    }

    private function warning(): void
    {
        logWarning('warning here.');
    }

    private function localCurrency()
    {
        $listing = Listing::find(1);

        return $listing->determineLocalCurrency();
    }

    private function routeUrlTest(): void
    {
        echo 'auto: ' . routeURL('home', [], 'auto') . '<br>';
        echo 'relative: ' . routeURL('home', [], 'relative') . '<br>';
        echo 'absolute: ' . routeURL('home', [], 'absolute') . '<br>';
        echo 'protocolRelative: ' . routeURL('home', [], 'protocolRelative') . '<br>';
    }

    private function testGzip(): void
    {
        $content = file_get_contents('http://www.hostelz.com');

        //ini_set('zlib.output_compression', false);
        //header("Content-Encoding: gzip");
        // http://stackoverflow.com/questions/388595/why-use-deflate-instead-of-gzip-for-text-files-served-by-apache
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $foo = gzencode($content, 1, FORCE_GZIP);
        }
        echo 'size: ' . strlen($foo) . ' elapsed: ' . (microtime(true) - $startTime);
        $startTime = microtime(true);
        for ($i = 0; $i < 1000; $i++) {
            $foo = gzencode($content, 9, FORCE_GZIP);
        }
        echo '<br>size: ' . strlen($foo) . ' elapsed: ' . (microtime(true) - $startTime);        //header("Content-Length: " . strlen($content));
        //echo $content;
    }

    private function chunkedEncodingTest()
    {
        //ob_start(); // function ($s) { echo $s; }, 2000000);
        for ($i = 0; $i < 8; $i++) {
            echo '12345678901234567890123456789012345678901234567890123456789012345678901234567890123456789012345678901234567890';
        }

        //ob_end_flush();
        return 'ok';
    }

    private function dbBackup(): void
    {
        $manager = App::make(\BackupManager\Manager::class);
        // ($database, $destination, $destinationPath, $compression)
        $manager->makeBackup()->run('mysql', 's3', 'backup.sql', 'gzip');
    }

    private function intlDate(): void
    {
        $dateFormatter = new \IntlDateFormatter(
            'fr_FR.utf8',
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            \date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN
        );
    }

    private function bookingSearchCriteria()
    {
        $s = new SearchCriteria();
        $s->setToDefaults();

        return json_encode($s->bookingSearchFormFields());
        echo json_encode($s);
        echo json_encode(['foo' => null, 'foo2' => 'bar']);
    }

    private function setStaffWriterPayAmounts(): void
    {
        $users = User::havePermission('staffWriter')->get();

        foreach ($users as $user) {
            $user->setPayAmount('cityInfoDescription', '5.00', true);
            echo $user->id . ' ';
        }
    }

    private function percentOnBookingSites(): void
    {
        $totalCount = $totalBookable = 0;
        $hwTotal = $hbTotal = 0;
        $countries = Listing::areLive()->where('propertyType', 'hostel')->groupBy('country')->pluck('country');
        foreach ($countries as $country) {
            $listings = Listing::areLive()->where('propertyType', 'hostel')->where('country', $country)->get();
            $count = $bookable = 0;
            foreach ($listings as $listing) {
                $count++;
                $totalCount++;
                if (! $listing->activeImporteds->where('system', 'BookHostels')->isEmpty()) {
                    $hwTotal++;
                }
                if (! $listing->activeImporteds->where('system', 'Hostelbookers')->isEmpty()) {
                    $hbTotal++;
                }
                if (! $listing->activeImporteds->where('system', 'BookHostels')->isEmpty() ||
                    ! $listing->activeImporteds->where('system', 'Hostelbookers')->isEmpty()) {
                    $bookable++;
                    $totalBookable++;
                }
            }
            if (! $count) {
                continue;
            }
            $percent = round(100 * $bookable / $count);
            echo "**$country** ($percent%), ";
        }

        $percent = round(100 * $totalBookable / $totalCount);
        echo "**TOTAL** ($percent%)";
        $percent = round(100 * $hwTotal / $totalCount);
        echo "**hwTotal** ($percent%)";
        $percent = round(100 * $hbTotal / $totalCount);
        echo "**hbTotal** ($percent%)";
    }

    /*    private function hiHostelCountries()
        {
            $totalCount = $totalBookable = 0;
            $hiCountries = Imported::where('system', 'HI')->where('status', 'active')->groupBy('country')->pluck('country');
            foreach ($hiCountries as $country) {
                $importeds = Imported::where('system', 'HI')->where('status', 'active')->where('country', $country)->get();
                $count = $bookable = 0;
                foreach ($importeds as $imported) {
                    if (!$imported->listing || !$imported->listing->isLive()) continue;
                    $count++;
                    $totalCount++;
                    if (!$imported->listing->activeImporteds->where('system', 'BookHostels')->isEmpty() ||
                        !$imported->listing->activeImporteds->where('system', 'Hostelbookers')->isEmpty()) {
                            $bookable++;
                            $totalBookable++;
                        }
                }
                if (!$count) continue;
                $percent = round(100*$bookable/$count);
                echo "**$country** ($percent%), ";
            }
            $percent = round(100*$totalBookable/$totalCount);
            echo "**TOTAL** ($percent%)";
        }*/

    private function sheetsStats(): void
    {
        $thing = 'towels';
        $features = Listing::areLive()->where('propertyType', 'hostel')->select('compiledFeatures', 'id')->where('compiledFeatures', 'LIKE', "%$thing%")->get();

        $counts = [];
        foreach ($features as $feature) {
            //echo "$feature->id ".$feature->compiledFeatures[$thing].'<br>';
            if (! isset($counts[$feature->compiledFeatures[$thing]])) {
                $counts[$feature->compiledFeatures[$thing]] = 0;
            }
            $counts[$feature->compiledFeatures[$thing]]++;
        }
        echo 'total:' . array_sum($counts);
        dd($counts);
    }

    private function wholeWordTruncate()
    {
        $s = '1234567'; // 89012345678901234567890 two threeone two threeone two threeone two threeone two threeone two threeone two threeone two threeone two threeone two three";

        return substr($s, 0, strrpos(substr($s, 0, 20), ' '));

        return wholeWordTruncate($s, 20);
        // return substr( $s, 0, strpos($s, ' ', 10) );

        $line = $s;
        if (preg_match('/^.{1,20}\b/s', $s, $match)) {
            $line = $match[0];
        }

        return $line;
    }

    private function listingQuickFixesBeforeSaving(): void
    {
        set_time_limit(3 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $listingIDs = Listing::pluck('id');

        foreach ($listingIDs as $listingID) {
            $listing = Listing::findOrFail($listingID);
            $oldValues = ['name' => $listing->name, 'city' => $listing->city, 'cityAlt' => $listing->cityAlt, 'address' => $listing->address, 'zipcode' => $listing->zipcode, 'tel' => $listing->tel, 'fax' => $listing->fax];
            $listing->listingMaintenance()->quickFixesBeforeSaving();
            $newValues = ['name' => $listing->name, 'city' => $listing->city, 'cityAlt' => $listing->cityAlt, 'address' => $listing->address, 'zipcode' => $listing->zipcode, 'tel' => $listing->tel, 'fax' => $listing->fax];
            $changes = EventLog::describeChanges($oldValues, $newValues);
            if ($changes) {
                echo "<pre>$changes</pre>";
                $listing->save();
            }
        }
    }

    private function geoMath(): void
    {
        $result = GeoMath::approximateBoundingBoxOfDistanceToAPoint(200, -18.1416, 178.4415, 'km');
        dd($result);
    }

    private function updateAndLog(): void
    {
        $user = User::findOrFail(45243);
        $user->updateAndLogEvent(['name' => ''], true, '', 'user');
    }

    private function associateUser(): void
    {
        $user = User::find(1);
        $user->associateEmailAddressWithUser();
    }

    private function mgmtURL()
    {
        return User::mgmtSignupURL(1, 'fr');
    }

    private function accessingObjectsLikeArrays(): void
    {
        $listings = Listing::where('city', 'Austin')->get();
        $result = searchArrayForProperty($listings, 'name', "Drifter Jack's Hostel", $returnKeyOrElement = 'key');
        dd($result);
    }

    private function newUsers(): void
    {
        set_time_limit(3 * 60 * 60);
        DB::disableQueryLog(); // to save memory
        ini_set('memory_limit', '512M');

        $userIDs = User::where('status', 'new')->pluck('id');
        foreach ($userIDs as $userID) {
            $user = User::find($userID);
            echo "<br>$user->id: ";
            if (! $user->reviews->isEmpty()) {
                echo 'reviews ';
                $user->status = 'ok';
                $user->save();
            } else {
                echo 'delete';
                $user->delete();
            }
        }
    }

    private function convertRatingsToNewFormat(): void
    {
        exit(); // disabled for safety

        /*
        Old: setting it to '' means it isn't listed on hostels.com (which is different than having no reviews, in which case it isn't set at all"
        */

        set_time_limit(3 * 60 * 60);
        DB::disableQueryLog(); // to save memory
        ini_set('memory_limit', '512M');

        $importedIDs = Imported::where('rating', '!=', '')->where('system', 'BookHostels')->where('rating', 'like', '%"overall";s:1:"0"%')->pluck('id');
        foreach ($importedIDs as $importedID) {
            $imported = Imported::find($importedID);
            $needToRecalcCombined = false;
            $newRatings = [];
            $websites = ['Hostelworld', 'Hostels.com'];
            foreach ($websites as $website) {
                if (! isset($imported->rating[$website]) || ($imported->rating[$website] && $imported->rating[$website]['count'] == 0)) {
                    $newRatings[$website] = ['count' => 0];
                } elseif ($imported->rating[$website]) {
                    if (! $imported->rating[$website]['overall']) {
                        $newRatings[$website] = ['count' => 0];
                        $needToRecalcCombined = true;
                    } else {
                        $newRatings[$website] = $imported->rating[$website];
                    }
                }
            }
            echo "$imported->id<br>old: " . json_encode($imported->rating) . '<br>new: ' . json_encode($newRatings) . '<br>';

            $imported->rating = $newRatings;
            $imported->save();

            if ($needToRecalcCombined) {
                echo '[combinedRating: ' . $imported->listing->combinedRating;
                $imported->listing->listingMaintenance()->calculateCombinedRating();
                echo ' -> ' . $imported->listing->combinedRating . ']<br>';
                $imported->listing->save();
            }

            echo '<br>';
        }
    }

    private function geo()
    {
        $cityInfo = CityInfo::find(Request::input('cityID'));
        $geo = Geonames::findCityRegionCountry($cityInfo->country, $cityInfo->region, $cityInfo->city);
        print_r($geo);

        return 'ok';
        //return 'ok';
        dd($geo);

        $cityInfo = CityInfo::where('city', 'Dallas')->first();
        print_r($cityInfo);
        $modified = $cityInfo->updateGeocoding(true, true);
        if ($modified) {
            echo '[modified!]';
        }
        print_r($cityInfo);

        return 'ok';

        //$result = Geonames::findCityRegionCountry('USA', 'Sunshine State', 'Austin');
        // ($latitude, $longitude, $maxKM, $maxTotalItems, $maxByTypeField, $featureCodes = null, $maxByTypeCount = null, $roundDistanceToDecimal = 1)
        //print_r(Geonames::findNearby($result['city']->latitude, $result['city']->longitude, 50, 50, 100, null, [ 'Airport'=>10, 'Bus Station'=>5, 'Train Station'=>75 ] ));
    }

    private function dataCorrectAll()
    {
        //return DataCorrection::correctAllDatabaseValues('', 'country', CityInfo::query(), 'cityInfo');
        return DataCorrection::correctAllDatabaseValues('', 'city', Listing::query(), Listing::$staticTable, null, 'country');
    }

    private function setDataCorrectionContexts(): void
    {
        set_time_limit(1 * 60 * 60);

        $ids = DataCorrection::where('dbTable', '')->where('contextValue1', '')->pluck('id');

        foreach ($ids as $id) {
            $correction = DataCorrection::findOrFail($id);
            echo "$correction->dbField '$correction->newValue': ";
            switch ($correction->dbField) {
                case 'city':
                    $countries = CityInfo::areLive()->where('city', $correction->newValue)->groupBy('country')->pluck('country');
                    if ($countries->isEmpty()) {
                        $countries = Listing::where('verified', '>=', 0)->where('city', $correction->newValue)->groupBy('country')->pluck('country');
                    }
                    switch ($countries->count()) {
                        case 0:
                            echo 'country not found!<br>';

                            break;
                        default:
                            $country = $countries->first();
                            DataCorrection::where('dbField', $correction->dbField)->where('newValue', $correction->newValue)
                                ->update(['contextValue1' => $country]);
                            echo "country '$country'<br>";

                            break;
                            /*
                            default:
                                echo "multiple countries! ".implode(', ', $countries->all())."<br>";
                                break;
                            */
                    }

                    break;

                case 'region':
                    $countries = CityInfo::areLive()->where('region', $correction->newValue)->groupBy('country')->pluck('country');
                    if ($countries->isEmpty()) {
                        $countries = Listing::where('verified', '>=', 0)->where('region', $correction->newValue)->groupBy('country')->pluck('country');
                    }
                    switch ($countries->count()) {
                        case 0:
                            echo 'country not found!<br>';

                            break;
                        default:
                            $country = $countries->first();
                            DataCorrection::where('dbField', $correction->dbField)->where('newValue', $correction->newValue)
                                ->update(['contextValue1' => $country]);
                            echo "country '$country'<br>";

                            break;
                            /*
                            default:
                                echo "multiple countries! ".implode(', ', $countries->all())."<br>";
                                break;
                            */
                    }

                    break;
            }
        }
    }

    private function maint()
    {
        DB::disableQueryLog(); // to save memoryset_time_limit(30*60);
        set_time_limit(30 * 60);

        return '<pre>' . App\Services\ImportSystems\BookHostels\BookHostelsService::hourlyMaintenance() . '</pre>';

//         return '<pre>'.  ListingMaintenance::maintenanceTasks('monthly') .'</pre>';
        return '<pre>' . \App\ImportSystems\BookingDotCom::hourlyMaintenance() . '</pre>';
//         return '<pre>'.  CountryInfo::maintenanceTasks('weekly') .'</pre>';
//         return '<pre>'.  Review::maintenanceTasks('daily') .'</pre>';
    }

    private function fixBookings(): void
    {
        $ids = Booking::where('system', 'bookingdotcom')->where('email', '!=', '')->where('userID', 0)->orderBy('id', 'desc')->limit(4000)->pluck('id');
        foreach ($ids as $id) {
            echo "$id ";
            $booking = Booking::findOrFail($id);
            $booking->automaticallyAssignToUserWithMatchingEmail();
            $booking->awardPoints();
            $booking->save();
        }
    }

    private function featureDisplay()
    {
        $listing = Listing::find(1);
        print_r(ListingFeatures::getDisplayValues($listing->compiledFeatures));

        return '';
    }

    private function colorTest()
    {
        return view('colorTest');
    }

    private function removeRatingPercentSign(): void
    {
        $importedIDs = Imported::where('rating', 'like', '%\\%%')->pluck('id');

        foreach ($importedIDs as $id) {
            $imported = Imported::findOrFail($id);
            $imported->rating = json_decode(str_replace('%', '', json_encode($imported->rating)), true);
            echo "$id ";
            $imported->save();
        }
    }

    private function cancelBooking()
    {
        $booking = Booking::find(356787);

        return App\Services\ImportSystems\Hostelsclub\HostelsclubService::cancelBooking($booking, $message) ? 'true' : 'false';
    }

    private function featureMerge()
    {
        $one = ['extras' => ['tv'], 'parking' => 'yes', 'towels' => 'yes', 'sheets' => 'yes'];
        $two = ['extras' => ['tv'], 'parking' => 'no', 'towels' => 'pay', 'sheets' => '$1'];

        return ListingFeatures::merge($one, $two);
    }

    private function fixHiFeatures()
    {
        $featureMap = [
            'Common room(s)' => 'lounge',
            'Individual traveller welcome' => null,
            'Groups welcome' => ['goodFor' => 'groups'],
            'Sports' => null, 'Cycle store at Hostel' => null, 'Sauna' => null, 'Basic store available at or near the hostel' => null, 'Garden' => null, 'Green Hostel' => null, 'Rates include local tax' => null, 'Playground' => null,
            'Male only' => ['gender' => 'maleOnly'],
            'Female only' => ['gender' => 'femaleOnly'],
            'Discounts and concessions available' => null,
            'Family rooms available' => ['goodFor' => 'families'],
            'Breakfast in price' => ['breakfast' => 'free'],
            'Credit card accepted' => 'cc',
            'Café/Bar' => null,
            'Laundry facilities' => ['extras' => 'laundry'],
            'Meals available' => ['extras' => 'food'],
            'Luggage Store' => 'luggageStorage',
            'Non smoking room/area' => null,
            'BBQ' => ['extras' => 'bbq'],
            'Internet access' => null,
            'Sheets in price' => ['sheets' => 'free'],
            'Cycle rental available at or near the hostel' => 'bikeRental',
            'Travel/Tour bureau' => ['extras' => 'info'],
            'Lockers available' => 'lockersInCommons',
            'Hostel open 24h' => ['curfew' => 'noCurfew'],
            'Air conditioning' => ['extras' => 'ac'],
            'Currency exchange at or near hostel' => ['extras' => 'exchange'],
            'Lift' => ['extras' => 'elevator'],
            'TV room' => ['extras' => 'tv'],
            'Self-catering kitchen' => 'kitchen',
            'Games room' => ['extras' => 'gameroom'],
            'Sheets for hire' => ['sheets' => 'pay'],
            'Disco' => null,
            'Suitable for wheelchair users' => 'wheelchair',
        ];

        DB::disableQueryLog(); // to save memory
        set_time_limit(30 * 60);

        $ids = DB::table('imported')->where('system', 'HI')->where('features', '!=', '')->pluck('features', 'id')->all();

        foreach ($ids as $id => $features) {
            if (substr($features, 0, 1) == '{') {
                continue;
            } // already converted
            $imported = Imported::find($id);
            echo "$id -> $features<br>";
            $features = explode(',', $features);
            $imported->features = ListingFeatures::mapFromImportedFeatures($features, $featureMap);
            echo json_encode($imported->features) . '<br>';
            if ($imported->features === null) {
                return 'error!';
            }
            $imported->save();
        }
    }

    private function mailAttachmentTest(): void
    {
        $mail = MailMessage::find(429621);
        $mail->addAttachmentByFilename('foo', '/home/hostelz/dev/storage/uploadTemp/ae75bc7b89af21452cbcb68a7b53cbdc');
    }

    private function getTest(): void
    {
        var_dump(Imported::find(2794077)->features);
    }

    private function lists()
    {
        var_dump(DB::table('users')->limit(10)->get()->all()); // returns array

        return 'ok';

        var_dump(User::limit(10)->get()->pluck('id', 'username')->all()); // returns collection

        var_dump(User::groupBy('status')->get()->pluck('status'));

        return 'ok';

        $coll = new Collection([User::find(1), Listing::find(1)]);
        var_dump($coll->pluck('id'));

        return 'ok';

        var_dump(User::limit(10)->get()); // returns collection
        var_dump(User::limit(10)->get()->pluck('id')); // returns collection
        var_dump(DB::table('users')->limit(10)->pluck('id')->all()); // returns array
    }

    private function memoryTest()
    {
        if (ini_get('memory_limit') < 256) {
            ini_set('memory_limit', '256M');
        }

        return ini_get('memory_limit');
    }

    private function createTables()
    {
        ListingSubscription::createTables();

        return 'ok';
    }

    private function updateListings()
    {
        set_time_limit(12 * 60 * 60);
        $listingIDs = Listing::areLive()->where('propertyType', 'Hostel')-> // where('verified', '>=', Listing::$statusOptions['newIgnored'])-> //  //  // where('verified', '=', Listing::$statusOptions['db2'])->
        where('lastUpdate', '<', Carbon::now()->subDays(90)->format('Y-m-d'))->
        orderBy('lastUpdate')->limit(5000)->pluck('id');

        $output = '';
        $listingMaintenance = new ListingMaintenance(null);
        $startTime = time();

        echo '(count: ' . count($listingIDs) . ")\n<br><br>";

        foreach ($listingIDs as $listingID) {
            $listingMaintenance->listing = Listing::find($listingID);
            if (! $listingMaintenance->listing) {
                $output .= "missing\n";

                continue;
            }
            $output .= '<h1><a href="' . routeURL('staff-listings', $listingID) . "\">$listingID " . $listingMaintenance->listing->name . ' (' . $listingMaintenance->listing->verified . ')</a></h1>';
            $output .= '<pre>' . $listingMaintenance->updateListing(true, true) . '</pre>';
            echo $output;
            $output = '';
        }

        $output .= '<h1>Total time: ' . ((time() - $startTime) / 60) . ' minutes</h1>';

        return $output;
    }

    private function utf8ToAscii()
    {
        return utf8ToAscii("'nº' A æ Übérmensch på høyeste nivå! И я люблю PHP! есть. ﬁ ¦");
    }

    private function dailyMaint()
    {
        return ListingDuplicate::dailyMaintenance();
    }

    private function testEach()
    {
        /*
       		public function each(callable $callback)
    	    {
    		    array_map($callback, $this->items);
        		return $this;
    	    }

        	public function map(callable $callback)
        	{
        		return new static(array_map($callback, $this->items, array_keys($this->items)));
        	}
        */

        $z = 0;

        $listings = Listing::limit(5000)->get();

        $startTime = microtime(true);
        $listings->each(function ($item): void {
            $item->name = 'foo'; // do nothing important
        });
        echo ' each elapsed: ' . (microtime(true) - $startTime);

        $startTime = microtime(true);
        foreach ($listings as $item) {
            $item->name = 'foo2';
        }
        echo ' foreach elapsed: ' . (microtime(true) - $startTime);

        // return $listings->pluck('name');

        $col = new Collection(['a', 'b', 'c']);
        for ($i = 0; $i < 1000; $i++) {
            $col->push('foo');
        }

        $startTime = microtime(true);
        for ($i = 0; $i < 10000; $i++) {
            foreach ($col as $item) {
                $z++; // do nothing important
            }
        }
        echo ' foreach elapsed: ' . (microtime(true) - $startTime);

        $startTime = microtime(true);
        for ($i = 0; $i < 10000; $i++) {
            $col->each(function ($item) use ($z): void {
                $z++;
            });
        }
        echo ' each elapsed: ' . (microtime(true) - $startTime);

        return ' ok';
        //return $col->toArray();
    }

    private function listingDupPriorityLevels(): void
    {
        set_time_limit(30 * 60);
        $ids = ListingDuplicate::where('priorityLevel', 0)->pluck('id');
        foreach ($ids as $id) {
            $dup = ListingDuplicate::find($id);
            $dup->setPriorityLevel();
            $dup->save();
            echo "$dup->id ($dup->priorityLevel) ";
        }
    }

    private function setIncomingLinkPriorityLevels(): void
    {
        set_time_limit(1 * 60 * 60);
        DB::disableQueryLog(); // to save memory

        $ids = IncomingLink::where('contactStatus', 'todo')->pluck('id');
        foreach ($ids as $id) {
            $link = IncomingLink::find($id);
            $link->setPriorityLevel();
            $link->save();
            echo "$link->id ($link->priorityLevel) ";
        }
    }

    private function recalculateSimilarities()
    {
        set_time_limit(2 * 60 * 60);

        $duplicates = ListingDuplicate::where('status', 'suspected')->orderBy('id')->get();

        foreach ($duplicates as $duplicate) {
            if (! $duplicate->listing) {
                echo "Missing $duplicate->listingID.";

                continue;
            }
            if (! $duplicate->otherListingListing) {
                echo "Missing other listing $duplicate->otherListing.";

                continue;
            }
            $score = ListingDuplicate::calculateSimilarity($duplicate->listing, $duplicate->otherListingListing, false);
            echo '. ';
            if ($score == $duplicate->score) {
                continue;
            }
            echo '<br><a href="' . $duplicate->listing->getURL() . '">' . $duplicate->listing->name . "</a> $duplicate->score -> $score<br>";
            $duplicate->score = $score;
            $duplicate->save();
        }

        return 'done.';
    }

    private function automerge()
    {
        $listing = Listing::find(160065);

        return ListingDuplicate::findDuplicates($listing, true, false);
    }

    private function listingRatings()
    {
        $listing = Listing::find(13);
        $ratings = Rating::getRatingsForListing($listing, 'en', true);
        echo 'count: ' . $ratings->count() . "\n\n<br>";
        $ratings = Rating::spliceRatingsForPage($ratings, 0);
        echo 'count: ' . $ratings->count() . "\n\n<br>";
        print_r($ratings->pluck('summary'));

        return 'done';
    }

    private function geocodingTest()
    {
        return file_get_contents('https://maps.googleapis.com/maps/api/geocode/json?sensor=false&address=Padre+Severino+St.+285%2C+Belo+Horizonte%2C+Minas+Gerais%2C+Brazil');
        echo '<pre>';
        //$temp = Geocoding::geocode('4507 Avenue F', 'Austin', 'Texas', 'USA');
        //print_r($temp);
        $temp = Geocoding::reverseGeocode(30.308563, -97.72686, 3);
        print_r($temp);
        echo '</pre>';

        // dd($temp);
        return 'ok';
    }

    private function propertyTypeFix(): void
    {
        set_time_limit(1 * 60 * 60);

        $listings = Listing::where('propertyTypeVerified', 0)->areNotListingCorrection()->orderBy('propertyType')->pluck('id');
        foreach ($listings as $listingID) {
            $listing = Listing::where('id', $listingID)->with('importeds')->first();
            $outputTemp = '';
            $newType = $listing->determineMostProbablePropertyType($outputTemp);
            if ($newType == $listing->propertyType || $newType == '(unknown)') {
                continue;
            }
            echo "Listing: <a href=\"http://dev.hostelz.com/hostel/$listing->id\">$listing->id $listing->name</a> -> <b>$newType</b>.  $outputTemp<br><br>";
            //$listing->propertyType = $newType;
            //$listing->save();
        }
    }

    private function priceHistory()
    {
        $listing = Listing::find(1);

        //return $listing->priceHistory();
        return PriceHistory::where('listingID', 1)->where('roomType', 'dorm')->where('month', '>', Carbon::now()->subMonths(6))->where('month', '<', Carbon::now()->addMonths(6))->get();
    }

    private function eventLogFix(): void
    {
        EventLog::where('subjectType', 'hostels')->update(['subjectType' => 'Listing']);
        EventLog::where('subjectType', 'reviews')->update(['subjectType' => 'Review']);
    }

    private function paypal()
    {
        \Lib\PayPal::$sandboxTestMode = true;

        return \Lib\PayPal::balance();
    }

    private function calculatePay()
    {
        $user = User::find(1); // 29669
        \App\Services\Payments::calculateUserPay($user, $output);

        return '<pre>' . $output . '</pre>';
    }

    private function plagiarismWarning(): void
    {
        $rows = AttachedText::where('subjectType', 'cityInfo')->where('plagiarismInfo', '!=', '')->get();

        foreach ($rows as $key => $row) {
            echo "Possible plagiarism score of $row->plagiarismPercent% for $row->id.";
            exit();
        }
    }

    private function checkAvailability()
    {
        dd();

        $listing = Listing::areLiveOrNew()->where('id', 2416)->first();

        $searchCriteria = new SearchCriteria(['startDate' => Carbon::now()->addDays(31), 'people' => 1, 'nights' => 2, 'roomType' => 'dorm', 'currency' => 'EUR', 'language' => 'en']);

        $availability = BookingService::getAvailabilityForListing($listing, $searchCriteria, true, 'single_compare');
        $rooms = BookingService::formatAvailableRoomsForDisplay($availability);

        dump($availability);
        dd($rooms);

        $availabiluty = \App\ImportSystems\BookingDotCom::getAvailability(Imported::whereIn('id', [2911786, 2823958, 2976846])->get(), $searchCriteria, 0);

        dd($availabiluty);

        return;
        // $listings = Listing::with('importeds')->find(13);
        $listings = Listing::with('importeds')->whereIn('id', [554, 376885, 2416, 474776])->get();
        $searchCriteria = new SearchCriteria(['startDate' => Carbon::now()->addDays(1), 'people' => 2, 'nights' => 2, 'roomType' => 'dorm', 'currency' => 'USD', 'language' => 'en']);

        return BookingService::getAvailabilityForListings($listings, $searchCriteria, true);
    }

    private function testHWAPI(): void
    {
        $searchCriteria = new SearchCriteria(['startDate' => Carbon::now()->addDays(1), 'people' => 2, 'nights' => 2, 'roomType' => 'dorm', 'currency' => 'EUR', 'language' => 'en']);
        $availabiluty = App\Services\ImportSystems\BookHostels\BookHostelsService::getAvailability(Imported::whereIn('id', ['2781295'])->get(), $searchCriteria, 0);

        dump($availabiluty);

        $availabiluty = \App\ImportSystems\BookingDotCom::getAvailability(Imported::whereIn('id', ['2861032'])->get(), $searchCriteria, 0);

        dd($availabiluty);

        return;
        // http://partner-api.hostelworld.com/propertylinks*?consumer_key=hostelz.com&consumer_signature=e6fc7204c3acffd37d050290500538345a787eff&Language=English&PropertyNumbers=84263

        $apiVersion = 2;
        $command = 'propertylinks';
        $curl = curl_init();
        $baseURL = $apiVersion == 2 ? 'https://partner-api.hostelworld.com/' : 'https://affiliate.xsapi.webresint.com/1.1/';

        curl_setopt_array($curl, [
            // Signature supposedly calculated from sha1('hostelz.com'.'–'.'6yUqN4aE') (6yUqN4aE is our "secret key").
            // (That didn't really work, we're just using the signature from their example in an email.)
            // Old 1.1 API: https://affiliate.xsapi.webresint.com/1.1/
            // New 2.2 API: https://partner-api.hostelworld.com/
            CURLOPT_URL => $baseURL . $command . '.json?consumer_key=hostelz.com&consumer_signature=e6fc7204c3acffd37d050290500538345a787eff&PropertyNumbers=84263&DateStart=2020-10-13&NumNights=2&Persons1=1',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 5,
        ]);

        curl_setopt($curl, CURLOPT_HTTPGET, true);

        // Sometimes the connection fails on some attempts, so we allow up to a few tries.
        $result = null;
        for ($try = 1; $try <= 1; $try++) {
            $resultData = curl_exec($curl);

            _d("Curl error '" . curl_error($curl) . "' (" . curl_errno($curl) . ')');
            if ($resultData) {
                // We also try to decode the data inside the try look
                // (because it was sometimes giving us data, but not valid json on the first try)
                $result = json_decode($resultData, true);
                if ($result) {
                    break;
                }
            }
        }

        dd($result);
    }

    private function testCheckAvailability(): void
    {
//         $listings = Listing::with('importeds')->find(13);
        $listings = Listing::with('importeds')->whereIn('id', [126869])->get();

        // Carbon::now()->addDays(35)*/
        $searchCriteria = new SearchCriteria(['startDate' => Carbon::create(2021, 07, 19)/*;now()->addDays(35)*/, 'people' => 1, 'nights' => 1, 'roomType' => 'dorm', 'currency' => 'USD', 'language' => 'en']);      // USD EUR
//        return BookingService::getAvailabilityForListings($listings, $searchCriteria, true);

        dump(BookingService::getAvailabilityForListings($listings, $searchCriteria, true));
//        dump($searchCriteria);
//        dump($listings);

        return;

        $importedIdsBySystem = [];

        foreach ($listings as $listing) {
            foreach ($listing->activeImporteds as $imported) {
                if (! $imported->getImportSystem()->onlineBooking) {
                    continue;
                }

                $importedIdsBySystem[$imported->system][] = $imported->id;
                $importedIdToListingIdMap[$imported->id] = $listing->id;
            }
        }

//        dump($importedIdsBySystem);

        $results = [];
        foreach ($importedIdsBySystem as $systemName => $importedIDs) {
            $systemClassName = ImportSystems::findByName($systemName)->getSystemService(); //ImportSystems::findByName($systemName)->getClassName();
            $results[$systemName] = $systemClassName::getAvailability(Imported::whereIn('id', $importedIDs)->get(), $searchCriteria, []);
        }

        dump($results);

        $availabilityByListingID = [];
        foreach ($results as $systemName => $roomAvailabilities) {
            foreach ($roomAvailabilities as $roomAvailability) {
                if (! $roomAvailability->isValid()) {
                    continue;
                } // (isValid() reports its own warnings)
                /*              if ($requireRoomDetails && !$roomAvailability->hasCompleteRoomDetails) {
                                  logError("requireRoomDetails set, but $systemName returned an availability without hasCompleteRoomDetails.");
                                  continue;
                              }*/
                if (! $roomAvailability->hasAvailabilityForEitherAlltheNightsOrAllTheBlocks()) {
                    continue;
                } // not enough availability to bother displaying
                $listingID = $importedIdToListingIdMap[$roomAvailability->importedID];
                $availabilityByListingID[$listingID][] = $roomAvailability;
            }
        }

        dump($availabilityByListingID);
    }

    private function testPageCache()
    {
        PageCache::reenable();
        PageCache::setVerbose(true);

        //PageCache::createTables();
        return 'ok';
    }

    private function testPageCacheDeleteExpired()
    {
        $deletedKeysArray = PageCache::deleteExpired();

        return '<br>deleted: ' . implode(', ', $deletedKeysArray);
    }

    private function paidReviewerLogTest(): void
    {
        file_put_contents('/home/hostelz/paid-reviewer-hit-log', Carbon::now() . ' ' . $_SERVER['REMOTE_ADDR'] . "\n", FILE_APPEND);
    }

    private function queueTest(): void
    {
        Queue::push(function ($job): void {
            echo 'start1';
            sleep(10);
            echo 'end1';
            $job->delete();
        }, null, 'queue1');
    }

    private function commissionCompare(): void
    {
        // commission should be 44.5%
        $bookings = Booking::where('bookingTime', 'like', '2015-03-%')->where('system', 'Hostelbookers')->get();

        $totalCount = $totalPercent = $totalTotalPrice = 0;

        foreach ($bookings as $booking) {
            $details = json_decode($booking->bookingDetails, true);

            $deposit = $details['deposit']['localCurrency'];
            if ($details['localCurrency'] != 'USD') {
                continue;
            } // $deposit = Currencies::convert($deposit, $details['localCurrency'], 'USD');

            $totalPrice = $details['totalPrice']['localCurrency'];
            if ($details['localCurrency'] != 'USD') {
                continue;
            } // $totalPrice = Currencies::convert($totalPrice, $details['localCurrency'], 'USD');

            $percent = round(100 * $booking->commission / $totalPrice, 1);
            $totalPercent += $percent;
            $totalTotalPrice += $totalPrice;
            $totalCount++;

            echo $booking->commission . ' / ' . $totalPrice . ' = ' . $percent . '%<br>';
        }

        echo '<br>Average: ' . round($totalPercent / $totalCount, 1) . '% commission<br>';
        echo 'Average Total Price: ' . round($totalTotalPrice / $totalCount, 2);
    }

    private function testDbLog()
    {
        return App\Models\Languages::allLiveSiteCodes();
    }

    private function airbnb()
    {
        //return file_get_contents('http://www.useragentstring.com/');
        // return file_get_contents('https://www.airbnb.com/calendar/ical/224603.ics?s=aa95f5ee66c9490a32d352955323dab5');
        return WebsiteTools::fetchPage('https://www.airbnb.com/calendar/ical/224603.ics?s=aa95f5ee66c9490a32d352955323dab5');
    }

    private function dupMaintTest()
    {
        return ListingDuplicate::dailyMaintenance();
    }

    private function mailRecipientAddressesFix(): void
    {
        $msgs = MailMessage::where('recipientAddresses', '')->where('recipient', '!=', '')->get();
        foreach ($msgs as $msg) {
            $msg->setRecipientAddresses();
            echo $msg->recipient;
            print_r($msg->recipientAddresses);
            $msg->save();
        }
    }

    private function pricelineNameFix(): void
    {
        $names = Listing::where('name', 'like', '%&%')->select('id', 'name')->get();

        foreach ($names as $name) {
            if (html_entity_decode($name->name) != $name->name) {
                echo "$name->id '" . htmlentities($name->name) . '\' to \'' . $name->name . '\'<br>';
                $listing = Listing::findOrFail($name->id);
                $listing->name = html_entity_decode($name->name);
                $listing->save();
                //break;
            }
        }

        exit();

        $names = Imported::where('name', 'like', '%&%')->select('id', 'name', 'system')->get();

        foreach ($names as $name) {
            if (html_entity_decode($name->name) != $name->name) {
                echo "$name->system $name->id '" . htmlentities($name->name) . '\' to \'' . $name->name . '\'<br>';
                $imported = Imported::findOrFail($name->id);
                $imported->name = html_entity_decode($name->name);
                $imported->save();
                //break;
            }
        }

        echo '<h2>previous</h2>';

        $names = Imported::where('previousName', 'like', '%&%')->select('id', 'previousName', 'system')->get();

        foreach ($names as $name) {
            if (html_entity_decode($name->previousName) != $name->previousName) {
                echo "$name->system $name->id '" . htmlentities($name->previousName) . '\' to \'' . $name->previousName . '\'<br>';
                $imported = Imported::findOrFail($name->id);
                $imported->previousName = html_entity_decode($name->previousName);
                $imported->save();
                //break;
            }
        }
    }

    private function bookingSerialize()
    {
        $availability = unserialize('O:31:"\App\BookingProcess\Availability":5:{s:15:"bookingCriteria";N;s:8:"roomCode";N;s:8:"roomName";N;s:15:"roomDescription";N;s:8:"criteria";O:34:"App\BookingProcess\BookingCriteria":5:{s:9:"startDate";O:13:"Carbon\Carbon":3:{s:4:"date";s:26:"2015-01-21 17:51:44.000000";s:13:"timezone_type";i:3;s:8:"timezone";s:15:"America/Chicago";}s:6:"people";i:2;s:6:"nights";N;s:8:"roomType";N;s:8:"currency";N;}}');
        dd($availability);
        $criteria = new BookingProcess\BookingCriteria();
        $criteria->people = 2;
        $criteria->startDate = Carbon::now();

        $availability = new BookingProcess\Availability();
        $availability->criteria = $criteria;

        $t = serialize($availability);

        return $t;
    }

    private function mergePriceline()
    {
        exit();

        set_time_limit(1 * 60 * 60);
        ignore_user_abort(true);

        $mod = Request::input('mod');

        $listingIDs = Listing::where('verified', '<', 0)->where('source', 'like', '%priceline%')->whereRaw('id % 100 = ' . $mod)->orderBy('id')->pluck('id');
        //dd($listingIDs);
        // $listingIDs = Listing::where('id', 258327)->pluck('id');

        $count = count($listingIDs);
        foreach ($listingIDs as $key => $listingID) {
            $listing = Listing::find($listingID);
            if (! $listing) {
                echo "[$listingID not found, already merged?] ";

                continue;
            }
            echo "<br>\n$key/$count " . round(memory_get_usage() / 1000000) . ' ' . $listing->id . ' ' . $listing->name . ":<br>\n";
            $didAutoMerge = ListingDuplicate::findDuplicates($listing, true, false, true);
            echo($didAutoMerge ? ' yes' : ' no') . "<br>\n";
            // if ($foundMatch) return 'done';
        }

        $mod++;
        if ($mod < 100) {
            WebsiteTools::fetchPage('http://secure-dev.hostelz.com/staff/temp/mergePriceline?mod=' . $mod, null, [CURLOPT_HTTPAUTH => CURLAUTH_ANY, CURLOPT_USERPWD => 'dev:ddeevv', false, true]);
            //dd($result);
        }

        return 'done.';
    }

    private function deleteBadImporteds(): void
    {
        set_time_limit(10 * 60);

        $importeds = Imported::where('name', '')->get();

        foreach ($importeds as $imported) {
            echo '[' . $imported->system . ' ' . $imported->id . "]\n";
            $imported->delete();
        }
    }

    private function fixReturns(): void
    {
        // importeds

        $importeds = Imported::where(function ($query): void {
            $query->where('address1', 'like', "%\n%")
                ->orWhere('address1', 'like', "%\r%");
        })->get();

        foreach ($importeds as $imported) {
            $s = self::fixStringFormatting($imported->address1);
            echo '[' . $imported->system . ' ' . $imported->id . ': ' . $s . "]\n";
            $imported->address1 = $s;
            $imported->save();
        }

        // listings

        $listings = Listing::where(function ($query): void {
            $query->where('address', 'like', "%\n%")
                ->orWhere('address', 'like', "%\r%");
        })->get();

        foreach ($listings as $listing) {
            $s = self::fixStringFormatting($listing->address);
            echo '[' . $listing->id . ': ' . $s . "]\n";
            $listing->address = $s;
            $listing->save();
        }
    }

    // Replaces returns with commas and gets rid of extra spaces or multiple commas in a row.

    private function fixStringFormatting($s)
    {
        // Replace returns with commas
        $s = mb_stri_replace("\n", ',', $s);
        $s = mb_stri_replace("\r", ',', $s);

        // Explode by commas and trim each part
        $parts = array_map(function ($v) {
            return mb_trim($v);
        }, explode(',', $s));
        $parts = array_filter($parts, function ($v) {
            return $v != '';
        });

        return implode(', ', $parts);
    }

    private function determineListingID(): void
    {
        echo MailMessage::find(130834)->determineListingIDs(true);
    }

    private function mergeTest()
    {
        $listing1 = Listing::find(180128);
        $listing2 = Listing::find(259698);

        $choices = ListingDuplicate::generateMergeChoices([$listing1, $listing2]);

        print_r($choices);

        return '';
    }

    private function testFresh()
    {
        $listing = Listing::find(1);
        $listing->name = 'foo';
        $listing = $listing->fresh();

        return $listing->name;
    }

    private function mailParseTest(): void
    {
        $mailParser = new \Lib\Mail_RFC822();
        //$parsedAddresses = $mailParser->parseAddressList("ggggrgrgrg <info@hihostels.hu>", null, false);
        //echo 'here!'; exit();
        $parsedAddresses = $mailParser->parseAddressList('Erdőháti Ágnes <info@hihostels.hu>', null, false);
        dd($parsedAddresses);
    }

    private function releaseMailLock(): void
    {
        releaseLock('fetchIncomingMail');
    }

    private function duplicateTest(): void
    {
        $listingDuplicate = ListingDuplicate::first();
        dd($listingDuplicate->listing->id);
    }

    private function deleteMailIDZeroAttachments(): void
    {
        $attachments = MailAttachment::where('mailID', 0)->get();
        foreach ($attachments as $attachment) {
            $attachment->delete();
        }
    }

    private function mailTest()
    {
        Emailer::send(User::where('username', 'orrd101@gmail.com')->first(), 'New Hostelz.com Staff Article Comment', 'generic-email', ['text' => "A Hostelz.com staff person has added a new comment to one of your articles.\n\nTo view the comment, see your Travel Articles list.",
        ], Config::get('custom.userSupportEmail'));
        // Emailer::send('orrd101@gmail.com', 'test subject', 'staff/email-plainText', [ 'messageText' => 'mailTest mailTest mailTest' ], 'test@hostelz.com', 'Staff');

        return 'ok';

        //Emailer::send(1, 'test subject', 'staff/email-plainText', [ 'messageText' => 'mailTest mailTest mailTest' ], 41299, 'Staff');

        MailMessage::sendQueuedMessages();

        return 'ok';
    }

    private function dateTesting()
    {
        return (string) Carbon::createFromDate(2015, 1, 1);

        $time = Carbon::now();
        if ($time->isWeekend() || $time->hour < 8 || $time->hour > 19) {
            if (! $time->isFuture()) {
                $time->addDays(1);
            }
            if (! $time->isWeekday()) {
                $time->modify('next weekday');
            } // go to the next weekday
            $time->hour = 8; // set to 8 AM (minutes/seconds are unchanged)
            $result = $time;
        } else {
            $result = $time->addMinutes($transmitDelayMinutes);
        }

        echo $result;
        exit();

        $time = Carbon::now()->addHours(-4);
        if ($time->isWeekend() || $time->hour < 8 || $time->hour > 19) {
            $time->hour = 9; // set to 9 AM (minutes/seconds are unchanged)
            if (! $time->isFuture()) {
                $time->addDays(1);
            }
            if (! $time->isWeekday()) {
                $time->addWeekday();
            }
        }
        echo $time;
    }

    private function checkForPlagiarism()
    {
        $output = '';
        $rows = AttachedText::where('subjectType', 'cityInfo')->where('type', 'description')->where('data', '!=', '')->whereIsNull('plagiarismCheckDate')->get();

        foreach ($rows as $key => $row) {
            echo $row->id . ' ';
            // * Check for Plagiarism *

            if (! $row->plagiarismCheckDate) {
                $result = \Lib\PlagiarismChecker::textCheck($row->data);
                if ($result == false) {
                    logError('PlagiarismChecker returned an error.');
                } else {
                    $output .= "(ID {$row->id} plagiarism percentMatched: " . $result['percentMatched'] . ') ';
                    $row->plagiarismCheckDate = Carbon::now();
                    $row->plagiarismPercent = $result['percentMatched'];
                    $row->plagiarismInfo = $result['details'];
                    $row->save();

                    if ($row->plagiarismPercent > 20) {
                        logError("Plagiarism score of $row->plagiarismScore for attached text $row->id.", [], 'alert');
                    }
                }
            }
            echo $output;
            $output = '';

            //return ' ';
        }

        return ' ';
    }

    private function macrosInsert(): void
    {
        return; // disabled

        // isInActiveBookingSystems
        $macros['Listing Support'] = [
            // ... array data goes here
        ];

        foreach ($macros as $category => $macroArray) {
            foreach ($macroArray as $macro) {
                $newMacro = new Macro(['status' => 'ok', 'userID' => ($category == 'Admin' ? 1 : 0), 'purpose' => 'mail', 'category' => $category, 'name' => $macro['name'], 'macroText' => $macro['text'],
                ]);
                if (isset($macro['conditions'])) {
                    $newMacro->conditions = $macro['conditions'];
                }
                $newMacro->save();
            }
        }
    }

    private function assertTest(): void
    {
        assert(false);
    }

    private function getPricelineListings()
    {
        $system = 'Priceline';

        $importeds = Imported::where('system', $system)->where('status', 'active')->where('hostelID', '!=', '')->limit(250)->orderBy(DB::raw('RAND()'))->get();

        $output = '';
        foreach ($importeds as $imported) {
            $listing = Listing::find($imported->hostelID);
            if (! $listing->isLive()) {
                continue;
            }
            $output .= $listing->getURL() . '<br>';
        }

        return $output;
    }

    private function getPricelineRoomTypes()
    {
        set_time_limit(5 * 60 * 60);

        $system = 'Priceline';
        require_once "system.$system.inc";
        define('BOOKING_DEBUG', false);

        $importeds = Imported::where('system', $system)->where('status', 'active')->limit(150)->orderBy(DB::raw('RAND()'))->get();

        $allRooms = [];
        foreach ($importeds as $imported) {
            $rooms = Priceline::tempGetRoomTypes($imported, 10, 1, 2015, 1, 'USD', 'en');
            usleep(150000);
            $allRooms = array_merge($allRooms, array_diff($rooms, $allRooms));
        }

        sort($allRooms);

        return implode('<br>', $allRooms);
    }

    private function translationTest(): void
    {
        dump("Enfin un Abri qui répond ?\n\n  toutes vos attentes de l'été");
        dump(\Lib\TranslationService::translate("Enfin un Abri qui répond ?\n\n  toutes vos attentes de l'été", null, 'en'));
        dump('ローマ, イタリア共和国');
        dump(\Lib\TranslationService::translate('ローマ, イタリア共和国', null, 'en'));
        dump('Yellow Hostelではプライベートルーム（ダブルルーム、クアッドルームはバスルーム、つまり風呂とトイレがついています）かドーミトリータイプ（ベッドが4つか6つ部屋の中にあります。バスルームは部屋の外にあります）の部屋を選択することが出来ます。シーツやタオルは値段に含まれており、インターネットのWi-Fiも含まれています。部屋の鍵はキーカードになっておりまして、部屋の中には貴重品や荷物を保管できるロッカーもあります。');
        dump(\Lib\TranslationService::translate('Yellow Hostelではプライベートルーム（ダブルルーム、クアッドルームはバスルーム、つまり風呂とトイレがついています）かドーミトリータイプ（ベッドが4つか6つ部屋の中にあります。バスルームは部屋の外にあります）の部屋を選択することが出来ます。シーツやタオルは値段に含まれており、インターネットのWi-Fiも含まれています。部屋の鍵はキーカードになっておりまして、部屋の中には貴重品や荷物を保管できるロッカーもあります。', null, 'en'));
    }

    private function languageTest()
    {
        return LanguageString::getAllEnFiles();
    }

    private function addPicTest(): void
    {
        $listing = Listing::find(482); // 6 131 (no good owner pics)

        $listing->getBestPics();

        // return 'result:' . $listing->addOwnerPic('/home/hostelz/live/public/pics/hostels/owner/originals/74/2577274.jpg', 'caption here');
    }

    /* Conversion notes:  To convert qf fieldinfo, pass convertToLaravel=1 to old scripts to output info */

    private function convertRestoreDatabaseToLaravel()
    {
        if (! Request::has('data')) {
            return '<form method=post>' . csrf_field() . '<textarea name=data></textarea><br><button type=submit>Submit</button></form>';
        }

        $data = Request::input('data');
        $data = explode("\n", $data);
        $output = '';

        foreach ($data as $line) {
            $line = trim(str_replace(['PRIMARY KEY (id)'], '', $line), " ,\n\t\r\x0B");
            if ($line == '') {
                continue;
            }

            if (! preg_match('|([a-zA-z0-9]+) ([a-zA-Z]+)(.*)$|', $line, $matches)) {
                $output .= "Couldn't parse line '$line'.\n";

                continue;
            }
            list(, $fieldName, $type, $misc) = $matches;
            $misc = trim(str_replace(['NOT NULL', "DEFAULT ''", 'DEFAULT 0'], '', $misc), ' ,');

            switch ($type) {
                case 'INT':
                    if ($misc == 'AUTO_INCREMENT') {
                        $misc = trim(str_replace('AUTO_INCREMENT', '', $misc));
                        $output .= "\$table->increments('$fieldName');" . ($misc != '' ? " /* $misc */" : '') . "\n";

                        break;
                    }
                    $output .= "\$table->integer('$fieldName');" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'UNSIGNED':
                    $misc = trim(str_replace('INT ', '', $misc));
                    $output .= "\$table->integer('$fieldName')->unsigned();" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'VARCHAR':
                    if (! preg_match('|\(([0-9]+)\)(.*)$|', $misc, $matches)) {
                        $output .= "Couldn't parse varchar for line '$line'.\n";

                        continue 2;
                    }
                    list(, $length, $misc) = $matches;
                    $misc = trim($misc);
                    $output .= "\$table->string('$fieldName', $length);" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'CHAR':
                    if (! preg_match('|\(([0-9]+)\)(.*)$|', $misc, $matches)) {
                        $output .= "Couldn't parse char for line '$line'.\n";

                        continue 2;
                    }
                    list(, $length, $misc) = $matches;
                    $misc = trim($misc);
                    $output .= "\$table->char('$fieldName', $length);" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'ENUM':
                    if (! preg_match('|\(([0-9a-zA-Z, \']+)\)(.*)$|', $misc, $matches)) {
                        $output .= "Couldn't parse varchar for line '$line'.\n";

                        continue 2;
                    }
                    list(, $enums, $misc) = $matches;
                    $misc = trim($misc);
                    $output .= "\$table->enum('$fieldName', [ $enums ]);" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'DECIMAL':
                    if (! preg_match('|\(([0-9]+),([0-9]+)\)(.*)$|', $misc, $matches)) {
                        $output .= "Couldn't parse varchar for line '$line'.\n";

                        continue 2;
                    }
                    list(, $lengthOne, $lengthTwo, $misc) = $matches;
                    $misc = trim($misc);
                    $output .= "\$table->decimal('$fieldName', $lengthOne, $lengthTwo);" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'BOOL':
                    $output .= "\$table->boolean('$fieldName');" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'TINYINT':
                    $output .= "\$table->tinyInteger('$fieldName');" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'DATE':
                    $output .= "\$table->date('$fieldName');" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'DATETIME':
                    $output .= "\$table->datetime('$fieldName');" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;
                case 'TEXT':
                    $output .= "\$table->text('$fieldName');" . ($misc != '' ? " /* $misc */" : '') . "\n";

                    break;

                default:
                    $output .= "Unknown type for line '$line'.\n";
            }
        }

        return '<pre>' . htmlentities($output) . '</pre>';
    }

    private function convertSmartyToBlade()
    {
        if (! Request::has('data')) {
            return '<form method=post><input type=hidden name=_token value=' . csrf_token() . '><textarea name=data></textarea><br><button type=submit>Submit</button></form>';
        }

        $data = Request::input('data');

        // Language config_load
        $languageSections = ['default'];
        if (preg_match_all('|\{config_load file\=\"language\_\$LANGUAGE\.config\" section\=\'(.+)\'\}|U', $data, $matches)) {
            foreach ($matches[1] as $match) {
                $languageSections[] = $match;
            }
        }
        $data = preg_replace('|(\{config_load file\=\"language\_\$LANGUAGE\.config\" section\=\'.+\'\})|U', '', $data); // remove the config_load tags

        // Language tags
        if (preg_match_all('|\{(\#.+)\}|U', $data, $matches)) {
            foreach ($matches[1] as $languageTag) {
                if (! preg_match('|\#(.+)\#(.*)$|U', $languageTag, $match)) {
                    echo "Couldn't extract language tag $languageTag<br>.";

                    continue;
                }
                $name = $match[1];
                $otherStuff = $match[2];
                // Find it in the language data
                $matchingSection = null;
                foreach ($languageSections as $section) {
                    $dbMatches = LanguageString::where('group', $section)->where('key', $name)->get();
                    if (! $dbMatches->isEmpty()) {
                        $matchingSection = $section;

                        break;
                    }
                }
                if ($matchingSection == null) {
                    echo "No LanguageString data found for $languageTag.<br>";

                    continue;
                }
                // echo "$matchingSection.$name<br>";
                // Replace it with the Blade format
                $data = str_replace('{' . $languageTag . '}', "{!! langGet('" . $matchingSection . '.' . $name . "'" .
                    ($otherStuff != '' ? ", $otherStuff" : '') .
                    ') !!}', $data);
            }
        }

        // Strings
        $replacements = [
            '{strip}' => '', '{/strip}' => '', '{*' => '{{--', '*}' => '--}}', '{else}' => '@else ', '{/if}' => '@endif ', '{/foreach}' => '@endforeach ',
            '$login->HasAccess(' => 'Auth::user()->hasPermission(',
        ];
        foreach ($replacements as $from => $to) {
            $data = str_replace($from, $to, $data);
        }

        // Regular expressions
        $replacements = [
            '|\{if (.*)\}|U' => '@if ($1)', // if
            '|\{elseif (.*)\}|U' => '@elseif($1)', // elseif
            '|\{include file\=[\'\"](.*)[\'\"]\}|U' => '@include($1)', // include
            '|\{(\$[a-zA-Z_]+)\.([a-zA-Z_]+)\}|U' => '{{{ $1->$2 }}}', // output variable with '.'
            '|\{(\$[a-zA-Z_]+)\}|U' => '{{{ $1 }}}', // variable output
            '|\{assign var\=([a-zA-Z_]+) value\=(.+)\}|U' => '<?php $$1 = $2; ?>', // assign
            '|\{foreach (\$[a-zA-Z_]+) as (\$[a-zA-Z_]+) \=\> (\$[a-zA-Z_]+)\}|U' => '@foreach ($1 as $2 => $3)',  // foreach with key
            '|\{foreach (\$[a-zA-Z_]+) as (\$[a-zA-Z_]+)\}|U' => '@foreach ($1 as $2)',  // foreach without key
            '|\{foreach from\=(\$[a-zA-Z_]+) item\="?\$?([a-zA-Z_]+)"?\}|U' => '@foreach ($1 as $$2)',  // foreach from/to
            '|\{foreach from\=(\$[a-zA-Z_]+) item\="?\$?([a-zA-Z_]+)"? key\="?\$?([a-zA-Z_]+)"?\}|U' => '@foreach ($1 as $$3 => $$2)',  // foreach from/to/key
        ];
        foreach ($replacements as $from => $to) {
            $data = preg_replace($from, $to, $data);
        }

        return '<pre>' . htmlentities($data) . '</pre>';
    }

    private function importLanguageConfigs(): void
    {
        global $smarty;
        require 'myPrepend.inc'; // for Smarty

        $langs = ['en'];
        $langPath = '/home/hostelz/dev/templates/';

        foreach ($langs as $lang) {
            $lines = file($langPath . "language_$lang.config");

            $lines[0] = '[]'; // a blank section so we start by loading the initial values

            $comment = '';

            foreach ($lines as $line) {
                $line = trim($line);
                switch (substr($line, 0, 1)) {
                    case '':
                    case ' ':
                        break;
                    case '#':
                        // (note: "##" lines are admin comments that are note shown to translators)
                        if (substr($line, 1, 1) != '#' && trim(substr($line, 1)) != '') {
                            $comment = substr($line, 1);
                        }

                        break;
                    case '[':
                        $comment = '';
                        $section = substr($line, 1, strlen($line) - 2);
                        echo "<br><center><h2>$section</h2></center>";
                        $smarty->clearConfig();
                        $smarty->configLoad($LANG_PATH . "language_$lang.config", $section);
                        $currentTranslations = $smarty->getConfigVars();
                        $smarty->clearConfig();
                        $smarty->configLoad('language_en.config', $section);
                        $english = $smarty->getConfigVars();
                        if ($section == '') {
                            $section = 'global';
                        }

                        break;
                    default:
                        $variable = substr($line, 0, strpos($line, ' '));
                        $value = $currentTranslations[$variable];
                        if ($value == '') {
                            continue 2;
                        }
                        $needsUpdate = ($lang != 'en' && $value == $english[$variable]);
                        if ($needsUpdate || $english[$variable] == '') {
                            continue 2;
                        } // sometimes we have an empty variable for something removed or to be added

                        // Replacements
                        // $newValue = preg_replace_callback("|([\_\[]+[^ ]+[\_\]]+)|", function ($matches) { return ':'.trim($matches[0], '[]__'); }, $value);

                        // Replace some of the old __NAME__ placeholders with [NAME].
                        $newValue = preg_replace_callback("|([\_]+[^ ]+[\_]+)|", function ($matches) {
                            return '[' . trim($matches[0], '_') . ']';
                        }, $value);

                        echo "Section: '$section', Variable: '$variable', Comment: '$comment', Text: '" . htmlentities($value) . "' -> '" . htmlentities($newValue) . "'<br>";

                        break;
                }
            }
        }
    }

    private function plagiarism()
    {
        set_time_limit(30 * 60 * 60);

        $output = '';

        // Useful online text comparison tools: http://www.compareitnow.net/  http://www.comparesuite.com

        /* TO DO: Should this be moved to a separate PlaceDescription class (based on this class)? (see Global Scopes) */

        // Note: non-new rows are for comparison purposes only (below)
        $rows = AttachedText::where('data', '!=', '')->where('subjectType', 'cityInfo')->where('type', 'description')->where('plagiarismCheckDate', '0000-00-00')->limit(500)->orderByRaw('RAND()')->get();
        // ->where('score', 100)
        foreach ($rows as $key => $row) {
            $output .= '<a href=/staff/editAttached.php?m=d&w[id]=' . $row->id . '>' . $row->id . '</a> ';

            // * Check for Plagiarism *

            if ($row->plagiarismCheckDate == '0000-00-00') {
                $result = Lib\PlagiarismChecker::textCheck($row->data);
                if ($result == false) {
                    break;
                }
                $output .= '(plagiarism percentMatched: ' . $result['percentMatched'] . ') ';
                $row->plagiarismCheckDate = Carbon::now();
                $row->plagiarismPercent = $result['percentMatched'];
                $row->plagiarismInfo = $result['details'];
            }

            // * Save *

            $row->save();
        }

        return $output;

        // $result = PlagiarismChecker::textCheck('foo', true);
        $text = AttachedText::findOrFail(17966762);
        $result = Lib\PlagiarismChecker::textCheck('None of our competitors offer a guarantee like this! Your booking is 100% confirmed. Any problems and we’ll give you $50.' /*$text->data*/, true);
        print_r($result);

        return '';
    }

    private function priceline()
    {
        /*
        Host: 		affiliate-db.pricelinepartnernetwork.com
Port: 		3306
Username: 	AT-affi
Password:  	Hx0*#,4,"_%S2{U
        */

        $db = new PDO('mysql:host=affiliate-db.pricelinepartnernetwork.com:3306;dbname=affiliate_data;charset=utf8', 'AT-affi', 'Hx0*#,4,"_%S2{U', [PDO::MYSQL_ATTR_DIRECT_QUERY]);
        if (! $db) {
            return 'no db';
        }

        //dd($db);

        //return $db ? 'yes' : 'no';

        // $q = "select * from area_ppn limit 1";

        // USE menagerie

        $q = 'SHOW tables';
        $sth = $db->query($q);
        if ($sth === false) {
            return 'no sth';
        }
        $r = $sth->fetchAll(PDO::FETCH_ASSOC);
        print_r($r);

        $sth = $db->query('SELECT * FROM `AT-hotel_v3` limit 10');
        if ($sth === false) {
            return 'no sth';
        }
        $r = $sth->fetchAll(PDO::FETCH_ASSOC);
        print_r($r);
    }

    public function fixWeb(): void
    {
        // select system,web from imported where web not like 'http%' and web != '' and web not like 'www%'

        echo '<h2>Importeds</h2>';

        $importeds = DB::table('imported')->where('web', '!=', '')->select(['id', 'web', 'system'])->get()->all();
        foreach ($importeds as $imported) {
            if (filter_var($imported->web, FILTER_VALIDATE_URL)) {
                continue;
            }
            $web = $imported->web;
            if (strpos($web, 'www.') === 0) {
                $web = 'http://' . $web;
            }
            $web = str_replace(
                ['Still designing it', 'http:\\', 'http:/w', 'http:// '],
                ['', 'http://', 'http://w', 'http://'],
                $web
            );
            echo $imported->id . ' ' . $imported->system . ': ' . $imported->web . ' -> ' . $web;
            if (! filter_var($web, FILTER_VALIDATE_URL)) {
                echo ' - still invalid.<br>';

                continue;
            }
            echo '<br>';
            DB::update('UPDATE imported SET web=? WHERE id=?', [$web, $imported->id]);
        }

        echo '<h2>Hostels</h2>';

        $rows = DB::table('listings')->where('web', '!=', '')->select(['id', 'web'])->get()->all();
        foreach ($rows as $row) {
            if (filter_var($row->web, FILTER_VALIDATE_URL)) {
                continue;
            }
            $web = $row->web;
            if (strpos($web, 'www.') === 0) {
                $web = 'http://' . $web;
            }
            $web = str_replace(
                ['Still designing it', 'http:\\', 'http:/w', 'http:// '],
                ['', 'http://', 'http://w', 'http://'],
                $web
            );
            echo '<a href=/staff/editListings.php?m=d&w[id]=' . $row->id . '>' . $row->id . '</a>: ' . $row->web . ' -> ' . $web;
            if (! filter_var($web, FILTER_VALIDATE_URL)) {
                echo ' - still invalid.<br>';

                continue;
            }
            echo '<br>';
            DB::update('UPDATE listings SET web=? WHERE id=?', [$web, $row->id]);
        }
    }

    private function testAll(): void
    {
        try {
            $client = new \Lib\RestClient('https://api.serpwatch.io/', null, 'mail@hostelgeeks.com', 'Jy3S90IfrXhRAtpc');

            $key_word = urlencode('hostels in amsterdam');

            dump($key_word);

            $key_get_result = $client->get('api/cmn_key_id/' . $key_word . '/');
            dump($key_get_result);

            $task_get_result = $client->get('api/rnk_tasks_get/');
            dump($task_get_result);

            // do something
        } catch (\Lib\RestClientException $e) {
            echo "\n";
            echo "HTTP code: {$e->getHttpCode()}\n";
            echo "Error code: {$e->getCode()}\n";
            echo "Message: {$e->getMessage()}\n";
//            print  $e->getTraceAsString();
//            echo "\n";
            exit();
        }
        $client = null;

        /*        DB::enableQueryLog();

                dump(DB::getQueryLog());*/

//        BookingDotCom::testImport();
    }
}
