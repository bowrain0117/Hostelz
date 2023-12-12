<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Lib\BaseModel;
use Lib\GeoBounds;
use Lib\GeoPoint;

/*
    DB Tables:

    geonames - List of locations of landamrks (airports, etc). Also adminDiv1-4. (keys: countryCode, geonamesID).  Source: geonames.

    geonamesCountries - Currency, languages, etc. (keys: countryCode).  Source: Geonames.

    altName - names in different languages. (keys: geonamesID). Source: Geonames. (previously also imported from Yahoo Geoplanet).

*/

class Geonames extends BaseModel
{
    protected $table = 'geonames';

    protected $guarded = [];

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $return = [
                    'id' => ['isPrimaryKey' => true],
                    'name' => [],
                    'latitude' => ['dataType' => 'Lib\dataTypes\NumericDataType'],
                    'longitude' => ['dataType' => 'Lib\dataTypes\NumericDataType'],
                    'featureClass' => [],
                    'featureCode' => [],
                    'theirFeatureCode' => [],
                    'countryCode' => [],
                    'adminDiv1' => [],
                    'adminDiv2' => [],
                    'adminDiv3' => [],
                    'adminDiv4' => [],
                    'population' => ['dataType' => 'Lib\dataTypes\NumericDataType'],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $return;
    }

    public static function altNamesSubquery($query, $altName, $comparisonOperator = '=')
    {
        return $query->select('geonamesID')->from('altNames')->where('altName', $comparisonOperator, $altName);
    }

    public static function findCityRegionCountry($country, $region = '', $city = '', $comparisonOperator = '=')
    {
        debugOutput("gnGetCityInfo($city, $region, $country)");

        $return = [];

        // * Country *

        $geonamesCountry = self::findCountry($country, $comparisonOperator);
        if (! $geonamesCountry) {
            return false;
        } // probably best to stop if we can't get the country info
        $return['country'] = $geonamesCountry;
        $countryCode = $geonamesCountry->countryCode;

        $baseQuery = self::where('countryCode', $countryCode)->orderBy('population', 'DESC');

        // * Region *

        $regionBaseQuery = null;
        if ($region != '') {
            $regionInfo = self::findRegion($region, $countryCode, $comparisonOperator);
            $return['region'] = $regionInfo;
            if ($regionInfo) {
                switch ($regionInfo->featureCode) {
                    case 'ADM1':
                        $regionBaseQuery = with(clone $baseQuery)->where('adminDiv1', $regionInfo->adminDiv1);

                        break;
                    case 'ADM2':
                        $regionBaseQuery = with(clone $baseQuery)->where('adminDiv2', $regionInfo->adminDiv2);

                        break;
                    case 'ADM3':
                        $regionBaseQuery = with(clone $baseQuery)->where('adminDiv3', $regionInfo->adminDiv3);

                        break;
                }
            }
        }

        if ($city == '') {
            return $return;
        }

        // * City *

        if ($regionBaseQuery) {
            $loopTries = [$regionBaseQuery];
        } // (removed ", $baseQuery" to let it try without the region because it tended to find the wrong city)
        else {
            $loopTries = [$baseQuery];
        }

        foreach ($loopTries as $baseQuery) {
            debugOutput('trying geonames for city');
            $return['city'] = with(clone $baseQuery)->where('name', $comparisonOperator, $city)->where('featureClass', 'City')->first();
            if ($return['city']) {
                return $return;
            }

            debugOutput('trying altNames for city');
            $return['city'] = with(clone $baseQuery)->where('featureClass', 'City')->whereIn('id', function ($query) use ($city, $comparisonOperator): void {
                self::altNamesSubquery($query, $city, $comparisonOperator);
            })->first();
            if ($return['city']) {
                return $return;
            }

            debugOutput('trying geonames for anything');
            $return['city'] = with(clone $baseQuery)->where('name', $comparisonOperator, $city)->first();
            if ($return['city']) {
                return $return;
            }

            debugOutput('trying altNames for anything');
            $return['city'] = with(clone $baseQuery)->whereIn('id', function ($query) use ($city, $comparisonOperator): void {
                self::altNamesSubquery($query, $city, $comparisonOperator);
            })->first();
            if ($return['city']) {
                return $return;
            }
        }

        return $return;
    }

