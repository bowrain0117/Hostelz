<?php

namespace Lib;

use AmrShawky\LaravelCurrency\Facade\Currency;
use App\Models\Languages;
use Exception;
use Illuminate\Support\Facades\Cache;

// probably should have a dependency on an App class, but oh well.

/*

    See also https://github.com/florianv/swap (can use it, or borrow from their providers list)

    currencies must be on the conversion table we use (currendtly http://www.ecb.int/stats/eurofxref/eurofxref-daily.xml OR xe.com)
    see http://www.stylusstudio.com/xsllist/200108/post10640.html
    http://en.wikipedia.org/wiki/List_of_circulating_currencies ->
    for symbols use finance.yahoo.com/currency-converter/?amt=1&from=USD&to=PKR (replace with currency code)
    global for when this is included from within a function

    Can use http://hostelinfo.com/xeTest.php to check and add new currencies.

    Sources of exchange rate data that we could use:
    - https://openexchangerates.org/
    - https://currencylayer.com/ - 1000 requests per month. 168 currencies.
    - https://1forge.com - 1000 requests per *day*.
    - https://fixer.io/ - 1000 requests per month. 170 currencies.
*/

class Currencies
{
    private static $POPULAR_CURRENCIES = ['USD', 'EUR'];

    // If the user hasn't yet chosen a currency, use the hostel's local currency only if it's one of these
    public static $LOCAL_CURRENCY_DEFAULTS = ['USD', 'EUR', 'AUS', 'CAD'];

