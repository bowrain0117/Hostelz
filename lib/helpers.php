<?php

/* Debug Output */

use App\Enums\CategorySlp;
use App\Enums\Listing\CategoryPage;
use App\Models\CityInfo;
use App\Models\District;
use App\Models\Imported;
use App\Models\SpecialLandingPage;
use Illuminate\Support\Collection;
use Illuminate\Support\Debug\Dumper;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Lib\Currencies;

function debugOutput($s)
{
    static $lastTime = 0;

    if (config('custom.debugOutput')) {
        $now = microtime(true);
        $difference = ($lastTime ? $now - $lastTime : 0);
        $lastTime = $now;
        // file_put_contents(app_path() . '/debug-log.txt', $s."\n", FILE_APPEND);
        // (We display the $debugCount counter so that we can tell the actual order of events since Clockwork tends to mix them up.) [i think they fixed that issue?]
        Clockwork::info('[' . round($difference, 3) . 's] ' . $s);
    }

    /*
    if (!Str::contains($s, 'cache')) {
        $temp = shell_exec('ls -l /tmp/magick*');
        file_put_contents('/tmp/debugOutput.txt', date('c') . " $s\n$temp\n" , FILE_APPEND | LOCK_EX);
    }
    */
}

//  in storage/app/
function debugOutputCustom($message, $fileName = 'customLog')
{
    $message = is_string($message) ? $message : var_export($message, true);
    $date = date('Y-m-d H:i:s');

    Storage::append("/logs/{$fileName}.log", "[$date] - {$message}");
}

function _dbo($data)
{
    if (! config('custom.debugOutput')) {
        return;
    }

    array_map(function ($x) {
        clock()->info($x);
    }, func_get_args());
}

function _dw($data)
{
    echo '<div class="_ddebug" >';

    _d($data);

    echo '</div>';
}

function _d($data)
{
    if (! config('custom.debugOutput')) {
        return;
    }

    $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
    if (! empty($backtrace[1])) {
        dump($backtrace[1]['file'] . ':' . $backtrace[1]['line']);
    }

    array_map(function ($x) {
        (new Dumper)->dump($x);
    }, func_get_args());
}

function _dh($data, $hide = true)
{
    if (! config('custom.debugOutput')) {
        return;
    } ?>

    <div class="_ddebug" <?php echo ($hide) ? 'style="display: none"' : ''; ?> >
        <?php var_dump($data); ?>
    </div>

    <?php
}

function logError($message, array $context = [], $level = 'error')
{
    $message = is_string($message) ? $message : json_encode($message);

    $traceback = with(new Exception())->getTraceAsString();
    Log::$level($message, array_merge($context, [
        'url' => Request::fullUrl(), 'referrer' => Request::server('HTTP_REFERER'), 'ip' => Request::server('REMOTE_ADDR'),
        'agent' => Request::server('HTTP_USER_AGENT'), 'user' => auth()->check() ? auth()->user()->id : 'none', 'time' => (string) Carbon::now(),
        'trace' => substr($traceback, strpos($traceback, "\n"), 1000),
    ]));
}

function logWarning($message, array $context = [])
{
    logError($message, $context, 'warning');
}

function logNotice($message, array $context = [])
{
    logError($message, $context, 'notice');
}

function logToFile($message, array $context = [], $fileName = 'customLog', $level = 'info')
{
    $message = is_string($message) ? $message : var_export($message, true);

    Log::useFiles(storage_path() . '/logs/' . $fileName . '.log');
    Log::$level($message, $context);
}

/* Laravel Helpers */

// this is the same as calling Lang::get(), except it lets you specify a default value to return if the key doesn't exist in the language file.
// If $default is false, it throws an exception if the key doesn't exist.
// Note: $default cannot be an array (if it is, we assume it's intended to be the $replace argument for this function).
// If $default is true, it returns the $key if a translation for it isn't found.

function langGet($key, $defaultOrReplace = false, $replace = [], $language = null, $number = null)
{
    // Allow a $replace array to be passed as the second parameter (if we aren't using a default)
    if (is_array($defaultOrReplace) && $replace === []) {
        $replace = $defaultOrReplace;
        $default = false;
    } else {
        $default = $defaultOrReplace;
    }

    if (Lang::has($key, $language)) {
        return $number === null ? Lang::get($key, $replace, $language) : Lang::choice($key, $number, $replace, $language);
    } else {
        if ($default === false) {
            throw new Exception("Unknown language key '$key'.");
        }
        if ($default === true) {
            return $key;
        }

        return $default;
    }
}

// this is the same as calling Lang::choice(), except it throws an exception if the key doesn't exist.

function langChoice($key, $number, $defaultOrReplace = false, $replace = [], $language = null)
{
    return langGet($key, $defaultOrReplace, $replace, $language, $number);
}

/* Time/Date */