    public static function findRegion($region, $countryCode, $comparisonOperator = '=')
    {
        debugOutput("gnGetRegionInfo($region,$countryCode)");

        if ($countryCode == '' || $region == '') {
            throw new Exception('Missing region or countryCode.');
        }

        debugOutput('trying geonames for region');
        $result = self::where('countryCode', $countryCode)->where('featureClass', 'Region')->where('name', $comparisonOperator, $region)
            ->orderBy('featureCode')->orderBy('population', 'DESC')->first();
        if ($result) {
            return $result;
        }

        debugOutput('trying altNames for region');
        $result = self::where('countryCode', $countryCode)->where('featureClass', 'Region')->whereIn('id', function ($query) use ($region, $comparisonOperator): void {
            self::altNamesSubquery($query, $region, $comparisonOperator);
        })->orderBy('featureCode')->orderBy('population', 'DESC')->first();
        if ($result) {
            return $result;
        }

        debugOutput('trying geonames for land/terrain/etc');
        $result = self::where('countryCode', $countryCode)->whereIn('featureClass', ['Land', 'Terrain', 'Vegetation'])->where('name', $comparisonOperator, $region)
            ->orderBy('featureCode')->orderBy('population', 'DESC')->first();
        if ($result) {
            return $result;
        }

        debugOutput('trying altNames for land/terrain/etc');
        $result = self::where('countryCode', $countryCode)->whereIn('featureClass', ['Land', 'Terrain', 'Vegetation'])
            ->whereIn('id', function ($query) use ($region, $comparisonOperator): void {
                self::altNamesSubquery($query, $region, $comparisonOperator);
            })->orderBy('featureCode')->orderBy('population', 'DESC')->first();
        if ($result) {
            return $result;
        }

        return null;
    }

    public static function findCountry($country, $comparisonOperator = '=')
    {
        debugOutput("gnGetCountryInfo($country)");

        debugOutput('search in geonamesCountries');
        $result = DB::table('geonamesCountries')->where('country', $comparisonOperator, $country)->first();
        if ($result) {
            return $result;
        }

        debugOutput('trying altNames for country');
        $result = DB::table('geonamesCountries')->whereIn('geonamesID', function ($query) use ($country, $comparisonOperator): void {
            self::altNamesSubquery($query, $country, $comparisonOperator);
        })->first();
        if ($result) {
            return $result;
        }

        debugOutput('trying geonames for anything');
        $result = DB::table('geonamesCountries')->whereIn('geonamesID', function ($query) use ($country, $comparisonOperator): void {
            $query->select('id')->from('geonames')->where('name', $comparisonOperator, $country)->orderBy('population', 'DESC')->orderBy('featureCode');
        })->first();
        if ($result) {
            return $result;
        }

        debugOutput('trying altNames anything');
        $result = DB::table('geonamesCountries')->whereIn('countryCode', function ($query) use ($country, $comparisonOperator): void {
            $query->select('countryCode')->from('geonames')->orderBy('population', 'DESC')->whereIn('id', function ($query) use ($country, $comparisonOperator): void {
                self::altNamesSubquery($query, $country, $comparisonOperator);
            });
        })->first();
        if ($result) {
            return $result;
        }

        return null;
    }

    public static function getCountryByGeonamesID($countryID)
    {
        return DB::table('geonamesCountries')->where('geonamesID', $countryID)->first();
    }

    public static function getCountryByCountryCode($countryCode)
    {
        return DB::table('geonamesCountries')->where('countryCode', $countryCode)->first();
    }

    // returns results, also adding 'km' field of the distance to fromPoint
    // $maxByTypeCount can be a number or an array of feature=>count

