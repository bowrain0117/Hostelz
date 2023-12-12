<?php

namespace App\Models;

use Carbon\Carbon;
use Exception;
use Lib\BaseModel;

class Rating extends BaseModel
{
    protected $table = 'comments';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $statusOptions = ['userDeleted', 'removed', 'suspicious', 'flagged', 'new', 'approved'];

    public static $ratingOptions = ['0', '1', '2', '3', '4', '5'];

    public const THUMBNAIL_HEIGHT = 80;

    public function save(array $options = []): void
    {
        $this->email = mb_strtolower($this->email);
        parent::save($options);
        $this->clearRelatedPageCaches();
    }

    public function delete(): void
    {
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
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'hostelID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('hostelID') == 'display')
                                && $model->listing ? $model->listing->fullDisplayName() : $model->hostelID;
                        }, ],
                    'rating' => ['type' => 'select', 'options' => self::$ratingOptions, 'optionsDisplay' => 'translate'],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'name' => ['maxLength' => 80, 'validation' => 'required'],
                    'homeCountry' => ['maxLength' => 80],
                    'age' => ['maxLength' => 3, 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'summary' => ['maxLength' => 70],
                    'comment' => ['type' => 'textarea', 'rows' => 15, 'listValueTruncate' => 70],
                    'originalComment' => ['type' => 'display'],
                    'ownerResponse' => ['type' => 'textarea', 'rows' => 3],
                    'notes' => ['type' => 'textarea', 'rows' => 2],
                    'ipAddress' => ['maxLength' => 50],
                    'sessionID' => [],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'email' => ['maxLength' => 255],
                    'emailVerified' => ['type' => 'select', 'options' => ['0', '1'], 'optionsDisplay' => 'translate'],
                    'ourBookingID' => ['maxLength' => 15],
                    'bookingID' => ['dataType' => 'Lib\dataTypes\NumericDataType'],
                    'commentDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType', 'maxLength' => 80, 'validation' => 'required'],
                    'bayesianBucket' => ['type' => 'display'],
                    'bayesianScore' => ['type' => 'display'],
                ];

                if ($purpose == 'staffEdit') {
                    $useFields = ['id', 'status', 'rating', 'language', 'name', 'homeCountry', 'age', 'summary', 'comment', 'notes'];
                    $displayOnly = ['hostelID', 'commentDate', 'emailVerified'];
                    $fieldInfos['id']['type'] = 'ignore';
                    $fieldInfos['rating']['validation'] = 'required|numeric|min:1';
                    array_walk($fieldInfos, function (&$fieldInfo, $fieldName, $displayOnly): void {
                        if (in_array($fieldName, $displayOnly)) {
                            $fieldInfo['editType'] = 'display';
                        }
                    }, $displayOnly);
                    $fieldInfos = array_intersect_key($fieldInfos, array_flip(array_merge($useFields, $displayOnly)));
                }

                break;

            case 'management':
                return [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'commentDate' => ['type' => 'display'],
                    'rating' => ['type' => 'display', 'options' => self::$ratingOptions, 'optionsDisplay' => 'translate'],
                    'name' => ['type' => 'display'],
                    'homeCountry' => ['type' => 'display'],
                    'age' => ['type' => 'display'],
                    'summary' => ['type' => 'display'],
                    'comment' => ['type' => 'display', 'listValueTruncate' => 50],
                    'ownerResponse' => ['type' => 'textarea', 'rows' => 3, 'listValueTruncate' => 50],
                ];

            case 'submitRating':
                $fieldInfos = [
                    'name' => ['maxLength' => 80, 'validation' => 'required', 'fieldLabelLangKey' => 'submitRating.name'],
                    'homeCountry' => ['maxLength' => 80, 'fieldLabelLangKey' => 'submitRating.homeCountry'],
                    'age' => ['maxLength' => 3, 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int', 'fieldLabelLangKey' => 'submitRating.age'],
                    'rating' => ['type' => 'select', 'options' => self::$ratingOptions, 'validation' => 'required', 'optionsDisplay' => 'translate', 'fieldLabelLangKey' => 'submitRating.rating'],
                    'summary' => ['maxLength' => 70, 'validation' => 'required|min:3|doesnt_contain_urls', 'fieldLabelLangKey' => 'submitRating.summary'],
                    'comment' => ['type' => 'textarea', 'rows' => 7, 'validation' => 'required|min:30|doesnt_contain_urls', 'fieldLabelLangKey' => 'submitRating.comment'],
                    'bookingID' => ['maxLength' => 30, 'fieldLabelLangKey' => 'submitRating.bookingID'],
                    'email' => ['maxLength' => 255, 'validation' => 'required|email', 'fieldLabelLangKey' => 'submitRating.email'],
                ];
                unset($fieldInfos['rating']['options']['0']); // remove the 'none' option

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    /* Accessors & Mutators */

    public function getRatingAttribute($rating)
    {
        if ($rating <= 5) {
            return $rating;
        }

        return $this->convertPercentToStarRating($rating);
    }

    /* Static */

    public static function getRatingsForListing($listing, $language = '', $onlyLive = true, $onlyIfLanguageMatches = false, $eagerLoadUsers = false, $addOldReviews = true)
    {
        $MAX_TO_CONSIDER = 500; // just to limit load on DB, but enough to consider lots to find our $language.
        $MIN_TO_SPECIFY_LANGUAGE = 1; // use only ratings of $language if there are at least this many of them.

        $query = self::where('hostelID', $listing->id)->orderBy('commentDate', 'desc')->limit($MAX_TO_CONSIDER);
        if ($eagerLoadUsers) {
            $query->with('user')->with('user.profilePhoto');
        }
        if ($onlyLive) {
            $query->areLive();
        }

        $ratings = null;
        // Show only $language ones if there are enough of them
        if ($language != '') {
            $languageSpecificRatings = with(clone $query)->where('language', $language)->get();
            if ($onlyIfLanguageMatches || $languageSpecificRatings->count() >= $MIN_TO_SPECIFY_LANGUAGE) {
                $ratings = $languageSpecificRatings;
            }
        }

        if (! $ratings) {
            $ratings = $query->get();
        }

        // Add old paid reviews as if they were ratings
        if ($addOldReviews) {
            $liveReview = $listing->getLiveReview($language);
            $reviewsQuery = Review::where('hostelID', $listing->id)->whereIn('status', ['publishedReview', 'postAsRating']);
            if ($language != '') {
                $reviewsQuery->where('language', $language);
            }
            if ($liveReview) {
                $reviewsQuery->where('id', '!=', $liveReview->id);
            }
            $reviews = $reviewsQuery->get();
            if (! $reviews->isEmpty()) {
                foreach ($reviews as $review) {
                    if (! $review->user) {
                        logNotice("no User for reviewID: {$review->id}");

                        continue;
                    }

                    $ratings->push(new self([
                        'name' => $review->user ? $review->user->getNicknameOrName('Anonymous') : 'Anonymous',
                        'commentDate' => $review->reviewDate != null ? $review->reviewDate : Carbon::createFromDate(2007, 5, 1)->format('Y-m-d') /* estimate for old reviews */,
                        'homeCountry' => $review->user ? $review->user->homeCountry : '',
                        'comment' => strip_tags($review->editedReview != '' ? $review->editedReview : $review->review),
                        'rating' => $review->rating,
                        'livePics' => $review->livePicsOfAnySize,
                        'ownerResponse' => $review->ownerResponse,
                        'user' => $review->user,
                    ]));
                }
                // Have to re-sort it
                $ratings = $ratings->sortByDesc('commentDate');
            }
        }

        return $ratings;
    }

    // based on the old website's getListingComments(). call this after getRatingsForListing().
    // TODO: When getting the ratings, should probably call ->load('user')->load('pics') (when they have pics) on the ratings.
    // $page - Currently only either 0 or 1.  0 is the listing, 1 is the "more comments" page.

    public static function spliceRatingsForPage($ratings, $page, &$hasMore = null)
    {
        // Average rating is 100 words in length. For SEO at least 300 words recommended, but more is better.
        $CONSIDERED_TO_BE_RECENT = Carbon::now()->subYear(2);
        $MIN_LISTING_PAGE_INCLUDING_OLD = 8; // min number to show on page 0, even if they're old ratings.
        $MAX_LISTING_PAGE_NEW_COMMENTS = 20; // max number to show on page 0 if there are a lot of recent ratings.
        $MAX_MORE_PAGE_COMMENTS = 30;

        $totalRatings = $ratings->count();

        // Count the "recent" ones
        $recentRatings = $ratings->filter(function ($rating) use ($CONSIDERED_TO_BE_RECENT) {
            return $rating->commentDate > $CONSIDERED_TO_BE_RECENT;
        });

        // Set $firstPageRatings count
        // Don't show old ones on listing page if there are enough new ones
        if ($recentRatings->count() >= $MIN_LISTING_PAGE_INCLUDING_OLD) {
            $firstPageRatings = min($recentRatings->count(), $MAX_LISTING_PAGE_NEW_COMMENTS);
        } else {
            $firstPageRatings = min($ratings->count(), $MIN_LISTING_PAGE_INCLUDING_OLD);
        }

        // Get ratings for this page
        $hasMore = false;
        if ($page == 0) {
            if ($ratings->count() > $firstPageRatings) {
                $ratings = $ratings->splice(0, $firstPageRatings);
                $hasMore = true;
            }
        } else {
            $ratings = $ratings->splice($firstPageRatings, $MAX_MORE_PAGE_COMMENTS);
        }

        // echo "(total ratings:".$ratings->count().", recentRatings:".$recentRatings->count().", firstPageRatings:$firstPageRatings)";

        return $ratings;
    }

    /* Misc */

    /* (not yet used, but should be ready to use when we let people upload rating photos (haven't yet created the pics folders)
    public function addPic($picFilePath, $caption = null)
    {
        $maxPicNum = -1;
        foreach ($this->pics as $pic) if ($pic->picNum > $maxPicNum) $maxPicNum = $pic->picNum;

        $result = Pic::makeFromFilePath($picFilePath, [
            'subjectType' => 'ratings', 'subjectID' => $this->id, 'type' => '', 'status' => 'ok',
            'picNum' => $maxPicNum + 1, 'caption' => (string) $caption
        ], [
            'originals' => [ ],
            // (rather than saving these other sizes here, we should use our picFix editor and script to edit them and then save them -- see how review pics are done)
            // 'thumbnail' => [ 'saveAsFormat' => 'jpg', 'outputQuality' => 80, 'maxHeight' => self::THUMBNAIL_HEIGHT ]
            // 'big' => [ 'saveAsFormat' => 'jpg', 'outputQuality' => 80, 'maxWidth' => Listing::BIG_PIC_MAX_WIDTH, 'maxHeight' => Listing::BIG_PIC_MAX_HEIGHT, 'skipIfUnmodified' => true ],
        ]);

        $this->load('pics'); // refresh the relationship values in case they were changed
        return $result;
    }
    */

    public function emailVerificationCode()
    {
        return crc32signed("d*7alja $this->id a8ajqo*");
    }

    public function clearRelatedPageCaches(): void
    {
        if (! $this->listing) {
            return;
        }
        $this->listing->clearRelatedPageCaches();
    }

    public function asAPercent()
    {
        $LOG_MULTIPLIER = 1.0; // higher values weight it towards higher percents for more stars

        return round(100 * (log($this->rating * $LOG_MULTIPLIER) / log(5 * $LOG_MULTIPLIER)));
    }

    public static function convertPercentToStarRating($percent)
    {
        $LOG_MULTIPLIER = 1.0; // higher values weight it towards higher percents for more stars

        return (int) round(exp(($percent / 100) * log(5 * $LOG_MULTIPLIER))); // This is the inverse of asAPercent()
    }

    // Note: This doesn't save the model.

    public function automaticallyAssignToUserWithMatchingEmail(): void
    {
        if ($this->userID) {
            return;
        } // if the user was logged in when they submitted the rating, the userID is already set
        $matchingUser = User::where('username', $this->email)->first();
        if ($matchingUser) {
            $this->userID = $matchingUser->id;
        }
    }

    public function awardPoints(): void
    {
        if (! $this->userID) {
            return;
        }
        User::findOrFail($this->userID)->awardPoints('listingRating');
    }

    /* Accessors & Mutators */

    public function getAgeAttribute($value)
    {
        return $value == 0 ? '' : $value; // so 0 age values show up as ''
    }

    /* Scopes */

    public function scopeAreLive($query)
    {
        return $query->where('status', 'approved');
    }

    /* Relationships */

    public function listing()
    {
        return $this->belongsTo(Listing\Listing::class, 'hostelID');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'userID');
    }

    public function booking()
    {
        return $this->belongsTo(\App\Models\Booking::class, 'bookingID');
    }

    /* (Pics aren't really used yet, but are displayed when a Review is converted to a Rating.) */
    public function pics()
    {
        return $this->hasMany(\App\Models\Pic::class, 'subjectID')->where('subjectType', 'Rating')->orderBy('picNum');
    }

    public function livePics()
    {
        return $this->hasMany(\App\Models\Pic::class, 'subjectID')->where('subjectType', 'Rating')->where('status', 'ok')->orderBy('picNum');
    }
}