    private static $CURRENCIES = [
        'USD' => ['name' => 'US Dollars', 'decimals' => 2, 'prefix' => '$', 'suffix' => ''],
        'EUR' => ['name' => 'Euros', 'decimals' => 2, 'prefix' => '€', 'suffix' => ''],
        'CAD' => ['name' => 'Canadian Dollars', 'decimals' => 2, 'prefix' => 'CAD$', 'suffix' => ''],
        'AUD' => ['name' => 'Australian Dollars', 'decimals' => 2, 'prefix' => 'AU$', 'suffix' => ''],
        'GBP' => ['name' => 'British Pounds', 'decimals' => 2, 'prefix' => '£', 'suffix' => ''],
        'JPY' => ['name' => 'Japanese Yen', 'decimals' => 0, 'prefix' => '¥', 'suffix' => ' JPY'], // JPY needed because same symbol used for Chinese yuan
        'BGN' => ['name' => 'Bulgarian Lev', 'decimals' => 2, 'prefix' => '', 'suffix' => ' лв (LEV)'],
        'CZK' => ['name' => 'Czech Koruna', 'decimals' => 0, 'prefix' => '', 'suffix' => ' Kč'],
        'DKK' => ['name' => 'Danish Krone', 'decimals' => 2, 'prefix' => '', 'suffix' => ' kr'],
        'HUF' => ['name' => 'Hungarian Forint', 'decimals' => 0, 'prefix' => '', 'suffix' => ' Ft'],
        'PLN' => ['name' => 'Polish Złoty', 'decimals' => 2, 'prefix' => '', 'suffix' => ' zł'],
        'SEK' => ['name' => 'Swedish Krona', 'decimals' => 2, 'prefix' => '', 'suffix' => ' kr'],
        'CHF' => ['name' => 'Swiss Francs', 'decimals' => 2, 'prefix' => '', 'suffix' => ' SFr'],
        'NOK' => ['name' => 'Norwegian Kroner', 'decimals' => 2, 'prefix' => '', 'suffix' => ' NKr'],
        'HRK' => ['name' => 'Croatian Kuna', 'decimals' => 2, 'prefix' => '', 'suffix' => ' kn'],
        'RUB' => ['name' => 'Russiam Rubles', 'decimals' => 0, 'prefix' => '', 'suffix' => ' руб.'],
        'TRY' => ['name' => 'Turkish Lira', 'decimals' => 2, 'prefix' => '', 'suffix' => 'TL'],
        'BRL' => ['name' => 'Brazil Real', 'decimals' => 2, 'prefix' => 'R$', 'suffix' => ' real'],
        'CNY' => ['name' => 'Chinese Renmimbi', 'decimals' => 2, 'prefix' => '¥', 'suffix' => ' CNY'], // CNY needed because same symbol used for Japanese yen
        'HKD' => ['name' => 'Hong Kong Dollars', 'decimals' => 2, 'prefix' => 'HK$', 'suffix' => ''],
        'INR' => ['name' => 'Indian Rupees', 'decimals' => 0, 'prefix' => '₹ ', 'suffix' => ' Rs.'],
        'MXN' => ['name' => 'Mexican Peso', 'decimals' => 0, 'prefix' => 'MEX$', 'suffix' => ''],
        'NZD' => ['name' => 'New Zealand Dollars', 'decimals' => 2, 'prefix' => 'NZ$', 'suffix' => ''],
        'SGD' => ['name' => 'Singapore Dollars', 'decimals' => 2, 'prefix' => 'S$', 'suffix' => ''],
        'THB' => ['name' => 'Thai Baht', 'decimals' => 0, 'prefix' => '฿', 'suffix' => ' THB'],
        'ZAR' => ['name' => 'South African Rand', 'decimals' => 2, 'prefix' => 'R', 'suffix' => ' Rand'],
        'DZD' => ['name' => 'Algerian Dinar', 'decimals' => 0, 'prefix' => '', 'suffix' => ' dinar'],
        'ARS' => ['name' => 'Argentine Peso', 'decimals' => 2, 'prefix' => 'ARS$', 'suffix' => ''],
        'AMD' => ['name' => 'Armenian Dram', 'decimals' => 0, 'prefix' => '', 'suffix' => ' dram'],
        'AZN' => ['name' => 'Azerbaijan New Manat', 'decimals' => 2, 'prefix' => '', 'suffix' => ' manat'],
        'BSD' => ['name' => 'Bahamian Dollar', 'decimals' => 2, 'prefix' => '$', 'suffix' => ' BSD'],
        'BHD' => ['name' => 'Bahraini Dinar', 'decimals' => 2, 'prefix' => 'BD ', 'suffix' => ''],
        'BDT' => ['name' => 'Bangladeshi Taka', 'decimals' => 0, 'prefix' => '', 'suffix' => ' Tk'],
        'BZD' => ['name' => 'Belize Dollar', 'decimals' => 2, 'prefix' => 'BZ$', 'suffix' => ''],
        'BOB' => ['name' => 'Bolivian Boliviano', 'decimals' => 2, 'prefix' => 'Bs. ', 'suffix' => ''],
        'BND' => ['name' => 'Brunei Dollar', 'decimals' => 2, 'prefix' => 'B$', 'suffix' => ' BND'],
        'KHR' => ['name' => 'Cambodian Riel', 'decimals' => 0, 'prefix' => '៛', 'suffix' => ' KHR'],
        'XOF' => ['name' => 'CFA Franc BCEAO', 'decimals' => 0, 'prefix' => '', 'suffix' => ' BCEAO'],
        'XAF' => ['name' => 'CFA Franc BEAC', 'decimals' => 0, 'prefix' => '', 'suffix' => ' BEAC'],
        'XPF' => ['name' => 'CFP Franc', 'decimals' => 0, 'prefix' => 'F', 'suffix' => ''],
        'CLP' => ['name' => 'Chilean Peso', 'decimals' => 0, 'prefix' => 'CLP$', 'suffix' => ''],
        'COP' => ['name' => 'Colombian Peso', 'decimals' => 0, 'prefix' => 'COL$', 'suffix' => ''],
        'KMF' => ['name' => 'Comoros Franc', 'decimals' => 0, 'prefix' => '', 'suffix' => ' CF'],
        'CRC' => ['name' => 'Costa Rican Colon', 'decimals' => 0, 'prefix' => '₡', 'suffix' => ''],
        'DJF' => ['name' => 'Djibouti Franc', 'decimals' => 0, 'prefix' => 'Fdj ', 'suffix' => ''],
        'DOP' => ['name' => 'Dominican R. Peso', 'decimals' => 2, 'prefix' => 'RD$', 'suffix' => ''],
        'XCD' => ['name' => 'East Caribbean Dollar', 'decimals' => 2, 'prefix' => 'EC$', 'suffix' => ''],
        'EGP' => ['name' => 'Egyptian Pound', 'decimals' => 2, 'prefix' => '', 'suffix' => ' EGP'],
        'EEK' => ['name' => 'Estonian Kroon', 'decimals' => 2, 'prefix' => '', 'suffix' => ' EEK'],
        'FJD' => ['name' => 'Fiji Dollar', 'decimals' => 2, 'prefix' => 'FJ$', 'suffix' => ''],
        'HNL' => ['name' => 'Honduran Lempira', 'decimals' => 2, 'prefix' => 'L', 'suffix' => ''],
        'ISK' => ['name' => 'Iceland Krona', 'decimals' => 0, 'prefix' => 'kr ', 'suffix' => ''],
        'IDR' => ['name' => 'Indonesian Rupiah', 'decimals' => 0, 'prefix' => 'Rp ', 'suffix' => ''],
        'ILS' => ['name' => 'Israeli New Shekel', 'decimals' => 2, 'prefix' => '₪', 'suffix' => ''],
        'JOD' => ['name' => 'Jordanian Dinar', 'decimals' => 2, 'prefix' => 'JOD ', 'suffix' => ''],
        'KZT' => ['name' => 'Kazakhstan Tenge', 'decimals' => 0, 'prefix' => 'KZT ', 'suffix' => ''],
        'KES' => ['name' => 'Kenyan Shilling', 'decimals' => 0, 'prefix' => '', 'suffix' => ' KSh'],
        'KWD' => ['name' => 'Kuwaiti Dinar', 'decimals' => 2, 'prefix' => '', 'suffix' => ' KD'],
        'KGS' => ['name' => 'Kyrgyzstanian Som', 'decimals' => 0, 'prefix' => '', 'suffix' => ' som'],
        'LAK' => ['name' => 'Lao Kip', 'decimals' => 0, 'prefix' => '', 'suffix' => ' kip'],
        'LVL' => ['name' => 'Latvian Lats', 'decimals' => 2, 'prefix' => 'Ls ', 'suffix' => ''],
        'LBP' => ['name' => 'Lebanese Pound', 'decimals' => 0, 'prefix' => 'L£', 'suffix' => ''],
        'LTL' => ['name' => 'Lithuanian Litas', 'decimals' => 2, 'prefix' => 'Lt ', 'suffix' => ''],
        'MYR' => ['name' => 'Malaysian Ringgit', 'decimals' => 2, 'prefix' => 'RM', 'suffix' => ''],
        'MRO' => ['name' => 'Mauritanian Ouguiya', 'decimals' => 0, 'prefix' => 'UM ', 'suffix' => ''],
        'MUR' => ['name' => 'Mauritius Rupee', 'decimals' => 2, 'prefix' => '₨ ', 'suffix' => ''],
        'MNT' => ['name' => 'Mongolian Tugrik', 'decimals' => 0, 'prefix' => '₮ ', 'suffix' => ''],
        'MAD' => ['name' => 'Moroccan Dirham', 'decimals' => 2, 'prefix' => '', 'suffix' => ' د.م.'],
        'NAD' => ['name' => 'Namibia Dollar', 'decimals' => 2, 'prefix' => 'N$', 'suffix' => ''],
        'NPR' => ['name' => 'Nepalese Rupee', 'decimals' => 0, 'prefix' => '₨ ', 'suffix' => ''],
        'NIO' => ['name' => 'Nicaraguan Cordoba Oro', 'decimals' => 2, 'prefix' => 'C$ ', 'suffix' => ''],
        'OMR' => ['name' => 'Omani Rial', 'decimals' => 2, 'prefix' => '', 'suffix' => ' ر.ع.'],
        'PKR' => ['name' => 'Pakistan Rupee', 'decimals' => 0, 'prefix' => 'Rs. ', 'suffix' => ''],
        'PGK' => ['name' => 'Papua New Guinea Kina', 'decimals' => 2, 'prefix' => 'K ', 'suffix' => ''],
        'PYG' => ['name' => 'Paraguayan Guarani', 'decimals' => 0, 'prefix' => '₲ ', 'suffix' => ''],
        'PEN' => ['name' => 'Peruvian Nuevo Sol', 'decimals' => 2, 'prefix' => 'S/. ', 'suffix' => ''],
        'PHP' => ['name' => 'Philippine Peso', 'decimals' => 0, 'prefix' => '₱', 'suffix' => ''],
        'QAR' => ['name' => 'Qatari Rial', 'decimals' => 2, 'prefix' => '', 'suffix' => ' ر.ق'],
        'RON' => ['name' => 'Romanian New Lei', 'decimals' => 2, 'prefix' => 'L ', 'suffix' => ''],
        'RWF' => ['name' => 'Rwandan Franc', 'decimals' => 0, 'prefix' => 'RF ', 'suffix' => ''],
        'WST' => ['name' => 'Samoan Tala', 'decimals' => 2, 'prefix' => 'WS$', 'suffix' => ''],
        'SAR' => ['name' => 'Saudi Riyal', 'decimals' => 2, 'prefix' => '', 'suffix' => ' ر.س'],
        'SOS' => ['name' => 'Somali Shilling', 'decimals' => 0, 'prefix' => 'So. ', 'suffix' => ''],
        'KRW' => ['name' => 'South-Korean Won', 'decimals' => 0, 'prefix' => '₩ ', 'suffix' => ''],
        'LKR' => ['name' => 'Sri Lanka Rupee', 'decimals' => 0, 'prefix' => '₨ ', 'suffix' => ''],
        'SZL' => ['name' => 'Swaziland Lilangeni', 'decimals' => 2, 'prefix' => 'SZL ', 'suffix' => ''],
        'TWD' => ['name' => 'Taiwan Dollar', 'decimals' => 2, 'prefix' => 'NT$', 'suffix' => ''],
        'TZS' => ['name' => 'Tanzanian Shilling', 'decimals' => 0, 'prefix' => 'x ', 'suffix' => ''],
        'TOP' => ['name' => 'Tonga Pa\'anga', 'decimals' => 2, 'prefix' => 'T$', 'suffix' => ''],
        'TTD' => ['name' => 'Trinidad/Tobago Dollar', 'decimals' => 2, 'prefix' => '', 'suffix' => ' TTD'],
        'TND' => ['name' => 'Tunisian Dinar', 'decimals' => 2, 'prefix' => '', 'suffix' => ' د.ت'],
        'UGX' => ['name' => 'Uganda Shilling', 'decimals' => 0, 'prefix' => 'USh ', 'suffix' => ''],
        'UAH' => ['name' => 'Ukraine Hryvnia', 'decimals' => 2, 'prefix' => '₴', 'suffix' => ' UAH'],
        'UYU' => ['name' => 'Uruguayan Peso', 'decimals' => 2, 'prefix' => '$U', 'suffix' => ' UYU'],
        'AED' => ['name' => 'Utd. Arab Emir. Dirham', 'decimals' => 2, 'prefix' => '', 'suffix' => ' د.إ'],
        'VUV' => ['name' => 'Vanuatu Vatu', 'decimals' => 0, 'prefix' => 'Vt ', 'suffix' => ''],
        'VEF' => ['name' => 'Venezuelan Bolivar', 'decimals' => 2, 'prefix' => 'Bs ', 'suffix' => ''],
        'VND' => ['name' => 'Vietnamese Dong', 'decimals' => 0, 'prefix' => '₫', 'suffix' => ' VND'],
        'CUP' => ['name' => 'Cuban Peso', 'decimals' => 0, 'prefix' => '₱', 'suffix' => ''],
        'GMD' => ['name' => 'Gambian Dalasi', 'decimals' => 0, 'prefix' => '', 'suffix' => ' dalasis'],
        'PAB' => ['name' => 'Panamanian Balboa', 'decimals' => 2, 'prefix' => '', 'suffix' => ' balboa'],
        'IRR' => ['name' => 'Iranian Rial', 'decimals' => 0, 'prefix' => '', 'suffix' => ' rials'],
        'MMK' => ['name' => 'Burmese Kyat', 'decimals' => 0, 'prefix' => 'K', 'suffix' => ' kyat'],
        'ALL' => ['name' => 'Albanian Lek', 'decimals' => 0, 'prefix' => '', 'suffix' => ' Lekë'],
        'GHS' => ['name' => 'Ghanaian Cedi', 'decimals' => 2, 'prefix' => 'GH₵', 'suffix' => ''],
        'JMD' => ['name' => 'Jamaican Dollar', 'decimals' => 0, 'prefix' => '$', 'suffix' => ' Jamaican'],
        'GTQ' => ['name' => 'Guatemalan Quetzal', 'decimals' => 0, 'prefix' => '', 'suffix' => ' quetzales'],
        'GEL' => ['name' => 'Georgian Lari', 'decimals' => 2, 'prefix' => '', 'suffix' => ' GEL'],
        'RSD' => ['name' => 'Serbian Dinar', 'decimals' => 0, 'prefix' => 'РСД', 'suffix' => ''],
        'GIP' => ['name' => 'Gibraltar Pound', 'decimals' => 2, 'prefix' => '£', 'suffix' => ' GIP'],
        'MZN' => ['name' => 'Mozambican Metical', 'decimals' => 0, 'prefix' => '', 'suffix' => ' meticais'],
        'BAM' => ['name' => 'Bosnia Convertible Mark', 'decimals' => 2, 'prefix' => '', 'suffix' => ' KM'],
        'BWP' => ['name' => 'Botswana Pula', 'decimals' => 0, 'prefix' => '', 'suffix' => ' pula'],
        'ZMW' => ['name' => 'Zambian Kwacha', 'decimals' => 0, 'prefix' => '', 'suffix' => ' kwacha'],
        'HTG' => ['name' => 'Haitian Gourde', 'decimals' => 0, 'prefix' => '', 'suffix' => ' gourde'],
        'ETB' => ['name' => 'Ethiopian Birr', 'decimals' => 0, 'prefix' => '', 'suffix' => ' birr'],
        'BYR' => ['name' => 'Belarusian Ruble', 'decimals' => 0, 'prefix' => '', 'suffix' => ' rubles'],
        'MDL' => ['name' => 'Moldovan Leu', 'decimals' => 0, 'prefix' => '', 'suffix' => ' lei'],
        'MWK' => ['name' => 'Malawian Kwacha', 'decimals' => 0, 'prefix' => '', 'suffix' => ' kwacha'],
        'MKD' => ['name' => 'Macedonian Denar', 'decimals' => 0, 'prefix' => '', 'suffix' => ' denari'],
        'MOP' => ['name' => 'Macau Pataca', 'decimals' => 0, 'prefix' => '', 'suffix' => ' patacas'],
        'UZS' => ['name' => 'Uzbekistani Som', 'decimals' => 0, 'prefix' => '', 'suffix' => ' som'],
        'CVE' => ['name' => 'Cape Verdean Escudo', 'decimals' => 0, 'prefix' => '$', 'suffix' => ' escudos'],
        'NGN' => ['name' => 'Nigerian Naira', 'decimals' => 0, 'prefix' => '₦', 'suffix' => ' naira'],
        'SRD' => ['name' => 'Surinamese Dollar', 'decimals' => 2, 'prefix' => '$', 'suffix' => ' SRD'],
        'BBD' => ['name' => 'Barbadian Dollar', 'decimals' => 2, 'prefix' => 'Bds$', 'suffix' => ''],
        'MGA' => ['name' => 'Madagascar Ariary', 'decimals' => 0, 'prefix' => 'Ar', 'suffix' => ''],
        'SCR' => ['name' => 'Seychellois Rupee', 'decimals' => 2, 'prefix' => '', 'suffix' => ' rupees'],
        'LSL' => ['name' => 'Lesotho Loti', 'decimals' => 0, 'prefix' => '', 'suffix' => ' maloti'],
        'SLL' => ['name' => 'Sierra Leonean Leone', 'decimals' => 0, 'prefix' => '', 'suffix' => ' leones'],
        'CDF' => ['name' => 'Congolese franc', 'decimals' => 0, 'prefix' => '', 'suffix' => ' francs'],
        'MVR' => ['name' => 'Maldivian Rufiyaa', 'decimals' => 0, 'prefix' => 'Rf. ', 'suffix' => ''],
        'YER' => ['name' => 'Yemeni Rial', 'decimals' => 0, 'prefix' => '', 'suffix' => ' rial'],
        'TJS' => ['name' => 'Tajikistani Somoni', 'decimals' => 0, 'prefix' => '', 'suffix' => ' TJS'],
        'GYD' => ['name' => 'Guyanaese Dollar', 'decimals' => 0, 'prefix' => '', 'suffix' => ' GYD'],
        'ANG' => ['name' => 'Netherlands Antillean Guilder', 'decimals' => 2, 'prefix' => '', 'suffix' => ' guilders'],
    ];