function zeroTimePartOfDate($carbonDate)
{
    return $carbonDate->hour(0)->minute(0)->second(0);
}

function carbonFromDateString($dateString)
{
    return Carbon::createFromFormat('Y-m-d', $dateString);
}

function carbonGenericFormat($dateString)
{
    return carbonFromDateString($dateString)->format('M j, Y');
}

/* Misc */

function parseFirstAndLastName($name)
{
    $nameParts = explode(' ', mb_trim($name));
    $lastName = array_pop($nameParts);
    $firstName = trim(implode(' ', $nameParts));

    return compact('lastName', 'firstName');
}

function accessDenied()
{
    if (auth()->check()) {
        // User is logged in, but doesn't have access to this page.
        // (we had to make a response() from it because otherwise it was getting errors when returned by middleware)
        return response(view('access-denied'));
    } else {
        // No user logged in, send them to the login page.
        // (guest() saves the URL to url.intended for later return using intended())
        if (request()->ajax()) {
            return response()->json(['error' => 'accessDenied', 'loginURL' => routeURL('login')], 401);
        }

        return Redirect::guest(routeURL('login'));
    }
}

function is_closure($t)
{
    return is_object($t) && ($t instanceof Closure);
}

function humanReadableFileSize($size)
{
    if ($size >= 1 << 30) {
        return number_format($size / (1 << 30), 1) . ' GB';
    }
    if ($size >= 1 << 20) {
        return number_format($size / (1 << 20), 1) . ' MB';
    }
    if ($size >= 1 << 10) {
        return number_format($size / (1 << 10), 1) . ' KB';
    }

    return number_format($size) . ' bytes';
}

function numberFormatKeepDecimals($value)
{
    if ($value === '') {
        return '';
    }
    $decimalPlaces = strlen(substr(strrchr($value, '.'), 1));

    return number_format($value, $decimalPlaces);
}

function sanitizeFilename($s)
{
    $s = str_replace(['/', '\\', '"', ':', '*', '?', '>', '<', '|'], '-', $s);
    $s = trim($s, '.');

    return trim($s);
}

function determineMimeTypeForFile($filePath)
{
    $finfo = finfo_open(FILEINFO_MIME_TYPE); // return mime type ala mimetype extension
    $mimeType = finfo_file($finfo, $filePath);
    finfo_close($finfo);

    return $mimeType;
}

// This one doesn't require that the file actually exists, it just uses the filename.

function determineMimeTypeForFilename($filename)
{
    $ext = pathinfo($filename, PATHINFO_EXTENSION);
    if (! $ext) {
        $ext = $filename;
    }
    $ext = strtolower($ext);
    $types = getSystemMimeTypes();

    return isset($types[$ext]) ? $types[$ext] : null;
}

function getSystemMimeTypes()
{
    $types = [];
    $lines = file('/etc/mime.types');
    foreach ($lines as $line) {
        $line = trim($line);
        if (Str::startsWith($line, '#') || $line == '') {
            continue;
        }
        $parts = preg_split('/\s+/', $line);
        if (count($parts) == 1) {
            continue;
        }
        $type = array_shift($parts);
        foreach ($parts as $part) {
            $types[$part] = $type;
        }
    }

    return $types;
}

/* Math */

function limitToBounds($value, $min, $max)
{
    return $value > $max ? $max : ($value < $min ? $min : $value);
}

function ceilPrecision($number, $precision)
{
    $coefficient = pow(10, $precision);

    return ceil($number * $coefficient) / $coefficient;
}

function floorPrecision($number, $precision)
{
    $coefficient = pow(10, $precision);

    return floor($number * $coefficient) / $coefficient;
}

// This makes 64 bit systems return a signed crc32 value that matches what crc32() does on 32 bit systems.

function crc32signed($s)
{
    $crc = crc32($s);
    if ($crc & 0x80000000) {
        return -(($crc ^ 0xffffffff) + 1);
    } else {
        return $crc;
    }
}

/* HTML / HTTP */

function preventBrowserCaching($response)
{
    $response->header('Cache-Control', 'nocache, no-store, max-age=0, must-revalidate');
    $response->header('Pragma', 'no-cache');
    $response->header('Expires', 'Fri, 01 Jan 1990 00:00:00 GMT');

    return $response; // for chaining convenience
}

function getPlainTextFromHTMLPage($html)
{
    // based on http://stackoverflow.com/questions/11626319/strip-html-to-remove-all-js-css-html-tags-to-give-actual-textdisplayed-on-brows
    $html = preg_replace('/(<|>)\1{2}/is', '', $html); // (not sure what this does)
    $html = preg_replace(['@<head[^>]*?>.*?</head>@siu',
        '@<style[^>]*?>.*?</style>@siu', '@<script[^>]*?.*?</script>@siu', '@<noscript[^>]*?.*?</noscript>@siu', ],
        '', $html);

    return strip_tags($html);
}

