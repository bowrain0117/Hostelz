<?php

namespace App\Models;

class ContinentInfo
{
    private const CONTINENTS_PATTERN = '^Africa|Australia---Oceania|Central---East-Asia|Central---South-America|Eastern-Europe---Russia|Mexico---Caribbean|Middle-East|North-America|South---Southeast-Asia|UK---Ireland|Western-Europe$';

    private static $continents = [
        'Africa' => [
            'urlSlug' => 'Africa',
            'bumpyCase' => 'Africa',
        ],
        'Australia & Oceania' => [
            'urlSlug' => 'Australia---Oceania',
            'bumpyCase' => 'AustraliaOceania',
        ],
        'Central & East Asia' => [
            'urlSlug' => 'Central---East-Asia',
            'bumpyCase' => 'CentralEastAsia',
        ],
        'Central & South America' => [
            'urlSlug' => 'Central---South-America',
            'bumpyCase' => 'CentralSouthAmerica',
        ],
        'Eastern Europe & Russia' => [
            'urlSlug' => 'Eastern-Europe---Russia',
            'bumpyCase' => 'EasternEuropeRussia',
        ],
        'Mexico & Caribbean' => [
            'urlSlug' => 'Mexico---Caribbean',
            'bumpyCase' => 'MexicoCaribbean',
        ],
        'Middle East' => [
            'urlSlug' => 'Middle-East',
            'bumpyCase' => 'MiddleEast',
        ],
        'North America' => [
            'urlSlug' => 'North-America',
            'bumpyCase' => 'NorthAmerica',
        ],
        'South & Southeast Asia' => [
            'urlSlug' => 'South---Southeast-Asia',
            'bumpyCase' => 'SouthSoutheastAsia',
        ],
        'UK & Ireland' => [
            'urlSlug' => 'UK---Ireland',
            'bumpyCase' => 'UKIreland',
        ],
        'Western Europe' => [
            'urlSlug' => 'Western-Europe',
            'bumpyCase' => 'WesternEurope',
        ],
    ];

    // * Non-static *

    public $continentName;

    public $continentInfo;

    public function __construct($continentName)
    {
        $this->continentName = $continentName;
        $this->continentInfo = self::$continents[$continentName];
    }

    // So we can get info directly such as $continentInfo->urlSlug, etc.

    public function __get($name)
    {
        return $this->continentInfo[$name] ?? null;
    }

    public function name()
    {
        return $this->continentName;
    }

    public function translation($language = null)
    {
        if ($language == 'en' || ($language == null && Languages::currentCode() == 'en')) {
            return $this->continentName;
        } else {
            return langGet('continents.' . $this->bumpyCase, false, [], $language);
        }
    }

    public function getURL($urlType = 'auto', $language = null)
    {
        return CityInfo::makeCitiesUrl('', '', $this->continentName, $urlType, $language);
    }

    // * Static Methods (repository) *

    public static function findByName($continentName)
    {
        // Returns a new instance of self.  We cache them so each continent never has more than one object created for it.
        static $instances = [];

        if (! isset($instances[$continentName])) {
            $instances[$continentName] = new static($continentName);
        }

        return $instances[$continentName];
    }

    public static function findByUrlSlug($slug)
    {
        // Check for a correct URL
        $result = searchArrayForProperty(self::$continents, 'urlSlug', $slug, 'key');
        if ($result) {
            return self::findByName($result);
        }

        // Try searching for it using the current language continent names
        foreach (langGet('continents') as $continentKey => $continentName) {
            if (is_array($continentName)) {
                continue;
            } // skip the language file's meta data
            $quoted = preg_quote($slug, null);
            if ($quoted === '') {
                return null;
            }
            if (preg_match('`^' . str_replace('\-', '.', $quoted) . '$`iu', $continentName)) {
                return self::findByBumpyCase($continentKey);
            }
        }

        return null;
    }

    public static function findByBumpyCase($slug)
    {
        $result = searchArrayForProperty(self::$continents, 'bumpyCase', $slug, 'key');
        if (! $result) {
            return null;
        }

        return self::findByName($result);
    }

    public static function all()
    {
        $return = [];
        foreach (self::$continents as $name => $info) {
            $return[$name] = self::findByName($name);
        }

        return $return;
    }

    public static function allNames()
    {
        return array_keys(self::$continents);
    }

    public static function isContinent($s)
    {
        return in_array($s, self::$continents);
    }

    public static function getContinentsSlugPattern()
    {
        return self::CONTINENTS_PATTERN;
    }

    public function countries()
    {
        return CountryInfo::areLive()->where('continent', $this->name())->orderBy('country')->get();
    }
}