    public static function findNearby(GeoPoint $point, $maxKM, $maxTotalItems = null, $featureCodes = null, $maxCountByFeature = null, $roundDistanceToDecimal = 1, $roundLatLongToDecimal = 4)
    {
        if ($maxCountByFeature && ! $featureCodes) {
            $featureCodes = array_keys($maxCountByFeature);
        }
        $query = self::groupBy('name', 'featureCode');
        if ($featureCodes) {
            $query->whereIn('featureCode', $featureCodes);
        }

        $geoItems = GeoBounds::makeFromApproximateDistanceFromPoint($point, $maxKM, 'km')->query($query)->get();

        // Sort by distance to the point (so if there are too many items, we'll at least get the closest ones)
        $geoItems = $geoItems->sort(function ($a, $b) use ($point) {
            $distanceA = $point->distanceToPoint($a->geoPoint());
            $distanceB = $point->distanceToPoint($b->geoPoint());
            if ($distanceA !== null && $distanceB !== null && $distanceA != $distanceB) {
                return $distanceA > $distanceB;
            }
        });

        $result = [];
        $featureCounts = [];
        $totalCount = 0;
        foreach ($geoItems as $geoItem) {
            $distance = $point->distanceToPoint($geoItem->geoPoint(), 'km');
            if ($distance > $maxKM) {
                continue;
            }
            if ($maxCountByFeature && $maxCountByFeature[$geoItem->featureCode]-- <= 0) {
                continue;
            }
            if ($maxTotalItems && ++$totalCount > $maxTotalItems) {
                break;
            }

            $result[] = [
                'name' => $geoItem->name,
                'featureCode' => $geoItem->featureCode,
                // (string is better than float for storing the exact decimal value)
                'latitude' => (string) ($roundLatLongToDecimal ? round($geoItem->latitude, $roundLatLongToDecimal) : $geoItem->latitude),
                'longitude' => (string) ($roundLatLongToDecimal ? round($geoItem->longitude, $roundLatLongToDecimal) : $geoItem->longitude),
                'km' => (string) ($roundDistanceToDecimal ? round($distance, $roundDistanceToDecimal) : $distance),
            ];
        }

        $result[] = static::addCityCenterGeoPoint($point);

        return $result;
    }

    private static function addCityCenterGeoPoint(GeoPoint $point): array
    {
        return [
            'name' => 'City Center',
            'featureCode' => 'Distance',
            'latitude' => $point->latitude,
            'longitude' => $point->longitude,
            'km' => '0',
        ];
    }

    /* Misc */

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

    public function getTranslation($language)
    {
        return DB::table('altNames')->where('geonamesID', $this->id)->where('language', $language)->orderBy('isShortName', 'desc')->orderBy('isPreferredName', 'desc')->value('altName');
    }