/* Modifies the current URL, adding the query variables $replace (replacing the value of any existing query variables that have the same names as the keys of the $replace array). Also removes any variables named in the $remove array. */

function currentUrlWithQueryVar($replace, $remove = null)
{
    $query = Request::query();
    if ($remove) {
        $query = array_diff_key(Request::query(), array_flip($remove));
    }

    return Request::url() . '?' . http_build_query(array_replace($query, $replace));
}

function currentUrlExceptQueryVar($except)
{
    $newQuery = http_build_query(Request::except($except));

    return $newQuery == '' ? Request::url() : Request::url() . '?' . $newQuery;
}

// Get multiple input variables at once, and optionally add them to an $otherValues array.

function inputGetMultiple($inputVariables, $otherValues = [])
{
    foreach ($inputVariables as $var) {
        if (Request::has($var)) {
            $otherValues[$var] = Request::input($var);
        }
    }

    return $otherValues;
}

function makeUrlQueryString($values)
{
    if (! is_array($values)) {
        throw new Exception('Array expected.');
    }

    return $values ? '?' . http_build_query($values) : '';
}

function makeUrlQueryStringFromInputs($inputVariables, $otherValues = [])
{
    return makeUrlQueryString(inputGetMultiple($inputVariables, $otherValues));
}

function htmlHiddenInputFromArray($array, $variablePrefix = '')
{
    if (! is_array($array)) {
        return '';
    }

    $output = '';

    foreach ($array as $key => $value) {
        if (is_array($value)) {
            $output .= htmlHiddenInputFromArray($value, ($variablePrefix == '' ? $key : ($variablePrefix . "[$key]")));
            continue;
        }

        if ($variablePrefix != '') {
            $key = $variablePrefix . (is_numeric($key) ? '[]' : "[$key]");
        }

        $output .= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '">';
    }

    return $output;
}

function cleanTextFromWYSIWYG($t)
{
    $t = str_replace('<p>&nbsp;</p>', '', $t);

    // Make changes to the adCopy text coming in from tiny_mce
    $t = str_replace('&nbsp;', ' ', $t); // he doesn't want multiple spaces to be preserved, which is what tiny_mce uses &nbsp; for, so we just make them regular spaces.
    $t = str_replace("\xA0", ' ', $t); // change non-breaking spaces to regular spaces
    $t = preg_replace('`(<p> </p>)`', '', $t); // this removes empty lines (tiny_mce used to have a remove_linebreaks but apparently no longer does in 4.x versions)
    $t = preg_replace("/(^[\r\n]*|[\r\n]+)[\s\t]*[\r\n]+/", "\n", $t);

    return $t;
}

function breadcrumb($linkText, $url = '', $specialClasses = '')
{
    static $breadcrumbPositionCount = 1;

    $specialClasses .= ' breadcrumb-item';

    if ($url === '') {
        echo '<li class="' . trim('active ' . $specialClasses) . '" property="itemListElement" typeof="ListItem">
                <span property="name">' . htmlentities($linkText) . '</span>
                <meta property="position" content="' . $breadcrumbPositionCount++ . '">
             </li>';
    } else {
        echo '<li ' . ($specialClasses !== '' ? 'class="' . $specialClasses . '" ' : '') .
            'property="itemListElement" typeof="ListItem"><a href="' . $url . '" property="item" typeof="WebPage">' .
            '<span property="name">' . htmlentities($linkText) . '</span></a>' .
            '<meta property="position" content="' . $breadcrumbPositionCount++ . '">
            </li>';
    }
}

/* Cookies */

// Pass expireMinutes as -1 to delete a cookie.

function setMultiCookie($name, $values, $secureWithHashCode = false, $expireMinutes = 0)
{
    if (! is_array($values)) {
        throw new Exception('setMultiCookie values not an array.');
    }
    $s = json_encode($values);
    debugOutput("setMultiCookie($name, $s)");
    if ($secureWithHashCode !== false) {
        $s = base64_encode(hash_hmac('sha256', $s, $secureWithHashCode, true)) . ':' . $s;
    }

    // We use setrawcookie()/rawurlencode() so the encoding of spaces matches the way javascript does it
    // (probably makes no difference, but might as well be consistant with cookies set by our JS setMultiCookie() function.)
    // (Note: Not passing domain doesn't work on local dev, and not needed for single-subdomain sites anyway.)
    setrawcookie($name, rawurlencode($s), $expireMinutes ? time() + $expireMinutes * 60 : 0, '/',
        config('custom.domainName') != '' ? '.' . config('custom.domainName') : null);
}

function unsetMultiCookie($name)
{
    setMultiCookie($name, [], false, -1);
}