    // When the user hasn't yet selected a currency, decide whether to use the hostel's local currency, otherwise default to USD.
    public static function defaultCurrency($localCurrency = '')
    {
        if (in_array($localCurrency, self::$LOCAL_CURRENCY_DEFAULTS, true)) {
            return $localCurrency;
        }

        // Otherwise we just default to USD
        return 'USD';
    }

    public static function isKnownCurrencyCode($currencyCode, $logErrorIfUnknown = true): bool
    {
        $isKnown = array_key_exists($currencyCode, self::$CURRENCIES);
        if (! $isKnown && $logErrorIfUnknown) {
            logError("Unknown currency code '$currencyCode'.");
        }

        return $isKnown;
    }

    public static function allByName(): array
    {
        $currencies = [];
        foreach (self::$CURRENCIES as $currency => $values) {
            $currencies[$currency] = $values['prefix'] ? $values['name'] . ' | ' . $values['prefix'] : $values['name'];
        }
        asort($currencies);

        // Move certain currencies to the top of the list
        foreach (self::$POPULAR_CURRENCIES as $topOne) {
            $topOnes[$topOne] = $currencies[$topOne];
            unset($currencies[$topOne]);
        }

        return $topOnes + $currencies;
    }

    public static function getInfo(string $currencyCode): array
    {
        if ($currencyCode === '') {
            throw new Exception('Missing currency.');
        }

        if (! array_key_exists($currencyCode, self::$CURRENCIES)) {
            logError("Unknown currency '{$currencyCode}', using generic formatting.");

            // return generic currency info
            return ['name' => $currencyCode, 'decimals' => 2, 'prefix' => '', 'suffix' => " $currencyCode"];
        }

        return self::$CURRENCIES[$currencyCode];
    }