    /** Re-import Data **/
    private static function importSources()
    {
        return [
            /*
                countryInfo - basic info about countries
                    Fields: ISO, ISO3, ISO-Numeric, fips, Country, Capital, Area(in sq km), Population, Continent, tld, CurrencyCode, CurrencyName, Phone,
                    Postal Code Format, Postal Code Regex, Languages, geonameid, neighbours, EquivalentFipsCode
            */
            'http://download.geonames.org/export/dump/countryInfo.txt' => [
                'countryInfo.txt' => [
                    'table' => 'geonamesCountries',
                    'setSource' => null,
                    'processRowCallback' => null,
                    'skipFirstLine' => false,
                    'fields' => [
                        'countryCode', '', '', '', 'country', '', '', '', 'continentCode', '', 'currencyCode', 'currencyName', '', '', 'postalRegex', 'languages', 'geonamesID', '', '',
                    ],
                ],
            ],

            /*
                alternative/translated names of things

                alternateNameId   : the id of this alternate name, int
                geonameid         : geonameId referring to id in table 'geoname', int
                isolanguage       : iso 639 language code 2- or 3-characters; 4-characters 'post' for postal codes and 'iata' or 'icao' for airport codes, fr-1793 for French Revolution names, varchar(7)
                alternate name    : alternate name or name variant, varchar(200)
                isPreferredName   : '1', if this alternate name is an official/preferred name
                isShortName       : '1', if this is a short name like 'California' for 'State of California'
            */
            'http://download.geonames.org/export/dump/alternateNames.zip' => [
                'alternateNames.txt' => [
                    'table' => 'altNames',
                    'setSource' => 'geonames',
                    'processRowCallback' => null,
                    'skipFirstLine' => false,
                    'fields' => [
                        '', // we ignore their id and just use auto_increment our own id
                        'geonamesID',
                        'language', // (best to get all.  language is blank for many.)
                        'altName',
                        'isPreferredName', //  => ['dontInsertIfValues' => 0],
                        'isShortName',
                    ],
                ],
            ],

            /*
        	    names of everything in all countries
        	    // Note: We delete all non-major airports in geonames at the end of this script

                geonameid         : integer id of record in geonames database
                name              : name of geographical point (utf8) varchar(200)
                asciiname         : name of geographical point in plain ascii characters, varchar(200)
                alternatenames    : alternatenames, comma separated varchar(4000) (varchar(5000) for SQL Server)
                latitude          : latitude in decimal degrees (wgs84)
                longitude         : longitude in decimal degrees (wgs84)
                feature class     : see http://www.geonames.org/export/codes.html, char(1)
                feature code      : see http://www.geonames.org/export/codes.html, varchar(10)
                country code      : ISO-3166 2-letter country code, 2 characters
                cc2               : alternate country codes, comma separated, ISO-3166 2-letter country code, 60 characters
                admin1 code       : fipscode (subject to change to iso code), isocode for the us and ch, see file admin1Codes.txt for display names of this code; varchar(20)
                admin2 code       : code for the second administrative division, a county in the US, see file admin2Codes.txt; varchar(80)
                admin3 code       : code for third level administrative division, varchar(20)
                admin4 code       : code for fourth level administrative division, varchar(20)
                population        : bigint (4 byte int)
                elevation         : in meters, integer
                gtopo30           : average elevation of 30'x30' (ca 900mx900m) area in meters, integer
                timezone          : the timezone id (see file timeZone.txt)
                modification date : date of last modification in yyyy-MM-dd format
            */
            'http://download.geonames.org/export/dump/allCountries.zip' => [
                'allCountries.txt' => [
                    'table' => 'geonames',
                    'setSource' => null,
                    'processRowCallback' => function (&$insertValues) {
                        $featureMap = [
                            // A country, state, region,...
                            'ADM1' => 'ADM1', 'ADM2' => 'ADM2', 'ADM3' => 'ADM3', 'ADM4' => 'ADM4', 'ADMD' => 'ADMD',
                            'PCL' => 'City', 'PCLD' => 'City', 'PCLF' => 'City', 'PCLI' => 'City', 'PCLIX' => 'City',
                            'PCLS' => 'City', 'PRSH' => 'City', 'TERR' => 'City', 'TERR' => 'City', 'ZN' => 'City', 'ZNB' => 'City',
                            // H stream, lake, ...
                            'BAY' => 'Bay', 'BAYS' => 'Bays', 'FLLS' => 'Waterfall', 'GLCR' => 'Glacier', 'GULF' => 'Gulf', 'LK' => 'Lake',
                            'LKC' => 'Crater Lake', 'LKS' => 'Lakes', 'LKSC' => 'Crater Lakes', 'SD' => 'Sound', 'SPNT' => 'Hot Spring',
                            // L parks,area, ...
                            'PRK' => 'Park', 'AMUS' => 'Amusement Park', 'AREA' => 'Area', 'CST' => 'Coast', 'RES' => 'Reserve',
                            'RESA' => 'Agricultural Reserve', 'RESF' => 'Forest Reserve', 'RESH' => 'Hunting Reserve', 'RESN' => 'Nature Reserve',
                            'RESP' => 'Nature Reserve', 'RESV' => 'Reservation', 'RESW' => 'Wildlife Reservation', 'RGN' => 'Region', 'RGNE' => 'Region',
                            'RGNL' => 'Region', 'TRB' => 'Tribal Area',
                            // P city, village,...
                            'PPL' => 'City', 'PPLA' => 'City', 'PPLC' => 'City', 'PPLG' => 'City', 'PPLL' => 'City', 'PPLQ' => 'City',
                            'PPLR' => 'City', 'PPLS' => 'City', 'PPLW' => 'City', 'PPLX' => 'City', 'STLMT' => 'City',
                            // S spot, building, farm
                            'MTRO' => 'Metro Station', 'BUSTN' => 'Bus Station', 'BUSTP' => 'Bus Stop', 'MUS' => 'Museum',
                            'RSTN' => 'Train Station', 'RSTP' => 'Train Stop', 'AIRP' => 'Airport', 'ZOO' => 'Zoo',
                            // T mountain,hill,rock,...
                            'MT' => 'Mountain', 'MTS' => 'Mountains', 'BCH' => 'Beach', 'BCHS' => 'Beaches', 'CAPE' => 'Cape',
                            'CNYN' => 'Canyon', 'ISL' => 'Island', 'ISLS' => 'Islands', 'HLL' => 'Hill', 'PT' => 'Point', 'PTS', 'Points',
                            // V forest,heath,...
                            'FRST' => 'Forest',
                        ];

                        if (array_key_exists($insertValues['theirFeatureCode'], $featureMap)) {
                            $insertValues['featureCode'] = $featureMap[$insertValues['theirFeatureCode']];
                        }

                        return true; // true means we do want to insert this row
                    },
                    'skipFirstLine' => false,
                    'fields' => [
                        'id',
                        'name',
                        '',
                        '',
                        'latitude',
                        'longitude',
                        'featureClass' => ['A' => 'Region', 'H' => 'Water', 'L' => 'Land', 'P' => 'City', 'R' => 'Road',
                            'S' => 'Structure', 'T' => 'Terrain', 'U' => 'Underwater', 'V' => 'Vegetation',
                        ],
                        'theirFeatureCode',
                        'countryCode',
                        '',
                        'adminDiv1',
                        'adminDiv2',
                        'adminDiv3',
                        'adminDiv4',
                        'population', //  => ['insertIfGreaterThan' => 15000]
                    ],
                ],
            ],

            // geoplanet
            /*
        	Yahoo Geoplanet - Also names, but no lat/long coordinates. -> Last version: http://developer.yahoo.com/geo/geoplanet/data/geoplanet_data_7.4.0.zip -> no longer updated.
        -> Saved copy of last version: http://ydn.zenfs.com/site/geo/geoplanet_data_7.9.0.zip

            	Types:
            	* Q (qualified name): this name is the preferred name for the place in a language different than that used by residents of the place (e.g. "紐約" for New York)
                * V (variation): this name is a well-known (but unofficial) name for the place (e.g. "New York City" for New York)
                * A (abbreviation): this name is a abbreviation or code for the place (e.g. "NYC" for New York)
                * S (synonym): this name is a colloquial name for the place (e.g. "Big Apple" for New York)
            	* P (English preferred) name type is similar to the Q name type, except that it applies only to the English language. If you want to display the English name for a place and there is no P name, use the name from the geoplanet_places file.
            */
            /* (removing - it didn't seem like we were actually using the geoplanet data for anything)
            	'http://ydn.zenfs.com/site/geo/geoplanet_data_7.9.0.zip' => [
            		'geoplanet_aliases_7.9.0.tsv' => [
            			'table' => 'altNames',
            			'setSource' => 'geoplanet',
        			    'processRowCallback' => null,
            			'skipFirstLine' => true,
            			'fields' => [
            				'geonamesID',
            				'altName',
            				'isPreferredName' => ['Q' => '1','V' => '0','A' => '0','S' => '0','P' => '0'],
            				'language' => $LANG_ISO_639_2_MAP
            			]
            		]
            	],
            */
        ];
    }