function getMultiCookie($name, $secureWithHashCode = false)
{
    debugOutput("getMultiCookie($name)");
    if (! ($c = @$_COOKIE[$name])) {
        return false;
    }
    if ($secureWithHashCode !== false) {
        $hash = strstr($c, ':', true);
        $c = substr($c, strlen($hash) + 1);
        if (base64_encode(hash_hmac('sha256', $c, $secureWithHashCode, true)) != $hash) {
            return false;
        } // tampered cookie
    }
    if ($c == '') {
        return [];
    }

    return json_decode($c, true);
}

/* Strings */

// Truncates to the next whole word breakpoint after $characterCount chracters.

function wholeWordTruncate($s, $characterCount, $addEllipsis = ' …')
{
    if (mb_strlen($s) <= $characterCount) {
        return $s;
    }

    $characterCount -= mb_strlen($addEllipsis); // to leave room for the ellipsis

    $return = $s;
    if (preg_match("/^.{1,$characterCount}\b/su", $s, $match)) { // \b in indicates a word boundary
        $return = $match[0];
    } else {
        // If the entire string has no white space breaks before $characterCount go ahead and break it in the middle of a word.
        $return = mb_substr($return, 0, $characterCount);
    }

    return $return . $addEllipsis;
}

function wholeWordReplace($subject, $find, $replace, $limit = -1, $caseSensitive = false, &$count = null)
{
    $find = (array) $find;
    foreach ($find as &$f) {
        $f = "`\b(" . preg_quote($f) . ")\b`us" . ($caseSensitive ? '' : 'i');
    }
    unset($f); // break the reference with the last element just to be safe

    return preg_replace($find, $replace, $subject, $limit, $count);
}

// Case-insensitive, ASCII string comparison
function stringSimilarityPercent($s1, $s2)
{
    $similarCharacters = similar_text(strtolower(utf8ToAscii($s1)), strtolower(utf8ToAscii($s2)), $percent);

    return $percent;
}

// Case-insensitive, ASCII string comparison, percent of identical words
function stringSimilarWordsPercent($s1, $s2)
{
    $s1 = explode(' ', strtolower(utf8ToAscii($s1)));
    $s2 = explode(' ', strtolower(utf8ToAscii($s2)));

    return 100 * count(array_intersect($s1, $s2)) / array_average([count($s1), count($s2)]);
}

// This is to convert text encoded with ISO-8859-1 (and potentially other encodings) to UTF-8.
function convertIncorrectCharset($s)
{
    if (iconv('UTF-8', 'UTF-8//IGNORE', $s) == $s) {
        return $s;
    }
    $new = iconv('ISO-8859-1', 'UTF-8', $s);
    if (iconv('UTF-8', 'UTF-8//IGNORE', $new) == $new) {
        return $new;
    } else {
        return $s;
    }
}

function removeIncorrectCharset($data)
{
    return preg_replace('/[[:^print:]]/', '', $data);
}

// Swaps the values of $a and $b if needed so that $a is most similar to $toBeSimilarToX and $b is most similar to $toBeSimilarToY.
function swapValuesForBestSimilarity(&$a, &$b, $toBeSimilarToX, $toBeSimilarToY)
{
    if (similar_text($a, $toBeSimilarToX) + similar_text($b, $toBeSimilarToY) <
        similar_text($b, $toBeSimilarToX) + similar_text($a, $toBeSimilarToY)) {
        $temp = $a;
        $a = $b;
        $b = $temp;
    }
}

function obfuscateString($s)
{
    $secretHash = 'AD05F312574B6671F0BF25389E7CD09F56568859FF6DCF1D2DB87105FD6137AD';

    return openssl_encrypt($s, 'AES-256-CBC', $secretHash, 0, '1234567890123456');
}

function unobfuscateString($s)
{
    $secretHash = 'AD05F312574B6671F0BF25389E7CD09F56568859FF6DCF1D2DB87105FD6137AD';

    return openssl_decrypt($s, 'AES-256-CBC', $secretHash, 0, '1234567890123456');
}

// This lets you specify an *array* of strings to trim. Unlike trim(), it allows the trim mask to be multi character strings.
// (I think this "u" modifier used in preg_replace() makes this multibyte string safe?)

function trimUsingArrayOfStrings($s, $arrayOfTrimStrings)
{
    $original = $s;
    foreach ($arrayOfTrimStrings as $trim) {
        $trim = preg_quote($trim, '/');
        $s = preg_replace("/^($trim)+|($trim)+$/us", '', $s);
    }
    if ($s != $original) {
        return trimUsingArrayOfStrings($s, $arrayOfTrimStrings);
    } // have to run again to get other trim characters now exposed after the first trim

    return $s;
}

// from http://stackoverflow.com/a/30130941/1257764 (see also his mb_rtrim() and mb_ltrim() if needed)
function mb_trim($string, $charlist = null)
{
    if (is_null($charlist)) {
        return trim($string); // the default characters are multi-byte safe anyway
    } else {
        $charlist = preg_quote($charlist, '/');

        return preg_replace("/(^[$charlist]+)|([$charlist]+$)/us", '', $string);
    }
}

