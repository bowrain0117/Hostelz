<?php

namespace App\Models;

use App\Helpers\EventLog;
use App\Models\Listing\Listing;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\DB;
use Lib\BaseModel;
use Lib\Emailer;
use Lib\PageCache;
use Lib\PlagiarismChecker;

class Review extends BaseModel
{
    protected $table = 'reviews';

    public static $staticTable = 'reviews'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $statusOptions = [
        'newHostel' /* todo: rename this to 'reviewHold' */, 'newReview' /* todo: rename this to 'submitted' */, 'markedForEditing',
        'staffEdited', 'publishedReview', 'removedReview', 'deniedReview', 'returnedReview', 'postAsRating',
    ];

    public static $ratingOptions = ['0', '1', '2', '3', '4', '5'];

    public static $payStatusOptions = ['notForPay', 'paid']; // Note: '' means it hasn't yet been paid.

    public static $minimumWordsAccepted = 375;

    public static $templateFormat = [
        'intro' => "Start with an introduction to the hostel here. You can use this space for miscellaneous things about the hostel that you want to point out right away, or things that don't fit the subtitled sections below.",
        'The Location' => "Info about the location/neighborhood and if it's difficult/easy to find. Accessibility to the train/bus station, or availability of parking (whichever is appropriate depending on the city).",
        'Rooms and Bathrooms' => 'Talk about the dorm rooms (such as doorlocks, in-room lockers, number and type of beds, option of private rooms) and bathrooms (quality of the showers, and whether they have space to change, hooks/shelves for your clothes, soap dishes, etc.).',
        'Common Spaces' => "What common spaces are there (kitchens, tv room, sitting areas, computers, outside seating, luggage storage, game room, book exchange, etc.)?  Is it a social hostel where you're likely to meet other friendly backpackers? It is a fun party hostel?  A subdued but social hostel?  Too quiet?  Full of too many creepy/sketchy people? Is it smoky?",
        'Summary' => "Brief summary of your overall impression of the hostel here. You can also include any additional positive or negative points that didn't fit one of the subtitled sections above.",
    ];

    public const PAY_AMOUNT = '10.00';

    public const DAYS_TO_HOLD_FOR_REVIEW = 45;

    public const YEARS_BEFORE_REREVIEW_WANTED = 4;

    // Pics

    // min allowed for newly submitted review photos
    public const NEW_PIC_MIN_WIDTH = 800;

    public const NEW_PIC_MIN_HEIGHT = 800;

    // min size to display old review photos
    public const OLD_PIC_MIN_WIDTH = 300;

    public const OLD_PIC_MIN_HEIGHT = 300;

    public const MIN_PIC_COUNT = 5;

    public const THUMBNAIL_HEIGHT = 220;

    // We're currently displaying markedForEditing review pics on the site because
    // we're behind schedule on editing them.
    public const LIVE_PIC_STATUSES = ['ok', 'markedForEditing'];

    public function save(array $options = []): void
    {
        parent::save($options);
        $this->clearRelatedPageCaches();
    }

