<?php

namespace App\Models\Listing;

use App\Booking\RoomInfo;
use App\Enums\CategorySlp;
use App\Enums\Listing\CategoryPage;
use App\Helpers\EventLog;
use App\Lib\Common\Images\Image;
use App\Lib\Common\Ota\OtaLinks\OtaLink;
use App\Lib\Listings\ListingPrices;
use App\Models\AttachedText;
use App\Models\Booking;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\HostelsChain;
use App\Models\Imported;
use App\Models\Languages;
use App\Models\MailMessage;
use App\Models\Pic;
use App\Models\PriceHistory;
use App\Models\Rating;
use App\Models\Review;
use App\Models\SearchRank;
use App\Models\SpecialLandingPage;
use App\Models\User;
use App\Models\Wishlist;
use App\Services\ImportSystems\BookHostels\ImportBookHostels;
use App\Services\ImportSystems\BookingDotCom\ImportBookingDotCom;
use App\Services\ImportSystems\Hostelsclub\ImportHostelsclub;
use App\Services\ImportSystems\ImportSystems;
use App\Services\WebsiteStatusChecker;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Lib\BaseModel;
use Lib\Currencies;
use Lib\GeoPoint;
use Lib\PageCache;
use Lib\WebSearch;

/**
 * @property ListingPrices $price
 */
class Listing extends BaseModel
{
    use Searchable, HasFactory;

    public const TOP_HOSTELS_MIN_RATIING = 80;

    protected $table = 'listings';

    public static $staticTable = 'listings'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    protected $casts = [
        'lastUpdated' => 'datetime',
        'isLive' => 'boolean',
    ];

    protected $appends = [
        //        'path',
        //        'min_price_formated'
    ];

    private $miscDataCaches = []; // Just used to temporarily store results from getLiveReview(), etc. to avoid multiple database calls.

    // Static Properties & Constants

    private static $propertyTypes = [
        'Hostel' => [
            'isPrimary' => true,
            'isTypeOfHostel' => true,
        ],
        'Hotel' => [
            'isPrimary' => false,
            'isTypeOfHostel' => false,
        ],
        'Guesthouse' => [
            'isPrimary' => false,
            'isTypeOfHostel' => false,
        ],
        'Apartment' => [
            'isPrimary' => false,
            'isTypeOfHostel' => false,
        ],
        'Campsite' => [
            'isPrimary' => true,
            'isTypeOfHostel' => false,
        ],
        'Other' => [
            'isPrimary' => true,
            'isTypeOfHostel' => true,
        ],
    ];

    public static $statusOptions = [
        'listingCorrection' => -50, 'listingCorrectionFlagged' => -52, 'removed' => -40, 'unlisted' => -30,
        'new' => -5, 'newIgnored' => -25, 'imported' => -3, 'ok' => 0,
    ];

    public static $contactStatusOptions = [
        'notSet' => 0, 'cantFindEmail' => 10, 'webForm' => 30, 'dontContact' => 50,
    ];

    public static $stickerStatusOptions = [
        'emailed', 'agreed', 'left', 'manager', 'refused', 'mailedCertificate', 'mailedSticker',
    ];

    public static $stickerPlacementOptions = [
        'insideSmall', 'insideLarge', 'outsideSmall', 'outsideLarge',
    ];

    public static $panoramaStatusOptions = [
        'emailed', 'agreed', 'refused', 'done',
    ];

    public static $locationStatusOptions = ['ok', 'outlier' /*, 'confusion' (not currently used */];

    // (must order from high to low). numbers are roughly the number of characters of unique content
    // (average word is 4.5 characters, for SEO at least 300 words is recommended, more is better).
    private static $contentRanks = ['good' => 2000, 'ok' => 1100, 'some' => 500];

    public static $emailFields = ['managerEmail', 'supportEmail', 'bookingsEmail', 'importedEmail', 'invalidEmails'];

    // DataCorrection is often done within the "context" of another field (e.j. each region correction is specific to a particular country).
    public static $dataCorrectionContexts = [
        // fieldName => [ contextValue1, contextValue2 ]
        'country' => [null, null],
        'region' => ['country', null],
        'city' => ['country', null],
        'cityAlt' => ['country', 'city'],
    ];

    public static $hostelgeeksActiveFeaturedOptions = ['yes', '5star'];

    // * Pics *

    // (based on the approximate max likely max size of the images shown on the city pages)
    public const IMG_THUMBNAIL_WIDTH = 450;

    public const IMG_THUMBNAIL_HEIGHT = 300;

    /* (no longer using) const SMALL_PIC_WIDTH = 220; */
    public const BIG_PIC_MAX_WIDTH = 1600;

    public const BIG_PIC_MAX_HEIGHT = 1000;

    // min allowed for newly submitted review photos
    public const NEW_PIC_MIN_WIDTH = 480;

    public const NEW_PIC_MIN_HEIGHT = 480;

    public const NEW_PANORAMA_MIN_WIDTH = 1000;

    public const NEW_PANORAMA_MIN_HEIGHT = 400;

    public const PREFERRED_PIC_MIN_WIDTH = 300;

    public const PREFERRED_PIC_MIN_HEIGHT = 300;

    public const MIN_PIC_COUNT_PREFERRED = 5;

    public const MAX_PIC_COUNT = 18;

    // * Other *

    public const LATLONG_PRECISION = 6;

    public const MIN_RATING_FOR_STICKER = 75;

    public const MIN_PREFERRED_DESCRIPTION_LENGTH = 425; // min. length of owner desc to use it instead of the imported desc.

    public const MIN_PREFERRED_LOCATION_LENGTH = 50; // min. length of owner desc to use it instead of the imported desc.

    public const MIN_PREFERRED_ORIGINALITY_SCORE = 60; // A "good" originality score for the desription or location to be considered unique content.

    public const NO_CONTINENT = 'noContinent';

    public const NOT_APPROVED = 'notApproved';

    public const REMOVED = 'removed';

    public const LIVE = 'live';

    public const NO_BOOKING_NOT_HOSTEL = 'noBookingSystemAndNotHostel';

    public const NO_BOOKING_NO_VALID = 'noBookingSystemAndNoValidWebsite';

    protected static function booted(): void
    {
        static::updated(function (self $listing) {
            cache()->tags(["listing:$listing->id"])->flush();
        });

        static::saving(function (self $listing) {
            $listing->setAttribute('isLive', $listing->isLive());
        });
    }

    public function __construct(array $attributes = [])
    {
        // Default values
        $this->dateAdded = date('Y-m-d');

        parent::__construct($attributes);
    }