// Trim whitespace from the beginning/end of lines of text

function trimLines($text)
{
    return implode("\n", array_map('trim', explode("\n", $text)));
}

function mb_stri_replace($needle, $replacement, $haystack)
{
    return mb_eregi_replace(preg_quote($needle), $replacement, $haystack);
}

// Removes invalid characters (also converts from other character sets)
// (See also the new http://php.net/manual/en/uconverter.transcode.php)

function convertStringToValidUTF8($s, $fromCharset = '')
{
    if ($s === '') {
        return '';
    }
    $fromCharset = ($fromCharset === '' ? 'UTF-8' : strtoupper($fromCharset));
    /* if (strpos($s, "\x1B")!==false) // this character seems to be the only indications it's ISO-2022-JP
    		$converted = trim(iconv('ISO-2022-JP',"UTF-8//IGNORE", $s)); */
    $s = str_replace("\xA0", ' ', $s); // the non-breaking space character was causing iconv() to return nothing

    try {
        $converted = iconv($fromCharset, 'UTF-8//IGNORE', $s); // Try iconv() first (it supports more types of encodings, but sometimes fails)
    } catch (Throwable $t) {
        $converted = '';
    }

    if (empty($converted)) {
        try {
            $converted = mb_convert_encoding($s, 'UTF-8', $fromCharset);
        } catch (Throwable $t) {
            $converted = '';
        }
    } // iconv() failed, try mb_convert_encoding()

    if ($converted === '' && $fromCharset !== 'UTF-8') {
        return convertStringToValidUTF8($s, 'UTF-8');
    } // next try setting the fromCharset to UTF-8

    return $converted;
}

function trimNonUTF8($string)
{
    return preg_replace('/[^(\x20-\x7F)]*/', '', $string);
}

// Converts all characters to ascii (removes accents from accented characters, etc.)
// (See also the new http://php.net/manual/en/uconverter.transcode.php)

function utf8ToAscii($s)
{
    /*  old method (didn't work for some characters)
        // we also trim any apostrophes that it may add at the end for accented letters (not sure why we only do that at the end?)
        return trim(iconv('UTF-8', 'ASCII//TRANSLIT', $s), '\''); */
    try {
        return transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $s);
    } catch (IntlException $e) {
        // Some invalid data causes transliterator_transliterate() to fail, but this is supposed to remove invalid UTF-8, then try again...
        $s = convertStringToValidUTF8($s, 'UTF-8');

        return transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0080-\u7fff] remove', $s);
    }
}

/* PHP has str_word_count() but it doesn't work well, especially with foreign characters. So we use this. */

function wordcount($string)
{
    return count(explode(' ', preg_replace('/\s+/', ' ', trim($string))));
}

function wholeWordStringReplace($search, $replace, $subject, $caseSensitive = false)
{
    return preg_replace('|\b' . preg_quote($search) . '\b|u' . ($caseSensitive ? '' : 'i'), $replace, $subject);
}

function longestString($array)
{
    if (! is_array($array)) {
        return false;
    }
    $longest = '';
    $longestLen = 0;
    foreach ($array as $a) {
        if (mb_strlen($a) > $longestLen) {
            $longestLen = strlen($a);
            $longest = $a;
        }
    }

    return $longest;
}

function sortByLengthDesc($a, $b)
{
    if ($a == $b) {
        return 0;
    }

    return strlen($a) > strlen($b) ? -1 : 1;
}

// Explode that allows multiple separators and also saves the separators in the result array.
// wholeWordSeparators are separators that must be surrounded by separator boundaries (and can not be substrings of any $separators).
function multiExplode($separators, $wholeWordSeparators, $string, $caseSensitive = false)
{
    // First we sort the separators by length descending, ensuring that substrings of other strings come later in the array.
    if ($separators) {
        usort($separators, 'sortByLengthDesc');
    }
    if ($wholeWordSeparators) {
        usort($wholeWordSeparators, 'sortByLengthDesc');
        $bothSepTypesMerged = array_merge($separators, $wholeWordSeparators);
    }

    $result = [];
    $currentString = '';
    $i = 0;
    $strLen = mb_strlen($string);
    while ($i < $strLen) {
        // * Whole Word Separators *

        if ($currentString == '' && $wholeWordSeparators) {
            foreach ($wholeWordSeparators as $sep) {
                $sepLen = mb_strlen($sep);
                if ($sepLen == 0) {
                    continue;
                }
                $substr = mb_substr($string, $i, $sepLen);
                if ($substr == $sep || (! $caseSensitive && mb_strtolower($substr) == mb_strtolower($sep))) {
                    // Now check to see if it's followed by a separator (if it's really a whole word)...
                    $followedBySep = false;
                    if ($sepLen + $i >= $strLen) {
                        $followedBySep = true;
                    } else {
                        foreach ($bothSepTypesMerged as $sep2) {
                            $sepLen2 = mb_strlen($sep2);
                            if ($sepLen2 == 0) {
                                continue;
                            }
                            $substr2 = mb_substr($string, $i + $sepLen, $sepLen2);
                            if ($substr2 == $sep2 || (! $caseSensitive && mb_strtolower($substr2) == mb_strtolower($sep2))) {
                                $followedBySep = true;
                                break;
                            }
                        }
                    }
                    if (! $followedBySep) {
                        continue;
                    } // not really a whole word because it isn't followed by another separator

                    $result[] = $substr;
                    $i += $sepLen;
                    continue 2;
                }
            }
        }

        // * Separators (regular, not whole word) *

        foreach ($separators as $sep) {
            $sepLen = mb_strlen($sep);
            if ($sepLen == 0) {
                continue;
            }
            $substr = mb_substr($string, $i, $sepLen);
            if ($substr == $sep || (! $caseSensitive && mb_strtolower($substr) == mb_strtolower($sep))) {
                if ($currentString != '') {
                    $result[] = $currentString;
                    $currentString = '';
                }
                $result[] = $substr;
                $i += $sepLen;
                continue 2;
            }
        }
        $currentString .= mb_substr($string, $i, 1);
        $i++;
    }
    if ($currentString != '') {
        $result[] = $currentString;
    }

    return $result;
}

