<?php

namespace App\Models\Listing;

use App\Models\Exeption;
use Exception;
use Lang;
use Lib\BaseModel;

/*

This extends our BaseModel so that some important methods work that are used by FormHandler, but we don't actually use Eloquent with it (we handle things like get/set/save).

*/

class ListingFeatures extends BaseModel
{
    private $listing;

    protected $fillable = [
        'id',
    ];
    /*
        $features array:

            'category' - 'amenities', 'goodFor', 'allowed', 'restrictions', 'details'
                - Used for dividing the features into categories on the listing page when they're displayed.
            'optionsDisplay' - 'translate' (default) or 'translateKeys'
    */

    private static $features = [
        // * amenities *

        // Yes/No

        'cc' => ['category' => 'amenities', 'featureType' => 'yesNo', 'displayIfNo' => true, 'validation' => 'required'],
        'lounge' => ['category' => 'amenities', 'featureType' => 'yesNo', 'displayIfNo' => true, 'validation' => 'required'],
        'kitchen' => ['category' => 'amenities', 'featureType' => 'yesNo', 'displayIfNo' => true, 'validation' => 'required'],
        'allNonsmoking' => ['category' => 'amenities', 'featureType' => 'yesNo', 'displayIfNo' => true, 'validation' => 'required'],
        'powerInRooms' => ['category' => 'amenities', 'featureType' => 'yesNo', 'displayIfNo' => true], // (could just move this to the features list)
        'wheelchair' => ['category' => 'amenities', 'featureType' => 'yesNo', 'displayIfNo' => true],

        // Free/Pay

        'airportPickup' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true, 'validation' => 'required'],
        'bikeRental' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => false],
        'breakfast' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true],
        'dinner' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => false],
        'lockersInCommons' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => false],
        'lockersInRoom' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true],
        'luggageStorage' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true, 'validation' => 'required'],
        'parking' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true],
        'pubCrawls' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => false],
        'safeDepositBox' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => false],
        'sheets' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true],
        'tours' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => false],
        'towels' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true],
        'wifiCommons' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true, 'validation' => 'required'],
        'wifiRooms' => ['category' => 'amenities', 'featureType' => 'price', 'displayIfNo' => true],

        // Checkboxes

        'extras' => ['category' => 'amenities', 'featureType' => 'checkboxes',
            'options' => [
                '24HourSecurity', 'ac', 'atm', 'bar', 'bbq', 'beach', 'bike_tours', 'board_games', 'books', 'cableTV',
                'camping', 'darts', 'dryer', 'elevator', 'evening_entertainment', 'exchange', 'food', 'gameroom', 'gym', 'hairdryers', 'hotShowers', 'hottub', 'info', 'karaoke', 'laundry', 'live_music_performance', 'meeting_banquet_facilities', 'movies', 'nightclub', 'noBunkbeds', 'outdoorSeating', 'pooltable', 'powerAtEachBed', 'privacyCurtains', 'sport', 'swimming', 'table_tennis', 'themed_dinner_nights', 'tour_class_local_culture', 'tv', 'videoGames', 'walking_tours', 'work', 'yoga_classes',
            ],
        ],

        // * goodFor *

        'goodFor' => ['category' => 'goodFor', 'featureType' => 'checkboxes',
            'options' => [
                'adventure_hostels', 'beach_hostels', 'business', 'couples', 'families', 'female_solo_traveller', 'groups', 'partying', 'quiet', 'seniors',
                'socializing', 'youth_hostels',
            ],
        ],

        // * room types

        'roomTypes' => [
            'category' => 'roomTypes',
            'featureType' => 'checkboxes',
            'options' => [
                'female_only_dorms',
            ],
        ],

        // * allowed *

        'petsAllowed' => ['category' => 'allowed', 'featureType' => 'yesNo', 'displayIfNo' => true],
        'serviceAnimals' => ['category' => 'allowed', 'featureType' => 'yesNo', 'displayIfNo' => true],

        // * restrictions *

        'minAgeWithout' => ['category' => 'restrictions', 'featureType' => 'select',
            'options' => ['allAges', '14', '15', '16', '17', '18', '19', '20', '21'], 'optionsLangKey' => 'ListingFeatures.forms.options.ages',
        ],
        'minAgeDorm' => ['category' => 'restrictions', 'featureType' => 'select',
            'options' => ['allAges', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21'],
            'optionsLangKey' => 'ListingFeatures.forms.options.ages',
        ],
        'minAgePriv' => ['category' => 'restrictions', 'featureType' => 'select',
            'options' => ['allAges', '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12', '13', '14', '15', '16', '17', '18', '19', '20', '21'],
            'optionsLangKey' => 'ListingFeatures.forms.options.ages',
        ],
        /* todo: (after moving to new site) Split this into maxAgeDorm and maxAgePriv, and tell Kristy when done so she can reply to the email about it. */
        'maxAge' => ['category' => 'restrictions', 'featureType' => 'select',
            'options' => ['allAges', '30', '35', '40', '45', '50', '55', '60', '65', '70', '75'], 'optionsLangKey' => 'ListingFeatures.forms.options.ages',
        ],
        'gender' => ['category' => 'restrictions', 'featureType' => 'select',
            'options' => ['maleAndFemale', 'maleOnly', 'femaleOnly'],
        ],
        'specialRestrictions' => ['category' => 'restrictions', 'featureType' => 'string'],

        // * details *

        'reception' => ['category' => 'details', 'featureType' => 'radioOrString', 'validation' => 'required',
            'options' => ['24hours', 'limitedHours'], 'lastOptionStringInput' => 'ListingFeatures.forms.lastOptionStringInput.openHours',
        ],
        'curfew' => ['category' => 'details', 'featureType' => 'select', 'optionsDisplay' => 'translateKeys',
            'options' => ['noCurfew' => 'noCurfew', 'hoursOfTheDay.time_21' => 'time_21', 'hoursOfTheDay.time_22' => 'time_22', 'hoursOfTheDay.time_23' => 'time_23',
                'hoursOfTheDay.time_0' => 'time_0', 'hoursOfTheDay.time_1' => 'time_1', 'hoursOfTheDay.time_2' => 'time_2', 'hoursOfTheDay.time_3' => 'time_3', ],
        ],
        'checkout' => ['category' => 'details', 'featureType' => 'select', 'validation' => 'required', 'optionsDisplay' => 'translateKeys',
            'options' => ['flexibleTime' => 'flexibleTime', 'hoursOfTheDay.time_9' => 'time_9', 'hoursOfTheDay.time_10' => 'time_10', 'hoursOfTheDay.time_11' => 'time_11',
                'hoursOfTheDay.time_12' => 'time_12', 'hoursOfTheDay.time_13' => 'time_13', 'hoursOfTheDay.time_14' => 'time_14', 'hoursOfTheDay.time_15' => 'time_15',
                'hoursOfTheDay.time_16' => 'time_16', 'hoursOfTheDay.time_17' => 'time_17', 'hoursOfTheDay.time_18' => 'time_18', 'hoursOfTheDay.time_19' => 'time_19', ],
        ],
        'openDates' => ['category' => 'details', 'featureType' => 'radioOrString',
            'options' => ['allYear', 'seasonal'],
            'lastOptionStringInput' => 'ListingFeatures.forms.lastOptionStringInput.seasonalDates',
        ],
        'lockout' => ['category' => 'details', 'featureType' => 'radioOrString', 'optionsDisplay' => 'translateKeys',
            'options' => ['global.no' => 'no', 'global.yes' => 'yes'],
            'lastOptionStringInput' => 'ListingFeatures.forms.lastOptionStringInput.lockoutHours',
        ],
        // not required because apartments won't know how to answer.
        'size' => ['category' => 'details', 'featureType' => 'select',
            'options' => ['sizeSm', 'sizeMed', 'sizeLg', 'sizeVLg'],
        ],
        'minStay' => ['category' => 'details', 'featureType' => 'radioOrString',
            'options' => ['minStay1', 'minStay2', 'minStay3', 'minStayOther'], 'lastOptionStringInput' => 'ListingFeatures.forms.lastOptionStringInput.minStayNights',
        ],
        'maxStay' => ['category' => 'details', 'featureType' => 'radioOrString',
            'options' => ['noMaxStay', 'maxStay7', 'maxStay14', 'maxStay30', 'maxStayOther'],
            'lastOptionStringInput' => 'ListingFeatures.forms.lastOptionStringInput.maxStayNights',
        ],

        /*

        Consider adding:
            - internet computers
            - sauna
        */
    ];

    public function __construct(Listing $listing)
    {
        $this->listing = $listing;
    }

    /* This is just a sanity check to make sure the $value is a legal value, otherwise throw an exception. */

    public static function validateValue($fieldName, $value): void
    {
        if ($value == '') {
            return;
        }

        $featureInfo = self::$features[$fieldName];

        switch ($featureInfo['featureType']) {
            case 'checkboxes':
                if (! is_array($value)) {
                    throw new Exeption("'value' for $fieldName is not an array.");
                }
                if (array_unique($value) != $value) {
                    throw new Exeption("array_unique test failed. Possible duplicate values in $fieldName.");
                }
                foreach ($value as $v) {
                    if (! in_array($v, $featureInfo['options'])) {
                        throw new Exception("'$v' not in $fieldName's options.");
                    }
                }

                break;

            case 'select':
                if (! in_array($value, $featureInfo['options'])) {
                    throw new Exception("'$value' not in $fieldName's options.");
                }

                break;

            case 'yesNo':
                if (! in_array($value, ['no', 'yes'])) {
                    throw new Exception("'value' for $fieldName's not yes/no.");
                }

                break;

            case 'price':
            case 'radioOrString':
            case 'string':
                // Nothing to check for these.
                break;
        }
    }

    // Merge two sets of features into one set.  If any overlap, $primary takes precidence.
    public static function merge($primary, $secondary, $mergeCheckboxes = true)
    {
        if (! $secondary) {
            return $primary;
        }
        if (! $primary) {
            return $secondary;
        }

        foreach ($secondary as $fieldName => $secondaryValue) {
            $primary[$fieldName] = self::mergeFeature($fieldName, $primary[$fieldName] ?? null, $secondaryValue);
        }

        return $primary;
    }

    public static function mergeFeature($fieldName, $primaryValue, $secondaryValue)
    {
        if (! isset($secondaryValue) || $secondaryValue === '' || $primaryValue == $secondaryValue) {
            return $primaryValue;
        }
        if (! isset($primaryValue) || $primaryValue === '') {
            return $secondaryValue;
        }

        $featureInfo = self::$features[$fieldName];

        switch ($featureInfo['featureType']) {
            case 'checkboxes':
                // Note array_values() is so we don't end up with weird array keys if array_unique() removes an element.
                $result = array_values(array_unique(array_merge($primaryValue, $secondaryValue)));

                break;

            case 'price':
                // Note: 'free' or 'pay' is more specific, so that's a higher priority than just 'yes' or 'no'.
                $valuePriorities = ['yes' => 1, 'no' => 1, 'free' => 2, 'pay' => 2]; // higher values are higher priority
                // Values that aren't in the $valuePriorities array are given a high priority of 10 because other values are better (more specific).
                $primaryPriority = (array_key_exists($primaryValue, $valuePriorities) ? $valuePriorities[$primaryValue] : 10);
                $secondaryPriority = (array_key_exists($secondaryValue, $valuePriorities) ? $valuePriorities[$secondaryValue] : 10);
                $result = ($secondaryPriority > $primaryPriority ? $secondaryValue : $primaryValue);

                break;

            default:
                // For other types we just ignore $secondary and let the $primary value stand.
                $result = $primaryValue;

                break;
        }

        self::validateValue($fieldName, $result);

        return $result;
    }

    /*
        $importedFeatureCodes - Array of imported codes (their codes).
        $map - Array of
            [ 'theirCode' =>
                'ourFeature' - Sets the feature to 'yes'.
                [ 'ourFeature' => 'specificValue' ] - Sets our feature to the specified value.
                [ 'ourFeature' => 'checkboxesValue' ] - Selects the checkbox checkboxesValue of ourFeature.
                [ 'ourFeature' => [ 'checkboxesValue1', 'checkboxesValue2' ] ] - Selects multiple checkbox checkboxesValues of ourFeature.
                null - Ignore this feature.
        ]
    */

    public static function mapFromImportedFeatures(array $importedFeatureCodes, $map): array
    {
        $result = [];

        foreach ($importedFeatureCodes as $importedCode) {
            $importedCode = (string) $importedCode; // convert it in case it's an XML object or whatever.

            if (! array_key_exists($importedCode, $map)) {
                logWarning("'$importedCode' not found in feature map.");

                continue;
            }

            $mapsTo = $map[$importedCode];
            if ($mapsTo == null) {
                continue;
            } // ignored feature

            if (is_array($mapsTo) && count($mapsTo) > 0) {
                foreach ($mapsTo as $featureName => $value) {
                    // Handle [ 'ourFeature' => 'specificValue' ] or [ 'ourFeature' => 'checkboxesValue' ]

//                    $featureName = key($mapsTo);
//                    $value = reset($mapsTo);

                    if (! isset(self::$features[$featureName])) {
                        continue;
                    }

                    // Special handling for certain featureTypes
                    switch (self::$features[$featureName]['featureType']) {
                        case 'checkboxes':
                            // [ ourFeature => checkboxesValue ]
                            if (! is_array($value)) {
                                $value = [$value];
                            }

                            break;
                    }

                    // We do a merge rather than just setting it just in case multiple imported features mapped to the same feature in our system.
                    $result[$featureName] = self::mergeFeature($featureName, $result[$featureName] ?? null, $value);
                }
            } else {
                // Handle 'ourFeature' - Sets the feature to 'yes'.

                $featureName = $mapsTo;

                // just the feature name
                switch (self::$features[$featureName]['featureType']) {
                    case 'yesNo':
                    case 'price': // we let them set price items to just be 'yes'
                        $value = 'yes';

                        break;

                    default:
                        if (isset(self::$features[$featureName]['options']) && in_array('yes', self::$features[$featureName]['options'])) {
                            $value = 'yes';
                        } else {
                            throw new Exception("Don't know how to handle just the feature name for '$importedCode'.");
                        }
                }

                // We do a merge rather than just setting it just in case multiple imported features mapped to the same feature in our system.
                $result[$featureName] = self::mergeFeature($featureName, $result[$featureName] ?? null, $value);
            }
        }

        return $result;
    }

    /* Create a fieldInfo[] array for displaying a FormHandler form. */

    public static function fieldInfo($enforceValidation = false, $longOrShortLabel = 'short')
    {
        $result = [];
        foreach (self::$features as $featureCode => $feature) {
            switch ($feature['featureType']) {
                case 'checkboxes':
                    $result[$featureCode] = [
                        'type' => 'checkboxes', 'options' => $feature['options'],
                        'optionsDisplay' => $feature['optionsDisplay'] ?? 'translate',
                    ];

                    break;

                case 'yesNo':
                    $result[$featureCode] = [
                        'type' => 'radio', 'searchType' => 'checkboxes',
                        'options' => ['no', 'yes'], 'optionsDisplay' => 'translate', 'optionsLangKey' => 'global',
                    ];

                    break;

                case 'price':
                    $result[$featureCode] = [
                        'type' => 'radio',
                        'options' => ['no', 'free', 'pay'], 'optionsDisplay' => 'translate', 'optionsLangKey' => 'global',
                        'lastOptionStringInput' => 'ListingFeatures.forms.lastOptionStringInput.price', 'maxLength' => 15,
                    ];

                    break;

                case 'radioOrString':
                    $result[$featureCode] = [
                        'type' => 'radio', 'searchType' => 'checkboxes',
                        'options' => $feature['options'], 'optionsDisplay' => $feature['optionsDisplay'] ?? 'translate',

                    ];
                    if (isset($feature['lastOptionStringInput'])) {
                        $result[$featureCode]['lastOptionStringInput'] = $feature['lastOptionStringInput'];
                    }
                    $result[$featureCode]['maxLength'] = 50;

                    break;

                case 'select':
                    $result[$featureCode] = [
                        'type' => 'select',
                        'options' => $feature['options'], 'optionsDisplay' => $feature['optionsDisplay'] ?? 'translate',
                    ];

                    break;

                case 'string':
                    $result[$featureCode] = [
                        'type' => 'string',
                    ];

                    break;
            }

            if ($enforceValidation && array_key_exists('validation', $feature)) {
                $result[$featureCode]['validation'] = $feature['validation'];
            }

            if ($longOrShortLabel == 'long') {
                $key = 'ListingFeatures.forms.fieldLabel_long.' . $featureCode;
                if (Lang::has($key)) {
                    $result[$featureCode]['fieldLabelLangKey'] = $key;
                }
            }

            // A few have a special 'optionsLangKey' source of the language for the options.
            if (isset($feature['optionsLangKey']) && $feature['optionsLangKey'] !== '') {
                $result[$featureCode]['optionsLangKey'] = $feature['optionsLangKey'];
            }
        }

        return $result;
    }

    /*
        Returns an array of categories (from the 'category' element of self::$features[]), like this...

            category => [   (Category name not displayed, just used for knowing where to put a <hr> tag to separate the categories.)
                subcategory => [   (See lang/en/ListingFeatures.php for the list of subcategory names.)
                    [
                        'displayType' - 'yes', 'no', 'pay', 'free', 'labelValuePair' - Tells how to display it (and what icon to use).
                        'label'
                        'value' (for 'labelValuePair' only)
                    ],
                    ...
                ],
                ...
            ]
    */

    public static function getDisplayValues($features)
    {
        if (! $features) {
            return [];
        }

        $fieldInfo = self::fieldInfo();

        $return = [];

        foreach ($features as $featureName => $value) {
            if ($value === '') {
                continue;
            } // empty value, ignore

            $featureInfo = self::$features[$featureName];
            $featureFieldInfo = $fieldInfo[$featureName];

            $addItems = [];
            switch ($featureInfo['featureType']) {
                case 'checkboxes':
                    if (! $value) {
                        continue 2;
                    } // empty
                    if (! is_array($value)) {
                        throw new Exception('Checkbox but not array.');
                    }

                    //  temp: fix duplication
                    $value = array_unique($value);

                    foreach ($value as $v) {
                        $addItems[] = ['displayType' => 'yes', 'label' => self::getDisplayTextForOption($featureName, $featureFieldInfo, $v)];
                    }

                    break;

                case 'yesNo':
                    if ($value == 'no' && ! $featureInfo['displayIfNo']) {
                        continue 2;
                    }
                    $addItems[] = ['displayType' => $value, 'label' => self::getLabel($featureName)];

                    break;

                case 'price':
                    if ($value == 'no' && ! $featureInfo['displayIfNo']) {
                        continue 2;
                    }

                    if ($value == 'no') {
                        $displayType = 'no';
                    } elseif ($value == 'free') {
                        $displayType = 'free';
                    } elseif ($value !== '') {
                        $displayType = 'pay';
                    } else {
                        continue 2;
                    } // no value

                    $addItems[] = ['displayType' => $displayType, 'label' => self::getLabel($featureName),
                        'value' => ($displayType == 'pay' && ! in_array($value, ['yes', 'pay']) ? $value : ''),
                    ];

                    break;

                case 'radioOrString':
                    $addItems[] = ['displayType' => 'labelValuePair', // 'option' : 'string',
                        'label' => self::getLabel($featureName),
                        'value' => in_array($value, $featureInfo['options']) ?
                            self::getDisplayTextForOption($featureName, $featureFieldInfo, $value) :
                            $value,
                    ];

                    break;

                case 'select':
                    $addItems[] = ['displayType' => 'labelValuePair', 'label' => self::getLabel($featureName),
                        'value' => self::getDisplayTextForOption($featureName, $featureFieldInfo, $value),
                    ];

                    break;

                case 'string':
                    $addItems[] = ['displayType' => 'labelValuePair', 'label' => self::getLabel($featureName), 'value' => $value];

                    break;

                default:
                    throw new Exception("Unknown feature type '$featureInfo[featureType]'.");
            }

            foreach ($addItems as $item) {
                // Get the subcategory
                $subcategory = langGet("ListingFeatures.categories.$featureInfo[category].$item[displayType]", null); // some items have a label for the particular displayType
                if ($subcategory === null) {
                    $subcategory = langGet("ListingFeatures.categories.$featureInfo[category]", null);
                } // other items just have a general label for the category

                $return[$featureInfo['category']][$subcategory][] = $item;
            }
        }

        // * Sort *

        // Sort by category

        uksort($return, function ($categoryA, $categoryB) {
            $categorySortOrder = ['goodFor', 'amenities', 'allowed', 'restrictions', 'details', 'roomTypes'];
            $aScore = array_search($categoryA, $categorySortOrder);
            if ($aScore === false) {
                throw new Exception("Unknown category '$categoryA'.");
            }
            $bScore = array_search($categoryB, $categorySortOrder);
            if ($bScore === false) {
                throw new Exception("Unknown category '$categoryB'.");
            }

            return $aScore - $bScore;
        });

        foreach ($return as $category => &$subcategories) {
            // Sort each subcategory's features by the label
            foreach ($subcategories as $subcategoryName => &$features) {
                uasort($features, function ($a, $b) {
                    return strcmp($a['label'], $b['label']);
                });
            }
            unset($features); // break the reference with the last element just to be safe

            // Sort each category's subcategories based on the displayType
            uasort($subcategories, function ($a, $b) {
                // We just use the displayType of the first feature in each subcategory (works fine)
                $displayTypeA = reset($a)['displayType'];
                $displayTypeB = reset($b)['displayType'];

                $displayTypeOrder = ['free', 'pay', 'yes', 'no', 'labelValuePair'];

                return array_search($displayTypeA, $displayTypeOrder) - array_search($displayTypeB, $displayTypeOrder);
            });
        }
        unset($subcategories); // break the reference with the last element just to be safe

        return $return;
    }

    public static function getListingGoodForFeatures($features): array
    {
        $items = self::getDisplayValues($features);
        if (! $items) {
            return [];
        }

        if (empty($items['goodFor'][__('ListingFeatures.categories.goodFor')])) {
            return [];
        }

        return collect($items['goodFor'][__('ListingFeatures.categories.goodFor')])
            ->filter(fn ($item) => $item['displayType'] === 'yes')
            ->map(fn ($item) => $item['label'])
            ->toArray();
    }

    public static function getSoloTravelerFeatures($compiledFeatures)
    {
        $result = [];
        if (! empty($compiledFeatures['pubCrawls'])) {
            $result[] = langGet('ListingFeatures.forms.fieldLabel.pubCrawls');
        }
        if (! empty($compiledFeatures['tours'])) {
            $result[] = langGet('ListingFeatures.forms.fieldLabel.tours');
        }

        $extras = ! empty($compiledFeatures['extras']) ? $compiledFeatures['extras'] : [];
        foreach ($extras as $feature) {
            if (in_array($feature, ['darts', 'karaoke', 'karaoke', 'table_tennis', 'evening_entertainment',
                'meeting_banquet_facilities', 'walking_tours', 'bike_tours', 'themed_dinner_nights',
                'tour_class_local_culture', 'live_music_performance', 'gameroom', 'pooltable', ])) {
                $result[] = langGet("ListingFeatures.forms.options.extras.{$feature}");
            }
        }

        return $result;
    }

    public static function getPartyingFeatures($compiledFeatures)
    {
        $result = [];
        if (! empty($compiledFeatures['pubCrawls'])) {
            $result[] = langGet('ListingFeatures.forms.fieldLabel.pubCrawls');
        }

        $extras = ! empty($compiledFeatures['extras']) ? $compiledFeatures['extras'] : [];
        foreach ($extras as $feature) {
            if (in_array($feature, ['bar', 'nightclub'])) {
                $result[] = langGet("ListingFeatures.forms.options.extras.{$feature}");
            }
        }

        return $result;
    }

    public static function getGoodForFeatures()
    {
        return self::$features['goodFor']['options'];
    }

    /* These functions are here so they can be used by FormHandler when getting/setting/saving the object. */

    public function __get($name)
    {
        return $this->listing->mgmtFeatures[$name] ?? null;
    }

    public function __set($name, $value): void
    {
        // Have to do this so that it properly uses the getter/setter for mgmtFeatures.
        $temp = $this->listing->mgmtFeatures;
        $temp[$name] = $value;
        $this->listing->mgmtFeatures = $temp;
    }

    public function save(array $options = []): void
    {
        $this->listing->listingMaintenance()->compileFeatures();
        $this->listing->save();
    }
}