    // this mostly just uses money_format() with a default format string, but it also changes the variable order to be more intuitive for smarty use
    public static function format($amount, $currency, $withDecimals = true, $language = null)
    {
        $currencyInfo = self::getInfo($currency);

        // Note: Even though the currency maybe from a different country, we still format the number using
        // number formating according to the user's own language locale by default).
        $formatted = Languages::get($language)->numberFormat((float) $amount, $withDecimals ? $currencyInfo['decimals'] : 0);

        return $currencyInfo['prefix'] . $formatted . $currencyInfo['suffix'];
    }

    // uses exchangeRate() and rounds to the proper # of decimals
    public static function convert($amount, $fromCurrency, $toCurrency, $formatted = false, $formatWithDecimals = true)
    {
        $convertedValue = round($amount * self::exchangeRate($fromCurrency, $toCurrency), self::$CURRENCIES[$toCurrency]['decimals']);

        return $formatted ? self::format($convertedValue, $toCurrency, $formatWithDecimals) : $convertedValue;
    }

    public static function exchangeRate(string $fromCurrency, string $toCurrency, $ignoreCache = false)
    {
        if ($fromCurrency === $toCurrency) {
            return 1;
        }

        // Make them alphabetical so we don't have to check the cache more than once for this pair
        if (strcmp($fromCurrency, $toCurrency) > 0) {
            $flipped = true;
            $temp = $fromCurrency;
            $fromCurrency = $toCurrency;
            $toCurrency = $temp;
        } else {
            $flipped = false;
        }

        // in memory cache (for multiple calls from the same script session)
        static $EXCHANGE_RATE_CACHE;
        if (! $ignoreCache && isset($EXCHANGE_RATE_CACHE["$fromCurrency$toCurrency"])) {
            return $flipped && $EXCHANGE_RATE_CACHE["$fromCurrency$toCurrency"] ?
                1 / $EXCHANGE_RATE_CACHE["$fromCurrency$toCurrency"] : $EXCHANGE_RATE_CACHE["$fromCurrency$toCurrency"];
        }

        // check cache
        // (would be better if this still returned old data even if it's expired if we couldn't get new data)

        $cacheKey = "currencyExchangeRate:$fromCurrency-$toCurrency";
        $dbRate = Cache::get($cacheKey);
        if (! $ignoreCache && $dbRate !== null) {
            return $flipped && $dbRate ? 1 / $dbRate : $dbRate;
        }

        $exchangeRate = self::getRate($fromCurrency, $toCurrency);
        if ($exchangeRate === false) {
            // backup (currencies not in ECB)
            // $exchangeRate = self::getRateFromXE($fromCurrency, $toCurrency);
            $exchangeRate = self::getRateFromFixer($fromCurrency, $toCurrency);
        }

        if ($exchangeRate === false) {
            throw new Exception("Can't get currency data for $fromCurrency / $toCurrency.");
        }

        // Record memory cache:
        $EXCHANGE_RATE_CACHE["$fromCurrency$toCurrency"] = $exchangeRate;

        // Record to cache:
        Cache::put($cacheKey, $exchangeRate, 24 * 60 * 60);

        return $flipped && $exchangeRate ? 1 / $exchangeRate : $exchangeRate;
    }

