<?php

namespace App\Models;

use App\Enums\DeviceBooking;
use App\Enums\StatusBooking;
use App\Helpers\EventLog;
use App\Models\Listing\Listing;
use App\Services\ImportSystems\ImportSystems;
use App\Utils\FieldInfo;
use Exception;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;
use Lib\BaseModel;
use Lib\Emailer;

class Booking extends BaseModel
{
    protected $table = 'bookings';

    public static $staticTable = 'bookings'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    protected $casts = [
        'bookingTime' => 'datetime',
        'status' => StatusBooking::class,
        'startDate' => 'datetime:Y-m-d',
        'endDate' => 'datetime:Y-m-d',
    ];

    public static $genderOptions = ['Male', 'Female', 'Mixed'];

    public static $arrivalTimeOptions = [
        0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16, 17, 18, 19, 20, 21, 22, 23,
    ];

    public function save(array $options = []): void
    {
        $this->email = mb_strtolower($this->email);

        parent::save($options);
    }

    /* Static */

    protected static function staticDataTypes()
    {
        static $dataTypes = [];

        if (! $dataTypes) {
            $dataTypes = [
                'invalidEmails' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'invalidEmails']),
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
                    'id' => ['isPrimaryKey' => true, 'editType' => 'display'],
                    'status' => [
                        'type' => 'select',
                        'options' => StatusBooking::values()->toArray(),
                        'editType' => 'display',
                    ],
                    'reject_reason' => [
                        'type' => 'display',
                        'searchType' => '',
                    ],
                    'label' => [
                        'type' => 'display',
                        'searchType' => '',
                    ],
                    'listingID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('listingID') == 'display')
                            && $model->listing ? $model->listing->fullDisplayName() : $model->listingID;
                        }, ],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('userID') == 'display')
                            && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'importedID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'bookingID' => ['maxLength' => 50],
                    'internalBookingID' => ['maxLength' => 250],
                    'system' => ['type' => 'select', 'options' => ImportSystems::allNamesKeyedByDisplayName(), 'optionsDisplay' => 'keys'],
                    'bookingTime' => ['searchType' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateTimeDataType'],
                    'startDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'nights' => ['type' => 'number', 'dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax', 'sanitize' => 'int'],
                    'people' => ['type' => 'number', 'dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax', 'sanitize' => 'int'],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'email' => ['maxLength' => 255],
                    'invalidEmails' => ['dataTypeObject' => self::staticDataTypes()['invalidEmails'], 'editType' => 'multi', 'maxLength' => 255],
                    'firstName' => ['maxLength' => 100],
                    'lastName' => ['maxLength' => 100],
                    'phone' => ['maxLength' => 40],
                    'nationality' => ['maxLength' => 100],
                    'gender' => ['type' => 'select', 'options' => self::$genderOptions, 'optionsDisplay' => 'translate'],
                    'arrivalTime' => ['type' => 'select', 'options' => self::$arrivalTimeOptions, 'optionsDisplay' => 'translate'],
                    'depositUSD' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax'],
                    'commission' => ['searchType' => 'minMax', 'dataType' => 'Lib\dataTypes\NumericDataType'],
                    'origination' => ['maxLength' => 500],
                    'affiliateID' => [],
                    'affiliateCommission' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'searchType' => 'minMax'],
                    'device' => [
                        'type' => 'select',
                        'options' => DeviceBooking::values()->toArray(),
                        'editType' => 'display',
                    ],
                    'messageText' => ['type' => 'display', 'searchType' => ''], // used in old bookings
                    'bookingDetails' => ['type' => 'display', 'searchType' => ''], // (temp -- TO DO: should properly format and display this info)
                ];

                if ($purpose == 'staffEdit') {
                    $staffEditable = ['email'];
                    $staffIgnore = ['id', 'commission'];
                    FieldInfo::fieldInfoType($fieldInfos, $staffEditable, $staffIgnore);
                }

                break;

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
                $output .= "\nOptimimize booking cache table.\n";
                // This needs to be done daily because it gets large fast if not optimized.
                DB::statement('OPTIMIZE TABLE bookingCache');

                $output .= "\nSend After-Stay Emails:\n";
                $bookings = self::where('email', '!=', '')->whereRaw("DATE_ADD(startDate, INTERVAL nights DAY)='" . date('Y-m-d') . "'")
                    ->groupBy('email', 'listingID')->with('listing')->get();
                foreach ($bookings as $booking) {
                    $output .= "$booking->id ";
                    $booking->sendAfterStayEmail();
                }

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    /* Accessors & Mutators */

    /* Misc */

    public function validateAndSave($trackingCode = '')
    {
        // Validation

        if ($this->bookingID === '') {
            logError('Missing bookingID.');

            return false;
        }
        if ($this->system === '') {
            logError('Missing system.');

            return false;
        }
        if (self::where('system', $this->system)->where('bookingID', $this->bookingID)->exists()) {
            logError("Booking $this->bookingID for $this->system already exists.");

            return false;
        }
        if (! $this->importedID) {
            logError('Missing importedID.');

            return false;
        }
        if (! $this->bookingTime) {
            logError('Missing bookingTime.');

            return false;
        }

        if (! $this->listingID) {
            $this->listingID = $this->imported->hostelID;
        }

        // Misc

        if ($trackingCode !== '') {
            BookingClick::fillInBookingFieldsFromMatchingClick($this, $trackingCode);
        }
        $this->setAffiliateIdAndCommission();
        $this->automaticallyAssignToUserWithMatchingEmail();
        $this->awardPoints();

        // Save
        $this->save();

        EventLog::log(
            category: 'system',
            action: 'addBookingInfo',
            subjectType: 'booking',
            subjectID: $this->id,
            subjectString: "{$this->system}:{$this->status->value}:{$this->device->value}",
        );

        return true;
    }

    public function setAffiliateIdAndCommission(): void
    {
        // Find if there is a user that is an affiliate for this booking

        $user = null;
        if ($this->affiliateID) {
            $user = User::find($this->affiliateID);
        } elseif ($this->origination != '') {
            // Determine affiliateID from the origination URL
            $affiliateUrlToUserMap = User::getAllAffiliateURLs();
            foreach ($affiliateUrlToUserMap as $url => $urlUser) {
                if (stripos($this->origination, $url) === 0) {
                    $user = $urlUser;

                    break;
                }
            }

            // If no regular affiliate, check to see if one of our marketers is reasponsible for the link...
            if (! $user || $user->status != 'ok') {
                $user = IncomingLink::findMarketingUserToPayForBooking($this->origination);
            }
        }

        if (! $user || $user->status != 'ok') {
            return;
        }

        // Set affiliateID and affiliateCommission

        $this->affiliateID = $user->id;
        $this->affiliateCommission = round($this->depositUSD * ((float) $user->payAmounts['affiliatePercent'] / 100), 2);
        if ($this->affiliateCommission > $this->commission) {
            $this->affiliateCommission = $this->commission;
        }
    }

    public function automaticallyAssignToUserWithMatchingEmail(): void
    {
        if ($this->userID) {
            return;
        } // if the user was logged in when they made the booking, the userID is already set
        $matchingUser = User::where('username', $this->email)->first();
        if ($matchingUser) {
            $this->userID = $matchingUser->id;
        }
    }

    public function awardPoints(): void
    {
        if (! $this->user || ! $this->depositUSD) {
            return;
        }
        $this->user->awardPoints('bookingCommissionDollar', $this->commission);
    }

    public function afterBookingCommentVerificationCode()
    {
        return substr(md5("IIFjasdf883FJoa $this->id fF8ow0(#8f"), 0, 10); // shortened to keep the URL short
    }

    public function sendAfterStayEmail(): void
    {
        if ($this->email === '' || ! $this->listing) {
            return;
        }
        $language = $this->language === '' ? 'en' : $this->language;
        $verificationURL = $this->getAfterBookingRatingLink($language);
        if (is_null($verificationURL)) {
            logNotice("the rating submitted for booking {$this->id}");

            return;
        }

        $emailText = str_replace('<br>', "<br>\n", langGet(
            'submitRating.afterStayEmail',
            ['hostelName' => $this->listing->name, 'commentLink' => "<a href=\"$verificationURL\">$verificationURL</a>"],
            [],
            $language
        ));
        Emailer::send(
            $this->email,
            langGet('submitRating.afterStayEmailSubject', ['hostelName' => $this->listing->name], [], $language),
            'generic-email',
            ['text' => $emailText],
            logCategory: 'system',
            logUserID: $this->user->id ?? 0
        );
    }

    public function getAfterBookingRatingLink(string $language = 'en')
    {
        return ! $this->isRatingSubmitted()
            ? routeURL('afterBookingRating', [$this->id, $this->afterBookingCommentVerificationCode()], 'publicSite', $language)
            : null;
    }

    public function isRatingSubmitted(): bool
    {
        return Rating::where('ourBookingID', $this->id)->exists();
    }

    public function getImportSystem(): ImportSystems
    {
        return ImportSystems::findByName($this->system);
    }

    public function getBookingIdDisplayString()
    {
        return $this->getImportSystem()->shortName() . ' ' . $this->bookingID;
    }

    public function parseFirstAndLastName($name): void
    {
        $result = parseFirstAndLastName($name);
        if ($result) {
            $this->firstName = $result['firstName'];
            $this->lastName = $result['lastName'];
        }
    }

    public function displayName()
    {
        return trim($this->firstName . ' ' . $this->lastName);
    }

    /* Relationships */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'userID');
    }

    public function imported(): BelongsTo
    {
        return $this->belongsTo(Imported::class, 'importedID');
    }

    public function listing(): BelongsTo
    {
        return $this->belongsTo(Listing::class, 'listingID');
    }
}