    private static function tempDataPath()
    {
        return config('custom.userRoot') . '/data/geonames/';
    }

    public static function downloadData()
    {
        $output = '';

        set_time_limit(1 * 60 * 60);

        self::resetDataImportDataCompletedCounters();

        foreach (self::importSources() as $sourceURL => $files) {
            $explodedURL = explode('/', $sourceURL);
            $sourceFile = end($explodedURL);

            $output .= "$sourceURL: '$sourceFile' ";

            if (! copy($sourceURL, self::tempDataPath() . $sourceFile)) {
                exit();
            }
            if (strpos($sourceFile, '.zip') !== false) {
                exec('unzip -o ' . self::tempDataPath() . "$sourceFile -d " . self::tempDataPath());
            }

            if (! file_exists(self::tempDataPath() . $sourceFile)) {
                throw new Exception('Destination file not found.');
            }

            $output .= "ok\n";
        }

        return $output;
    }

    /*
        This resets the cache variables that are used to track how much of importData() was done
        so it can be restarted if the script doesn't complete on the first try (which happens).
    */

    public static function resetDataImportDataCompletedCounters(): void
    {
        foreach (self::importSources() as $sourceURL => $files) {
            foreach ($files as $file => $fileInfo) {
                // Make sure we resent our dataCompleted counters since we're getting new data
                Cache::forget("Geonames:dataCompleted:$file");
            }
        }
    }

    /*
        $testMode - Doesn't write to the database.
    */