    /* Private Methods */

    private static function getRateFromFixer($fromCurrency, $toCurrency)
    {
        $FIXER_API_KEY = '114bfd171c2bad35efe39d8d53e354ba';

        $cacheKey = 'fixerCurrencyRateData';
        $rateData = Cache::get($cacheKey);

        if (! $rateData) {
            // Cache was expired, get fresh data

            $rawData = @file_get_contents('http://data.fixer.io/api/latest?access_key=' . $FIXER_API_KEY . '&format=1');

            $rateData = @json_decode($rawData);
            if (! $rateData || ! isset($rateData->rates)) {
                logError("Couldn't get Fixer currency data.");

                return false;
            }
            if ($rateData->base !== 'EUR') {
                logError('Fixer currency data is supposed to always be EUR based.');

                return false;
            }

            // Record to cache:
            Cache::put($cacheKey, $rateData, 12 * 60 * 60); // (they allow up to 1000 API calls per month, so this should be fine)
        }

        $rates = $rateData->rates;

        if (! isset($rates->$fromCurrency)) {
            // Unsupported currency.
            logError("'$fromCurrency' isn't a currency supported by Fixer.");

            return false;
        }

        if (! isset($rates->$toCurrency)) {
            // Unsupported currency.
            logError("'$toCurrency' isn't a currency supported by Fixer.");

            return false;
        }

        $fromPrice = (float) $rates->$fromCurrency;

        $toPrice = (float) $rates->$toCurrency;

        if ($toPrice == 0.0 || $fromPrice == 0.0) {
            logError("Zero price rate data returned from Fixer for $fromCurrency, $toCurrency.");

            return false;
        }

        return (float) $toPrice / $fromPrice;
    }

    private static function getRate(mixed $fromCurrency, mixed $toCurrency)
    {
        $exchangeRate = Currency::convert()
                                ->from($fromCurrency)
                                ->to($toCurrency)
                                ->get();

        return $exchangeRate ?? false;
    }
}