function stringDigits($s)
{
    $n = '';
    for ($i = 0; $i < strlen($s); $i++) {
        if (is_numeric($s[$i])) {
            $n .= $s[$i];
        }
    }

    return $n;
}

function itemList($items, $singularName = '', $pluralName = '')
{
    if (! $items) {
        return '';
    }
    $items = array_values($items); // make sure it's an indexed array (not associative)

    if (count($items) == 1) {
        return trim("$items[0] $singularName");
    }

    if ($singularName == '') {
        $text = '';
    } else {
        $text = ($pluralName != '' ? " $pluralName" : ' ' . Str::plural($singularName));
    }

    if (count($items) == 2) {
        return trim("$items[0] and $items[1]$text");
    }
    $items[count($items) - 1] = 'and ' . $items[count($items) - 1];

    return implode(', ', $items) . $text;
}

// $wordOrWords - If it's an array, it just has to contain any one of the words in the array.

function containsWord($s, $wordOrWords, $caseInsensitive = true)
{
    if (is_array($wordOrWords)) {
        foreach ($wordOrWords as $word) {
            if (containsWord($s, $word, $caseInsensitive)) {
                return true;
            }
        }

        return false;
    }

    return (bool) preg_match('#\b' . preg_quote($wordOrWords, '#') . '\b#' . ($caseInsensitive ? 'i' : ''), $s);
}

function paragraphsAsBullets($s, $bulletText = '&bull; ', $divider = '<br>', $noBulletsIfOnlyOneParagraph = true)
{
    $s = trim(preg_replace("/([\t\r\n]+ *[\t\r\n]+)/", "\n", $s)); // replace multiple linebreaks/spaces with single linebreaks.
    if (! $noBulletsIfOnlyOneParagraph || (strpos($s, "\n") !== false)) {
        $s = $bulletText . $s;
    } // add initial bullet

    return str_replace("\n", $divider . $bulletText, $s);
}

/* Arrays */

function array_average($array)
{
    return array_sum($array) / count($array);
}

/* NOTE: For Laravel collections don't use this function, use pluck() instead! For arrays use array_column(). */
function objectArrayColumn($array, $elementName)
{
    return array_map(function ($element) use ($elementName) {
        return $element->$elementName;
    }, $array);
}

/**
 * Search an array by keys and return the matching elements.
 * The $properties list can be a list of the property names: [ 'foo', 'bar', ... ]
 * Or if the result needs to name them differently, keys can be used: [ 'foo' => 'bar', ... ]
 */
function getArrayFromObjectProperties($object, $properties)
{
    $result = [];
    foreach ($properties as $key => $property) {
        if (is_numeric($key)) {
            $result[$property] = $object->$property;
        } else {
            $result[$key] = $object->$property;
        }
    }

    return $result;
}

/*
    $array can be an array of arrays or objects.
    NOTE: For Laravel collections use ->where($key, $value, $strict = true)->first() instead.
*/

function searchArrayForProperty($array, $property, $value, $returnKeyOrElement = 'element')
{
    foreach ($array as $key => $element) {
        if ((is_object($element) ? $element->$property : $element[$property]) == $value) {
            return $returnKeyOrElement == 'key' ? $key : $element;
        }
    }

    return null;
}

function keysOfArrayWithMatchingElements($array, $element, $value)
{
    $result = [];
    foreach ($array as $key => $values) {
        if ($values[$element] == $value) {
            $result[] = $key;
        }
    }

    return $result;
}

// Returns the key of the next element in $array after $key.
function nextArrayKey($array, $key)
{
    reset($array);
    while (key($array) !== $key) {
        next($array);
    }

    return next($array);
}