    public function delete(): void
    {
        foreach ($this->pics as $pic) {
            $pic->delete();
        }
        parent::delete();
        $this->clearRelatedPageCaches();
    }

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true],
                    'reviewerID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('reviewerID') == 'display')
                                && $model->user ? $model->user->username : $model->reviewerID;
                        }, ],
                    'hostelID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('hostelID') == 'display')
                                && $model->listing ? $model->listing->fullDisplayName() : $model->hostelID;
                        }, ],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'notes' => ['type' => 'textarea', 'rows' => 3],
                    'expirationDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'reviewDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'rating' => ['type' => 'select', 'options' => self::$ratingOptions, 'optionsDisplay' => 'translate'],
                    'review' => ['type' => 'textarea', 'rows' => 20],
                    'editedReview' => ['type' => 'textarea', 'rows' => 12],
                    'ownerResponse' => ['type' => 'textarea', 'rows' => 3],
                    'author' => ['maxLength' => 75],
                    'bookingInfo' => ['maxLength' => 250],
                    'comments' => ['type' => 'display', 'searchType' => 'text', 'unescaped' => true],
                    'newComment' => ['type' => 'ignore', 'editType' => 'textarea', 'rows' => 4,
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($value != '') {
                                $model->addComment($value, 'staff');
                            }
                        },
                    ],
                    'newReviewerComment' => ['type' => 'select', 'editType' => 'ignore', 'options' => ['0', '1'], 'optionsDisplay' => 'translate'],
                    'newStaffComment' => ['type' => 'select', 'editType' => 'ignore', 'options' => ['0', '1'], 'optionsDisplay' => 'translate'],
                    'plagiarismCheckDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType', 'maxLength' => 80],
                    'plagiarismPercent' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax', 'sanitize' => 'int'],
                    'plagiarismInfo' => ['type' => 'textarea', 'rows' => 4],
                    'payStatus' => ['type' => 'select', 'options' => self::$payStatusOptions, 'optionsDisplay' => 'translate'],
                    'rereviewWanted' => ['type' => 'select', 'options' => ['0', '1'], 'optionsDisplay' => 'translate'],
                    // To let us search for reviews that have photos
                    'hasPics' => ['type' => 'ignore', 'searchType' => 'select', 'options' => ['0', '1'], 'optionsDisplay' => 'translate', 'searchQuery' => function ($formHandler, $query, $value) use ($purpose) {
                        if ($value == '1') {
                            return $query->has('pics');
                        } elseif ($value == '0') {
                            return $query->doesntHave('pics');
                        }
                    },
                    ],
                ];

                if ($purpose == 'staffEdit') {
                    $useFields = ['id', 'language', 'editedReview', 'rating', 'status'];
                    $displayOnly = ['hostelID'];
                    $searchOnly = ['reviewerID'];

                    $fieldInfos['editedReview']['getValue'] = function ($formHandler, $model) {
                        return $model->editedReview == '' ? $model->review : $model->editedReview;
                    };
                    $fieldInfos['id']['type'] = 'ignore';
                    array_walk($fieldInfos, function (&$fieldInfo, $fieldName, $displayOnly): void {
                        if (in_array($fieldName, $displayOnly)) {
                            $fieldInfo['editType'] = 'display';
                        }
                    }, $displayOnly);
                    array_walk($fieldInfos, function (&$fieldInfo, $fieldName, $searchOnly): void {
                        if (in_array($fieldName, $searchOnly)) {
                            $fieldInfo['editType'] = 'ignore';
                            echo '!';
                        }
                    }, $searchOnly);
                    $fieldInfos = array_intersect_key($fieldInfos, array_flip(array_merge($useFields, $displayOnly, $searchOnly)));
                }

                break;

            case 'reviewer':
                return [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'reviewerID' => ['type' => 'ignore'],
                    //'reviewDate' => [ 'type' => 'ignore' ],
                    'rating' => ['type' => 'select',
                        'displayType' => 'ignore', // don't show rating once the review is approved (because sometimes we change their rating)
                        'options' => self::$ratingOptions, 'optionsDisplay' => 'translate', 'validation' => 'not_in:0', ],
                    'review' => ['type' => 'textarea', 'rows' => 20],
                    'bookingInfo' => ['maxLength' => 250 /* , 'validation' => 'required' */],
                    'comments' => ['type' => 'display', 'searchType' => 'text', 'unescaped' => true],
                    'newComment' => ['type' => 'ignore', 'editType' => 'textarea', 'rows' => 2,
                        'setValue' => function ($formHandler, $model, $value): void {
                            if ($value != '') {
                                $model->addComment($value, 'user');
                            }
                        },
                    ],
                    // 'newReviewerComment' => [ 'type' => 'ignore' ],
                ];

            case 'management':
                return [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'reviewDate' => ['type' => 'display'],
                    'status' => ['type' => 'display', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'editedReview' => ['type' => 'display', 'listValueTruncate' => 50],
                    'ownerResponse' => ['type' => 'textarea', 'rows' => 3, 'listValueTruncate' => 50],
                ];

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'daily':

                $output .= 'Plagiarism Check: ';

                set_time_limit(30 * 60);
                $reviews = self::where('status', 'newReview')->whereNull('plagiarismCheckDate')->has('pics')->get();
                foreach ($reviews as $review) {
                    $output .= "[$review->id] ";
                    $review->doPlagiarismChecks();
                }

                $output .= "\nPublish edited reviews: ";
                // (Note: We do this once daily to allow a little time to undo accidentally edited or approved reviews.)
                $reviews = self::where('status', 'staffEdited')->with('pics')->get();
                if (! $reviews->isEmpty()) {
                    // Limit the publish reviews to a limited number per day so that there are always fresh
                    // ones in the "recent reviews" list on the homepage
                    $reviews = $reviews->take(ceil($reviews->count() / 28));
                    foreach ($reviews as $review) {
                        $output .= "[$review->id] ";
                        $review->publish();
                    }
                }

                $output .= "\nLog payments for accepted reviews: " . self::logPaymentsForAcceptedReviews();

                $output .= "\nNotify reviewers of soon to be expiring review holds: ";
                $reviews = self::where('status', 'newHostel')->where(
                    'expirationDate',
                    Carbon::now()->addDays(4)->format('Y-m-d')
                )->groupBy('reviewerID')->get();
                foreach ($reviews as $review) {
                    if (! $review->user->isAllowedToLogin()) {
                        continue;
                    } // skip if the user was banned
                    $output .= "$review->id ";
                    Emailer::send($review->user, 'Notice of Review Holds Expiring', 'generic-email', ['text' =>
                        // Todo: After the new site is live, include a link to the 'reviewer:reviews' route.
                        'One or more of your holds on a hostel to review are expiring soon.  If you still plan to review the hostel, you should login and renew the hold.',
                    ]);
                }

                $output .= "\n";

                break;

            case 'weekly':
                $output .= 'Delete Expired Review Holds: ';
                $reviews = self::where('status', 'newHostel')->where('expirationDate', '<', Carbon::now()->format('Y-m-d'))->get();
                foreach ($reviews as $review) {
                    $output .= "$review->id ";
                    $review->delete();
                }

                $output .= "\nAllow re-reviewing of old reviews.\n";
                self::where('rereviewWanted', false)->where('reviewDate', '<=', Carbon::now()->subYears(self::YEARS_BEFORE_REREVIEW_WANTED))->update(['rereviewWanted' => true]);

                break;

            case 'monthly':
                $output .= 'Move really old reviews to Ratings: ';
                foreach (self::where('status', 'publishedReview')->where('reviewDate', '<', Carbon::now()->subYears(6)->format('Y-m-d'))->limit(100)->get() as $review) {
                    $output .= "$review->id ";
                    $review->publishAsARating();
                }

                $output .= "\nOptimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function logPaymentsForAcceptedReviews()
    {
        $output = '';

        $reviews = self::whereIn('status', ['markedForEditing', 'staffEdited', 'publishedReview'])->where('payStatus', '')->get();
        foreach ($reviews as $review) {
            $output .= "[$review->id] ";
            $review->pay();
        }

        return $output;
    }

    public static function listingIsAvailabileForReviewing($listing, $ignoreReviewID = null, $language = null)
    {
        if (! $listing->isLiveOrNew()) {
            return false;
        }
        if ($language === null) {
            $language = Languages::currentCode();
        }
        $query = self::where('hostelID', $listing->id)->where('rereviewWanted', false)
            ->whereNotIn('status', ['deniedReview', 'removedReview', 'postAsRating'])->where('language', $language);
        if ($ignoreReviewID) {
            $query->where('id', '!=', $ignoreReviewID);
        }

        return ! $query->exists();
    }

    /* Accessors & Mutators */

    /* Misc */

    public function doPlagiarismChecks($forceUpdateAll = false): void
    {
        // Check for similar reviews from same user

        $otherReviews = self::where('id', '!=', $this->id)
            ->where('review', '!=', '')
            ->whereNotIn('status', ['newHostel', 'deniedReview', 'returnedReview'])
            ->where(function ($query): void {
                $query->where('reviewerID', $this->reviewerID) // compare to all reviews of this reviewer
                    ->orWhere('hostelID', $this->hostelID); // compare to existing reviews of the same listing
            })
            ->get();

        foreach ($otherReviews as $otherReview) {
            $similar = similar_text($this->review, $otherReview->review, $percent);
            $percent = round($percent);
            // This "percent" sounds high, but reviews at that level tend to be fairly different.
            if ($percent > 48) {
                $subjectName = $otherReview->listing->name;
                $this->addComment("This review uses too many words and phrases that are similar to the review for $subjectName. You may want to use http://www.copyscape.com/compare.php to compare the texts.  [This is an automatically-generated response from our text analyzer script.]");
                $this->status = 'returnedReview';
                $this->save();
                logError("Review $this->id returned (similar $percent% to $otherReview->id).", [], 'alert');

                return;
            }
        }

        // Check for duplicate photos from the web

        foreach ($this->pics as $pic) {
            if (! $forceUpdateAll && $pic->imageSearchCheckDate) {
                continue;
            }
            $pic->updateImageSearchMatches(['thumbnail'], true)->save(); // the thumbnail size seems to work fine
        }

        // Check for similar text from other websites

        if ($forceUpdateAll || ! $this->plagiarismCheckDate) {
            $result = PlagiarismChecker::textCheck($this->review);

            if ($result == false) {
                logError("PlagiarismChecker returned an error for review $this->id.");
            } else {
                $this->plagiarismCheckDate = date('Y-m-d');
                $this->plagiarismPercent = $result['percentMatched'];
                $this->plagiarismInfo = $result['details'];
                $this->save();

                if ($this->plagiarismPercent >= 13) {
                    logError("Plagiarism score of $this->plagiarismPercent for review $this->id.", [], 'alert');
                }
            }
        }
    }

    public function getSummary($maxCharacters)
    {
        $text = ($this->editedReview != '' ? $this->editedReview : $this->review);
        $textLines = array_values(array_filter(array_map('trim', explode("\n", strip_tags($text)))));
        $paragraph = '';

        // Use the next line after Summary
        if (($position = array_search('Summary', $textLines)) !== false) {
            $paragraph = $textLines[$position + 1];
        } else {
            // No summary, just use the first non-title line
            $titles = array_keys(self::$templateFormat);
            foreach ($textLines as $line) {
                if (! in_array($line, $titles) && mb_strlen($line) > 25 && (strpos($line, '.') || strpos($line, '.'))) {
                    $paragraph = $line;

                    break;
                }
            }
        }

        $paragraph = trim(str_replace('!', '.', $paragraph));
        if ($paragraph == '') {
            return '';
        }
        $firstSentence = mb_strstr($paragraph, '.', true) . '.';
        $length = mb_strlen($firstSentence);
        // If too short, use more text from the paragraph...
        if ($length < $maxCharacters / 3 && $length < $maxCharacters) {
            $firstSentence = $paragraph;
        }

        return wholeWordTruncate($firstSentence, $maxCharacters);
    }

    public function resetExpirationDate()
    {
        $this->expirationDate = Carbon::now()->addDays(self::DAYS_TO_HOLD_FOR_REVIEW)->format('Y-m-d');

        return $this; // for chaining
    }

    public function pay()
    {
        if ($this->payStatus != '') {
            logError("Review $this->id can't be paid because its payStatus is '$this->payStatus'.");

            return false;
        }
        $this->payStatus = 'paid';
        $this->save();
        // We record the log event (which is how we know to pay the user).
        EventLog::log('staff', 'accepted', 'Review', $this->id, '', '', $this->reviewerID);
    }

    public function publish()
    {
        if (! $this->hostelID) {
            logError("Review $this->id has no hostelID.");

            return false;
        }
        if ($this->editedReview == '') {
            $this->editedReview = $this->review;
        }
        $this->reviewDate = date('Y-m-d'); // use the publish date as the review date

        $this->status = 'publishedReview';
        $this->save();
        $this->clearRelatedPageCaches();

        foreach ($this->pics as $pic) {
            if ($pic->status != 'new') {
                continue;
            }
            $pic->status = 'markedForEditing';
            $pic->save();
        }

        $listing = $this->listing;

        // If it's a positive we review, we tell the hostel owner
        if ($this->rating >= 4) {
            $listing->sendEmail(['subject' => langGet('Listing.emails.positiveReviewNotice.subject'),
                'bodyText' => langGet('Listing.emails.positiveReviewNotice.bodyText', ['listingURL' => $listing->getURL('publicSite', 'en', true)]), ]);
        }

        // Do an update listing to take the review rating and content into account.
        // (We do it right away so the URLs on the homepage of recent reviews will be
        // good content type URLs, and the review will be in the listing, etc.).
        $listing->listingMaintenance()->updateListing(true, false);
        PageCache::clearByTag('homepage'); // for the most recent reviews list on the homepage
    }

    public function publishAsARating()
    {
        if (! $this->hostelID) {
            logError("Review $this->id has no hostelID.");

            return false;
        }
        if ($this->editedReview == '') {
            $this->editedReview = $this->review;
        }

        $this->status = 'postAsRating';
        $this->save();
        $this->clearRelatedPageCaches();

        foreach ($this->pics as $pic) {
            if ($pic->status != 'new') {
                continue;
            }
            $pic->status = 'markedForEditing';
            $pic->save();
        }
    }

    public function addComment($comment, $asStaffOrUser = 'staff')
    {
        if ($comment == '') {
            throw new Exception('Empty comment.');
        }

        if ($asStaffOrUser == 'staff') { // If we're writing a comment, notify the user...
            // Assumes the 'source' is the user ID
            if (! $this->user) {
                logError('No user with id ' . $this->reviewerID . '.');

                return false;
            }
            Emailer::send($this->user, 'New Hostelz.com Staff Review Comment', 'generic-email', ['text' => "A Hostelz.com staff person has added a new comment to one of your reviews.\n\nTo view the comment, see your Reviews list.",
                // TODO: After the new site is live, add the link to their reviews page here.
            ], config('custom.adminEmail'));
            $this->newStaffComment = true;
        } elseif ($asStaffOrUser == 'user') {
            $this->newReviewerComment = true;
        } else {
            throw new Exception('Unknown asStaffOrUser value.');
        }

        $this->comments = $this->comments . '(' . date('M j, Y') . ') <b>' . ($asStaffOrUser == 'staff' ? 'Hostelz.com:' : 'User:') . '</b> ' . htmlspecialchars($comment) . "<br>\n";

        return true;
    }

    public function clearRelatedPageCaches(): void
    {
        if (! $this->listing) {
            return;
        }
        $this->listing->clearRelatedPageCaches();
    }

    public function ratingAsAPercent()
    {
        // (Note: This multiplier is higher than the one for Ratings because paid reviewers tend to be stingier with 5 star ratings.
        $LOG_MULTIPLIER = 1.5; // higher values weight it towards higher percents for more stars

        return round(100 * (log($this->rating * $LOG_MULTIPLIER) / log(5 * $LOG_MULTIPLIER)));
    }

    public function addPic($picFilePath, $caption = null)
    {
        $maxPicNum = -1;
        foreach ($this->pics as $pic) {
            if ($pic->picNum > $maxPicNum) {
                $maxPicNum = $pic->picNum;
            }
        }

        $result = Pic::makeFromFilePath($picFilePath, [
            'subjectType' => 'reviews', 'subjectID' => $this->id, 'type' => '', 'status' => 'new',
            'picNum' => $maxPicNum + 1, 'caption' => (string) $caption,
        ], [
            'originals' => ['storageType' => 'privateCloud'],
            // This thumbnail is temporary, just for displaying to the reviewer and staff.
            // We'll create a new thumbnail after the pic is edited.
            'thumbnail' => ['saveAsFormat' => 'jpg', 'outputQuality' => 80,
                'maxHeight' => 120, /* this is bigger than the final thumbnail height of the edited pics so it's easier to see */],

            // Previously we waited for picFix to save the big size, but now we're creating it when saving
            // so that it's available to display before they're edited.  See LIVE_PIC_STATUSES.)
            'big' => ['saveAsFormat' => 'jpg', 'outputQuality' => 82, 'maxWidth' => Listing::BIG_PIC_MAX_WIDTH, 'maxHeight' => Listing::BIG_PIC_MAX_HEIGHT,
                'watermarkImage' => public_path() . '/images/hostelz-watermark.png', 'watermarkHeight' => 32, 'watermarkOpacity' => 0.26,
                'minWidthToAddWatermark' => 300, 'minHeightToAddWatermark' => 300,
            ],
        ]);

        $this->load('pics'); // refresh the relationship values in case they were changed

        return $result;
    }

    public static function picFixPicOutputTypes()
    {
        return [
            'thumbnail' => ['saveAsFormat' => 'jpg', 'outputQuality' => 80, 'maxHeight' => self::THUMBNAIL_HEIGHT], // currently only used when old reviews are displayed as a Rating, and for "Recent Reviews" on the homepage
            'big' => ['saveAsFormat' => 'jpg', 'outputQuality' => 82, 'maxWidth' => Listing::BIG_PIC_MAX_WIDTH, 'maxHeight' => Listing::BIG_PIC_MAX_HEIGHT,
                'watermarkImage' => public_path() . '/images/hostelz-watermark.png', 'watermarkHeight' => 32, 'watermarkOpacity' => 0.26,
                'minWidthToAddWatermark' => 300, 'minHeightToAddWatermark' => 300,
            ],
        ];
    }

    /* Scopes */

    public function scopePublishedReviews($query)
    {
        return $query->where('status', 'publishedReview');
    }

    /* Relationships */

    public function listing()
    {
        return $this->belongsTo(\App\Models\Listing\Listing::class, 'hostelID');
    }

    public function pics()
    {
        return $this->hasMany(\App\Models\Pic::class, 'subjectID')->where('subjectType', 'reviews')->orderBy('picNum');
    }

    public function livePicsOfAnySize()
    {
        return $this->hasMany(\App\Models\Pic::class, 'subjectID')->where('subjectType', 'reviews')->whereIn('status', self::LIVE_PIC_STATUSES)->orderBy('picNum');
    }

    public function livePicsAtLeastMinimumSize()
    {
        return $this->hasMany(\App\Models\Pic::class, 'subjectID')->where('subjectType', 'reviews')->whereIn('status', self::LIVE_PIC_STATUSES)
            ->where('originalWidth', '>=', self::OLD_PIC_MIN_WIDTH)->where('originalHeight', '>=', self::OLD_PIC_MIN_HEIGHT)->orderBy('picNum');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'reviewerID');
    }
}
