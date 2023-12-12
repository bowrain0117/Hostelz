<?php

namespace App\Models;

use App\Models\Listing\Listing;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Collection;
use Laravel\Scout\Searchable;
use Lib\BaseModel;
use Lib\WebSearch;

/*

This actually uses the 'countries' table. (to do: rename the database table to countryInfo)

*/

class CountryInfo extends BaseModel
{
    use HasFactory;
    use Searchable;

    protected $table = 'countries';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $regionTypeOptions = ['Regions', 'States', 'IslandsRegions', 'Provinces', 'ProvincesRegions', 'ProvincesTerritories', 'Counties', 'StatesTerritories', 'Islands'];

    // Other
    public const COUNTRY_DESCRIPTION_MINIMUM_WORDS = 525;

    public const REGION_DESCRIPTION_MINIMUM_WORDS = 375;

    public const COUNTRY_DESCRIPTION_PAY = 9.00;

    public const REGION_DESCRIPTION_PAY = 7.00;

    private $miscDataCaches = []; // Just used to temporarily store results from getLiveReview(), etc. to avoid multiple database calls.

    private static $miscStaticDataCaches = [];

    public function save(array $options = []): void
    {
        parent::save($options);

        CityInfo::fixAllDisplaysRegionsValues(); // in case the regionType changed
    }

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $comparisonTypeOptions = ['equals', 'substring', 'isEmpty', 'notEmpty'];

                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'country' => ['maxLength' => 70, 'validation' => 'required', 'comparisonTypeOptions' => $comparisonTypeOptions],
                    'rememberCountryRenaming' => ['type' => 'ignore', 'editType' => 'checkbox', 'value' => true, 'fieldLabelText' => ' ',
                        'checkboxText' => 'Remember renaming rule if the country is changed.',
                        'getValue' => function ($formHandler, $model) {
                            return auth()->user()->hasPermission('admin'); /* defaults to on only for admin */
                        },
                        'setValue' => function ($formHandler, $model, $value): void { /* do nothing, this is handled by a 'setModelData' callback */
                        },
                    ],
                    'continent' => ['type' => 'select', 'options' => ContinentInfo::allNames(), 'comparisonTypeOptions' => $comparisonTypeOptions],
                    'regionType' => ['type' => 'select', 'options' => self::$regionTypeOptions, 'optionsDisplay' => 'translate',
                        'comparisonTypeOptions' => ['equals', 'isEmpty', 'notEmpty'], ],
                    'currencyCode' => ['maxLength' => 3, 'comparisonTypeOptions' => $comparisonTypeOptions],
                    'geonamesCountryID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'cityCount' => ['type' => 'display', 'searchType' => 'minMax', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'notes' => ['type' => 'textarea', 'rows' => 6],
                ];

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
            case 'weekly':
                $output .= 'Updating CountryInfo: ';

                foreach (self::get() as $countryInfo) {
                    if ($countryInfo->geonamesCountryID == 0) {
                        $countryIDs = $countryInfo->cityInfos()->where('gnCountryID', '!=', 0)->groupBy('gnCountryID')->pluck('gnCountryID');
                        // may just not have any hostels in the country. multiples are checked for by the DataChecksController.
                        if ($countryIDs->count() == 1) {
                            $countryID = $countryIDs->first();
                            $geonamesCountry = Geonames::getCountryByGeonamesID($countryID);
                            if (! $geonamesCountry) {
                                throw new Exception("Geonames country $countryID not found.");
                            }
                            $output .= "$countryInfo->country ($countryID $countryInfo->currencyCode) ";
                            $countryInfo->geonamesCountryID = $countryID;
                            $countryInfo->currencyCode = $geonamesCountry->currencyCode;
                            $countryInfo->save();
                            $output .= "[GeoNames update for $countryInfo->country] ";
                        }
                    }

                    $cityCount = CityInfo::areLive()->where('country', $countryInfo->country)->count();
                    $countryInfo->cityCount = $cityCount;
                    $countryInfo->save();
                    $output .= "[$countryInfo->country ($cityCount)] ";
                }

                $output .= "\n";

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    // This returns the name, whether it's an existing country or a country that is not yet in the countries database.

    public static function determineCountryNameFromCountryCode(string $countryCode, bool $existingCountriesOnly = false): string
    {
        $countryCode = strtoupper($countryCode);

        // Special cases

        switch ($countryCode) {
            case 'GB':
                return 'United Kingdom'; // (Our site uses Scotland, England, etc. as the country names, but 'GB' can be anywhere in the UK.

            case 'XC': // This code wasn't in the geonames data, but is used by Booking.com
                return 'Maldives';
        }

        // Find in Geonames

        $geonamesCountry = Geonames::getCountryByCountryCode($countryCode);
        if (! $geonamesCountry) {
            logWarning("no country for countryCode: {$countryCode}");

            return '';
        }

        // If it's an existing country, use our name for the country

        $countryInfo = self::where('geonamesCountryID', $geonamesCountry->geonamesID)->first();
        if ($countryInfo) {
            return $countryInfo->country;
        }

        if ($existingCountriesOnly) {
            return '';
        }

        return $geonamesCountry->country;
    }

    /* Accessors & Mutators */

    /* Misc */

    public function displaysRegions()
    {
        return $this->regionType != '';
    }

    public function getURL($urlType = 'auto', $language = null)
    {
        return CityInfo::makeCitiesUrl('', $this->country, '', $urlType, $language);
    }

    public function getRegionURL($regionName, $urlType = 'auto', $language = null, $ignoreDisplaysRegions = false)
    {
        if (! $ignoreDisplaysRegions && ! $this->displaysRegions()) {
            throw new Exception("This country isn't supposed to have region URLs.");
        }

        return CityInfo::makeCitiesUrl($regionName, $this->country, '', $urlType, $language);
    }

    public function getContinentURL($urlType = 'auto', $language = null)
    {
        if ($this->continent == '') {
            throw new Exception("Continent missing for continent URL for countryInfo $this->id.");
        }

        return CityInfo::makeCitiesUrl('', '', $this->continent, $urlType, $language);
    }

    public function countryCode()
    {
        if (! $this->geonames) {
            return null;
        }

        switch ($this->country) {
            case 'England':
                return 'GB-ENG';
            case 'Wales':
                return 'GB-WLS';
            case 'Ireland':
                return 'IE';
            case 'Scotland':
                return 'GB-SCT';
            case 'Northern Ireland':
                return 'GB-NIR';
            default:
                return $this->geonames->countryCode;
        }
    }

    public function translation($language = null, $useCache = true)
    {
        if ($language == null) {
            $language = Languages::currentCode();
        }
        if ($language == 'en') {
            return $this;
        } // no translation neededz

        if ($useCache) {
            $cached = $this->miscDataCaches['translation'][$language] ?? null;
            if ($cached) {
                return $cached;
            }
        }

        $result = clone $this;

        if ($this->geonamesCountryID && ($geoname = $this->geonames) && ($translation = $geoname->getTranslation($language)) != '') {
            $result->country = $translation;
        }
        if ($this->continent != '') {
            $result->continent = $this->continentInfo()->translation($language);
        }

        $this->miscDataCaches['translation'][$language] = $result;

        return $result;
    }

    public function regionFullDisplayName($region, $ignoreDisplaysRegions = false)
    {
        if (! $ignoreDisplaysRegions && ! $this->displaysRegions()) {
            throw new Exception("This country isn't supposed to display regions.");
        }

        return $region . ', ' . $this->translation()->country;
    }

    // $forRegion - Set to the region, or '' to get the region description
    // Use $language = null to get a review in the current language, or '' to get the first description in any language.

    public function getLiveDescription($region = '', $language = null, $useCache = true)
    {
        if ($language === null) {
            $language = Languages::currentCode();
        }

        if ($useCache) {
            $cached = $this->miscDataCaches['description'][$region][$language] ?? null;
            if ($cached) {
                return $cached;
            }
        }
        $result = self::attachedTexts()->where('subjectString', $region)->where('type', 'description')->where('language', $language)
            ->where('status', 'ok')->orderBy('id', 'desc')->first();
        $this->miscDataCaches['description'][$region][$language] = $result;

        return $result;
    }

    public function isAvailableForDescriptionWriting($regionOrCityGroup = '', $language = null)
    {
        if ($language === null) {
            $language = Languages::currentCode();
        }

        return ! $this->attachedTexts()->where('type', 'description')->where('language', $language)
            ->where('subjectString', $regionOrCityGroup)->where('status', '!=', 'denied')->exists();
    }

    public function updateSearchRank()
    {
        $searchPhrase = "$this->country hostels";

        $results = WebSearch::search($searchPhrase, 50);
        if (! $results) {
            logError("Unable to perform search for '$searchPhrase'.");

            return null;
        }

        $rank = 0;
        foreach ($results as $key => $result) {
            if (stripos($result['url'], 'hostelz.com/hostels-in/') !== false || stripos($result['url'], 'hostelz.com/hotels-in/') !== false) {
                $rank = $key + 1;

                break;
            }
        }

        $new = new SearchRank([
            'checkDate' => date('Y-m-d'), 'source' => 'Google', 'searchPhrase' => $searchPhrase, 'rank' => $rank,
            'placeType' => 'CountryInfo', 'placeID' => $this->id,
        ]);
        $new->save();

        return $rank;
    }

    public function determineLocalLanguage()
    {
        $geonamesCountry = Geonames::getCountryByGeonamesID($this->geonamesCountryID);
        if (! $geonamesCountry) {
            return null;
        }

        $languages = $geonamesCountry->languages;
        if (! $languages) {
            return null;
        }

        // Just use the first one
        $languages = explode(',', $languages);
        $language = $languages[0];

        // If it's something like 'en-US', just use the first part.
        $parts = explode('-', $language);

        return $parts[0];
    }

    /* Scopes */

    public function scopeAreLive($query)
    {
        return $query->where('cityCount', '>', 0);
    }

    public function scopeByAltName($query, $altName)
    {
        return $query->whereIn('geonamesCountryID', function ($query) use ($altName): void {
            Geonames::altNamesSubquery($query, $altName);
        });
    }

    /* Relationships */

    public function cityInfos()
    {
        return $this->hasMany(\App\Models\CityInfo::class, 'country', 'country');
    }

    public function listings()
    {
        return $this->hasMany(Listing::class, 'country', 'country');
    }

    public function attachedTexts()
    {
        return $this->hasMany(\App\Models\AttachedText::class, 'subjectID')->where('subjectType', 'countryInfo');
    }

    // Note that this isn't a real Laravel relationship since ContinentInfo doesn't use Eloquent.  So this has to be called with the partentheses.
    public function continentInfo()
    {
        return ContinentInfo::findByName($this->continent);
    }

    public function geonames()
    {
        return $this->hasOne(\App\Models\Geonames::class, 'id', 'geonamesCountryID');
    }

    public function slp()
    {
        return $this->morphMany(SpecialLandingPage::class, 'subjectable');
    }

    //

    public function regions(): Collection
    {
        return Region::where([
            ['country', $this->country],
            ['region', '!=', ''],
            ['displaysRegion', 1],
        ])
                     ->groupBy('region')
                     ->get();
    }

    public function cityGroups(): Collection
    {
        return CityGroup::where([
            ['country', $this->country],
            ['cityGroup', '!=', ''],
        ])
                     ->groupBy('cityGroup')
                     ->get();
    }
}