// Check if two array have the same elements (even if the order is different)
function arraysHaveEquivalentValues($a1, $a2)
{
    return ! array_diff($a1, $a2) && ! array_diff($a2, $a1);
}

function insertIntoArrayAfterKey($array, $afterKey, $insertArray)
{
    if (! array_key_exists($afterKey, $array)) {
        throw new Exception("'$afterKey' doesn't exist in the array.");
    }
    $new = [];
    foreach ($array as $key => $value) {
        $new[$key] = $value;
        if ($key == $afterKey) {
            foreach ($insertArray as $insertKey => $insertValue) {
                $new[$insertKey] = $insertValue;
            }
        }
    }

    return $new;
}

// Case insensitive in_array() (from PHP documentation comments)
function in_arrayi($needle, $haystack)
{
    return in_array(strtolower($needle), array_map('strtolower', $haystack));
}

function renameArrayKeys($array, $keyNameMap, $useOriginalValueIfNotInMap = false)
{
    return array_combine(array_map(function ($element) use ($keyNameMap, $useOriginalValueIfNotInMap) {
        if ($useOriginalValueIfNotInMap && ! isset($keyNameMap[$element])) {
            return $element;
        } else {
            return $keyNameMap[$element];
        }
    }, array_keys($array)), array_values($array));
}

/* Locks */

/* Note: This should be changed to use Redis instead of trying to use Cache for this. */

function acquireLock($name, $maximumHoldTimeSeconds = 60, $timeoutSeconds = false)
{
    $isLocked = Cache::get('acquireLock:' . $name);
    if ($isLocked) {
        if ($timeoutSeconds === 0) {
            return false;
        } // timeout
        sleep(1);

        return acquireLock($name, $maximumHoldTimeSeconds, $timeoutSeconds === false ? false : $timeoutSeconds - 1);
    }

    // Note there is a race condition here where someone could have acquired the lock between our get and set.  I don't know of a solution, but not important for our purposes.
    Cache::put('acquireLock:' . $name, true, $maximumHoldTimeSeconds);

    return true;
}

function releaseLock($name)
{
    Cache::forget('acquireLock:' . $name);
}

function minDelayBetweenCalls($name, $miliSeconds)
{
    // These are just used for the lock timeouts (normally shouldn't be what really determines the wait time)
    $secondsRoundedUp = ceil($miliSeconds / 1000);
    $minutesRoundedUp = ceil($miliSeconds / 60000);

    $seconds = $miliSeconds / 1000;

    // just to make sure another script isn't waiting at the same time we are (timeouts shouldn't normally matter)
    acquireLock("minDelayBetweenCalls:$name", $secondsRoundedUp, $secondsRoundedUp);

    $lastRequest = Cache::get("minDelayBetweenCalls:$name");
    $time = microtime(true);

    if ($lastRequest && $time - $lastRequest < $seconds) {
        usleep(round(($seconds - ($time - $lastRequest)) * 1000000));
    }

    // Record the current time so we know how long it has been the next time this function is called.
    Cache::put("minDelayBetweenCalls:$name", microtime(true), $secondsRoundedUp); // timeout is at least as long as the delay (normally shouldn't matter)

    releaseLock("minDelayBetweenCalls:$name");
}

/* Curl */

// Curl doesn't currently have a built-in way to limit the size of the data in downloads.
// This aborts the download if it's over a certain size (but still doesn't give us a way to keep what was already downloaded).
// Causes Curl to return a CURLE_ABORTED_BY_CALLBACK error.
// Based on http://stackoverflow.com/questions/17641073/how-to-set-a-maximum-size-limit-to-php-curl-downloads
function curlAbortIfOverDownloadSize($curl, $sizeInKB)
{
    curl_setopt($curl, CURLOPT_BUFFERSIZE, 1024);
    curl_setopt($curl, CURLOPT_NOPROGRESS, false);
    curl_setopt($curl, CURLOPT_PROGRESSFUNCTION,
        function ($handle, $downloadSize, $downloaded, $uploadSize, $uploaded) use ($sizeInKB) {
            return ($downloadSize > ($sizeInKB * 1024)) ? 1 : 0;
        }
    );
}

function getYoutubeIDFromURL($url)
{
//    $url = "https://www.youtube.com/v/ifK9nMTNZeM&hl=en_US&fs=1&";

    preg_match("/^(?:http(?:s)?:\/\/)?(?:www\.)?(?:m\.)?(?:youtu\.be\/|youtube\.com\/(?:(?:watch)?\?(?:.*&)?v(?:i)?=|(?:embed|v|vi|user)\/))([^\?&\"'>]+)/", $url, $match);

    return isset($match[1]) ? $match[1] : null;
}

