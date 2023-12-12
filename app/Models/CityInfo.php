<?php

namespace App\Models;

use App\Enums\Listing\CategoryPage;
use App\Models\Listing\Listing;
use App\Utils\FieldInfo;
use Carbon\Carbon;
use Exception;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Lib\BaseModel;
use Lib\Currencies;
use Lib\DataCorrection;
use Lib\GeoBounds;
use Lib\GeoPoint;
use Lib\PageCache;
use Lib\WebSearch;

class CityInfo extends BaseModel
{
    use HasFactory;

    protected $table = 'cityInfo';

    public static $staticTable = 'cityInfo'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    // (We only include the region in the city URL for certain countries)
    public static $countriesWithRegionInTheCityPageURL = ['USA', 'Australia', 'Canada', 'Ireland'];

    // DataCorrection is often done within the "context" of another field (e.j. each region correction is specific to a particular country).
    public static $dataCorrectionContexts = [
        // fieldName => [ contextValue1, contextValue2 ]
        'country' => [null, null],
        'region' => ['country', null],
        'city' => ['country', null],
    ];

    public static $featuredCitiesNames = ['Tulum', 'Cancún', 'Amsterdam', 'Sydney', 'Maui', 'Miami Beach', 'Auckland', 'Santa Teresa', 'London', 'New York City'];

    // Pics
    public const PIC_WIDTH = 300; // TODO: change to a different size?

    public const PIC_MIN_WIDTH = 300;

    public const PIC_MIN_HEIGHT = 250;

    public const GEOCODING_PRECISION = 4;

    // City Page Display
    public const CITY_MAP_MARKER_HEIGHT = 64; // this has to be the heigh/width of the actual PNG image file

    public const CITY_MAP_MARKER_WIDTH = 47; // this has to be the heigh/width of the actual PNG image file

    public const DEFAULT_LIST_FORMAT = 'list';

    public const DEFAULT_MAP_MODE = 'closed';

    public const DEFAULT_ORDER_BY = 'default';

    public const DEFAULT_Results_Per_Page = 20;

    // Other
    // Set to a high enough number to get most of the good content hostels on the first page for SEO.
    public const CITY_DESCRIPTION_MINIMUM_WORDS = 300;

    public const CITY_DESCRIPTION_PAY = 6.00;

    public const CITY_PIC_PAY = 1.00;

    public const SPECIAL_HOSTELS_PAGE_MIN_HOSTELS = [
        'cheap' => 46,
        'best' => 20,
        'party' => 45,
        'private' => 20,
    ];

    private $miscDataCaches = []; // Just used to temporarily store results from getLiveReview(), etc. to avoid multiple database calls.

    private $bestFor = [
        'topRated' => null,
        'soloTraveller' => null,
    ];

    public function save(array $options = []): void
    {
        // Update displaysRegion (mostly only important for new cities or if the country was changed)
        if ($this->countryInfo) {
            $this->displaysRegion = $this->countryInfo->displaysRegions();
        }
        if ($this->region == '') {
            $this->gnRegionID = 0;
        }

        parent::save($options);
        $this->clearRelatedPageCaches();
    }

    public function delete()
    {
        if (
            $this->cityComments()->count() || $this->pics()->count() || $this->attachedTexts()->count() ||
            $this->districts()->count() || $this->slp()->count()
        ) {
            // Has stuff we don't want to delete, so just set totalListingCount=0 instead
            $this->totalListingCount = 0;
            $this->save();
            $this->clearRelatedPageCaches();

            return false; // so we don't actually delete the record
        }

        // Complex models that need us to call their delete() method for special delete handling
        /* (no need to do this because we don't delete cityInfos with pics) foreach ($this->pics as $pic) $pic->delete(); */
        /* (no need to do this because we don't delete attachedTexts with pics) foreach ($this->attachedTexts as $item) $item->delete(); */
        /* (no need to do this because we don't delete cityComments with pics) foreach ($this->cityComments as $item) $item->delete(); */

        parent::delete();

        $this->clearRelatedPageCaches();
    }

    public function merge($otherCityInfo): void
    {
        if ($this->country != $otherCityInfo->country || $this->city != $otherCityInfo->city) {
            throw new Exception("Attempted merging of unmatched cities ('$this->city', '$this->country' and '$otherCityInfo->city', '$otherCityInfo->country').");
        }
        if ($this->region != '' && $otherCityInfo->region != '' && $this->region != $otherCityInfo->region) {
            throw new Exception('Attempted merging of cities with different regions set.');
        }

        // (some of these should probably merge rather than just replace?)
        if ($this->region == '') {
            $this->region = $otherCityInfo->region;
        }
        if ($this->cityGroup == '') {
            $this->cityGroup = $otherCityInfo->cityGroup;
        }
        if ($this->cityAlt == '') {
            $this->cityAlt = $otherCityInfo->cityAlt;
        }
        if ($this->tips == '') {
            $this->tips = $otherCityInfo->tips;
        }
        if ($this->links == '') {
            $this->links = $otherCityInfo->links;
        }
        if ($this->infoLink == '') {
            $this->infoLink = $otherCityInfo->infoLink;
        }
        if ($this->postalCode == '') {
            $this->postalCode = $otherCityInfo->postalCode;
        }
        if ($this->weatherLink == '') {
            $this->weatherLink = $otherCityInfo->weatherLink;
        }
        $this->staffNotes = trim($this->staffNotes . "\n\n" . $otherCityInfo->staffNotes);
        $this->totalListingCount = $this->totalListingCount + $otherCityInfo->totalListingCount;
        $this->hostelCount = $this->hostelCount + $otherCityInfo->hostelCount;
        $this->save();

        foreach ($otherCityInfo->pics as $item) {
            $item->subjectID = $this->id;
            $item->save();
        }
        foreach ($otherCityInfo->attachedTexts as $item) {
            $item->subjectID = $this->id;
            $item->save();
        }
        foreach ($otherCityInfo->cityComments as $item) {
            $item->cityID = $this->id;
            $item->save();
        }

        Ad::handlePlaceIdChange('CityInfo', $otherCityInfo->id, $this->id);
        IncomingLink::handlePlaceIdChange('CityInfo', $otherCityInfo->id, $this->id);

        $this->clearRelatedPageCaches();
        $otherCityInfo->clearRelatedPageCaches();

        $otherCityInfo->delete();
    }

