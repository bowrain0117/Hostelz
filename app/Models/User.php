<?php

namespace App\Models;

use App\Helpers\EventLog;
use App\Models\Listing\Listing;
use App\Services\Payments;
use App\Utils\FieldInfo;
use Exception;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Lib\BaseModel;
use Lib\Emailer;
use Lib\PageCache;

class User extends BaseModel implements AuthenticatableContract
{
    use HasFactory;

    protected $table = 'users';

    public static $staticTable = 'users'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'lastPaid' => 'datetime',
        //        'birthDate' => 'date:Y-m-d',
    ];

    public static $statusOptions = ['ok', 'disabled'];

    public static $genderOptions = [
        0 => 'not selected',
        1 => 'male',
        2 => 'female',
        3 => 'other',
    ];

    public static $accessOptions = ['admin', 'staff', 'developer', 'staffCityInfo', 'staffEditHostels', 'staffPicEdit',
        'staffEditComments', 'staffEditCityComments', 'staffEditReviews', 'staffEmail', 'staffBookings', 'staffEditUsers', 'staffTranslation',
        'staffMarketing', 'staffMarketingLevel2', 'staffEditAttached', 'reviewer', 'placeDescriptionWriter', 'staffWriter', 'affiliate',
        'affiliateWhiteLabel', 'affiliateXML', /* these two only used to mark users who have expressed interest */];

    public static $payAmountTypes = ['affiliatePercent', 'articleWriting', 'adCreate',
        'cityComment', 'commentEdit', 'reviewEdit', 'placeDescriptionApprove', 'translation', 'hostel', 'cityInfo', 'email',
        'hostelEmail', 'merge', 'nonduplicate', 'picEdit', 'linkUpdate', 'socialMsg', 'socialMsgOther', ];

    public static $apiAccessOptions = ['cityData', 'listingData', 'listingDescs'];

    // (When adding a new points action, have to also add it to the recalculatePoints() method.)
    public static $pointsPerAction = ['bookingCommissionDollar' => 8, 'listingRating' => 50, 'cityComment' => 40, 'profilePhoto' => 20];

    public static $ADMIN_USER_ID = 1;

    public const DEFAULT_AFFILIATE_PERCENT = 45;

    public const PASSWORD_VALIDATION = 'required|min:7';
    /* const EMAIL_VALIDATION = 'required|email|unique:users,email,[THIS_ID]|not_all_uppercase'; */

    // Profile photo
    public const PROFILE_PHOTO_WIDTH = 100;

    public const PROFILE_PHOTO_HEIGHT = 100;

    private static $loggedUser;

    public function save(array $options = []): void
    {
        $this->username = mb_strtolower($this->username);
        $this->paymentEmail = mb_strtolower($this->paymentEmail);

        parent::save($options);

        $this->clearRelatedPageCaches();
    }

    public function delete(): void
    {
        if ($this->profilePhoto) {
            $this->profilePhoto->delete();
        }
        Booking::where('userID', $this->id)->update(['userID' => 0]);
        parent::delete();
        $this->clearRelatedPageCaches();
    }

    /* Static Methods */

    protected static function staticDataTypes()
    {
        static $dataTypes = [];

        if (! $dataTypes) {
            $dataTypes = [
                'access' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'access', 'allPossibleValues' => self::$accessOptions]),
                'alsoGetLocalEmailFor' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'alsoGetLocalEmailFor']),
                'mgmtListings' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'mgmtListings']),
                'countries' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'countries']),
                'apiAccess' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'apiAccess', 'allPossibleValues' => self::$apiAccessOptions]),
                'invalidEmails' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'invalidEmails']),
                'languages' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'languages']),
                'dreamDestinations' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'dreamDestinations']),
                'favoriteHostels' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'favoriteHostels']),
            ];
        }

        return $dataTypes;
    }

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'comparisonType' => 'equals', 'editType' => 'display' /* [ 'dataSearchType' => 'userID',] */],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'username' => ['maxLength' => 120, 'validation' => 'required' /* [ 'dataSearchType' => 'email',] */], /* TODO: Rename this to email. */
                    'passwordHash' => ['type' => 'display', 'maxLength' => 70],
                    'access' => ['dataTypeObject' => self::staticDataTypes()['access'], 'type' => 'checkboxes', 'options' => self::$accessOptions,
                        'optionsDisplay' => 'translate', 'maxLength' => 250, ],
                    'name' => ['maxLength' => 150 /* [ 'dataSearchType' => 'name',] */],
                    'nickname' => [
                        'validation' => ['required'],
                        //  validation for the user who is being edited
                        'validationSometimes' => [Rule::unique('users')->ignore(auth()->user()->id), function ($input) {
                            $exists = self::where('nickname', $input->nickname)->where('id', '!=', $input->id)->exists();

                            return $exists;
                        }],
                        'maxLength' => 30,
                    ],
                    'slug' => ['type' => 'display'],
                    'birthDate' => ['type' => 'datePicker', 'validation' => 'date_format:Y-m-d|before:today', 'dataType' => 'Lib\dataTypes\DateDataType', 'maxLength' => 10],
                    'isPublic' => ['type' => 'radio', 'searchType' => 'checkboxes', 'options' => ['0', '1'], 'defaultValue' => '1', 'validation' => 'boolean', 'optionsDisplay' => 'translate'],
                    'gender' => ['type' => 'select', 'options' => array_keys(self::$genderOptions), 'validation' => 'numeric', 'optionsDisplay' => 'translate'],
                    'languages' => ['dataTypeObject' => self::staticDataTypes()['languages'], 'editType' => 'multi', 'type' => 'select', 'options' => Languages::allLiveSiteCodesKeyedByName()],
                    'facebook' => ['maxLength' => 150, 'validation' => 'url'],
                    'instagram' => ['maxLength' => 150, 'validation' => 'url'],
                    'tiktok' => ['maxLength' => 150, 'validation' => 'url'],
                    'bio' => ['type' => 'textarea'],
                    'localEmailAddress' => ['maxLength' => 120, 'validation' => 'email|required_with:alsoGetLocalEmailFor'],
                    'alsoGetLocalEmailFor' => ['dataTypeObject' => self::staticDataTypes()['alsoGetLocalEmailFor'], 'editType' => 'multi', 'validation' => 'emailList'],
                    'paymentEmail' => ['maxLength' => 120], // or can be "none"
                    'payAmounts' => ['editType' => 'multi', 'keys' => self::$payAmountTypes],
                    'countries' => ['dataTypeObject' => self::staticDataTypes()['countries'], 'editType' => 'multi', 'maxLength' => 250],
                    'lastPaid' => ['searchType' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateTimeDataType', 'dataAccessMethod' => 'dataType'],
                    'homeCountry' => ['maxLength' => 80],
                    'dateAdded' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType', 'maxLength' => 10],
                    'formData' => ['editType' => 'multi', 'keys' => ''],
                    'sessionID' => [/* [ 'dataSearchType' => 'sessionID',] */],
                    'points' => ['searchType' => 'minMax', 'dataType' => 'Lib\dataTypes\NumericDataType', 'maxLength' => 10, 'sanitize' => 'int'],
                    'affiliateURLs' => ['editType' => 'multi', 'validation' => 'urlList'],
                    'mgmtListings' => ['dataTypeObject' => self::staticDataTypes()['mgmtListings'], 'searchType' => 'text', 'editType' => 'multi', 'maxLength' => 1000],
                    'invalidEmails' => ['dataTypeObject' => self::staticDataTypes()['invalidEmails'], 'editType' => 'multi', 'maxLength' => 255],
                    'apiAccess' => ['dataTypeObject' => self::staticDataTypes()['apiAccess'], 'type' => 'checkboxes',
                        'options' => self::$apiAccessOptions, 'optionsDisplay' => 'translate', 'maxLength' => 250, ],
                    'apiKey' => ['maxLength' => 16],
                    'data' => ['type' => 'textarea'],
                ];

                if ($purpose === 'staffEdit') {
                    $staffEditable = ['username', 'name', 'nickname', 'homeCountry', 'affiliateURLs', 'mgmtListings'];
                    $staffIgnore = ['passwordHash', 'access', 'localEmailAddress', 'alsoGetLocalEmailFor', 'paymentEmail', 'payAmounts', 'lastPaid', 'sessionID',
                        'apiAccess', 'apiKey', 'data', ];
                    FieldInfo::fieldInfoType($fieldInfos, $staffEditable, $staffIgnore);
                }

                return $fieldInfos;

            case 'userSettings':
                return [
                    'isPublic' => ['type' => 'radio', 'searchType' => 'checkboxes', 'options' => ['0', '1'], 'defaultValue' => '1', 'validation' => 'boolean', 'optionsDisplay' => 'translate'],
                    'nickname' => ['validation' => ['required', Rule::unique('users')->ignore(auth()->user()->id)], 'maxLength' => 30],
                    'name' => ['maxLength' => 150],
                    'homeCountry' => ['validation' => '', 'maxLength' => 80],
                    'bio' => ['type' => 'textarea', 'validation' => '', 'fieldLabelText' => langGet('User.forms.fieldLabel.bioSettings')],
                    'birthDate' => ['type' => 'datePicker', 'validation' => 'date_format:Y-m-d|before:today', 'dataType' => 'Lib\dataTypes\DateDataType', 'defaultDate' => null, 'maxLength' => 10],
                    'gender' => ['type' => 'select', 'options' => array_keys(self::$genderOptions), 'validation' => 'numeric', 'optionsDisplay' => 'translate', 'showBlankOption' => false],
                    'languages' => ['dataTypeObject' => self::staticDataTypes()['languages'], 'editType' => 'multi', 'type' => 'select', 'options' => Languages::allLiveSiteCodesKeyedByName()/*'validation' => [Rule::in(Languages::allLiveSiteCodesKeyedByName())]*/],
                    'facebook' => ['maxLength' => 150, 'validation' => ['nullable', 'regex:/^(https?:\/\/)?(www\.)?facebook.com\/[a-zA-Z0-9(\.\?)?]/i']],
                    'instagram' => ['maxLength' => 150, 'validation' => ['nullable', 'regex:/^(https?:\/\/)?(www\.)?instagram.com\/[a-zA-Z0-9(\.\?)?]/i']],
                    'tiktok' => ['maxLength' => 150, 'validation' => ['nullable', 'regex:/^(https?:\/\/)?(www\.)?www.tiktok.com\/@[a-zA-Z0-9(\.\?)?]/i']],
                    'dreamDestinations' => [
                        'dataTypeObject' => self::staticDataTypes()['dreamDestinations'],
                        'getValue' => function ($formHandler, $model) {
                            return $model->dreamDestinationsList()->select('city_id')->get()->pluck('city_id')->toArray();
                        },
                        'setValue' => function ($formHandler, $model, $value) {
                            if (empty($value)) {
                                $model->dreamDestinationsList()->detach();

                                return true;
                            }

                            $listings = CityInfo::select('id')->whereIn('id', $value)->get();
                            if (! $listings->isEmpty()) {
                                $model->dreamDestinationsList()->sync($listings);
                            }
                        },
                        'itemClass' => 'dreamDestinations',
                        'searchType' => 'text', 'editType' => 'multi', 'maxLength' => 1000,
                    ],
                    'favoriteHostels' => [
                        'dataTypeObject' => self::staticDataTypes()['favoriteHostels'],
                        'getValue' => function ($formHandler, $model) {
                            return $model->favoriteHostelsList()->select('listings.id')->get()->pluck('id')->toArray();
                        },
                        'setValue' => function ($formHandler, $model, $value) {
                            if (empty($value)) {
                                $model->favoriteHostelsList()->detach();

                                return true;
                            }

                            $listings = Listing::select('listings.id')->whereIn('id', $value)->get();
                            if (! $listings->isEmpty()) {
                                $model->favoriteHostelsList()->sync($listings);
                            }
                        },
                        'itemClass' => 'favoriteHostels',
                        'searchType' => 'text', 'editType' => 'multi', 'maxLength' => 1000,
                    ],
                    'searchHistory' => [
                        'showAddButton' => false,
                        'showRemoveButton' => false,
                        'disabled' => true,
                        'getValue' => function ($formHandler, $model) {
                            return $model->searchHistory()->latest()->get()->pluck('query')->toArray();
                        },
                        'setValue' => function ($formHandler, $model, $value): void {
                        },
                        'searchType' => 'text', 'editType' => 'multi', 'maxLength' => 1000,
                    ],
                ];

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'daily':
                set_time_limit(1 * 60 * 60); // Note: This also resets the timeout timer.

                $output .= 'Email new affiliates: ';

                $sendingUser = self::find(config('custom.adminEmailUserID'));

                $events = EventLog::where('subjectString', 'becomeAffiliate')
                    ->where('eventTime', 'like', Carbon::now()->subDays(2)->format('Y-m-d') . '%') // signed up a couple days ago
                    ->get();

                foreach ($events as $event) {
                    $user = self::where('id', $event->subjectID)->areAllowedToLogin()->havePermission('affiliate')->doesntHavePermission('staff')->first();
                    if (! $user) {
                        continue;
                    }

                    $pastEmails = MailMessage::forRecipientOrBySenderEmail($user->username)->where('userID', $sendingUser->id)->count();
                    if ($pastEmails) {
                        continue;
                    } // don't bother anyone that the same admin user already had direct correspondence with

                    if ($user->affiliateURLs) {
                        $messageText = 'Hi.  I noticed that you signed up for our affiliate program a couple days ago.  Let me know if you have any questions to need any assistance with setting up your links.';
                    } else {
                        $messageText = "Hi.  I noticed that you signed up for our affiliate program, but haven't yet set your website URLs so you can get paid for bookings.\n\nThe first step is to add a link to any Hostelz.com page from your website. Once you've done that, all you have to do is enter the URLs of those pages into our affiliate system so that we know to give you a commission for any bookings that come from people who used the link from your website.  You can do that from the \"Affiliate Program\" link on your Hostelz.com user menu, or by going directly to this page: \n\n " . routeURL('affiliate:menu', [], 'publicSite') .
                            "\n\nLet me know if you have any questions.";
                    }

                    $mail = MailMessage::createOutgoing([
                        'recipient' => $user->getEmailAddress(),
                        'subject' => 'affiliate program',
                        'bodyText' => $messageText . "\n" . $sendingUser->getEmailSignature(),
                    ], $sendingUser, 20, true);

                    $output .= "[$user->username] ";
                }

                $output .= "\n";

                break;

            case 'monthly':
                $output .= "Optimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function createNewFromSignup($attributes, $password, $andSave = true)
    {
        $user = new self(array_merge([
            'status' => 'ok',
            'dateAdded' => date('Y-m-d'),
        ], $attributes));

        $user->setPassword($password);
        if ($andSave) {
            $user->save();
        }

        return $user;
    }

    public static function getAllAffiliateURLs()
    {
        static $cachedResult = null;

        if ($cachedResult) {
            return $cachedResult;
        }

        $users = self::areAllowedToLogin()->where('affiliateURLs', '!=', '')->get();
        $urlToUserMap = [];

        foreach ($users as $user) {
            foreach ($user->affiliateURLs as $url) {
                $urlToUserMap[$url] = $user;
            }
        }

        $cachedResult = $urlToUserMap;

        return $urlToUserMap;
    }

    public static function mgmtSignupURL($listingID, $language = null)
    {
        return routeURL('listingMgmtSignup', [], 'publicSite', $language) . '?' .
            http_build_query(['l' => $listingID, 'm' => self::mgmtSignupVerificationToken($listingID)]);
    }

    public static function mgmtSignupVerificationToken($listingID)
    {
        return crc32signed($listingID . config('custom.mgmtSignupSalt'));
    }

    public static function emailVerificationToken($email, $specifics)
    {
        return substr(md5("$specifics $email " . config('custom.emailVerificationSalt')), 0, 10); // shortened to keep the URL short
    }

    public static function sendUserSignupVerificationEmail(
        $verificationType,
        $routeName,
        $email,
        $secondWaitSinceLast = null,
        $emailVerificationTokenSpecifics = null,
        $extraQueryVariables = []
    ) {
        $cacheKey = "sentUserVerificationEmail:$verificationType:" . md5($email);
        if (Cache::has($cacheKey)) {
            return 'tooSoon';
        }

        $verificationURL = routeURL($routeName, self::emailVerificationToken($email, $emailVerificationTokenSpecifics), 'publicSite') .
            '?' . http_build_query(array_merge(['e' => $email], $extraQueryVariables));
        $emailText = langGet("loginAndSignup.$verificationType.verifyEmailText", ['url' => "<a href=\"$verificationURL\">$verificationURL</a>"]);
        Emailer::send($email, langGet("loginAndSignup.$verificationType.verifyEmailSubject"), 'generic-email', ['text' => $emailText]);

        Cache::put($cacheKey, true, $secondWaitSinceLast);

        return true;
    }

    public static function arrayMapOfIncomingEmailAddressesToUserIDs()
    {
        static $cachedResult = null;

        if ($cachedResult) {
            return $cachedResult;
        }

        $return = [];

        $users = self::areAllowedToLogin()->havePermission('staffEmail')->where('localEmailAddress', '!=', '')
            ->select('id', 'localEmailAddress', 'alsoGetLocalEmailFor')->get();

        foreach ($users as $user) {
            $addresses = [$user->localEmailAddress];
            if ($user->alsoGetLocalEmailFor) {
                $addresses = array_merge($addresses, $user->alsoGetLocalEmailFor);
            }

            foreach ($addresses as $address) {
                if (array_key_exists($address, $return)) {
                    throw new \RuntimeException("'$address' is an incoming email address for multiple users.");
                }
                $return[$address] = $user->id;
            }
        }

        $return['default'] = self::$ADMIN_USER_ID; // special default if no other match.

        $cachedResult = $return;

        return $return;
    }

    public static function getTheListingSupportUser()
    {
        static $cachedResult = null;

        if ($cachedResult) {
            return $cachedResult;
        }

        $userID = self::arrayMapOfIncomingEmailAddressesToUserIDs()[config('custom.listingSupportEmail')] ?? null;
        if (! $userID) {
            logWarning('Listing support email not found, using user 1.');
            $userID = 1;
        }

        return self::findOrFail($userID);
    }

    /* Accessors & Mutators */

    public function getPayAmountsAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setPayAmountsAttribute($value): void
    {
        $this->attributes['payAmounts'] = ($value ? json_encode($value) : '');
    }

    public function getFormDataAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setFormDataAttribute($value): void
    {
        $this->attributes['formData'] = ($value ? json_encode($value) : '');
    }

    public function getAffiliateURLsAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setAffiliateURLsAttribute($value): void
    {
        $this->attributes['affiliateURLs'] = ($value ? json_encode($value) : '');
    }

    public function getShowGenderAttribute()
    {
        if ($this->gender === '0' || ! isset(self::$genderOptions[$this->gender])) {
            return null;
        }

        return self::$genderOptions[$this->gender];
    }

    protected function birthDate(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => blank($value) ? null : Carbon::parse($value)->format('Y-m-d'),
            set: fn (string $value) => blank($value) ? null : Carbon::parse($value)->format('Y-m-d'),
        );
    }

    public function getShowBirthDateAttribute()
    {
        return date('j M, Y', strtotime($this->birthDate));
    }

    public function getPublishedReviewsAttribute()
    {
        return $this->reviews()->publishedReviews()->count() ?? 0;
    }

    public function writtenArticles(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->articles()->count() ?? 0,
        );
    }

    public function getApprovedRatingsAttribute()
    {
        return $this->ratings()->areLive()->count() ?? 0;
    }

    public function getUserAge()
    {
        return Carbon::parse($this->birthDate)->age;
    }

    /* Events */

    public function loginEvent($remember): void
    {
        $this->updateLoginInfoCookie($remember);
    }

    public function logoutEvent(): void
    {
        self::unsetOurLoginCookies();
    }

    public static function unsetOurLoginCookies(): void
    {
        // Remove our special login cookie
        unsetMultiCookie('loginInfo');
    }

    /* Scopes */

    public function scopeAreMgmtForListing($query, $listingID)
    {
        return self::staticDataTypes()['mgmtListings']->searchQuery($query, $listingID, 'matchAny');
    }

    public function scopeAreAllowedToLogin($query)
    {
        return $query->where('status', '!=', 'disabled');
    }

    // $matchType - 'matchAll', 'matchAny', 'matchNone', etc.
    public function scopeHavePermission($query, $permissions, $matchType = 'matchAny')
    {
        return self::staticDataTypes()['access']->searchQuery($query, $permissions, $matchType);
    }

    public function scopeDoesntHavePermission($query, $permissions)
    {
        return $this->scopeHavePermission($query, $permissions, 'matchNone');
    }

    public function scopeByLocalEmails($query, $emails)
    {
        return $query->where(function ($query) use ($emails) {
            return self::staticDataTypes()['alsoGetLocalEmailFor']->searchQuery($query, $emails, 'matchAny')
                ->orWhereIn('localEmailAddress', (array) $emails);
        });
    }

    /* Misc */

    public function sendPayment($amount, $paymentIdPrefix, $description, $logData, $eventCategory, $paymentSystemPassword, $andSave = true)
    {
        $result = Payments::pay(
            $this->getEmailAddress('payment'),
            $amount,
            $description,
            $paymentIdPrefix . '-' . $this->id,
            $paymentSystemPassword
        );

        if ($result) {
            EventLog::log($eventCategory, 'payment', '', 0, $amount, $logData, $this->id);
            $this->lastPaid = Carbon::now();
            if ($andSave) {
                $this->save();
            }
        }

        return $result;
    }

    public function createPasswordChangeToken()
    {
        if ($existing = Cache::get('userPasswordChangeToken:' . $this->id)) {
            return $existing;
        }

        // Create a random token
        $value = str_shuffle(sha1(spl_object_hash($this) . microtime(true)));
        $token = (string) hash_hmac('sha1', $value, config('app.key')); // (the key doesn't really need to be our app key, but it's a useful random string to use)

        Cache::put('userPasswordChangeToken:' . $this->id, $token, 3 * 24 * 60 * 60);

        return $token;
    }

    public function isPasswordChangeTokenValid($token)
    {
        return Cache::get('userPasswordChangeToken:' . $this->id) === (string) $token;
    }

    public function deletePasswordChangeToken(): void
    {
        Cache::forget('userPasswordChangeToken:' . $this->id);
    }

    public function updateLoginInfoCookie($remember = null): void
    {
        if ($remember === null) {
            $existingCookie = getMultiCookie('loginInfo');
            $remember = ($existingCookie && $existingCookie['remember']);
        }

        // Set our own login cookie (read by Javascript to set the header username so that we can have cached webpages but still show the user's name)
        $loginCookieValues = ['username' => $this->username, 'id' => $this->id, 'permissions' => $this->access,
            'points' => $this->points, 'remember' => $remember, /* just so we know what expiraction to use when updating the cookie */];

        // Note: The 5*365*24*60 seems to match the expiration date of Laravel's remember cookie, so that's why we use that expiration.
        setMultiCookie('loginInfo', $loginCookieValues, false, $remember ? 5 * 365 * 24 * 60 : 0);
    }

    public function clearRelatedPageCaches(): void
    {
        if (! $this->id) {
            return;
        }
        PageCache::clearByTag('user:' . $this->id); // clear cached pages related to this user.
        /* PageCache::clearByTag('user:aggregation'); // clear cached pages related to all users. (none yet) */
    }

    public function setProfilePhoto($picFilePath)
    {
        $result = Pic::makeFromFilePath($picFilePath, [
            'subjectType' => 'users', 'subjectID' => $this->id, 'type' => 'profile', 'status' => 'new',
        ], [
            'originals' => [],
            'thumbnails' => [
                'saveAsFormat' => 'jpg',
                'outputQuality' => 75,
                'absoluteWidth' => self::PROFILE_PHOTO_WIDTH,
                'absoluteHeight' => self::PROFILE_PHOTO_WIDTH,
                'cropVerticalPositionRatio' => 0.2, /* If crop needed, crop closer to the top to avoid cutting off heads */
            ],
        ]);

        if ($result) {
            $this->clearRelatedPageCaches();
            EventLog::log(auth()->id() == $this->id ? 'user' : 'staff', 'update', 'User', $this->id, 'setProfilePhoto');
        }

        return $result;
    }

    public function getMgmtListings()
    {
        if (! $this->mgmtListings) {
            return false;
        }

        return Listing::whereIn('id', $this->mgmtListings)->get();
    }

    public function addMgmtListingIDs($listingIDs, $andSave = true, $andLog = false): void
    {
        $listingIDs = (array) $listingIDs;
        $this->mgmtListings = array_unique(array_merge($this->mgmtListings, $listingIDs));
        if ($andSave) {
            $this->save();
        }
        if ($andLog) {
            EventLog::log('user', 'update', 'User', $this->id, 'mgmtListings', 'mgmtListings added listings: ' . implode(', ', $listingIDs));
        }
    }

    public function removeMgmtListingIDs($listingIDs, $andSave = true): void
    {
        $listingIDs = (array) $listingIDs;
        $this->mgmtListings = array_diff($this->mgmtListings, $listingIDs);
        if ($andSave) {
            $this->save();
        }
    }

    public function userCanEditListing($listingID)
    {
        if ($this->hasPermission('staffEditHostels')) {
            return true;
        }
        if (! in_array($listingID, $this->mgmtListings)) {
            return false;
        }
        $listing = Listing::find($listingID);
        if (! $listing) {
            throw new Exception("Unknown listing $listingID.");
        }
        if (! $listing->isEditableByMgmt()) {
            return false;
        }

        return true;
    }

    public function isAllowedToLogin()
    {
        return $this->status !== 'disabled';
    }

    public function isAdmin(): bool
    {
        return $this->hasPermission('admin');
    }

    public function hasPermission($permission)
    {
        return in_array($permission, $this->access);
    }

    public function hasAnyPermissionOf($permissions)
    {
        return array_intersect($this->access, $permissions) != [];
    }

    public function hasAllPermissions($permissions)
    {
        return ! array_diff($permissions, $this->access);
    }

    public function grantPermissions($permissions, $andSave = true): void
    {
        $permissions = (array) $permissions;
        $this->access = array_unique(array_merge($this->access, $permissions));
        if ($andSave) {
            $this->save();
        }
    }

    public function revokePermissions($permissions): void
    {
        $permissions = (array) $permissions;
        $this->access = array_diff($this->access, $permissions);
    }

    public function allLocalEmailAddresses()
    {
        if ($this->localEmailAddress == '') {
            return [];
        }
        $result = [$this->localEmailAddress];
        if ($this->alsoGetLocalEmailFor) {
            $result = array_merge($result, $this->alsoGetLocalEmailFor);
        }

        return $result;
    }

    public function getEmailAddress($purpose = 'contact')
    {
        switch ($purpose) {
            case 'contact':
                return $this->localEmailAddress != '' && $this->isAllowedToLogin() && $this->hasPermission('staffEmail') ?
                    $this->localEmailAddress : $this->username;

            case 'payment':
                return $this->paymentEmail != '' ? $this->paymentEmail : $this->username;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }
    }

    // Returns [ 'name', 'email' ], used for sending outgoing emails to the user, or as the "from" info for users sent *as* the user.
    // (Used by the Lib Emailer class.)

    public function getOutgoingEmailInfo()
    {
        return ['name' => $this->getNicknameOrName(), 'email' => $this->getEmailAddress()];
    }

    public function getNicknameOrName($default = '')
    {
        if ($this->nickname != '') {
            return $this->nickname;
        }
        if ($this->name != '') {
            return $this->name;
        }

        return $default;
    }

    public function getEmailSignature()
    {
        return "\nBest regards,\n" . ($this->getNicknameOrName()) . "\n" . config('custom.websiteDisplayName') . "\n";
    }

    public function setPassword($password): void
    {
        $this->passwordHash = Hash::make($password);
    }

    public function becomeAffiliate($andSave = true): void
    {
        EventLog::log('user', 'update', 'User', $this->id, 'becomeAffiliate');
        $this->grantPermissions('affiliate', false);
        $this->setPayAmount('affiliatePercent', self::DEFAULT_AFFILIATE_PERCENT, $andSave); // note: $andSave makes it save it here
    }

    public function becomePaidReviewer($andSave = true): void
    {
        EventLog::log('user', 'update', 'User', $this->id, 'becomePaidReviewer');
        $this->grantPermissions(['reviewer', 'placeDescriptionWriter'], $andSave);
    }

    public function setPayAmount($payType, $payAmount, $andSave = true): void
    {
        $payAmounts = $this->payAmounts;
        $payAmounts[$payType] = $payAmount;
        $this->payAmounts = $payAmounts;
        EventLog::log('user', 'update', 'User', $this->id, '', "payAmount: '$payType' -> '$payAmount'");
        if ($andSave) {
            $this->save();
        }
    }

    public function makeBalanceAdjustment($balanceAdjustAmount, $reason): void
    {
        EventLog::log('staff', 'balance adjustment', '', 0, $balanceAdjustAmount, $reason, $this->id);
    }

    // Associates the email address with this userID in other database tables

    public function associateEmailAddressWithUser($email = null, $andRecalculatePoints = true): void
    {
        if ($email === null) {
            $email = $this->username;
        }

        // Bookings
        Booking::where('email', $email)->where('userID', 0)->update(['userID' => $this->id]);
        Booking::where('invalidEmails', $email)->where('userID', 0)->update(['userID' => $this->id]);

        // Ratings
        Rating::where('email', $email)->where('userID', 0)->update(['userID' => $this->id]);

        // MgmtListings
        $listingIDs = Listing::areLiveOrNew()->anyMatchingEmail($email)->pluck('id')->all();
        if ($listingIDs) {
            $this->addMgmtListingIDs($listingIDs, false, true);
        }

        if ($andRecalculatePoints) {
            $this->recalculatePoints(false);
        }

        $this->save();
    }

    public function recalculatePoints($andSave = true): void
    {
        $originally = $this->points;
        $this->points = 0;

        // Bookings
        $commission = $this->bookings()->sum('commission');
        $this->awardPoints('bookingCommissionDollar', $commission, false);

        // Ratings
        $this->awardPoints('listingRating', $this->ratings()->areLive()->count(), false);

        // City Comments
        $this->awardPoints('cityComment', $this->cityComments()->areLive()->count(), false);

        // User Profile Photo
        if ($this->profilePhoto) {
            $this->awardPoints('profilePhoto', 1, false);
        }

        if ($andSave && $this->points != $originally) {
            $this->save();
        }
        if (auth()->id() == $this->id) {
            $this->updateLoginInfoCookie();
        }
    }

    public function awardPoints($actionType, $howMany = 1, $andSave = true): void
    {
        if (! $howMany) {
            return;
        }
        $this->points += round(self::$pointsPerAction[$actionType] * $howMany);
        if ($andSave) {
            $this->save();
        }
        if (auth()->id() == $this->id) {
            $this->updateLoginInfoCookie();
        }
    }

    public function hasCompletedProfile()
    {
        if (! $this->profilePhoto) {
            return false;
        }
        if ($this->name == '' && $this->nickname == '') {
            return false;
        }

        return true;
    }

    public function activePaymentMethods()
    {
        return PaymentMethod::sortPaymentMethods($this->paymentMethods->where('status', '=', 'active'));
    }

    public function activeAndDeactivatedPaymentMethods()
    {
        return PaymentMethod::sortPaymentMethods($this->paymentMethods->whereIn('status', ['active', 'deactivated']));
    }

    public function isSame(self $user)
    {
        return $this->id === $user->id;
    }

    protected function pathPublicPage(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->isPublic ? routeURL('userPublic:show', $this->slug, 'absolute') : null,
        );
    }

    protected function image(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->profilePhoto ? $this->profilePhoto->url(['thumbnails'], 'absolute') : null,
        )->shouldCache();
    }

    /* Relationships */
    public function comparisons()
    {
        return $this->belongsToMany(Listing::class, 'comparisons')->as('comparison');
    }

    public function profilePhoto()
    {
        return $this->hasOne(Pic::class, 'subjectID')->where('subjectType', 'users')->where('type', 'profile');
    }

    public function articles()
    {
        return $this->hasMany(Article::class, 'userID');
    }

    public function attachedTexts()
    {
        return $this->hasMany(AttachedText::class, 'userID');
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class, 'userID');
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class, 'reviewerID');
    }

    public function ratings(): HasMany
    {
        return $this->hasMany(Rating::class, 'userID');
    }

    public function cityComments(): HasMany
    {
        return $this->hasMany(CityComment::class, 'userID');
    }

    public function mailMessage()
    {
        return $this->hasMany(MailMessage::class, 'userID');
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class);
    }

    public function dreamDestinationsList(): BelongsToMany
    {
        return $this->belongsToMany(CityInfo::class, 'users_dream_destinations', 'user_id', 'city_id')->withTimestamps();
    }

    public function favoriteHostelsList(): BelongsToMany
    {
        return $this->belongsToMany(Listing::class, 'users_favorite_hostels')->withTimestamps();
    }

    public function searchHistory(): HasMany
    {
        return $this->hasMany(UsersSearchHistory::class);
    }

    /* UserInterface Required Methods */

    public function getRouteKeyName()
    {
        return 'nickname';
    }

    public function getAuthIdentifierName() // see https://laravel.com/docs/5.2/upgrade#upgrade-5.2.0
    {
        return 'id';
    }

    public function getAuthIdentifier()
    {
        return $this->id;
    }

    public function getAuthPassword()
    {
        return $this->passwordHash;
    }

    // a token for "remember me" sessions

    public function getRememberToken()
    {
        return $this->remember_token;
    }

    public function setRememberToken($value): void
    {
        $this->remember_token = $value;
    }

    public function getRememberTokenName()
    {
        return 'remember_token';
    }
}
