<?php

namespace App\Booking;

use App\Models\Languages;
use Carbon\Carbon;
use Exception;
use Lib\Currencies;
use Validator;

class SearchCriteria
{
    public static $searchFormFieldNames = ['startDate', 'nights', 'roomType', 'people', 'rooms', 'groupType', 'groupAgeRanges', 'currency'];

    public $startDate; // Carbon object

    public $nights;

    public $roomType; // 'dorm' or 'private'

    public $people;

    public $rooms; // only used for private rooms

    public $groupType; // only used for groups

    public $groupAgeRanges; // only used for groups

    public $currency; // optional ('' means use the local currency)

    public $language; // included because results may be cached with rooms in a language

    public $userIp;

    // These are roughly based on Hostelworld/HostelBookers categories / ages.
    public const GROUP_TYPE_OPTIONS = ['friends', 'juniorSchool', 'highSchool', 'college', 'business', 'party', 'sports', 'cultural'];

    public const GROUP_AGE_RANGE_OPTIONS = ['0to12', '13to17', '18to21', '22to35', '36to49', '50plus'];

    public const MONTHS_BOOKABLE_IN_ADVANCE = 12;

    public const MAX_NIGHTS = 31;

    public const MAX_NON_GROUP_PEOPLE = 7;

    public const MAX_PEOPLE = 80;

    public const MAX_ROOMS = 10;

    public const DEFAULT_START_DATE_DAYS_FROM_NOW = 2;

    public function __construct($properties = [])
    {
        $this->language = Languages::currentCode(); // defaults to the user's current language
        $this->userIp = $this->getUserIp();

        foreach ($properties as $property => $value) {
            $this->$property = $value;
        }
    }

    public function hasGroupInfo()
    {
        return $this->people > self::MAX_NON_GROUP_PEOPLE;
    }

    // Create a unique value representing this search's parameters (used for cache keys)

    public function hashValue()
    {
        $values = $this->startDate->format('Y-m-d') . ":$this->nights:$this->roomType:$this->people:$this->currency:$this->language";
        if ($this->roomType == 'private') {
            $values .= ":$this->rooms";
        }
        if ($this->hasGroupInfo()) {
            $values .= ":$this->groupType:" . implode(',', (array) $this->groupAgeRanges);
        }

        return md5($values);
    }

    // be sure the startDate time is all zeros for consistency (only the date is significant)
    // (not currently used, but might be useful)

    public function setTimePortionOfStartDateToZero()
    {
        zeroTimePartOfDate($this->startDate); // for consistency (only the date matters, not the time)

        return $this; // for chaining
    }

    // Returns/set just the values that are relevant to what is actually displayed on the booking search form and stored in the booking search cookie.

    public function bookingSearchFormFields($setFieldsTo = null)
    {
        if ($setFieldsTo) {
            // Set $this from form input
            foreach (self::$searchFormFieldNames as $fieldName) {
                $value = $setFieldsTo[$fieldName] ?? null;
                if ($value === null) {
                    continue;
                }
                switch ($fieldName) {
                    case 'startDate':
                        try {
                            $this->startDate = carbonFromDateString($value);
                        } catch (Exception $e) {
                            logWarning("Invalid search criteria date field value '$value'.");
                            $this->startDate = null; // the value was invalid
                        }

                        break;
                    case 'nights':
                    case 'people':
                    case 'rooms':
                        $this->$fieldName = (int) $value;

                        break;
                    default:
                        $this->$fieldName = $value;
                }
            }
        } else {
            // Get form value from $this
            $return = [];
            foreach (self::$searchFormFieldNames as $fieldName) {
                switch ($fieldName) {
                    case 'startDate':
                        $return['startDate'] = ($this->startDate ? $this->startDate->format('Y-m-d') : date('Y-m-d'));

                        break;
                    case 'nights':
                        $return['nights'] = ($this->nights ? $this->nights : self::DEFAULT_START_DATE_DAYS_FROM_NOW);

                        break;
                    default:
                        $return[$fieldName] = ($this->$fieldName === null ? '' : $this->$fieldName);
                }
            }

            return $return;
        }
    }

    // An error message, or null if no errors.

    public function getValidationError()
    {
        $validations = [
            'startDate' => 'required',
            'nights' => 'numeric|min:1|max:' . self::MAX_NIGHTS,
            'roomType' => 'in:dorm,private',
            'people' => 'numeric|min:1|max:' . self::MAX_PEOPLE,
            'rooms' => ($this->roomType == 'private' ? 'numeric|min:1|max:' . self::MAX_ROOMS : ''),
        ];

        if ($this->hasGroupInfo()) {
            $validations = array_merge($validations, [
                'groupType' => 'required|in:' . implode(',', self::GROUP_TYPE_OPTIONS),
                'groupAgeRanges' => 'required',
            ]);

            if (! is_array($this->groupAgeRanges)) {
                $this->groupAgeRanges = [];
            }
            foreach ($this->groupAgeRanges as $value) {
                if (! in_array($value, self::GROUP_AGE_RANGE_OPTIONS)) {
                    return langGet('validation.in', ['attribute' => langGet('bookingProcess.searchCriteria.groupAgeRanges')]);
                }
            }
        }

        if ($this->currency != '' && ! Currencies::isKnownCurrencyCode($this->currency)) { // shouldn't happen normally
            return langGet('bookingProcess.errors.misc');
        }

        $validator = Validator::make(get_object_vars($this), $validations);
        $validator->setAttributeNames(langGet('bookingProcess.searchCriteria'));
        if ($validator->fails()) {
            return $validator->messages()->first();
        }

        if ($this->startDate->lt(Carbon::today())) {
            return langGet('bookingProcess.errors.pastDate');
        }

        return null;
    }

    public function setToDefaults($setNullStartDate = true)
    {
        $this->startDate = $setNullStartDate ? null : Carbon::now()->addDays(self::DEFAULT_START_DATE_DAYS_FROM_NOW);
        $this->nights = self::DEFAULT_START_DATE_DAYS_FROM_NOW;
        $this->people = $this->rooms = 1;
        $this->roomType = 'dorm';
        $this->groupType = 'friends';
        $this->groupAgeRanges = [];
        $this->currency = ''; // '' means it will use the local currency of the listing
        $this->language = Languages::currentCode();

        return $this; // for chaining
    }

    public function getEndDate()
    {
        return $this->startDate->copy()->addDays($this->nights);
    }

    public function numberOfBookableBlocksRequested()
    {
        return $this->roomType == 'private' ? $this->rooms : $this->people;
    }

    public function summaryForDebugOutput()
    {
        $values = $this->startDate->format('Y-m-d') . " n:$this->nights $this->roomType p:$this->people $this->currency $this->language";
        if ($this->roomType == 'private') {
            $values .= " r:$this->rooms";
        }
        if ($this->hasGroupInfo()) {
            $values .= " $this->groupType:" . implode(',', (array) $this->groupAgeRanges);
        }

        return $values;
    }

    private function getUserIp(): ?string
    {
        $ip = request()?->ip();

        return $ip !== '127.0.0.1' ? $ip : null;
    }
}