    public static function importData($testMode)
    {
        $output = '';

        if (! $testMode && ! App::environment('production')) {
            throw new Exception('Should be run from production. Otherwise Clockwork and/or debug mode causes it to run out of memory.');
        }

        set_time_limit(6 * 60 * 60);
        DB::disableQueryLog(); // to save memory
        if (ini_get('memory_limit') < 256) {
            ini_set('memory_limit', '256M');
        }

        $totalInserts = 0;

        foreach (self::importSources() as $sourceURL => $files) {
            $output .= "* $sourceURL: *\n\n";
            $lastPercentDone = 0;

            foreach ($files as $file => $fileInfo) {
                $filesize = File::size(self::tempDataPath() . $file);
                $output .= "$file: ($filesize bytes) ";

                $fp = fopen(self::tempDataPath() . $file, 'r');
                if (! $fp) {
                    throw new Exception("Couldn't open geonames $file.");
                }

                if ($testMode) {
                    $doneBytes = 0;
                } else {
                    /* we track how much was done so it can be restarted if the script doesn't complete on the first try (which happens) */
                    $completedDataCounterKey = "Geonames:dataCompleted:$file";
                    $doneBytes = Cache::get($completedDataCounterKey, 0);
                    if (! $doneBytes) {
                        // Starting a new data file, delete existing data from the table
                        if ($fileInfo['setSource'] != '') {
                            DB::table($fileInfo['table'])->where('source', $fileInfo['setSource'])->delete();
                            DB::statement("OPTIMIZE TABLE $fileInfo[table]");
                        } else {
                            DB::statement("TRUNCATE TABLE $fileInfo[table]");
                        }
                    } else {
                        if ($doneBytes == $filesize) {
                            $output .= "already completed.\n\n";

                            continue;
                        }
                        fseek($fp, $doneBytes);
                        $output .= "($doneBytes bytes already done) ";
                    }
                }

                $isFirstLine = true;
                while (true) {
                    $s = fgets($fp);
                    if ($s === false) {
                        break;
                    } // end of file
                    if ($s == '') {
                        $output .= '(empty line, skipping.) ';

                        continue; // no id, something not right, maybe that was the last line
                    }

                    $doneBytes += strlen($s);
                    $percentDone = round(100 * $doneBytes / $filesize);
                    if ($percentDone > $lastPercentDone) {
                        $output .= "$percentDone% ";
                        $lastPercentDone = $percentDone;
                    }

                    if (strpos($s, '#') === 0 || ($isFirstLine && $fileInfo['skipFirstLine'])) { // ("#" lines are comments)
                        $isFirstLine = false;

                        continue;
                    }

                    $values = explode("\t", $s);
                    if (! $values[0]) {
                        $output .= "('$s' has no values, stopping.) ";

                        break; // no id, something not right, maybe that was the last line
                    }

                    $insertValues = ($fileInfo['setSource'] ? ['source' => $fileInfo['setSource']] : []);
                    $fieldNum = 0;

                    if ($testMode) {
                        continue;
                    }

                    foreach ($fileInfo['fields'] as $fieldKey => $field) {
                        $value = trim($values[$fieldNum], "\x0B\0\n\r\" "); // value of this field in the current row
                        if (is_array($field)) { // map their values to ours using a map
                            if (array_key_exists($value, $field)) {
                                $insertValues[$fieldKey] = $field[$value];
                            } else {
                                $insertValues[$fieldKey] = $value;
                            }
                        } elseif ($field != '') {
                            $insertValues[$field] = $value;
                        }
                        $fieldNum++;
                    }

                    if ($fileInfo['processRowCallback']) {
                        if (! $fileInfo['processRowCallback']($insertValues)) {
                            continue;
                        } // (if the processRowCallback returns false, we don't save that row)
                    }

                    DB::table($fileInfo['table'])->insert($insertValues);
                    Cache::forever($completedDataCounterKey, $doneBytes);
                }

                fclose($fp);

                $output .= "\n\n";
            }
        }

        // * Misc fixes *

        if (! $testMode) {
            // ** Delete all non-Major airports from geonames (by removing any without a IATA code)
            $ids = self::where('featureCode', 'Airport')->leftJoin('altNames', function ($join): void {
                $join->on('geonames.id', '=', 'altNames.geonamesID')->where('language', '=', 'IATA');
            })->whereNull('altNames.id')->select('geonames.id')->pluck('id');
            self::whereIn('id', $ids)->delete();

            // Language Changes
            // Geonames uses "pt" for most of their Portuguese, but our site currently uses pt-br for Portuguese...
            DB::table('altNames')->where('language', 'pt')->update(['language' => 'pt-br']);
        }

        return $output;
    }
}
