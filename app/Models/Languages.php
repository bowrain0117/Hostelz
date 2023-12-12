<?php

namespace App\Models;

use Exception;
use Illuminate\Support\Facades\App;
use Request;

class Languages
{
    public const OTHER_CODE_STANDARDS_SUPPORTED = ['IANA', 'Google'];

    public const DEFAULT_LANG_CODE = 'en';

    private static $LANGUAGE_INFO = [
        'en' => [
            'name' => 'English',
            'isUsedOnLiveSite' => true,
            'locale' => 'en_US.utf8',
            'bookingSearchDateFormat' => 'D, M d',
        ],
        'fr' => [
            'name' => 'French',
            'isUsedOnLiveSite' => true,
            'locale' => 'fr_FR.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'es' => [
            'name' => 'Spanish',
            'isUsedOnLiveSite' => true,
            'locale' => 'es_US.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'it' => [
            'name' => 'Italian',
            'isUsedOnLiveSite' => true,
            'locale' => 'it_IT.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'de' => [
            'name' => 'German',
            'isUsedOnLiveSite' => true,
            'locale' => 'de_DE.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'ja' => [
            'name' => 'Japanese',
            'isUsedOnLiveSite' => true,
            'locale' => 'ja_JP.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'pt-br' => [
            'name' => 'Brazilian Portuguese',
            'isUsedOnLiveSite' => true,
            'locale' => 'pt_BR.utf8',
            'otherCodes' => ['IANA' => 'pt-BR', 'Google' => 'pt-BR'],
            'bookingSearchDateFormat' => 'D d M',
        ],
        'pt' => [
            'name' => 'Portuguese',
            'isUsedOnLiveSite' => false,
            'locale' => 'pt_PT.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'zh' => [
            'name' => 'Simplified Chinese', /* simplified chinese is actually zh-hans, but we use zh as an abbreviation.  zh-hant is traditional (Hong Kong / Taiwan) if we eventually add that */
            'isUsedOnLiveSite' => false,
            'locale' => 'zh_CN.utf8',
            'otherCodes' => ['IANA' => 'zh-HANS', 'Google' => 'zh-CN'],
            'bookingSearchDateFormat' => 'D d M',
        ],
        'ko' => [
            'name' => 'Korean',
            'isUsedOnLiveSite' => false,
            'locale' => 'ko_KR.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'sv' => [
            'name' => 'Swedish',
            'isUsedOnLiveSite' => false,
            'locale' => 'sv_SE.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'fi' => [
            'name' => 'Finnish',
            'isUsedOnLiveSite' => false,
            'locale' => 'fi_FI.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'no' => [
            'name' => 'Norwegian',
            'isUsedOnLiveSite' => false,
            'locale' => 'nr_ZA.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'da' => [
            'name' => 'Danish',
            'isUsedOnLiveSite' => false,
            'locale' => 'da_DK.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'cs' => [
            'name' => 'Czech',
            'isUsedOnLiveSite' => false,
            'locale' => 'cs_CZ.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'pl' => [
            'name' => 'Polish',
            'isUsedOnLiveSite' => false,
            'locale' => 'pl_PL.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'nl' => [
            'name' => 'Dutch',
            'isUsedOnLiveSite' => false,
            'locale' => 'nl_NL.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
        'ru' => [
            'name' => 'Russian',
            'isUsedOnLiveSite' => false,
            'locale' => 'ru_RU.utf8',
            'bookingSearchDateFormat' => 'D d M',
        ],
    ];

    private static $currentLanguageCode;

    /** Non-static Interface **/
    public $languageCode;

    public function __construct($languageCode)
    {
        if (! array_key_exists($languageCode, self::$LANGUAGE_INFO)) {
            throw new Exception("Unknown language code '$languageCode'.");
        }
        $this->languageCode = $languageCode;
    }

    // So we can get info directly such as $language->name, etc.

    public function __get($propertyName)
    {
        return self::$LANGUAGE_INFO[$this->languageCode][$propertyName];
    }

    public function otherCodeStandard($otherCodeStandard)
    {
        if (! in_array($otherCodeStandard, self::OTHER_CODE_STANDARDS_SUPPORTED)) {
            throw new Exception("Unknown code standard '$otherCodeStandard'.");
        }

        return isset(self::$LANGUAGE_INFO[$this->languageCode]['otherCodes'][$otherCodeStandard]) ?
            $this->otherCodes[$otherCodeStandard] : $this->languageCode;
    }

    public function urlPrefix()
    {
        if ($this->languageCode === 'en') {
            return '';
        }

        return "/l/$this->languageCode";
    }

    public function addUrlPrefix($toURL)
    {
        return self::alterPathOfUrl($toURL, function ($path) {
            return $this->urlPrefix() . $path;
        });
    }

    public function removeUrlPrefix($fromURL)
    {
        // We get some URLs that start with "//"
        if (substr($fromURL, 0, 2) == '//') {
            $fromURL = substr($fromURL, 1);
        }

        return self::alterPathOfUrl($fromURL, function ($path) {
            // (if English the only thing this function really does is change '' URLs to '/')
            if ($this->languageCode == 'en') {
                return $path;
            }
            if (strpos($path, $this->urlPrefix()) !== 0) {
                throw new Exception("'$path' is missing the language prefix '" . $this->urlPrefix() . "'.");
            }

            return substr($path, strlen($this->urlPrefix()));
        });
    }

    public function changeUrlFromThisLanguageTo($url, $toLanguageCode)
    {
        return self::get($toLanguageCode)->addUrlPrefix($this->removeUrlPrefix($url));
    }

    // Perform an action on the path part of a url with a $callback($path) function.
    // Url can be absolute or relative ('http://yahoo.com/foo' or '/foo').

    private static function alterPathOfUrl($url, $callback)
    {
        // Remove unnecesarry added ports (some bots add the port)
        if (strpos($url, ':80') === 0 || strpos($url, ':443') === 0) {
            $url = explode(':', $url);
            $url = array_pop($url);
        }

        // Special case: "?" query variables on the root homepage.
        if (substr($url, 0, 1) == '?') {
            $url = '/' . $url;
        }

        if (substr($url, 0, 1) == '/' || $url == '') {
            // Relative URL
            $urlParts = ['host' => '', 'path' => $url];
        } else {
            // Absolute URL
            $urlParts = parse_url($url);
            if (! isset($urlParts['scheme']) || ! isset($urlParts['host'])) {
                throw new Exception("Scheme or host missing for '$url'.");
            }
            $host = "$urlParts[scheme]://$urlParts[host]";
            $urlParts = ['host' => $host, 'path' => substr($url, strlen($host))];
        }
        if ($urlParts['path'] == '/') {
            $urlParts['path'] = '';
        }
        $urlParts['path'] = $callback($urlParts['path']);
        $result = $urlParts['host'] . $urlParts['path'];

        return $result == '' ? '/' : $result;
    }

    // Format a number in the current language's locale, using $decimals decimal places. (was done by smarty_modifier_localNumberFormat($string, $decimals=0) on the old site)

    public function numberFormat($number, $decimals = null)
    {
        $numberFormatter = new \NumberFormatter($this->locale, \NumberFormatter::DECIMAL);
        if ($decimals !== null) {
            $numberFormatter->setAttribute(\NumberFormatter::FRACTION_DIGITS, $decimals);
        }

        return $numberFormatter->format((float) $number);
    }

    /*
        Format a number in the current language's locale, using $decimals decimal places.
        Note: We do this instead of Carbon's formatLocalized(), because that currently uses setlocale() which isn't thread-safe (in case we want to run on a threaded server).
        See formats: http://userguide.icu-project.org/formatparse/datetime
    */

    public function dateFormat($carbonTimeObject, $format)
    {
        $dateFormatter = new \IntlDateFormatter(
            $this->locale,
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::FULL,
            \date_default_timezone_get(),
            \IntlDateFormatter::GREGORIAN,
            $format
        );

        return $dateFormatter->format($carbonTimeObject);
    }

    /**  Static Methods (repository) **/
    public static function determineTheLanguageFromUrlPath($urlPath)
    {
        if (substr($urlPath, 0, 2) !== 'l/') {
            return 'en';
        }
        $langStrLen = strpos($urlPath . '/', '/', 2) - 2;
        if ($langStrLen < 2) {
            return null;
        }
        $language = substr($urlPath, 2, $langStrLen);
        if ($language === 'en' || ! self::isCodeUsedOnLiveSite($language)) {
            return null;
        } // 'l/en' URLs aren't allowed, or is unknown language

        return $language;
    }

    public static function get($languageCode = '')
    {
        // Returns a new instance of self.  We cache them so each continent never has more than one object created for it.
        static $instances = [];

        if ($languageCode == '') {
            $languageCode = self::$currentLanguageCode ?? self::DEFAULT_LANG_CODE;
        }
        if (! isset($instances[$languageCode])) {
            $instances[$languageCode] = new static($languageCode);
        }

        return $instances[$languageCode];
    }

    public static function getFromOtherCodeStandard($otherCodeStandard, $langCode)
    {
        if (! in_array($otherCodeStandard, self::OTHER_CODE_STANDARDS_SUPPORTED)) {
            throw new Exception("Unknown code standard '$otherCodeStandard'.");
        }

        foreach (self::$LANGUAGE_INFO as $ourLangCode => $langInfo) {
            if ($ourLangCode === $langCode || (isset($langInfo['otherCodes']) && $langInfo['otherCodes'][$otherCodeStandard] === $langCode)) {
                return self::get($ourLangCode);
            }
        }

        return null;
    }

    public static function isKnownLanguageCode($languageCode)
    {
        return array_key_exists($languageCode, self::$LANGUAGE_INFO);
    }

    public static function isCodeUsedOnLiveSite($languageCode)
    {
        return self::isKnownLanguageCode($languageCode) && self::$LANGUAGE_INFO[$languageCode]['isUsedOnLiveSite'];
    }

    private static function allLive()
    {
        $result = [];
        foreach (self::allLiveSiteCodes() as $code) {
            $result[$code] = self::get($code);
        }

        return $result;
    }

    public static function allLiveSiteCodes()
    {
        return array_filter(self::allCodes(), function ($code) {
            return self::$LANGUAGE_INFO[$code]['isUsedOnLiveSite'];
        });
    }

    public static function allCodes()
    {
        return array_keys(self::$LANGUAGE_INFO);
    }

    public static function allCodesKeyedByName()
    {
        $return = [];
        foreach (self::$LANGUAGE_INFO as $langCode => $langInfo) {
            $return[$langInfo['name']] = $langCode;
        }

        return $return;
    }

    public static function allLiveSiteCodesKeyedByName()
    {
        $return = [];
        foreach (self::$LANGUAGE_INFO as $langCode => $langInfo) {
            if ($langInfo['isUsedOnLiveSite']) {
                $return[$langInfo['name']] = $langCode;
            }
        }

        return $return;
    }

    public static function allNamesKeyedByCode()
    {
        return array_flip(self::allCodesKeyedByName());
    }

    /** Current Website Language **/
    public static function currentCode($setLanguage = null)
    {
        if ($setLanguage != null) {
            self::$currentLanguageCode = $setLanguage;
        }

        return self::$currentLanguageCode;
    }

    public static function setLanguageLocale($languageCode): void
    {
        self::currentCode($languageCode);
        App::setLocale($languageCode);
    }

    public static function urlWithLanguage($url)
    {
        $arr = explode('/', $url);

        if (array_search('l', $arr, true)) {
            array_splice($arr, array_search('l', $arr, true), 2);
            $url = implode('/', $arr);
        }

        return self::get(self::$currentLanguageCode)->addUrlPrefix($url);
    }

    public static function current()
    {
        $languageCode = self::determineTheLanguageFromUrlPath(Request::path());

        if (empty($languageCode)) {
            // set it to English and define the routes below so we can check for $languageCode == '' and throw an error at the bottom of this script.
            self::currentCode('en');
        } else {
            self::currentCode($languageCode);
            if ($languageCode !== 'en') {
                App::setLocale($languageCode);
            } // sets the language for Laravel language/translation functions
        }

        return self::get(self::$currentLanguageCode);
    }

    public static function isDefaultLanguage(): bool
    {
        return self::determineTheLanguageFromUrlPath(Request::path()) === self::DEFAULT_LANG_CODE;
    }

    public static function currentUrlInAllLiveLanguages()
    {
        $currentUrlWithoutPrefix = self::current()->removeUrlPrefix(Request::getRequestUri());

        $return = [];
        foreach (self::allLive() as $languageCode => $language) {
            $return[$languageCode] = $language->addUrlPrefix($currentUrlWithoutPrefix);
        }

        return $return;
    }
}