    protected static function staticDataTypes()
    {
        static $dataTypes = [];

        if (! $dataTypes) {
            $dataTypes = [
                'supportEmail' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'supportEmail']),
                'managerEmail' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'managerEmail']),
                'bookingsEmail' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'bookingsEmail']),
                'importedEmail' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'importedEmail']),
                'invalidEmails' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'invalidEmails']),
            ];
        }

        return $dataTypes;
    }

    public function save(array $options = []): void
    {
        $this->listingMaintenance()->quickFixesBeforeSaving();

        parent::save($options);

        $this->clearRelatedPageCaches();
    }

    public function delete()
    {
        // USUALLY FOR CHANGES MADE HERE, SIMILAR CHANGES NEED TO BE MADE IN THE MERGE CODE in [ where ever we do listing merges ]

        if ($this->reviews->count()) {
            throw new Exception("Can't delete listing because it has reviews.");

            return false; // so we don't actually delete the record
        }

        /*
        TO DO: "DELETE FROM bookingCache WHERE hostelID=$listingID"
        */

        foreach (User::areMgmtForListing($this->id)->get() as $user) {
            $user->removeMgmtListingIDs($this->id);
        }
        Imported::where('hostelID', $this->id)->update(['hostelID' => 0]);
        Booking::where('listingID', $this->id)->update(['listingID' => 0]);
        MailMessage::where('listingID', $this->id)->update(['listingID' => 0]);

        // Simple models that don't need us to call their delete() handler
        ListingDuplicate::forListingID($this->id)->delete();
        PriceHistory::where('listingID', $this->id)->delete();

        // Complex models that need us to call their delete() method for special delete handling
        foreach ($this->attachedTexts as $item) {
            $item->delete();
        }
        foreach ($this->reviews as $item) {
            $item->delete();
        }
        foreach ($this->ratings as $item) {
            $item->delete();
        }
        foreach ($this->ownerPics as $item) {
            $item->delete();
        }
        foreach ($this->panoramas as $item) {
            $item->delete();
        }

        // Delete listing corrections
        foreach (self::listingCorrectionStatusOptions() as $statusCode) {
            foreach (self::where('targetListing', $this->id)->where('verified', $statusCode)->get() as $item) {
                $item->delete();
            }
        }

        parent::delete();

        $this->clearRelatedPageCaches();
    }

    // Used to move reviews, pics, etc. from another listing to this one before merging the listings

    public function acquireResourcesFromAnotherListing($otherListing): void
    {
        foreach (User::areMgmtForListing($otherListing->id)->get() as $user) {
            $user->removeMgmtListingIDs($otherListing->id);
            $user->addMgmtListingIDs($this->id);
        }

        foreach ($otherListing->ownerPics as $item) {
            $item->subjectID = $this->id;
            $item->save();
        }

        foreach ($otherListing->panoramas as $item) {
            $item->subjectID = $this->id;
            $item->save();
        }

        foreach ($otherListing->attachedTexts as $item) {
            $item->subjectID = $this->id;
            $item->save();
        }

        self::where('targetListing', $otherListing->id)->update(['targetListing' => $this->id]);
        Imported::where('hostelID', $otherListing->id)->update(['hostelID' => $this->id]);
        Booking::where('listingID', $otherListing->id)->update(['listingID' => $this->id]);
        MailMessage::where('listingID', $otherListing->id)->update(['listingID' => $this->id]);
        PriceHistory::mergeListings($this->id, $otherListing->id);
        Rating::where('hostelID', $otherListing->id)->update(['hostelID' => $this->id]);
        Review::where('hostelID', $otherListing->id)->update(['hostelID' => $this->id]);
        EventLog::where('subjectType', 'Listing')->
        where('subjectID', $otherListing->id)->update(['subjectID' => $this->id]);

        // Temp: In case some log entries use the old sytem's 'hostels' subjectType. TODO: Remove this when the old system is no longer used.
        EventLog::where('subjectType', 'hostels')->
        where('subjectID', $otherListing->id)->update(['subjectID' => $this->id, 'subjectType' => 'Listing']);
    }

    public static function fieldInfo($purpose = null)
    {
        $allFields = [
            'id' => ['isPrimaryKey' => true, 'editType' => 'display'],
            'name' => ['maxLength' => 80, 'validation' => 'required'],
            'verified' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translateKeys'],
            'isLive' => ['type' => 'ignore', 'searchType' => 'checkbox', 'value' => true, 'fieldLabelText' => ' ', 'checkboxText' => 'Listing is Live',
                'searchQuery' => function ($formHandler, $query, $value) {
                    return $value ? $query->areLive() : $query;
                }, ],
            'contactStatus' => ['type' => 'select', 'options' => self::$contactStatusOptions, 'optionsDisplay' => 'translateKeys'],
            'roomTypes' => ['type' => 'display', 'maxLength' => 250],
            'propertyType' => ['type' => 'select', 'options' => self::propertyTypes(), 'validation' => 'required'],
            'propertyTypeVerified' => ['type' => 'radio', 'searchType' => 'checkboxes', 'options' => ['0', '1'], 'optionsDisplay' => 'translate'],
            'continent' => ['maxLength' => 60],
            'country' => ['maxLength' => 70, 'validation' => 'required', 'comparisonType' => 'equals'],
            'region' => ['maxLength' => 70, 'comparisonType' => 'equals'],
            'city' => ['maxLength' => 70, 'validation' => 'required', 'comparisonType' => 'equals'],
            'cityAlt' => ['maxLength' => 70, 'comparisonType' => 'equals'],
            'rememberCityAltRenaming' => ['type' => 'ignore', 'editType' => 'checkbox', 'value' => true, 'fieldLabelText' => ' ',
                'checkboxText' => 'Remember renaming rule if the Neighborhood is changed.',
                'getValue' => function ($formHandler, $model) {
                    return false; /* auth()->user()->hasPermission('admin'); probably too risky to be on by default */
                },
                'setValue' => function ($formHandler, $model, $value): void { /* do nothing, this is handled by a 'setModelData' callback */
                },
            ],
            'address' => ['maxLength' => 130],
            'mapAddress' => ['maxLength' => 130],
            'zipcode' => ['maxLength' => 30],
            'poBox' => ['maxLength' => 100],
            'mailingAddress' => ['type' => 'textarea', 'rows' => 4],
            'supportEmail' => ['dataTypeObject' => self::staticDataTypes()['supportEmail'], 'editType' => 'multi', 'maxLength' => 255, 'validation' => 'emailList'],
            'managerEmail' => ['dataTypeObject' => self::staticDataTypes()['managerEmail'], 'editType' => 'multi', 'maxLength' => 255, 'validation' => 'emailList'],
            'bookingsEmail' => ['dataTypeObject' => self::staticDataTypes()['bookingsEmail'], 'editType' => 'multi', 'maxLength' => 255, 'validation' => 'emailList'],
            'importedEmail' => ['dataTypeObject' => self::staticDataTypes()['importedEmail'], 'editType' => 'multi', 'maxLength' => 255, 'validation' => 'emailList'],
            'invalidEmails' => ['dataTypeObject' => self::staticDataTypes()['invalidEmails'], 'editType' => 'multi', 'maxLength' => 255],
            'ownerName' => ['maxLength' => 255],
            'web' => ['type' => 'url', 'maxLength' => 200, 'validation' => 'url', 'sanitize' => 'url'],
            'webStatus' => [
                'type' => 'display',
                'searchType' => 'select',
                'options' => WebsiteStatusChecker::getWebsiteStatusLanguageKeys(),
                'optionsDisplay' => 'translateKeys',
                'searchFormDefaultValue' => '',
            ],
            'webDisplay' => ['type' => 'select', 'options' => ['0', '1', '-1'], 'optionsDisplay' => 'translate'],
            'tel' => ['maxLength' => 150],
            'fax' => ['maxLength' => 150],
            'videoURL' => ['searchType' => '', 'type' => 'display'], // should be set by using the Video management page
            'videoEmbedHTML' => ['type' => 'display'], // gets set automatically
            'videoSchema' => ['type' => 'display'], // gets set automatically
            'mgmtBacklink' => ['maxLength' => 250],
            'mgmtFeatures' => ['type' => 'text', 'editType' => 'display', 'getValue' => function ($formHandler, $model) {
                return $model->attributes['mgmtFeatures']; // just output the json encoded string
            }],
            'compiledFeatures' => ['type' => 'text', 'editType' => 'display', 'getValue' => function ($formHandler, $model) {
                return $model->attributes['compiledFeatures']; // just output the json encoded string
            }],
            'contentScores' => ['type' => 'text', 'editType' => 'display', 'getValue' => function ($formHandler, $model) {
                return $model->attributes['contentScores']; // just output the json encoded string
            }],
            'overallContentScore' => ['type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax'],
            'specialNote' => ['type' => 'textarea', 'rows' => 2],
            'featuredListingPriority' => ['type' => 'select', 'options' => ['-1', '0', '1', '2'], 'optionsDisplay' => 'translate'],
            'boutiqueHostel' => ['type' => 'select', 'options' => ['0', '1'], 'optionsDisplay' => 'translate'],
            'featured' => ['type' => 'select', 'options' => ['no', 'yes', '5star'], 'optionsDisplay' => 'translate'],
            'blockSnippet' => ['type' => 'checkbox', 'value' => true, 'checkboxText' => ' '],
            //            'useForBookingPrice' => [ 'type' => 'checkbox', 'value' => true, 'checkboxText' => ' ' ],
            'onlineReservations' => ['type' => 'select', 'options' => ['1', '0', '-1'], 'optionsDisplay' => 'translate'],
            'unavailableCount' => ['type' => 'display'],
            'preferredBooking' => ['type' => 'display', 'options' => ['1', '0'], 'optionsDisplay' => 'translate'],
            'combinedRating' => ['type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax'],
            'combinedRatingCount' => ['type' => 'display', 'dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax'],
            'lastEditSessionID' => ['type' => 'display'],
            'ownerLatitude' => [],
            'ownerLongitude' => [],
            'latitude' => [],
            'longitude' => [],
            'geocodingLocked' => ['type' => 'checkbox', 'value' => true, 'checkboxText' => ' '],
            'locationStatus' => ['type' => 'display', 'searchType' => 'select', 'options' => self::$locationStatusOptions, 'optionsDisplay' => 'translate'],
            'privatePrice' => ['type' => 'display'],
            'sharedPrice' => ['type' => 'display'],
            'stickerStatus' => ['type' => 'select', 'options' => self::$stickerStatusOptions, 'optionsDisplay' => 'translate'],
            'stickerPlacement' => ['type' => 'select', 'options' => self::$stickerPlacementOptions, 'optionsDisplay' => 'translate'],
            'stickerDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
            'panoramaStatus' => ['type' => 'select', 'options' => self::$panoramaStatusOptions, 'optionsDisplay' => 'translate'],
            'dateAdded' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType'],
            'lastUpdate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
            'lastUpdated' => ['type' => 'text', 'searchType' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateTimeDataType', 'dataAccessMethod' => 'dataType', 'comparisonType' => 'minMax'],
            'source' => ['maxLength' => 255],
            'comment' => ['type' => 'textarea'],
            'hostels_chain_id' => ['type' => 'select-key', 'options' => HostelsChain::forOptions()],
            'uniqeTextFor' => [
                'type' => 'ignore',
                'searchType' => 'select',
                'options' => CategorySlp::values()->toArray(),
                'skipQuery' => true,
            ],
            'uniqueText' => [
                'type' => 'ignore',
                'getValue' => function ($formHandler, self $model) {
                    if (empty($formHandler->inputData['uniqeTextFor'])) {
                        return '-';
                    }

                    $category = CategorySlp::tryFrom($formHandler->inputData['uniqeTextFor']);
                    if (is_null($category)) {
                        return '';
                    }

                    $item = self::withSlpTextExists($category)->find($model->id);
                    $isInCategory = SpecialLandingPage::forCity($model->city)
                            ->where('category', $formHandler->inputData['uniqeTextFor'])
                            ->first()
                            ->hostels
                            ->pluck('id')
                            ->search($model->id) !== false;

                    $getClass = function (bool $isInCategory, bool $textExists = false) {
                        if ($isInCategory === false) {
                            return '';
                        }

                        if (! $textExists) {
                            return 'bg-row-danger';
                        }

                        return 'bg-row-success';
                    };

                    return sprintf(
                        '<span class="%s">%s</span>',
                        $getClass($isInCategory, $item->slp_text_exists),
                        (int) $item->slp_text_exists
                    );
                },
                'setValue' => function ($formHandler, $model, $value): void {
                },
                'userSortable' => false,
            ],
            'hasStoredPrice' => [
                'type' => 'ignore',
                'getValue' => function ($formHandler, $model) {
                    return self::whereId($model->id)
                        ->hasActivePriceHistoryPastMonths()
                        ->exists() ? 1 : '-';
                },
                'setValue' => function ($formHandler, $model, $value): void {
                },
                'userSortable' => false,
            ],
        ];

        $return = [];

        switch ($purpose) {
            case null:
            case 'mergeListings':
                $useFields = array_keys($allFields);
                $displayOnly = [];

                break;

            case 'adminEdit':
                $allFields['ids'] = [
                    'maxLength' => 524288,
                    'type' => 'ignore',
                    'searchType' => 'text',
                    'searchQuery' => function ($formHandler, $query, $value) {
                        return $value ? $query->whereIn('id', explode(',', $value))->orderBy('id') : $query;
                    },
                    'setValue' => function ($formHandler, $model, $value): void {
                    },
                ];

                $allFields['missing_unique_text'] = [
                    'type' => 'ignore',
                    'searchType' => 'checkbox',
                    'value' => true,
                    'fieldLabelText' => 'SLP With Missing Unique Hostel Text',
                    'checkboxText' => '',
                    'searchQuery' => function ($formHandler, $query, $value) {
                        if (! $value) {
                            return $query;
                        }

                        if (empty($formHandler->inputData['uniqeTextFor'])) {
                            return $query;
                        }

                        $category = CategorySlp::tryFrom($formHandler->inputData['uniqeTextFor']);
                        if (is_null($category)) {
                            return $query;
                        }

                        return $query->whereNotExists(function ($query) use ($category) {
                            $query->select(DB::raw(1))
                                ->from('attached')
                                ->whereColumn('attached.subjectID', 'listings.id')
                                ->where([
                                    ['subjectType', 'hostels'],
                                    ['type', $category->value],
                                    ['language', Languages::currentCode()],
                                ]);
                        });
                    },
                ];

                $useFields = array_keys($allFields);
                $displayOnly = [];
                $allFields['verified']['options'] = self::notListingCorrectionStatusOptions();

                break;

            case 'staffEdit':
                $useFields = [
                    'id', 'name', 'verified', 'contactStatus', 'roomTypes', 'propertyType', 'propertyTypeVerified',
                    'country', 'region', 'city', 'cityAlt', 'rememberCityAltRenaming', 'address', 'mapAddress', 'zipcode', 'poBox', 'mailingAddress',
                    'supportEmail', 'managerEmail', 'bookingsEmail', 'invalidEmails', 'ownerName', 'web', 'tel', 'fax',
                    'ownerLatitude', 'ownerLongitude', 'geocodingLocked', 'latitude', 'longitude',
                    'mgmtBacklink', 'comment',
                ];
                $displayOnly = ['importedEmail', 'lastUpdate', 'dateAdded'];
                $allFields['verified']['options'] = self::notListingCorrectionStatusOptions();

                break;

            case 'listingCorrection':
                $useFields = [
                    'supportEmail', 'managerEmail', 'web', 'tel', 'fax', 'comment',
                ];
                $displayOnly = [];

                break;

            case 'listingCorrectionStaffDisplay':
                $return = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'verified_hidden_input' => ['type' => 'hidden', 'modelPropertyName' => 'verified', 'options' => self::listingCorrectionStatusOptions()],
                    'verified' => ['type' => 'display', 'options' => self::listingCorrectionStatusOptions(), 'optionsDisplay' => 'translateKeys'],
                    'dateAdded' => ['type' => 'display', 'fieldLabelText' => 'Date Submitted'],
                    'targetListing_name' => ['type' => 'display', 'searchType' => '', 'dataAccessMethod' => 'dataType',
                        'dataTypeObject' => new \Lib\dataTypes\StringDataType(['tableName' => self::$staticTable, 'fieldName' => 'name', 'firstTableKeyFieldname' => 'targetListing']), ],
                ];
                $useFields = [];
                $displayOnly = ['supportEmail', 'managerEmail', 'web', 'tel', 'fax', 'comment'];

                break;

            case 'listingMerge':
                $useFields = [
                    'name', 'verified', 'contactStatus', 'roomTypes', 'propertyType', 'propertyTypeVerified', 'country', 'region', 'city',
                    'cityAlt', 'address', 'mapAddress', 'zipcode', 'poBox', 'mailingAddress', 'supportEmail', 'managerEmail', 'ownerName',
                    'web', 'tel', 'fax', 'comment',
                ];
                $displayOnly = [];

                break;

            case 'submitListing':
                $useFields = [
                    'name', 'propertyType', 'country', 'city', 'address', 'supportEmail', 'web', 'tel',
                ];

                $displayOnly = [];

                $_allFields['name'] = [
                    'validation' => 'required|min:3|not_all_uppercase|not_all_lowercase',
                    'formGroupClass' => 'form-group mb-4 mb-sm-5',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'row' => 'skip',
                ];
                $_allFields['propertyType'] = [
                    'formGroupClass' => 'form-group mb-4 mb-sm-5',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'row' => 'skip',
                ];
                $_allFields['country'] = [
                    'validation' => 'required|min:3|not_all_uppercase|not_all_lowercase',
                    'formGroupClass' => 'form-group mb-4 mb-sm-5',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'row' => ['start' => true],
                    'col' => 'col-sm-6',
                ];
                $_allFields['city'] = [
                    'validation' => 'required|min:3|not_all_uppercase|not_all_lowercase',
                    'formGroupClass' => 'form-group mb-4 mb-sm-5',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'row' => ['end' => true],
                    'col' => 'col-sm-6',

                ];
                $_allFields['address'] = [
                    'validation' => 'not_all_lowercase',
                    'formGroupClass' => 'form-group pb-sm-3 mb-4 mb-sm-5',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'row' => 'skip',

                ];

                $_allFields['supportEmail'] = [
                    'row' => ['start' => true, 'end' => true],
                    'formGroupClass' => 'form-group',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'col' => 'col-sm-6',
                    'beforeField' => view('partials/_submitFormBeforeSupportEmail'),
                ];

                $_allFields['web'] = [
                    'validation' => 'required|url', // we now require a website url for new listings
                    'formGroupClass' => 'form-group mb-4 mb-sm-5',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'row' => ['start' => true],
                    'col' => 'col-sm-6',
                ];
                $_allFields['tel'] = [
                    'formGroupClass' => 'form-group mb-4 mb-sm-5',
                    'label' => [
                        'class' => 'font-montserat font-weight-600 display-3 mb-3',
                    ],
                    'row' => ['end' => true],
                    'col' => 'col-sm-6',

                ];

                $allFields = array_replace_recursive($allFields, $_allFields);

                break;

            case 'basicInfo':
                $useFields = [
                    'supportEmail', 'managerEmail', 'ownerName', 'web', 'tel', 'fax',
                ];
                $displayOnly = ['country', 'region', 'city', 'cityAlt', 'address', 'zipcode', 'poBox'];
                /*
                TO DO:
                $qf->data['lastEditSessionID'] = $COOKIE['id']; or ->lastEditSessionID = \Session::getId();
                */
                $allFields['supportEmail']['validation'] .= '|required';

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        // Get only the fields we need for this purpose
        $return = array_merge($return, array_intersect_key($allFields, array_flip(array_merge($useFields, $displayOnly))));

        // Set $displayOnly[] fields to 'display' editType.
        array_walk($return, function (&$fieldInfo, $fieldName, $displayOnly): void {
            if (in_array($fieldName, $displayOnly)) {
                $fieldInfo['editType'] = 'display';
            }
        }, $displayOnly);

        return $return;
    }

    /*
        DataCorrection is often done within the "context" of another field (e.j. each region correction is specific to a particular country).
        This gets the value of the context field for the current listing object when correcting for $fieldName for $contextNumber context (1 or 2).
    */

    public function getDataCorrectionContextValue($fieldName, $contextNumber)
    {
        $context = self::$dataCorrectionContexts[$fieldName][$contextNumber - 1];

        return $context === null ? null : $this->$context;
    }

    /* Static */

    public static function maintenanceTasks($timePeriod)
    {
        return ListingMaintenance::maintenanceTasks($timePeriod);
    }

    public static function listingCorrectionStatusOptions()
    {
        return array_intersect_key(self::$statusOptions, array_flip(['listingCorrection', 'listingCorrectionFlagged']));
    }

    public static function notListingCorrectionStatusOptions()
    {
        return array_diff_key(self::$statusOptions, array_flip(['listingCorrection', 'listingCorrectionFlagged']));
    }

    public static function propertyTypes()
    {
        return array_keys(self::$propertyTypes);
    }

    public static function primaryPropertyTypes()
    {
        return keysOfArrayWithMatchingElements(self::$propertyTypes, 'isPrimary', true);
    }

    public static function hostelPropertyTypes()
    {
        return keysOfArrayWithMatchingElements(self::$propertyTypes, 'isTypeOfHostel', true);
    }

    public static function averageGeocodingLocation($listings, $ignoreOutliers = false)
    {
        $count = $latitudeSum = $longitudeSum = 0;
        foreach ($listings as $listing) {
            if (! $listing->hasLatitudeAndLongitude()) {
                continue;
            }
            if ($ignoreOutliers && $listing->locationStatus != 'ok') {
                continue;
            }
            $count++;
            $latitudeSum += $listing->latitude;
            $longitudeSum += $listing->longitude;
        }

        if (! $count) {
            return null;
        }

        return new GeoPoint($latitudeSum / $count, $longitudeSum / $count);
    }

    public static function scoreAsHue($score)
    {
        $startHue = 0;
        $endHue = 105;
        // Map scores => hue to where the land on the hue scale (both are 0-100).
        $valueMap = [0 => 0, 10 => 0, 20 => 0, 30 => 0, 40 => 10, 50 => 35, 60 => 45, 70 => 60, 80 => 70, 90 => 80, 100 => 100];
        $scoreTensPlace = floor($score / 10) * 10;
        $scoreOnesPlace = $score % 10;
        $scaledScore = $valueMap[$scoreTensPlace];
        $nextHigherScaledScore = $valueMap[min($scoreTensPlace + 10, 100)];
        $interval = $nextHigherScaledScore - $scaledScore;
        $preciseScaledScore = $scaledScore + ($interval * ($scoreOnesPlace / 10));

        return round($startHue + ($endHue - $startHue) * ($preciseScaledScore / 100));
    }

    public static function getTestListings(): Collection
    {
        return self::query()
            ->select(['id', 'country', 'city', 'name', 'verified'])
            ->where(function ($query): void {
                $query
                    ->where('country', 'regexp', '(^|[[:space:]]|_)test([[:space:]]|_|$)')
                    ->orWhere('city', 'regexp', '(^|[[:space:]]|_)test([[:space:]]|_|$)')
                    ->orWhere('name', 'regexp', '(^|[[:space:]]|_)test([[:space:]]|_|$)');
            })
            ->where(function ($query): void {
                $query
                    ->where('verified', self::$statusOptions['new'])
                    ->orWhere('verified', self::$statusOptions['newIgnored'])
                    ->orWhere('verified', self::$statusOptions['imported'])
                    ->orWhere('verified', self::$statusOptions['ok']);
            })
            ->get();
    }

    /* Accessors & Mutators */

    public function setCityAttribute($value): void
    {
        if ($value == $this->city) {
            return;
        } // nothing to do

        // If the city changed, we clear page caches of the *old* city name first.
        // Note: The listing hasn't yet been saved, so if the city is viewed
        // before we actually save it, it may cache the old city name page,
        // but oh well.
        $this->clearRelatedPageCaches();
        $this->attributes['city'] = $value;
    }

    public function setWebAttribute($value): void
    {
        // Also upate the webStatus and websiteDomain automatically
        if (isset($this->attributes['web']) && $this->attributes['web'] !== $value) {
            $this->webStatus = WebsiteStatusChecker::$websiteStatusOptions['unknown'];
            $this->websiteDomain = (string) \Lib\WebsiteTools::getRootDomainName($value);
        }
        $this->attributes['web'] = $value;
    }

    public function getContentScoresAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setContentScoresAttribute($value): void
    {
        $this->attributes['contentScores'] = ($value ? json_encode($value) : '');
    }

    public function getMgmtFeaturesAttribute($value)
    {
        /* return ($value == '' ? [ ] : json_decode($value, true)); */

        /* Legacy: TEMP to fix issue with old data have capitalized "No" for "lockout" */
        $temp = ($value === '' ? [] : json_decode($value, true, 512, JSON_THROW_ON_ERROR));

        if ($temp && isset($temp['lockout']) && $temp['lockout'] === 'No') {
            $temp['lockout'] = 'no';
        }

        return $temp;
    }

    public function setMgmtFeaturesAttribute($value): void
    {
        $this->attributes['mgmtFeatures'] = ($value ? json_encode($value) : '');
    }

    public function getCompiledFeaturesAttribute($value)
    {
        /* return ($value == '' ? [ ] : json_decode($value, true)); */

        /* TEMP to fix issue with old data have capitalized "No" for "lockout". TO DO: fix that. */
        $temp = ($value === '' || is_null($value) ? [] : json_decode($value, true, 512, JSON_THROW_ON_ERROR));

        if ($temp && isset($temp['lockout']) && $temp['lockout'] === 'No') {
            $temp['lockout'] = 'no';
        }

        return $temp;
    }

    public function setCompiledFeaturesAttribute($value): void
    {
        $this->attributes['compiledFeatures'] = ($value ? json_encode($value) : '');
    }

    public function getSnippetFullAttribute()
    {
        return Cache::remember(
            "listing_{$this->id}_snippet_lang_" . Languages::currentCode(),
            60 * 60 * 24 * 7, // week
            function () {
                $attachetSnippet = $this->attachedTexts()
                    ->where('type', 'snippet')
                    ->where('language', Languages::currentCode())
                    ->first();

                if (! $attachetSnippet) {
                    return '';
                }

                return $attachetSnippet->data;
            }
        );
    }

    public function getSlpText(CategorySlp $category)
    {
        return $this->attachedTexts()
            ->where('type', $category->value)
            ->where('language', Languages::currentCode())
            ->first()?->text ?? '';
    }

    protected function name(): Attribute
    {
        return Attribute::make(
            get: fn (?string $value) => $this->getNameWithoutCity((string) $value),
            set: fn (?string $value) => $this->getNameWithoutCity((string) $value),
        );
    }

    protected function distanceToCityCenter(): Attribute
    {
        return Attribute::make(
            get: function () {
                $listingGeoPoint = $this->geoPoint();
                if (! $listingGeoPoint) {
                    return null;
                }

                $cityGeoPoint = $this->cityInfo?->geoPoint();
                if (! $cityGeoPoint) {
                    return null;
                }

                return round($cityGeoPoint->distanceToPoint($listingGeoPoint), 2);
            },
        );
    }

    /* Misc */

    public function qualifiesForSticker()
    {
        return in_array($this->stickerStatus, ['', 'emailed', 'refused']) && $this->combinedRating >= self::MIN_RATING_FOR_STICKER &&
            $this->propertyType == 'Hostel' && $this->isLive();
    }

    public function fullDisplayName()
    {
        return "$this->name ($this->city, $this->country)";
    }

    public function hasLatitudeAndLongitude()
    {
        return (float) $this->latitude !== 0.0 || (float) $this->longitude !== 0.0;
    }

    public function geoPoint()
    {
        if (! $this->hasLatitudeAndLongitude()) {
            return null;
        }

        return new GeoPoint($this->latitude, $this->longitude);
    }

    public function determineLocalCurrency()
    {
        if ($this->cityInfo && ($currency = $this->cityInfo->determineLocalCurrency()) != '') {
            return $currency;
        }

        foreach ($this->importeds as $imported) {
            if ($imported->localCurrency != '' && Currencies::isKnownCurrencyCode($imported->localCurrency)) {
                return $imported->localCurrency;
            }
        }

        return null;
    }

    public function determineLocalLanguage()
    {
        $countryInfo = $this->countryInfo;
        if (! $countryInfo) {
            return null;
        }

        return $countryInfo->determineLocalLanguage();
    }

    public function formatCombinedRating($language = null)
    {
        return Languages::get($language)->numberFormat($this->combinedRating / 10, 1);
    }

    public function clearRelatedPageCaches($alsoClearCityCache = true): void
    {
        if (! $this->id) {
            return;
        }
        PageCache::clearByTag('listing:' . $this->id); // clear cached pages related to this listing.

        Cache::tags(['listing:' . $this->id])->flush();

        if ($alsoClearCityCache) {
            if ($this->cityInfo) {
                $this->cityInfo->clearRelatedPageCaches();
            }
        }

        // Update lastUpdated (mostly used for knowing when to have the listing re-load in users' browsers).
        $this->lastUpdated = Carbon::now();
        // We update just lastUpdated directly in the database (rather than saving the entire listing, which may not be wanted).
        self::where('id', $this->id)->update(['lastUpdated' => $this->lastUpdated]);
    }

    public function lastUpdatedTimeStamp()
    {
        return $this->lastUpdated ? $this->lastUpdated->timestamp : 0;
    }

    public function isPrimaryPropertyType()
    {
        return in_array($this->propertyType, self::primaryPropertyTypes());
    }

    public function isTypeOfHostel()
    {
        return in_array($this->propertyType, self::hostelPropertyTypes());
    }

    public function isLiveOrWhyNot()
    {
        /* This must match the logic of scopeAreLive() */

        if ($this->continent === '') {
            return self::NO_CONTINENT;
        }
        if ($this->verified === self::$statusOptions['unlisted'] ||
            $this->verified === self::$statusOptions['removed']) {
            return self::REMOVED;
        }
        if ($this->verified < self::$statusOptions['ok']) {
            return self::NOT_APPROVED;
        }
        if ($this->onlineReservations) {
            return self::LIVE;
        }

        // No onlineReservations...
        if (! $this->isPrimaryPropertyType()) {
            return self::NO_BOOKING_NOT_HOSTEL;
        }
        if (! $this->hasValidWebsite()) { // $this->overallContentScore < self::$contentRanks['ok']
            return self::NO_BOOKING_NO_VALID;
        }

        return self::LIVE;
    }

    public function isLive(): bool
    {
        return $this->isLiveOrWhyNot() === self::LIVE;
    }

    public function isLiveOrNew()
    {
        return $this->verified >= self::$statusOptions['newIgnored'];
    }

    // Is a listing that the property management should be able to edit (not removed, not a listing correction, etc.)
    public function isEditableByMgmt()
    {
        return $this->verified >= self::$statusOptions['ok'] || $this->verified == self::$statusOptions['new'];
    }

    public function isPoorContentPage($language = null)
    {
        if ($language === null) {
            $language = Languages::currentCode();
        }

        if (! isset($this->contentScores[$language])) {
            return true;
        }

        return $this->contentScores[$language] < self::$contentRanks['ok'];
    }

    public function isTopRated()
    {
        return $this->liveCityInfo->topRatedHostel === $this->id;
    }

    public function isListingCorrection()
    {
        return in_array($this->verified, self::listingCorrectionStatusOptions());
    }

    public function hasImportSystemWithOnlineBooking($ignoreSystemsNotQualifiedForOnlineBookingStatus = true)
    {
        $activeImporteds = $this->activeImporteds->where('availability', true);

        if ($ignoreSystemsNotQualifiedForOnlineBookingStatus) {
            $onlineBookingStatusSystems = array_keys(ImportSystems::all('qualifiesListingForOnlineBookingStatus', true));
            $activeImporteds = $activeImporteds->whereIn('system', $onlineBookingStatusSystems);
        }

        return ! $activeImporteds->isEmpty();
    }

    public function hasValidWebsite()
    {
        return $this->web != '' && $this->webStatus >= WebsiteStatusChecker::$websiteStatusOptions['unknown'];
    }

    public function hasInvalidWebsite()
    {
        return $this->web != '' && $this->webStatus < WebsiteStatusChecker::$websiteStatusOptions['unknown'];
    }

    public function getBestEmail($purposeOrEmailTypes = 'customerSupport')
    {
        if (is_array($purposeOrEmailTypes)) {
            $emailTypePreferenceOrder = $purposeOrEmailTypes;
        } else {
            switch ($purposeOrEmailTypes) {
                case 'customerSupport':
                    $emailTypePreferenceOrder = ['supportEmail', 'managerEmail', 'bookingsEmail', 'importedEmail', 'imported'];

                    break;
                case 'listingIssue':
                    $emailTypePreferenceOrder = ['managerEmail', 'supportEmail', 'bookingsEmail', 'importedEmail', 'imported'];

                    break;

                default:
                    throw new Exception("Unknown purpose '$purposeOrEmailTypes'.");
            }
        }

        foreach ($emailTypePreferenceOrder as $type) {
            // Not sure this is necessary since the listing also has importedEmail as a field,
            // but this checks the importeds for their emails (could be useful if the imported emails
            // were added sometime after the listing was created).
            if ($type == 'imported') {
                if (! $this->importeds) {
                    continue;
                }
                foreach ($this->importeds as $imported) {
                    if ($imported->email != '') {
                        return $imported->email;
                    }
                }
            } else {
                if ($this->$type) {
                    return implode(', ', $this->$type);
                }
            }
        }

        return '';
    }

    public function sendEmail($messageAttributes)
    {
        if (isset($messageAttributes['recipient']) && $messageAttributes['recipient'] === '') {
            $messageAttributes['recipient'] = $this->getBestEmail('listingIssue');
            if ($messageAttributes['recipient'] === '') {
                return null;
            }
        }

        if (isset($messageAttributes['subject']) && $messageAttributes['subject'] === '') {
            $messageAttributes['subject'] = $this->name;
        }

        $messageAttributes['listingID'] = $this->id;
        $sendingUser = User::getTheListingSupportUser();

        $mail = MailMessage::createOutgoing($messageAttributes, $sendingUser, 20, true);

        return $mail;
    }

    public function getAllEmails($emailTypes = null)
    {
        if ($emailTypes === null) {
            $emailTypes = self::$emailFields;
        }

        return collect($emailTypes)->flatMap(function ($emailType) {
            return $this->$emailType;
        })->unique()->toArray();
    }

    // Emails can be an array or a single email address

    public function hasAnyMatchingEmail($emails, $emailTypesToSearch = null)
    {
        return array_intersect($this->getAllEmails($emailTypesToSearch), (array) $emails) != [];
    }

    // $language = null, $ignorePoorContent = false, $fullURL = false
    public function getURL($urlType = 'auto', $language = null, $ignorePoorContent = false, $shortURL = false)
    {
        // Replace characters that could cause problems for Googlebot, SQL, or URLs.
        $name = str_replace(
            [' ', "'", '@', '&', '!', '*', '(', ')', ',', '+', ':', '"', '<', '>', '%', '/'],
            ['-', '', '', '-', '', '-', '', '', '', '-', '', '', '', '', '', '-'],
            $this->name
        );

        return routeURL(
            'hostel',
            $this->id . (! $shortURL ? '-' . urlencode($name) : ''),
            $urlType,
            $language
        );
    }

    public function hadAvailabilityOfType($dormOrPrivate, $plusOrMinusMonths = 6)
    {
        return PriceHistory::where('listingID', $this->id)
            ->where('roomType', $dormOrPrivate)
            ->where('month', '>', Carbon::now()->subMonths($plusOrMinusMonths))
            ->where('month', '<', Carbon::now()->addMonths($plusOrMinusMonths))
            ->exists();
    }

    // We put listing maintenance methods in a separate class.  This method gives us access to that class from a Listing object.

    public function listingMaintenance()
    {
        if (isset($this->miscDataCaches['listingMaintenance'])) {
            return $this->miscDataCaches['listingMaintenance'];
        }

        return $this->miscDataCaches['listingMaintenance'] = ListingMaintenance::create($this);
    }

    public function isFeaturedListing()
    {
        return $this->subscriptions->where('type', 'featured')->where('status', 'active')->first();
    }

    // Use $language = null to get a review in the current language, or '' to get the first review in any language.

    public function getLiveReview($language = null, $useCache = true)
    {
        if ($language === null) {
            $language = Languages::currentCode();
        }

        if ($useCache) {
            $cached = $this->miscDataCaches['reviews'][$language] ?? null;
            if ($cached) {
                return $cached;
            }
        }
        $query = Review::where('hostelID', $this->id)->where('status', 'publishedReview');
        if ($language != '') {
            $query->where('language', $language);
        }
        $result = $query->orderBy('id', 'desc')->first();
        $this->miscDataCaches['reviews'][$language] = $result;

        return $result;
    }

    /*
        $type - Current acceptable values are 'description' or 'location'.
    */

    public function getText($type, $language = null, $useCache = true, $languageMustMatch = false)
    {
        if ($language === null) {
            $language = Languages::currentCode();
        }

        if ($useCache && ! $languageMustMatch) { // (the cache is only used for $languageMustMatch == false results)
            $cached = $this->miscDataCaches[$type][$language] ?? null;
            if ($cached) {
                return $cached;
            }
        }

        $importedIDs = $this->importeds->pluck('id')->all();

        $selects = [DB::raw(
            '*, ' .
            'LENGTH(data) >= ' . ($type == 'description' ? self::MIN_PREFERRED_DESCRIPTION_LENGTH : self::MIN_PREFERRED_LOCATION_LENGTH) . ' as isMinLength, ' .
            'language="' . $language . '" as languageMatch'
        )];

        $query = AttachedText::where('type', $type)->where('data', '!=', '')
            ->where(function ($query) use ($importedIDs): void {
                $query->where('subjectType', 'hostels')->where('subjectID', $this->id);
                if ($importedIDs) {
                    $query->orWhere(function ($query) use ($importedIDs): void {
                        $query->where('subjectType', 'imported')->whereIn('subjectID', $importedIDs);
                    });
                }
            })
            ->orderBy('languageMatch', 'desc');

        if ($language != 'en' && ! $languageMustMatch) {
            // If the requested language isn't available, this makes English be the second choice.
            $selects[] = DB::raw("language='en' as englishIsSecondBest");
            $query->orderBy('englishIsSecondBest', 'desc');
        }

        $query->orderBy('isMinLength', 'desc');

        if ($importedIDs) {
            // This puts mgmt text first (0), then active importeds (because $this->importeds is sorted by status), then inactive.
            $selects[] = DB::raw("IF(subjectType='imported', FIND_IN_SET(subjectID, '" . implode(',', $importedIDs) . "'), 0) as importedPriority");
            $query->orderBy('importedPriority', 'asc');
        }

        $result = $query->select($selects)->first();

        if (! $languageMustMatch) { // (the cache is only used for $languageMustMatch == false results)
            $this->miscDataCaches[$type][$language] = $result;
        }

        return $result;
    }

    // This checks to see if the description/location $text is considered original enough that it can be included in the listing
    // HTML without worrying that Google will see it as duplicate content (in which case we should load it separately).

    public function isTextOriginalEnough($text)
    {
        return false; // probably safest to assume all descriptions are duplicate content from somewhere (unless we copyscape test it perhaps)
        // return $text->subjectType != 'imported' && $text->score >= Listing::MIN_PREFERRED_ORIGINALITY_SCORE;
    }

    // Returns a two-dimensional array where there is a row for each imported system (keyed by the system's name),
    // with each row having an array of the ratings for each category (Cleanliness, Security, etc.).
    // Also a 'summary' row.

    public function getImportedRatingScoresForDisplay()
    {
        // Initialize default values (all '') for $importedRatings
        $importedRatings = [];
        foreach (ImportSystems::all() as $systemName => $systemInfo) {
            if (! $systemInfo->alwaysShowInRatingsList) {
                continue;
            }
            if ($systemInfo->displayRatings) {
                if (is_array($systemInfo->displayRatings)) { // special... an array of multiple names
                    foreach ($systemInfo->displayRatings as $v) {
                        $importedRatings[$v] = '';
                    }
                } else {
                    $importedRatings[$systemInfo->shortName()] = '';
                }
            }
        }

        foreach ($this->importeds as $imported) {
            if (! $imported->getImportSystem()->displayRatings) {
                continue;
            }

            if (is_array($imported->getImportSystem()->multipleRatingSites)) {
                // For systems like BookHostels that have multiple websites' ratings (Hostels.com and Hostelworld)
                foreach ($imported->getImportSystem()->multipleRatingSites as $displayName) {
                    if (isset($importedRatings[$displayName]) && isset($importedRatings[$displayName]['count'])) {
                        continue;
                    } // if already have rating for this system, skip.
                    $importedRatings[$displayName] = ($imported->rating[$displayName] ?? '');
                }
            } else {
                $displayName = $imported->getImportSystem()->shortName();
                if (isset($importedRatings[$displayName]) && isset($importedRatings[$displayName]['count'])) {
                    continue;
                } // if already have rating for this system, skip.
                if (! isset($importedRatings[$displayName]) || ! $imported->rating) {
                    continue;
                } // not alwaysShowInRatingsList or no reviews, skip.
                $importedRatings[$displayName] = ($imported->rating ?: ['count' => 0]);
            }
        }

        // Calculate averages
        $ratingSums = $ratingCounts = [];
        foreach ($importedRatings as $rating) {
            if (! $rating || ! $rating['count']) {
                continue;
            }
            foreach ($rating as $type => $value) {
                $ratingSums[$type] = ($ratingSums[$type] ?? null) + $value * $rating['count'];
                // count is per type because some systems may be missing a type of rating
                $ratingCounts[$type] = ($ratingCounts[$type] ?? null) + $rating['count'];
            }
        }
        if ($ratingCounts) {
            foreach ($ratingSums as $type => $value) {
                $importedRatings['average'][$type] = round($value / $ratingCounts[$type]);
            }
        }

        // use combinedRating for overall
        if ($this->combinedRating && isset($importedRatings['average']['overall'])) {
            $importedRatings['average']['overall'] = $this->combinedRating;
        }

        return $importedRatings;
    }

    public function getImportedReviewsAsRatings($maxReviews, $language)
    {
        $importedIDs = $this->importeds->pluck('id')->all();
        if (! $importedIDs) {
            return [];
        }

        $attachedData = AttachedText::where('type', 'reviews')->where('status', 'ok')->where('language', $language)
            ->where('subjectType', 'imported')->whereIn('subjectID', $importedIDs)->get();

        $ratingsCollection = new Collection();

        foreach ($attachedData as $data) {
            $reviews = $data->getUnserializedData();
            $systemName = $this->importeds->find($data->subjectID)->getImportSystem()->shortName();

            foreach ($reviews as $review) {
                $rating = $review['rating'] ?? 0;

                // We create a Rating object for each imported review (basically translating their reviews into our Rating object)
                $ratingsCollection->push(new Rating([
                    'systemName' => $systemName, // (a special property that isn't usually included in a Rating)
                    'name' => $review['name'], 'commentDate' => $review['date'],
                    'homeCountry' => $review['country'] ?? '', 'comment' => $review['text'],
                    'rating' => round((float) $rating / ($systemName !== 'HostelBookers' ? 1 : 20)),
                ]));
            }
        }

        $ratingsCollection = $ratingsCollection->sortByDesc('commentDate');
        if ($ratingsCollection->count() > $maxReviews) {
            $ratingsCollection = $ratingsCollection->take($maxReviews);
        }

        return $ratingsCollection;
    }

    public function updateSearchRank()
    {
        if (stripos($this->name, $this->city) === false) {
            $searchPhrase = "$this->name, $this->city";
        } else {
            $searchPhrase = $this->name;
        }

        $results = WebSearch::search($searchPhrase, 50);
        if (! $results) {
            logError("Unable to perform search for '$searchPhrase'.");

            return null;
        }

        $rank = 0;
        foreach ($results as $key => $result) {
            if (stripos($result['url'], 'hostelz.com/hostel/') !== false || stripos($result['url'], 'hostelz.com/hotel/') !== false) {
                $rank = $key + 1;

                break;
            }
        }

        $new = new SearchRank([
            'checkDate' => date('Y-m-d'), 'source' => 'Google', 'searchPhrase' => $searchPhrase, 'rank' => $rank,
            'placeType' => 'Listing', 'placeID' => $this->id,
        ]);
        $new->save();

        return $rank;
    }

    /* Pics */

    public function addOwnerPic($picFilePath, $caption = null)
    {
        $maxPicNum = -1;
        foreach ($this->ownerPics as $pic) {
            if ($pic->picNum > $maxPicNum) {
                $maxPicNum = $pic->picNum;
            }
        }

        $result = Pic::makeFromFilePath(
            $picFilePath,
            [
                'subjectType' => 'hostels',
                'subjectID' => $this->id,
                'type' => 'owner',
                'status' => 'ok',
                'picNum' => $maxPicNum + 1,
                'caption' => (string) $caption,
            ],
            config('pics.importedOptions')
        );

        $this->load('ownerPics'); // refresh the relationship values in case they were changed

        return $result;
    }

    public function addPanorama($picFilePath, $caption = null)
    {
        $maxPicNum = -1;
        foreach ($this->panoramas as $pic) {
            if ($pic->picNum > $maxPicNum) {
                $maxPicNum = $pic->picNum;
            }
        }

        $result = Pic::makeFromFilePath($picFilePath, [
            'subjectType' => 'hostels', 'subjectID' => $this->id, 'type' => 'panorama', 'status' => 'ok',
            'picNum' => $maxPicNum + 1, 'caption' => (string) $caption,
        ], [
            'originals' => ['storageType' => 'privateCloud'],
            '' => ['saveAsFormat' => 'jpg', 'outputQuality' => 80, 'maxWidth' => 3000,
                'watermarkImage' => public_path() . '/images/hostelz-watermark.png', 'watermarkHeight' => 200, 'watermarkOpacity' => 0.7, ],
        ]);

        $this->load('panoramas'); // refresh the relationship values in case they were changed

        return $result;
    }

    public function getExploreSectionData(string|int $key): array
    {
        return [
            'name' => $this->name,
            'url' => $this->getUrl(),
            'pic' => $this->thumbnail,
            'rating' => $this->formatCombinedRating(),
            'snippet' => $this->snippetFull,
            'label' => is_string($key) ? __('cities.bestFor.' . $key) : null,
            'minPrice' => $this->price->min?->formated,
            'cityAlt' => $this->cityAlt,
            'address' => $this->address,
        ];
    }

    public function thumbnailURL()
    {
        if (! $this->id) {
            throw new Exception('Listing ID unknown. Listing must be saved first.');
        }

        $pic = $this->getOwnerPics();
        if ($pic) {
            return $pic->url(['thumbnails', 'big', 'originals'], 'absolute');
        }

        $pics = $this->getBestPics();
        if ($pics->isEmpty() && $this->getLiveReview('')) {
            $pics = $this->getLiveReview('')->livePicsOfAnySize;
        }

        $primaryPhoto = $pics->where('isPrimary', true)->first();
        if ($primaryPhoto) {
            return $primaryPhoto->url(['thumbnails', 'big', 'originals'], 'absolute');
        }

        $pic = $pics->first();
        if ($pic) {
            return $pic->url(['thumbnails', 'big', 'originals'], 'absolute');
        }

        return Pic::getDefaultImageUrl();
    }

    protected function thumbnail(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->getPics(1)->first();
            },
        );
    }

    public function getPics(int $limit = 0)
    {
        return $this->getBestPics()
            ->when($limit, fn ($pics) => $pics->take($limit))
            ->map(fn ($pic) => Image::create($pic, $this->picAltTitle))
            ->pipe(function ($pics) {
                return $pics->isNotEmpty()
                    ? $pics
                    : collect([Image::default(altTitle: $this->picAltTitle)]);
            });
    }

    // Get the best set of pics from either the owner pics or any imported pics (review pics are not included)

    public function getBestPics()
    {
        if (Cache::has($this->picCacheKey())) {
            return Cache::get($this->picCacheKey());
        }

        $this->load(['importeds', 'ownerPics', 'cityInfo']);

        $picGroups = Pic::getForMultipleSubjectIDs(
            $this->importeds()
                ->whereIn('system', [ImportHostelsclub::SYSTEM_NAME, ImportBookingDotCom::SYSTEM_NAME, ImportBookHostels::SYSTEM_NAME])
                ->pluck('id'),
            'imported'
        );

        // todo: temp solution (issue with owner pics and webp)
//        $ownerPics = $this->ownerPics;
//        if (! $ownerPics->isEmpty()) {
//            $picGroups['owner'] = $ownerPics;
//        }

        if ($picGroups->isEmpty()) {
            return $picGroups;
        } // no pics, return empty collection

        // Find out if there are some too small pics, but also some big enough pics
        // (owner pics and new reviewer pics already have a minimum size limit, but tiny pics can still be imported)
        $haveBigPics = $haveSmallPics = false;
        foreach ($picGroups as $picGroup) {
            foreach ($picGroup as $pic) {
                if ($pic->originalWidth < self::PREFERRED_PIC_MIN_WIDTH || $pic->originalHeight < self::PREFERRED_PIC_MIN_HEIGHT) {
                    $haveSmallPics = true;
                } else {
                    $haveBigPics = true;
                }
            }
        }

        // If we have both big and tiny pics, delete all the tiny pics
        if ($haveSmallPics && $haveBigPics) {
            foreach ($picGroups as $picGroupKey => $picGroup) {
                $picGroups[$picGroupKey] = $picGroup->reject(function ($pic) {
                    return $pic->originalWidth < self::PREFERRED_PIC_MIN_WIDTH || $pic->originalHeight < self::PREFERRED_PIC_MIN_HEIGHT;
                });
                // Delete the group if it's now empty
                if ($picGroups[$picGroupKey]->isEmpty()) {
                    unset($picGroups[$picGroupKey]);
                }
            }
        }

        // Default to using the owner's pics if there are at least a minimum number of them...
        if (isset($picGroups['owner']) && count($picGroups['owner']) >= self::MIN_PIC_COUNT_PREFERRED) {
            Cache::put($this->picCacheKey(), $picGroups['owner'], now()->addMinutes());

            return $picGroups['owner'];
        }

        // Calculate totalSize (width + height) of all pics (used to find system with biggest pics)
        $totalSizes = [];
        foreach ($picGroups as $picGroupKey => $picGroup) {
            $totalSize = 0;
            foreach ($picGroup as $pic) {
                $totalSize += $pic->originalFilesize / ($pic->originalWidth + $pic->originalHeight);
            }
            $totalSizes[$picGroupKey] = $totalSize / count($picGroup);
        }

        // Find source with the highest resolution pics (or the most pics if the there are less than 5 pics)
        $bestGroupPics = [];
        $bestGroupAverageSize = 0;
        foreach ($picGroups as $picGroupKey => $picGroup) {
            if ((count($bestGroupPics) < self::MIN_PIC_COUNT_PREFERRED && count($picGroup) > count($bestGroupPics)) ||
                ((count($picGroup) >= self::MIN_PIC_COUNT_PREFERRED || count($picGroup) == count($bestGroupPics)) && $totalSizes[$picGroupKey] > $bestGroupAverageSize)) {
                $bestGroupAverageSize = $totalSizes[$picGroupKey];
                $bestGroupPics = $picGroup;
            }
        }

        Cache::put($this->picCacheKey(), $bestGroupPics, now()->addMinutes());

        return $bestGroupPics;
    }

    private function picCacheKey(): string
    {
        return 'pics-' . $this->id;
    }

    private function ownerPicCacheKey(): string
    {
        return 'owner-pic-' . $this->id;
    }

    private function getOwnerPics()
    {
        if (Cache::has($this->ownerPicCacheKey())) {
            return Cache::get($this->ownerPicCacheKey()) ?? [];
        }

        $pic = $this->ownerPics->where('isPrimary', true)->first();
        Cache::put($this->ownerPicCacheKey(), $pic ?? [], now()->addMinutes());

        return $pic;
    }

    protected function getNameWithoutCity(string $value): string
    {
        if (! $this->hostelsChain && str_ends_with(strtolower($value), strtolower($this->getAttribute('city')))) {
            $hostelNameWithoutCity = ucwords(trim(str_replace(strtolower($this->getAttribute('city')), '', strtolower($value))));

            return str_word_count($hostelNameWithoutCity) < 2 || str_ends_with(trim($hostelNameWithoutCity), '-')
                ? $value
                : $hostelNameWithoutCity;
        }

        return $value;
    }

    private function priceRangeCacheKey(): string
    {
        return 'priceRange-' . $this->id;
    }

    /* Scopes */

    public function scopeNearbyListingsInCountry($query, $latitude, $longitude, $countryName)
    {
        return $query->select(
            'id',
            'city',
            'country',
            'region',
            'name',
            DB::raw("6371 * acos(cos(radians($latitude))
                 * cos(radians(latitude))
                 * cos(radians(longitude) - radians($longitude))
                 + sin(radians($latitude))
                 * sin(radians(latitude))) AS distance")
        )
            ->where('country', $countryName)
            ->having('distance', '>', 1);
    }

    public function scopeBySearchString($query, $search)
    {
        if (is_numeric($search)) {
            return $query->where('id', (int) $search);
        }

        return $query->where(function ($query) use ($search): void {
            $query->where('name', 'like', '%' . $search . '%');
            // orWhere('cityAlt', 'like', '%'.$search.'%')->
        });
    }

    public function scopeArePrimaryPropertyType($query)
    {
        return $query->whereIn('propertyType', self::primaryPropertyTypes());
    }

    public function scopeHostels($query)
    {
        return $query->where('propertyType', 'Hostel');
    }

    public function scopeTopRated($query)
    {
        return $query->where('combinedRating', '>=', self::TOP_HOSTELS_MIN_RATIING);
    }

    public function scopeAreNotPrimaryPropertyType($query)
    {
        return $query->whereNotIn('propertyType', self::primaryPropertyTypes());
    }

    public function scopeHaveValidWebsite($query)
    {
        return $query->where(function ($query): void { // (Needed so there are parenthesis around it so it's associated properly when used next to an OR)
            $query->where('web', '!=', '')->where('webStatus', '>=', WebsiteStatusChecker::$websiteStatusOptions['unknown']);
        });
    }

    public function scopeHaveLatitudeAndLongitude($query)
    {
        return $query->where(function ($query): void {
            $query->where('latitude', '!=', 0)->orWhere('longitude', '!=', 0);
        });
    }

    public function scopeDontHaveInvalidWebsite($query)
    {
        return $query->where(function ($query): void {
            $query->where('web', '=', '')->orWhere('webStatus', '>=', WebsiteStatusChecker::$websiteStatusOptions['unknown']);
        });
    }

    public function scopeAreLive($query)
    {
        // (this logic should match isLiveOrWhyNot()
        return $query
            ->where('continent', '!=', '')
            ->where('verified', '>=', self::$statusOptions['ok'])
            ->where(function ($query): void {
                $query
                    ->where('onlineReservations', true)
                    ->orWhere(function ($query): void {
                        $query
                            ->arePrimaryPropertyType()
                            ->haveValidWebsite();
                    });
            });
    }

    public function scopeActiveBookingPrice($query)
    {
        return $query->where('useForBookingPrice', true);
    }

    public function scopeInChain($query, $chain_id)
    {
        return $query->areLive()->where('hostels_chain_id', $chain_id);
    }

    public function scopeAreLiveOrNew($query)
    {
        return $query->where('verified', '>=', self::$statusOptions['newIgnored']);
    }

    public function scopeAreListingCorrection($query)
    {
        return $query->whereIn('verified', self::listingCorrectionStatusOptions());
    }

    public function scopeAreNotListingCorrection($query)
    {
        return $query->whereNotIn('verified', self::listingCorrectionStatusOptions());
    }

    public function scopeByCityInfo(Builder $query, $cityInfo)
    {
        return $query->where('country', '=', $cityInfo?->country)
            ->where('region', '=', $cityInfo?->region)
            ->where('city', '=', $cityInfo?->city);
    }

    public function scopeAnyMatchingEmail($query, $emails, $emailTypesToSearch = null)
    {
        $emails = (array) $emails; // make it an array if it was a string

        if ($emailTypesToSearch === null) {
            $emailTypesToSearch = self::$emailFields;
        }

        $query->where(function ($query) use ($emails, $emailTypesToSearch): void {
            foreach ($emailTypesToSearch as $num => $emailType) {
                if (! $num) {
                    self::staticDataTypes()[$emailType]->searchQuery($query, $emails, 'matchAny');
                } else {
                    $query->orWhere(function ($query) use ($emails, $emailType): void {
                        self::staticDataTypes()[$emailType]->searchQuery($query, $emails, 'matchAny');
                    });
                }
            }
        });

        return $query;
    }

    public function scopeHostelsForCategory(Builder $query, CategorySlp $category, CityInfo $city)
    {
        match ($category) {
            CategorySlp::Best => $query->bestHostels($city),
            CategorySlp::Private => $query->privateHostels($city),
            CategorySlp::Cheap => $query->cheapHostels($city),
            CategorySlp::Party => $query->partyHostels($city),
            default => $query->bestHostels($city),
        };
    }

    public function scopeBestHostels(Builder $query, CityInfo $city)
    {
        $query->byCityInfo($city)
            ->topRated()
            ->hostels()
            ->areLive()
            ->hasActivePriceHistoryPastMonths()
            ->orderBy('combinedRating', 'desc')
            ->orderBy('overallContentScore', 'desc');
    }

    public function scopePrivateHostels(Builder $query, CityInfo $city)
    {
        $query->byCityInfo($city)
            ->hostels()
            ->areLive()
            ->hasActivePricePrivateHistoryPastMonths()
            ->orderBy('combinedRating', 'desc')
            ->orderBy('overallContentScore', 'desc');
    }

    public function scopeCheapHostels(Builder $query, CityInfo $city)
    {
        $query->byCityInfo($city)
            ->hostels()
            ->areLive()
            ->select(['listings.*'])
            ->selectRaw('min(priceHistory.averagePricePerNight) minPrice')
            ->join('priceHistory', 'listings.id', '=', 'priceHistory.listingID')
            ->where('priceHistory.month', '>=', Carbon::now()->subMonths(PriceHistory::MONTH_RANGE))
            ->where('priceHistory.month', '<=', Carbon::now()->addMonths(PriceHistory::MONTH_RANGE))
            ->groupBy('priceHistory.listingID')
            ->orderBy('minPrice')
            ->orderBy('listings.combinedRating', 'desc');
    }

    public function scopePartyHostels($query, CityInfo $city)
    {
        $query->byCityInfo($city)
            ->hostels()
            ->areLive()
            ->hasActivePriceHistoryPastMonths()
            ->where('compiledFeatures', 'like', '%partying%')
            ->whereHas('activeImporteds', function (Builder $query) {
                $query->where('system', ImportBookHostels::SYSTEM_NAME);
            })
            ->orderBy('combinedRating', 'desc')
            ->orderBy('overallContentScore', 'desc');
    }

    public function scopeHasActivePriceHistoryPastMonths($query)
    {
        return $query->whereHas('priceHistory', function ($query) {
            $query->activeHistoryPastMonth(6);
        });
    }

    public function scopeHasActivePricePrivateHistoryPastMonths($query)
    {
        return $query->whereHas('priceHistory', function ($query) {
            $query->activeHistoryPastMonth(6, RoomInfo::TYPE_PRIVATE);
        });
    }

    public function scopeHasActivePriceCheapHistoryPastMonths($query)
    {
        return $query->whereHas('priceHistory', function ($query) {
            $query->activeHistoryPastMonth(6);
        });
    }

    public function scopeFeaturedOnHostelgeeks($query)
    {
        return $query->whereIn('featured', self::$hostelgeeksActiveFeaturedOptions);
    }

    public function scopeWithSlpTextExists($query, CategorySlp $category)
    {
        return $query->withExists([
            'attachedTexts as slp_text_exists' => fn (Builder $query) => $query->hostelsByType($category->value),
        ]);
    }

    public function scopeWithFeatures(Builder $query, string $feature)
    {
        return $query
            ->where(fn ($query) => $query
                ->where('compiledFeatures', 'like', "%{$feature}%")
                ->orWhere('mgmtFeatures', 'like', "%{$feature}%")
            );
    }

    public function scopeCityCategoryPage(Builder $query, CityInfo $cityInfo, CategoryPage $categoryPage)
    {
        return $query
            ->byCityInfo($cityInfo)
            ->hostels()
            ->withFeatures($categoryPage->suitableFor())
            ->areLive();
    }

    /* Relationships */

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'comparisons')->as('comparison');
    }

    public function importeds(): HasMany
    {
        return $this->hasMany(Imported::class, 'hostelID')->orderBy('status'); /* orderBy is so active before inactive */
    }

    public function activeImporteds(): HasMany
    {
        return $this->hasMany(Imported::class, 'hostelID')->where('status', 'active');
    }

    public function inactiveImporteds()
    {
        return $this->hasMany(Imported::class, 'hostelID')->where('status', '!=', 'active');
    }

    public function bookings()
    {
        return $this->hasMany(Booking::class, 'listingID');
    }

    public function mailMessages()
    {
        return $this->hasMany(MailMessage::class, 'listingID');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'hostelID');
    }

    public function ratings()
    {
        return $this->hasMany(Rating::class, 'hostelID');
    }

    public function ownerPics()
    {
        return $this->hasMany(Pic::class, 'subjectID')->where('subjectType', 'hostels')
            ->where('type', 'owner')->orderBy('picNum');
    }

    public function panoramas()
    {
        return $this->hasMany(Pic::class, 'subjectID')->where('subjectType', 'hostels')
            ->where('type', 'panorama')->orderBy('picNum');
    }

    public function attachedTexts()
    {
        return $this->hasMany(AttachedText::class, 'subjectID')->where('subjectType', 'hostels');
    }

    public function priceHistory()
    {
        return $this->hasMany(PriceHistory::class, 'listingID');
    }

    public function targetListingListing() // to do: rename targetListing to targetListingID and rename this function to targetListing()
    {
        if (! $this->targetListing) {
            throw new Exception("This listing doesn't have a targetListing.");
        }

        return $this->hasOne(self::class, 'id', 'targetListing');
    }

    public function cityInfo()
    {
        // Note: This has a where() clause that depends on $this values, so it can't be eager loaded.
        return $this->hasOne(CityInfo::class, 'city', 'city')
            ->where('country', $this->country)
            ->where('region', $this->region);
    }

    public function countryInfo()
    {
        return $this->hasOne(CountryInfo::class, 'country', 'country');
    }

    // Only returns the cityInfo if the cityInfo page is live.
    public function liveCityInfo()
    {
        // Note: This has a where() clause that depends on $this values, so it can't be eager loaded.
        return $this->hasOne(CityInfo::class, 'city', 'city')
            ->where('country', $this->country)
            ->where('region', $this->region)
            ->areLive();
    }

    public function pendingListingCorrections()
    {
        return $this->hasMany(self::class, 'targetListing', 'id');
    }

    public function subscriptions()
    {
        return $this->hasMany(ListingSubscription::class, 'listing_id');
    }

    public function wishlists()
    {
        return $this->belongsToMany(Wishlist::class);
    }

    public function hostelsChain()
    {
        return $this->belongsTo(HostelsChain::class);
    }

    /*  custom  */

    public function getAllActiveSystemImporteds()
    {
        return $this->importeds()->whereIn('system', ImportSystems::allActiveSystemsName())->get();
    }

    public function getActiveImporteds()
    {
        return $this->activeImporteds()->whereIn('system', ImportSystems::allActiveSystemsName())->get();
    }

    public function getHwImporteds()
    {
        return $this->activeImporteds()->where('system', ImportBookHostels::SYSTEM_NAME)->get();
    }

    public function getBdcImporteds()
    {
        return $this->activeImporteds()->where('system', ImportBookingDotCom::SYSTEM_NAME)->get();
    }

    public function getDormAveragePrice()
    {
        return $this->price->dorm->avg->value;
    }

    public function getPriceRange(): ?array
    {
        return $this->price->range?->toArray();
    }

    protected function price(): Attribute
    {
        return Attribute::make(
            get: function (): ListingPrices {
                $priceRange = $this->priceHistory()->priceRange()->get()->keyBy('roomType');

                return ListingPrices::create($priceRange, getCurrencyFromSearch());
            },
        );
    }

    protected function minPriceFormated(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->price->min->formated;
            },
        );
    }

    protected function minPricePrivateFormated(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->price->private?->min->formated,
        );
    }

    protected function picAltTitle(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (Cache::has('picAltTitle:' . $this->id)) {
                    return Cache::get('picAltTitle:' . $this->id);
                }

                if ($this?->cityInfo) {
                    $title = "{$this->name}, {$this->cityInfo->city}";

                    Cache::put('picAltTitle:' . $this->id, $title, now()->addMinutes());

                    return "{$this->name}, {$this->cityInfo->city}";
                }

                Cache::put('picAltTitle:' . $this->id, $this->name, now()->addMinutes());

                return $this->name;
            }
        );
    }

    protected function path(): Attribute
    {
        return Attribute::make(
            get: function () {
                return $this->getURL('absolute');
            }
        );
    }

    public function isBreakfastFree(): string
    {
        return data_get($this->compiledFeatures, 'breakfast', '') === 'free';
    }

    public function isPriceLower(self|null $other): bool
    {
        if (is_null($other)) {
            return true;
        }

        if (! $other->price->min?->isset()) {
            return true;
        }

        if (! $this->price->min?->isset()) {
            return false;
        }

        return $this->price->min->isLower($other->price->min);
    }

    public function isPricePrivateLower(self|null $other): bool
    {
        if (is_null($other)) {
            return true;
        }

        if (! $other->price->private->min?->isset()) {
            return true;
        }

        if (! $this->price->private->min?->isset()) {
            return false;
        }

        return $this->price->private->min->isLower($other->price->private->min);
    }

    public function isRatingBetter(self|null $other): bool
    {
        return $this->isRatingBetterMain($other, fn (Listing $a, $b) => $a->isPriceLower($b));
    }

    public function isRatingPrivateBetter(self|null $other): bool
    {
        return $this->isRatingBetterMain($other, fn (Listing $a, $b) => $a->isPricePrivateLower($b));
    }

    protected function isRatingBetterMain(self|null $other, callable|null $onEqualCallback): bool
    {
        if (is_null($other)) {
            return true;
        }

        if ($this->combinedRating < $other->combinedRating) {
            return false;
        }

        if (! is_null($onEqualCallback)) {
            if ($this->combinedRating === $other->combinedRating) {
                return $onEqualCallback($this, $other);
            }
        }

        return true;
    }

    public function isParting(): bool
    {
        return in_array(
            'partying',
            data_get($this->compiledFeatures, 'goodFor', []) ?? []
        );
    }

    public function isSoloTraveler(): bool
    {
        return in_array(
            'socializing',
            data_get($this->compiledFeatures, 'goodFor', []) ?? []
        );
    }

    public function isCouples(): bool
    {
        return in_array(
            'couples',
            data_get($this->compiledFeatures, 'goodFor', []) ?? []
        );
    }

    public function isFemaleSoloTraveller(): bool
    {
        return in_array(
            'female_solo_traveller',
            data_get($this->compiledFeatures, 'goodFor', []) ?? []
        );
    }

    public function isFamilies(): bool
    {
        return in_array(
            'families',
            data_get($this->compiledFeatures, 'goodFor', []) ?? []
        );
    }

    public function getOtaLinks($cmpLabel = 'city'): Collection
    {
        $items = $this->getActiveImporteds()
            ->mapWithKeys(function (Imported $imported) use ($cmpLabel) {
                $importSystem = $imported->getImportSystem();

                return [
                    $importSystem->systemName => (new OtaLink(
                        $importSystem->systemName,
                        $importSystem->shortName(),
                        $imported->staticLink(getCMPLabel($cmpLabel, $this->city, $this->name))
                    ))];
            });

        return collect([ImportBookHostels::SYSTEM_NAME, ImportBookingDotCom::SYSTEM_NAME, ImportHostelsclub::SYSTEM_NAME])
            ->flip()
            ->merge($items)
            ->reject(fn ($item) => is_int($item));
    }

    public function isPreferredBooking(): bool
    {
        return $this->activeImporteds
            ->filter(function ($imported) {
                return $imported->getImportSystem()->isPreferredBookingSystem;
            })
            ->isNotEmpty();
    }

    //  for Scout
    public function toSearchableArray()
    {
        return [
            'id' => $this->getKey(),
            'name' => $this->name,
            'city' => $this->city,
            'country' => $this->country,
            'combinedRating' => $this->combinedRating,
            'combinedRatingCount' => $this->combinedRatingCount,
        ];
    }

    public function shouldBeSearchable()
    {
        return $this->isLive();
    }
}