function getYoutubeIframeFromID($youtubeVideoID)
{
    return '<iframe type="text/html" width="620" height="349" ' . // not perfect aspect ratio for all videos, but works ok for most
        'src="https://www.youtube.com/embed/' . $youtubeVideoID . '" scrolling="no" frameborder="0" allowfullscreen></iframe>';
}

function nl2p($string)
{
    return collect(explode("\n", $string))
        ->map(fn ($line) => trim($line))
        ->filter()
        ->map(function ($line) {
            return ! str($line)->startsWith(['<h1', '<h2', '<h3', '<h4', '<p', '<ul', '<ol', '<li', '</li', '</ul', '</ol']) ? '<p>' . $line . '</p>' : $line;
        })
        ->implode('');
}

function clearTextForSchema($text)
{
    return
        htmlentities(
            trim(
                strip_tags(
                    str_replace(["\n", "\r"], '',
                        str_replace(['\\'], '/', $text)
                    )
                ),
                '""\\'
            ),
            ENT_COMPAT
        );
}

function getCMPLabel($linkLocation, $city, $name = '')
{
    $city = str()->slug($city);
    $name = $name !== '' ? str($name)->slug() : '';

    switch ($linkLocation) {
        //  for city page
        case CategorySlp::Best:
            $label = 'best_' . $city;
            break;
        case CategorySlp::Private:
            $label = 'private_' . $city;
            break;
        case CategorySlp::Cheap:
            $label = 'cheap_' . $city;
            break;
        case CategorySlp::Party:
            $label = 'party_' . $city;
            break;
        case 'reservations':
        case 'slp_sidebar':
        case 'district':
        case 'city':
            $label = $linkLocation . '_' . $city;
            break;
            //  for Right Sidebar
        case 'single_sidebar_is_closed':
        case 'single_sidebar':
            //  for Comparing Prices Interface
        case 'single_compare':
        case 'single_contact_listed':
        case str($linkLocation)->startsWith(CategoryPage::TABLE_KEY):
        case 'single_contact_not_listed':
            $label = $linkLocation . '_' . $city . '_' . $name;
            break;
        case 'best_city_map':
            $label = 'best_' . $city . '_map';
            break;
        default:
            $label = $linkLocation . '_' . $city . '_' . $name;
    }

    return urlencode($label);
}

function makeTrackingCode()
{
    return (string) str()->ulid();
}

function makeStaticLinkRedirect($link, $systemName, $trackingCode, $importedId = null)
{
    return routeURL(
        'bookings-linkStaticRedirect',
        [
            $importedId,
            'b' => urlencode(obfuscateString($link)),
            'system' => $systemName,
            't' => urlencode(obfuscateString($trackingCode)),
        ]
    );
}

function getEditURL($data)
{
    if (! auth()->user()->hasPermission('admin')) {
        return '';
    }

    if (empty($data)) {
        return '';
    }

    switch ($data['target']) {
        case 'hostelChain':
            return routeURL('staff-hostelsChain', $data['id']);
        case 'city':
            return routeURL('staff-cityInfos', $data['id']);
        case 'listing':
            return routeURL('staff-listings', $data['id']);
        case 'continents':
            return routeURL('staff-attachedTexts', $data['id']);
        case 'slp':
            return SpecialLandingPage::find($data['id'])?->pathEdit;
        case 'district':
            return District::find($data['id'])?->pathEdit;
        case 'categoryPage':
            return [
                [
                    'target' => 'city',
                    'url' => routeURL('staff-cityInfos', $data['id']),
                ],
                [
                    'target' => 'category',
                    'url' => CategoryPage::tryFrom($data['categoryType'])?->editUrl(CityInfo::find($data['id']))],
            ];
        default:
            return '';
    }
}

function replaceShortcodes(string $string)
{
    return str_replace(
        ['[year]', '[month]'],
        [date('Y'), date('F')],
        $string
    );
}

function createCsv(string $fileName, Collection $rows, array $header = [])
{
    $fp = fopen(storage_path('app/') . "{$fileName}.csv", 'wb');

    $rows
        ->when($header, fn ($rows) => $rows->prepend($header))
        ->each(fn ($row) => fputcsv($fp, $row));

    fclose($fp);
}

function formatFileSize($bytes)
{
    if ($bytes >= 1073741824) {
        $bytes = number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        $bytes = number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        $bytes = number_format($bytes / 1024, 2) . ' KB';
    } elseif ($bytes > 1) {
        $bytes .= ' bytes';
    } elseif ($bytes === 1) {
        $bytes .= ' byte';
    } else {
        $bytes = '0 bytes';
    }

    return $bytes;
}

function getCurrencyFromSearch()
{
    $currency = Currencies::defaultCurrency();

    if (empty($_COOKIE[config('custom.bookingSearchCriteriaCookie')])) {
        return $currency;
    }

    $cookie = json_decode($_COOKIE[config('custom.bookingSearchCriteriaCookie')]);

    return ! empty($cookie->currency) ? $cookie->currency : $currency;
}