    public function updateSearchRank()
    {
        $searchPhrase = "$this->city hostels";

        $results = WebSearch::search($searchPhrase, 50);
        if (! $results) {
            logError("Unable to perform search for '$searchPhrase'.");

            return null;
        }

        $rank = 0;
        foreach ($results as $key => $result) {
            if (stripos($result['url'], 'hostelz.com/hostels/') !== false || stripos($result['url'], 'hostelz.com/hotels/') !== false) {
                $rank = $key + 1;

                break;
            }
        }

        $new = new SearchRank([
            'checkDate' => date('Y-m-d'), 'source' => 'Google', 'searchPhrase' => $searchPhrase, 'rank' => $rank,
            'placeType' => 'CityInfo', 'placeID' => $this->id,
        ]);
        $new->save();

        return $rank;
    }

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'editType' => 'ignore'],
                    'continent' => ['type' => 'display'],
                    'country' => ['maxLength' => 70, 'validation' => 'required'],
                    'region' => ['maxLength' => 70],
                    'rememberRegionRenaming' => ['type' => 'ignore', 'editType' => 'checkbox', 'value' => true, 'fieldLabelText' => ' ',
                        'checkboxText' => 'Remember renaming rule if the region is changed.',
                        'getValue' => function ($formHandler, $model) {
                            return false;
                        },
                        'setValue' => function ($formHandler, $model, $value): void { /* do nothing, this is handled by a 'setModelData' callback */
                        },
                    ],
                    'cityGroup' => ['maxLength' => 70],
                    'city' => ['maxLength' => 70, 'validation' => 'required'],
                    'rememberCityRenaming' => ['type' => 'ignore', 'editType' => 'checkbox', 'value' => true, 'fieldLabelText' => ' ',
                        'checkboxText' => 'Remember renaming rule if the city is changed.',
                        'getValue' => function ($formHandler, $model) {
                            return auth()->user()->hasPermission('admin'); /* defaults to on only for admin */
                        },
                        'setValue' => function ($formHandler, $model, $value): void { /* do nothing, this is handled by a 'setModelData' callback */
                        },
                    ],
                    'cityAlt' => ['maxLength' => 70],
                    'postalCode' => ['maxLength' => 70],
                    'tips' => ['type' => 'textarea', 'rows' => 3],
                    'links' => ['editType' => 'multi', 'comparisonType' => 'substring', 'maxLength' => 500],
                    'infoLink' => ['maxLength' => 100],
                    'latitude' => ['type' => 'display'],
                    'longitude' => ['type' => 'display'],
                    'weatherImage' => ['maxLength' => 150],
                    'weatherLink' => ['maxLength' => 150],
                    'hostelCount' => ['searchType' => 'minMax', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'totalListingCount' => ['searchType' => 'minMax', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'gnCityID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'gnRegionID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'gnCountryID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'nearbyCities' => ['type' => 'display', 'getValue' => function ($formHandler, $model) {
                        return $model->attributes['nearbyCities']; // just output the json encoded string
                    }],
                    'poi' => ['type' => 'display', 'getValue' => function ($formHandler, $model) {
                        return $model->attributes['poi']; // just output the json encoded string
                    }],
                    'staffNotes' => ['type' => 'textarea', 'rows' => 6],
                    'topRatedHostel' => ['type' => 'display'],
                    'cheapestHostel' => ['type' => 'display'],
                ];

                if ($purpose == 'staffEdit') {
                    $staffEditable = ['country', 'region', 'cityGroup', 'city', 'cityAlt', 'postalCode', 'staffNotes'];
                    $staffIgnore = ['latitude', 'longitude', 'gnCityID', 'gnRegionID', 'gnCountryID', 'nearbyCities'];
                    FieldInfo::fieldInfoType($fieldInfos, $staffEditable, $staffIgnore);
                }

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    public static function picFixPicOutputTypes()
    {
        return [
            '' => ['saveAsFormat' => 'jpg', 'outputQuality' => 85, 'maxWidth' => self::PIC_WIDTH,
                'watermarkImage' => public_path() . '/images/hostelz-watermark.png', 'watermarkHeight' => 20, 'watermarkOpacity' => 0.24, ],
        ];
    }

    public static function fixAllDisplaysRegionsValues(): void
    {
        $countriesWithDisplayedRegions = CountryInfo::where('regionType', '!=', '')->pluck('country');
        self::where('displaysRegion', false)->whereIn('country', $countriesWithDisplayedRegions)->update(['displaysRegion' => true]);
        $countriesWithoutDisplayedRegions = CountryInfo::where('regionType', '=', '')->pluck('country');
        self::where('displaysRegion', true)->whereIn('country', $countriesWithoutDisplayedRegions)->update(['displaysRegion' => false]);
    }

    public static function maintenanceTasks($timePeriod): string
    {
        $output = '';

        switch ($timePeriod) {
            case 'daily':
                $output .= 'Geocode/Geoname if not yet set: ';
                // (we could compute the CRC32 code to see if the city changed, but probably enough to just do the ones that have no CRC set,
                // and we do *all* of them anyway in the afterListingDataImport maintenance.)
                foreach (self::areLive()->where('mapCRC', 0)->get() as $cityInfo) {
                    if ($cityInfo->updateGeocoding(false)) {
                        $output .= "'$cityInfo->city' ";
                    }
                }

                $output .= "\nSet displaysRegion.\n";
                // (displaysRegion is set also automatically when countries are saved, but this catches any remaining inconsistencies.)
                self::fixAllDisplaysRegionsValues();

                break;

            case 'weekly':
                if (! App::environment('production')) {
                    throw new Exception('Should be run from production. Otherwise Clockwork and/or debug mode causes it to run out of memory.');
                }

                set_time_limit(2 * 60 * 60); // Note: This also resets the timeout timer.

                $output .= 'Set cityInfo regions from listing data: ';

                $cityRegions = self::query()
                    ->join(Listing::$staticTable, function ($join): void {
                        $join->on(self::$staticTable . '.city', '=', Listing::$staticTable . '.city')
                            ->on(self::$staticTable . '.country', '=', Listing::$staticTable . '.country');
                    })
                    ->where(self::$staticTable . '.region', '=', '')
                    ->where(Listing::$staticTable . '.verified', '=', Listing::$statusOptions['ok'])
                    ->where(Listing::$staticTable . '.region', '!=', '')
                    ->groupBy(Listing::$staticTable . '.city', Listing::$staticTable . '.country')
                    ->select([Listing::$staticTable . '.city', Listing::$staticTable . '.region', Listing::$staticTable . '.country'])
                    ->get();

                foreach ($cityRegions as $cityRegion) {
                    $output .= "['$cityRegion->city' region '$cityRegion->region'] ";
                    self::where('region', '')->where('city', $cityRegion->city)->where('country', $cityRegion->country)->
                    update(['region' => $cityRegion->region]);
                }

                $output .= "\nSet listing regions from cityInfo data: ";

                $cityRegions = Listing::join(self::$staticTable, function ($join): void {
                    $join->on(Listing::$staticTable . '.city', '=', self::$staticTable . '.city')
                        ->on(Listing::$staticTable . '.country', '=', self::$staticTable . '.country');
                })
                    ->where(Listing::$staticTable . '.region', '=', '')
                    ->where(self::$staticTable . '.region', '!=', '')
                    ->groupBy(self::$staticTable . '.city', self::$staticTable . '.country')
                    ->select([self::$staticTable . '.city', self::$staticTable . '.region', self::$staticTable . '.country'])
                    ->get();

                foreach ($cityRegions as $cityRegion) {
                    $output .= "['$cityRegion->city' region '$cityRegion->region'] ";
                    Listing::where('region', '')->where('city', $cityRegion->city)->where('country', $cityRegion->country)->
                    update(['region' => $cityRegion->region]);
                }

                $output .= "\nCountry Data Corrections: ";
                $output .= DataCorrection::correctAllDatabaseValues(
                    '',
                    'country',
                    self::query(),
                    self::$staticTable,
                    null,
                    self::$dataCorrectionContexts['country'][0],
                    self::$dataCorrectionContexts['country'][1]
                );
                $output .= "\nRegion Data Corrections: ";
                $output .= DataCorrection::correctAllDatabaseValues(
                    '',
                    'region',
                    self::query(),
                    self::$staticTable,
                    null,
                    self::$dataCorrectionContexts['region'][0],
                    self::$dataCorrectionContexts['region'][1]
                );
                $output .= "\nCity Data Corrections: ";
                $output .= DataCorrection::correctAllDatabaseValues(
                    '',
                    'city',
                    self::query(),
                    self::$staticTable,
                    null,
                    self::$dataCorrectionContexts['city'][0],
                    self::$dataCorrectionContexts['city'][1]
                );

                $output .= "\nInsert new cityInfo cities: ";

                Listing::query()
                    ->areLive()
                    ->select(['id', 'city', 'cityAlt', 'region', 'country', 'zipcode'])
                    ->groupBy('city', 'region', 'country')
                    ->lazyById()->each(function (Listing $listingCity) use (&$output) {
                        if ($listingCity['city'] == '') {
                            throw new Exception("Listing $listingCity[id] has no city!");
                        }
                        if ($listingCity['country'] == '') {
                            throw new Exception("Listing $listingCity[id] has no country!");
                        }

                        if (self::where('city', $listingCity['city'])->where('region', $listingCity['region'])->where('country', $listingCity['country'])->exists()) {
                            return;
                        }

                        $output .= "['$listingCity[country]', '$listingCity[region]', '$listingCity[city]'] ";
                        $cityInfo = new self(['country' => $listingCity['country'], 'region' => $listingCity['region'], 'city' => $listingCity['city']]);
                        $cityInfo->save();
                    });

                $output .= "\nMerge duplicates: ";

                // Note: This groups by binary so capitalizations are considered separately.  Issues with multiple capitalizations are handled by DataChecksController.
                self::query()
                    ->select(DB::raw('*, count(*) as count'))
                    ->groupBy(DB::raw('CAST(city AS BINARY), CAST(region AS BINARY), CAST(country AS BINARY)'))
                    ->havingRaw('count(*) > 1')
                    ->lazyById()->each(function (self $cityInfo) use (&$output) {
                        // Merge duplicates
                        $duplicates = self::where('id', '!=', $cityInfo->id)->whereRaw('BINARY city=?', [$cityInfo->city])
                            ->whereRaw('BINARY region=?', [$cityInfo->region])->whereRaw('BINARY country=?', [$cityInfo->country])->get();

                        if ($duplicates->isEmpty()) {
                            throw new Exception("There appeared to be duplicates of cityInfo $cityInfo->id, but none were found (shouldn't happen).");
                        }
                        foreach ($duplicates as $duplicate) {
                            $output .= "[duplicate for '$cityInfo->city', '$cityInfo->country' merged] ";
                            $cityInfo->merge($duplicate);
                        }
                    });

                $output .= "\nRemove empty cities and set hostel/listing counts: ";

                // Note: This groups by binary so capitalizations are considered separately.  Issues with multiple capitalizations are handled by DataChecksController.
                self::query()
                    ->select(DB::raw('*, count(*) as count'))
                    ->groupBy(DB::raw('CAST(city AS BINARY), CAST(region AS BINARY), CAST(country AS BINARY)'))
                    ->lazyById()->each(function (self $cityInfo) use (&$output) {
                        $modified = false;
                        $totalListingCount = Listing::areLive()->byCityInfo($cityInfo)->count();
                        // for this we count actual Hostels only (not "other" type, even though that's included in hostel list pages)
                        $hostelCount = Listing::areLive()->byCityInfo($cityInfo)->where('propertyType', 'Hostel')->count();
                        if ($totalListingCount != $cityInfo->totalListingCount || $hostelCount != $cityInfo->hostelCount) {
                            $modified = true;
                            $cityInfo->totalListingCount = $totalListingCount;
                            $cityInfo->hostelCount = $hostelCount;
                            // todo: temp skip the removing city
                            /*if ($totalListingCount == 0) {
                                $output .= "[removing empty cityInfo '$cityInfo->city'] ";
                                $cityInfo->delete(); // note that this may not actually delete the db row if the cityInfo has useful data we might want to keep

                                continue;
                            }*/
                        }

                        if ($cityInfo->setSpecialListings()) {
                            $modified = true;
                        }

                        if ($modified) {
                            $output .= "$cityInfo->id [$hostelCount/$totalListingCount] ";
                            $cityInfo->save();
                        }
                    });

                $output .= "\nSet Continent: ";

                foreach (self::where('continent', '')->get() as $cityInfo) {
                    $continent = CountryInfo::where('country', $cityInfo->country)->value('continent');
                    $output .= "['$cityInfo->country' -> '$continent'] ";
                    if ($continent == '') {
                        logWarning("No CountryInfo for cityInfo country \"$cityInfo->country\".");

                        continue;
                    }
                    $cityInfo->continent = $continent;
                    $cityInfo->save();
                }

                $output .= "\n";

                break;

            case 'monthly':
                $output .= "Optimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                break;

            case 'afterListingDataImport':
                if (! App::environment('production')) {
                    throw new Exception('Should be run from production. Otherwise Clockwork and/or debug mode causes it to run out of memory.');
                }

                $output .= self::maintenanceTasks('weekly'); // first do all of the weekly tasks!

                set_time_limit(2 * 60 * 60); // Note: This also resets the timeout timer.

                $output .= "Update Geocoding/Geoname and Nearby Cities.\n";

                self::areLive()
                    ->lazyById()
                    ->each->updateGeocoding();

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    /* Accessors & Mutators */

    public function getLinksAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setLinksAttribute($value): void
    {
        $this->attributes['links'] = ($value ? json_encode($value) : '');
    }

    public function getPoiAttribute($value)
    {
        return $value == '' ? [] : unserialize($value);
    }

    public function setPoiAttribute($value): void
    {
        $this->attributes['poi'] = ($value ? serialize($value) : '');
    }

    public function getNearbyCitiesAttribute($value)
    {
        return $value == '' ? [] : unserialize($value);
    }

    public function setNearbyCitiesAttribute($value): void
    {
        $this->attributes['nearbyCities'] = ($value ? serialize($value) : '');
    }

    /* Static */

    // $continent isn't needed except for continent pages.
    // If $language == '', the current language is used.

    public static function makeCitiesUrl($region, $country, $continent = '', $urlType = 'auto', $language = null)
    {
        $unwantedUrlCharacters = [' ', "'", '@', '&', '!', '*', '(', ')', ',', '+', ':', '"', '<', '>', '%', '/'];
        $slug = ($continent != '' && $country == '' && $region == '' ? urlencode(str_replace($unwantedUrlCharacters, '-', $continent)) : '') .
            ($country != '' ? urlencode(str_replace($unwantedUrlCharacters, '-', $country)) : '') .
            ($region != '' ? '/' . urlencode(str_replace($unwantedUrlCharacters, '-', $region)) : '');

        return routeURL('cities', $slug, $urlType, $language);
    }

    /* Misc */

    public function setSpecialListings()
    {
        $modified = false;

        $hostelsIdsSortedByRating = Listing::areLive()
            ->activeBookingPrice()
            ->byCityInfo($this)
            ->where('propertyType', 'Hostel')
            ->where('featuredListingPriority', '!=', -1)
            ->orderBy('combinedRating', 'DESC')->pluck('id');

        // topRatedHostel

        $topRatedHostel = (int) $hostelsIdsSortedByRating->first();
        if ($this->topRatedHostel != $topRatedHostel) {
            $this->topRatedHostel = $topRatedHostel;
            $modified = true;
        }

        // cheapestHostel

        if ($hostelsIdsSortedByRating->isEmpty()) {
            $cheapestHostel = 0;
        } else {
            $cheapestHostel = (int) PriceHistory::select(DB::raw('listingID, AVG(averagePricePerNight) as priceAverage'))
                ->whereIn('listingID', $hostelsIdsSortedByRating)
                ->where('roomType', 'dorm')
                ->where('month', '>=', Carbon::now()->subMonths(1)->startOfMonth()->format('Y-m-d'))
                ->where('month', '<=', Carbon::now()->addMonths(2)->startOfMonth()->format('Y-m-d'))
                ->groupBy('listingID')
                ->orderBy('priceAverage', 'ASC')->value('listingID');
        }
        if ($this->cheapestHostel != $cheapestHostel) {
            $this->cheapestHostel = $cheapestHostel;
            $modified = true;
        }

        return $modified;
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
        if ($this->countryInfo) {
            $currency = $this->countryInfo->currencyCode;
            if ($currency != '' && Currencies::isKnownCurrencyCode($currency)) {
                return $currency;
            }
        }

        return null;
    }

    public function clearRelatedPageCaches(): void
    {
        if (! $this->id) {
            return;
        }
        PageCache::clearByTag('city:' . $this->id); // clear cached pages related to this city.
        PageCache::clearByTag('city:aggregation'); // clear cached pages related to all cities.
    }

    public function isLive()
    {
        return $this->totalListingCount != 0;
    }

    public function getTotalListingsInRegion(): int
    {
        return (int) self::where('region', $this->region)->sum('totalListingCount');
    }

    // If $language is null, the current language is used.

    public function getUrlWithoutRegion()
    {
        return $this->getURL(urlType: 'absolute', useRegion: false);
    }

    public function getURL($urlType = 'auto', $language = null, $useRegion = true)
    {
        if ($this->city === '' || $this->country === '') {
            throw new Exception('Missing city or country for city ID ' . $this->id . '.');
        }

        $region = (
            $useRegion
            && $this->region !== ''
            && in_array($this->country, self::$countriesWithRegionInTheCityPageURL)
        )
            ? $this->region
            : '';

        $unwantedUrlCharacters = [' ', "'", '@', '&', '!', '*', '(', ')', ',', '+', ':', '"', '<', '>', '%', '/'];

        $slug = urlencode(str_replace($unwantedUrlCharacters, '-', $this->country)) . '/';
        $slug .= $region
            ? urlencode(str_replace($unwantedUrlCharacters, '-', $region)) . '/'
            : '';
        $slug .= urlencode(str_replace($unwantedUrlCharacters, '-', $this->city));

        // (we used to also have /hotels/ city URLs, but no longer do that -- better to keep them consistent.)
        return routeURL('city', $slug, $urlType, $language);
    }

    public function getSpecialHostelsURL($specialType, $urlType = 'auto', $language = null)
    {
        $unwantedUrlCharacters = [' ', "'", '@', '&', '!', '*', '(', ')', ',', '+', ':', '"', '<', '>', '%', '/'];
        $slug = urlencode(str_replace($unwantedUrlCharacters, '-', $this->city)) . '-' . urlencode(str_replace($unwantedUrlCharacters, '-', $this->country));
        $slug = mb_strtolower($slug);

        return routeURL($specialType, $slug, $urlType, $language);
    }

    public function getCategoryPageDescription(CategoryPage $categoryPage, $language = 'en'): ?string
    {
        return $this->attachedTexts()
            ->where('type', $categoryPage->attachmentType())
            ->where('language', $language)
            ->first()
            ?->data;
    }

    public function getCityGroupURL($urlType = 'auto', $language = null)
    {
        if ($this->cityGroup == '' || $this->country == '') {
            throw new Exception("cityGroup or country missing for cityGroup URL for cityInfo $this->id.");
        }

        return self::makeCitiesUrl($this->cityGroup, $this->country, '', $urlType, $language);
    }

    public function getRegionURL($urlType = 'auto', $language = null, $ignoreDisplaysRegions = false)
    {
        if (! $ignoreDisplaysRegions && ! $this->displaysRegion) {
            throw new Exception("This city '$this->city' displaysRegion is false.");
        }
        if ($this->region == '' || $this->country == '') {
            throw new Exception("Region or country missing for region URL for cityInfo $this->id.");
        }

        return self::makeCitiesUrl($this->region, $this->country, '', $urlType, $language);
    }

    public function getCountryURL($urlType = 'auto', $language = null)
    {
        if ($this->country == '') {
            throw new Exception("Country missing for country URL for cityInfo $this->id.");
        }

        return self::makeCitiesUrl('', $this->country, '', $urlType, $language);
    }

    public function getContinentURL($urlType = 'auto', $language = null)
    {
        if ($this->continent == '') {
            throw new Exception("Continent missing for continent URL for cityInfo $this->id.");
        }

        return self::makeCitiesUrl('', '', $this->continent, $urlType, $language);
    }

    // Use $language = null to get a review in the current language, or '' to get the first description in any language.

    public function getLiveDescription($language = null, $useCache = true)
    {
        if ($language === null) {
            $language = Languages::currentCode();
        }

        if ($useCache) {
            $cached = $this->miscDataCaches['description'][$language] ?? null;
            if ($cached) {
                return $cached;
            }
        }
        $result = $this->attachedTexts()->where('type', 'description')->where('language', $language)
            ->where('status', 'ok')->orderBy('id', 'desc')->first();
        $this->miscDataCaches['description'][$language] = $result;

        return $result;
    }

    public function isAvailableForDescriptionWriting($language = null)
    {
        if ($language === null) {
            $language = Languages::currentCode();
        }

        // Added 2017-04.  Decided to no longer accept descriptions of cities without hostels.
        if (! $this->hostelCount) {
            return false;
        }

        return ! $this->attachedTexts()->where('type', 'description')->where('language', $language)
            ->where('status', '!=', 'denied')->exists();
    }

    public function updateGeocoding($forceUpdate = true, $andSave = true)
    {
        $modified = false;

        // * CRC *
        $mapCRC = crc32("$this->city:$this->cityAlt:$this->region:$this->country");
        if (! $forceUpdate && $mapCRC == $this->mapCRC) {
            return $modified;
        }
        $this->mapCRC = $mapCRC;

        // * Get Geonames IDs & Geocoding *
        $result = Geonames::findCityRegionCountry($this->country, $this->region, $this->city);
        if ($result && ! $result['city'] && $this->cityAlt !== '') {
            $result = Geonames::findCityRegionCountry($this->country, $this->region, $this->cityAlt);
        }
        $originalGeoIDs = [$this->gnCityID, $this->gnRegionID, $this->gnCountryID];
        $this->gnCityID = (isset($result['city']) ? $result['city']->id : 0);
        $this->gnRegionID = (isset($result['region']) ? $result['region']->id : 0);
        $this->gnCountryID = (isset($result['country']) ? $result['country']->geonamesID : 0);
        if ($originalGeoIDs !== [$this->gnCityID, $this->gnRegionID, $this->gnCountryID]) {
            $modified = true;
        }

        // * Latitude/Longitude *
        if ($result && $result['city']) { // Use the Geonames info
            $geoPoint = $result['city']->geoPoint();
        } else {
            // Use the average of the city's live listings
            $geoPoint = Listing::averageGeocodingLocation(Listing::areLive()->byCityInfo($this)->haveLatitudeAndLongitude()->where('locationStatus', 'ok')->get());
        }

        if ($geoPoint) {
            $geoPoint->roundToPrecision(self::GEOCODING_PRECISION);
            if (! $this->geoPoint() || ! $this->geoPoint()->equals($geoPoint)) {
                $this->latitude = $geoPoint->latitude;
                $this->longitude = $geoPoint->longitude;
                $modified = true;
            }
        }

        if ($this->hasLatitudeAndLongitude()) {
            // * POI *
            $poi = Geonames::findNearby($this->geoPoint(), 60, 100, null, ['Airport' => 8, 'Bus Station' => 5, 'Train Station' => 10]);
            if ($this->poi != $poi) {
                $this->poi = $poi;
                $modified = true;
            }

            // * Nearby Cities *
            $approxMaxRangInKM = 200;
            $nearbyCities = GeoBounds::makeFromApproximateDistanceFromPoint($this->geoPoint(), $approxMaxRangInKM, 'km')->query(self::areLive())
                ->where('id', '!=', $this->id)->get()->transform(function ($otherCity) {
                    return [
                        'cityID' => $otherCity->id,
                        'km' => (string) $this->geoPoint()->distanceToPoint($otherCity->geoPoint(), 'km', 2),
                    ];
                })->sort(function ($a, $b) {
                    return $a['km'] - $b['km'];
                })->take(7)->values()->toArray();

            if ($this->nearbyCities != $nearbyCities) {
                $this->nearbyCities = $nearbyCities;
                $modified = true;
            }
        }

        if ($modified && $andSave) {
            $this->save();
        }

        return $modified;
    }

    public function updateSpecialListings()
    {
        if ($this->setSpecialListings()) {
            $this->save();

            return true;
        }

        return false;
    }

    public function fullDisplayName()
    {
        return $this->translation()->city .
            ($this->translation()->cityAlt != '' ? ' (' . $this->translation()->cityAlt . ')' : '') .
            ($this->displaysRegion && $this->translation()->region != '' ? ', ' . $this->translation()->region : '') .
            ($this->translation()->country != '' ? ', ' . $this->translation()->country : '');
    }

    public function cityGroupFullDisplayName()
    {
        if ($this->cityGroup == '') {
            throw new Exception("This cityInfo doesn't have a cityGroup.");
        }

        return $this->translation()->cityGroup .
            ($this->translation()->country != '' ? ', ' . $this->translation()->country : '');
    }

    public function regionFullDisplayName($ignoreDisplaysRegion = false)
    {
        if ($this->region == '') {
            throw new Exception("This cityInfo doesn't have a region.");
        }
        if (! $ignoreDisplaysRegion && ! $this->displaysRegion) {
            throw new Exception('Not displaying regions for this country.');
        }

        return $this->translation()->region .
            ($this->translation()->country != '' ? ', ' . $this->translation()->country : '');
    }

    /* Pics */

    public function addPic($picFilePath, $source, $caption = null)
    {
        $maxPicNum = -1;
        foreach ($this->pics as $pic) {
            if ($pic->picNum > $maxPicNum) {
                $maxPicNum = $pic->picNum;
            }
        }

        return Pic::makeFromFilePath($picFilePath, [
            'subjectType' => 'cityInfo', 'subjectID' => $this->id, 'type' => 'user', 'status' => 'new',
            'source' => $source, 'picNum' => $maxPicNum + 1,
            'caption' => (string) $caption,
        ], [
            'originals' => [],
            // This '' one is temporary, just used to display the thumbnails to the user.
            // (will get replaced later by pic fix when our photo editor edits them)
            '' => ['saveAsFormat' => 'jpg', 'outputQuality' => 85, 'maxWidth' => self::PIC_WIDTH],
        ]);
    }

    public function translation($language = null, $useCache = true)
    {
        if ($language == null) {
            $language = Languages::currentCode();
        }
        if ($language == 'en') {
            return $this;
        } // no translation needed

        if ($useCache) {
            $cached = $this->miscDataCaches['translation'][$language] ?? null;
            if ($cached) {
                return $cached;
            }
        }

        $result = clone $this;

        if ($this->gnCityID && ($geoname = Geonames::find($this->gnCityID)) && ($translation = $geoname->getTranslation($language)) != '') {
            $result->city = $translation;
        }
        if ($this->region != '' && $this->gnRegionID && ($geoname = Geonames::find($this->gnRegionID)) && ($translation = $geoname->getTranslation($language)) != '') {
            $result->region = $translation;
        }
        if ($this->gnCountryID && ($geoname = Geonames::find($this->gnCountryID)) && ($translation = $geoname->getTranslation($language)) != '') {
            $result->country = $translation;
        }
        if ($this->continent != '') {
            $result->continent = ContinentInfo::findByName($this->continent)->translation($language);
        }

        $this->miscDataCaches['translation'][$language] = $result;

        return $result;
    }

    /* Scopes */

    public function scopeAreLive($query)
    {
        return $query->where('totalListingCount', '>', 0);
    }

    public function scopeByCitySlug($query, $slug)
    {
        return $query->where('city', 'like', Str::slug($slug, '_'));
    }

    public function scopeLiveCities($query, $limit = 12)
    {
        return $query->areLive()->orderByDesc('hostelCount')->limit($limit);
    }

    public function scopeByCityAltName($query, $altName)
    {
        return $query->whereIn('gnCityID', function ($query) use ($altName): void {
            Geonames::altNamesSubquery($query, $altName);
        });
    }

    public function scopeByRegionAltName($query, $altName)
    {
        return $query->whereIn('gnRegionID', function ($query) use ($altName): void {
            Geonames::altNamesSubquery($query, $altName);
        });
    }

    public function scopeByCountryAltName($query, $altName)
    {
        return $query->whereIn('gnCountryID', function ($query) use ($altName): void {
            Geonames::altNamesSubquery($query, $altName);
        });
    }

    public function scopeHaveLatitudeAndLongitude($query)
    {
        return $query->where(function ($query): void {
            $query->where('latitude', '!=', 0)->orWhere('longitude', '!=', 0);
        });
    }

    public function scopeNearbyCitiesInCountry($query, $latitude, $longitude, $countryName)
    {
        return $query->select(
            'id',
            'city',
            'country',
            'region',
            'nearbyCities',
            DB::raw("6371 * acos(cos(radians($latitude))
                 * cos(radians(latitude))
                 * cos(radians(longitude) - radians($longitude))
                 + sin(radians($latitude))
                 * sin(radians(latitude))) AS distance")
        )
            ->where('region', '')
            ->where('country', $countryName)
            ->having('distance', '>', 1);
    }

    public static function scopeFromUrlParts($query, $country, $region = '', $city = '')
    {
        // Change dashes to the SQL wildcard character "_" because some city characters are replaced with "-" when generating URLs.
        $country = str_replace('-', '_', $country);
        $query->where('country', strpos($country, '_') !== false ? 'LIKE' : '=', $country);

        if ($city != '') {
            $city = str_replace('-', '_', $city);
            $query->where('city', strpos($city, '_') !== false ? 'LIKE' : '=', $city);
        }

        if ($region != '') {
            $region = str_replace('-', '_', $region);
            $query->where('region', strpos($region, '_') !== false ? 'LIKE' : '=', $region);
        }

        return $query;
    }

    public static function scopeFromCityGroupUrlParts($query, $country, $cityGroup)
    {
        // Change dashes to the SQL wildcard character "_" because some city characters are replaced with "-" when generating URLs.
        $country = str_replace('-', '_', $country);
        $query->where('country', strpos($country, '_') !== false ? 'LIKE' : '=', $country);

        $cityGroup = str_replace('-', '_', $cityGroup);
        $query->where('cityGroup', strpos($cityGroup, '_') !== false ? 'LIKE' : '=', $cityGroup);

        return $query;
    }

    public function getThumbnailAttribute()
    {
        return Cache::remember("ThumbnailAttribute:{$this->id}", 60 * 60 * 24, function () {
            if ($this->pics()->where('isPrimary', '1')->first()) {
                return $this->pics->where('isPrimary', '1')->first()->url([''], 'absolute');
            }

            if ($this->pics()->where('status', 'ok')->first()) {
                return $this->pics->where('status', 'ok')->first()->url([''], 'absolute');
            }

            return '';
        });
    }

    public function getHeroImageAttribute()
    {
        $primaryIMG = $this->pics()->where('isPrimary', '1')->first();
        if ($primaryIMG) {
            return $primaryIMG->url([''], 'absolute');
        }

        $imgs = $this->pics()->where('status', 'ok')->first();
        if ($imgs) {
            return $imgs->url([''], 'absolute');
        }

        return '';
    }

    public function path(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->getURL('absolute'),
        );
    }

    public function pathEdit(): Attribute
    {
        return Attribute::make(
            get: fn () => route('staff-cityInfos', $this->id),
        );
    }

    /* Relationships */

    public function pics()
    {
        // the "old" pics are still used on the live site currently
        return $this->hasMany(Pic::class, 'subjectID')
            ->where('subjectType', 'cityInfo')
            ->whereIn('type', ['user', 'old']);
    }

    public function attachedTexts()
    {
        return $this->hasMany(\App\Models\AttachedText::class, 'subjectID')->where('subjectType', 'cityInfo');
    }

    public function cityComments()
    {
        return $this->hasMany(\App\Models\CityComment::class, 'cityID');
    }

    public function countryInfo()
    {
        return $this->hasOne(\App\Models\CountryInfo::class, 'country', 'country');
    }

    public function slp()
    {
        return $this->morphMany(SpecialLandingPage::class, 'subjectable');
    }

    public function districts()
    {
        return $this->hasMany(District::class, 'cityId');
    }

    /* Note: No listings() because it probably wouldn't work well as a Laravel relation because of having to match city/region/country.
    Use Listing::byCityInfo() instead. */

    /*  custom  */
    public function getQuickestAnswer()
    {
        $items = [
            'topRatedHostel' => $this->getTopRatedHostel(),
            'bestSoloTraveller' => $this->getBestHostelByType('socializing', 'exceptTopRated'),
            'cheapestHostel' => $this->getCheapestHostel(),
        ];

        return count(array_filter($items)) > 0 ? $items : null;
    }

    public function getCheapestHostel()
    {
        return Listing::where('id', $this->cheapestHostel)->first();
    }

    public function getFiltersCacheKey(): string
    {
        return 'city-filters-' . $this->id;
    }

    public function getLowestDormPriceAttribute()
    {
        return Cache::remember("LowestDormPriceAttribute:{$this->id}", 10 * 60, function () {
            $cheapestHostel = $this->getCheapestHostel();
            if (! $cheapestHostel) {
                return PriceHistory::CITY_DEFAULT_PRICE;
            }

            $lowestDormPrice = round(PriceHistory::select(DB::raw('AVG(averagePricePerNight) as priceAverage'))
                ->where('listingID', $cheapestHostel->id)
                ->where('roomType', 'dorm')
                ->where('month', '>=', Carbon::now()->subMonths(6)->startOfMonth()->format('Y-m-d'))
                ->where('month', '<=', Carbon::now()->addMonths(2)->startOfMonth()->format('Y-m-d'))
                ->groupBy('listingID')
                ->orderBy('priceAverage', 'ASC')
                ->limit(1)
                ->value('priceAverage'));

            return $lowestDormPrice ?: PriceHistory::CITY_DEFAULT_PRICE;
        });
    }

    public function getPartyHostels(): array|null
    {
        $listings = Listing::query()
            ->byCityInfo($this)
            ->hostels()
            ->where(function ($query): void {
                $query->where('compiledFeatures', 'like', '%partying%')
                    ->orWhere('mgmtFeatures', 'like', '%partying%');
            })
            ->orderBy('combinedRating', 'desc')
            ->areLive();

        if (! $listings) {
            return null;
        }

        return [
            'count' => $listings->count(),
            'best' => $listings->first(),
        ];
    }

    public function getAVGHostelsRating()
    {
        $rating = Listing::where([
            ['country', $this->country],
            ['region', $this->region],
            ['city', $this->city],
            ['propertyType', 'Hostel'],
        ])
            ->areLive()
            ->avg('combinedRating');

        return round($rating);
    }

    public function getPriceAVG()
    {
        return cache()->tags(['city:' . $this->id])->remember(
            "city:getPriceAVG:{$this->id}",
            now()->addDay(),
            function () {
                $listingIDs = Listing::areLive()->byCityInfo($this)->where('propertyType', 'Hostel')->pluck('id');

                $privatePrices = PriceHistory::select(DB::raw('AVG(averagePricePerNight) as averagePricePerNight'))
                    ->whereIn('listingID', $listingIDs)
                    ->where('roomType', 'private')
                    ->where('month', '>=', Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d'))
                    ->where('month', '<=', Carbon::now()->addMonths(2)->startOfMonth()->format('Y-m-d'))
                    ->groupBy('listingID')
                    ->pluck('averagePricePerNight')
                    ->avg();

                $dormPrices = PriceHistory::select(DB::raw('AVG(averagePricePerNight) as averagePricePerNight'))
                    ->whereIn('listingID', $listingIDs)
                    ->where('roomType', 'dorm')
                    ->where('month', '>=', Carbon::now()->subMonths(3)->startOfMonth()->format('Y-m-d'))
                    ->where('month', '<=', Carbon::now()->addMonths(2)->startOfMonth()->format('Y-m-d'))
                    ->groupBy('listingID')
                    ->pluck('averagePricePerNight')
                    ->avg();

                return [
                    'dorm' => round($dormPrices),
                    'private' => round($privatePrices),
                ];
            }
        );
    }

    public function getTopRatedHostel()
    {
        if ($this->bestFor['topRated'] !== null) {
            return $this->bestFor['topRated'];
        }

        $this->bestFor['topRated'] = Listing::where('id', $this->topRatedHostel)->first();

        return $this->bestFor['topRated'];
    }

    /*    public function getBestSoloTraveller( ) {
            if ($this->bestFor['soloTraveller'] !== null) {
                return $this->bestFor['soloTraveller'];
            }

            $this->bestFor['soloTraveller'] = Listing::
                where([
                    ['combinedRating', '>=', 85],
                    ['country', $this->country],
                    ['region', $this->region],
                    ['city', $this->city],
                    ['propertyType', 'Hostel'],
                ])->
                where(function($query) {
                    $query->where('compiledFeatures', 'like', '%socializing%')
                          ->orWhere('mgmtFeatures', 'like', '%socializing%');
                })->
                orderBy('combinedRating', 'desc')->
                areLive()->
                first();

            return $this->bestFor['soloTraveller'];
        }*/

    public function getBestHostelByType($type, $filter = '')
    {
        return cache()->tags(['city:' . $this->id])->remember(
            "city:getBestHostelByType:{$type}:{$filter}:{$this->id}",
            now()->addDay(),
            function () use ($type, $filter) {
                return Listing::query()
                    ->byCityInfo($this)
                    ->hostels()
                    ->where([
                        ['combinedRating', '>=', Listing::TOP_HOSTELS_MIN_RATIING],
                    ])
                    ->where(function ($query) use ($type): void {
                        $query->where('compiledFeatures', 'like', "%{$type}%")
                            ->orWhere('mgmtFeatures', 'like', "%{$type}%");
                    })
                    ->when(
                        ($filter === 'exceptTopRated' && $this->getTopRatedHostel()),
                        fn ($query) => $query->where('id', '!=', $this->getTopRatedHostel()->id)
                    )
                    ->hasActivePriceHistoryPastMonths()
                    ->orderBy('combinedRating', 'desc')
                    ->orderBy('overallContentScore', 'desc')
                    ->areLive()
                    ->first();
            }
        );
    }

    public function getBestTwoFemaleSoloTraveller(): Collection
    {
        return Listing::query()
            ->byCityInfo($this)
            ->hostels()
            ->topRated()
            ->where(function ($query): void {
                $query->where('compiledFeatures', 'like', '%female_solo_traveller%')
                    ->orWhere('mgmtFeatures', 'like', '%female_solo_traveller%');
            })
            ->orderBy('combinedRating', 'desc')
            ->areLive()
            ->limit(2)
            ->get();
    }

    public function getBestHostelByTypes($type1, $type2)
    {
        $listing = Listing::query()
            ->byCityInfo($this)
            ->hostels()
            ->topRated()
            ->where(function ($query) use ($type1, $type2): void {
                $query->where([
                    ['compiledFeatures', 'like', "%{$type1}%"],
                    ['compiledFeatures', 'like', "%{$type2}%"],
                ])
                    ->orWhere([
                        ['mgmtFeatures', 'like', "%{$type1}%"],
                        ['mgmtFeatures', 'like', "%{$type2}%"],
                    ]);
            })
            ->orderBy('combinedRating', 'desc')
            ->areLive()
            ->first();

        return $listing;
    }

    public function getMostRatingNeighborhood()
    {
        $items = Listing::select(DB::raw('count(*) as cityAlt_count, cityAlt'))
            ->byCityInfo($this)
            ->hostels()
            ->where([
                ['cityAlt', '!=', ''],
            ])
            ->areLive()
            ->orderBy('cityAlt_count', 'desc')
            ->groupBy('cityAlt')
            ->limit(3)
            ->get()
            ->toArray();

        if (! $items) {
            return [];
        }

        return Arr::pluck($items, 'cityAlt');
    }

    public function getListingCounts()
    {
        $items = Listing::select(DB::raw('count(*) as count, LOWER (propertyType) as ptype '))
            ->byCityInfo($this)
            ->whereIn('propertyType', ['Hostel', 'Hotel', 'Guesthouse', 'Apartment'])
            ->areLive()
            ->groupBy('propertyType')
            ->get()
            ->toArray();

        if (! $items) {
            return [];
        }

        return Arr::pluck($items, 'count', 'ptype');
    }

    /*    public function activeCategoryPage(CategoryPage $categoryPage): bool
        {
            return $this->countListingsCategoryPage($categoryPage) >= CategoryPage::MIN_LISTINGS_COUNT;
        }

        public function getListingsCategoryPage(CategoryPage $categoryPage): Collection
        {
            return Listing::query()
                ->cityCategoryPage($this, $categoryPage)
                ->get();
        }*/

    public function countListingsCategoryPage(CategoryPage $categoryPage): int
    {
        return Listing::query()
            ->cityCategoryPage($this, $categoryPage)
            ->count();
    }

    public static function getFeaturedCitiesData(?array $select = null): Collection
    {
        if ($select === null) {
            $select = ['id', 'city', 'hostelCount', 'country', 'region', 'cheapestHostel'];
        }

        $cities = self::select($select)
            ->whereIn('city', self::$featuredCitiesNames)
            ->with('pics')
            ->orderBy('hostelCount', 'DESC')
            ->get()
            ->reduce(
                fn ($carry, $item) => ! $carry->contains('city', $item->city) ? $carry->push($item) : $carry,
                collect([])
            )
            ->keyBy('city');

        if ($cities->isEmpty()) {
            return collect();
        }

        return collect(array_flip(self::$featuredCitiesNames))
            ->merge($cities);
    }
}

class EmptyCityInfo
{
    public $city = '';

    public $country = '';

    public $region = '';

    public function __construct()
    {
//        logWarning('no CityInfo');
    }

    public function translation()
    {
        return $this;
    }
}
