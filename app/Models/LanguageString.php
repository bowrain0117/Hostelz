<?php

namespace App\Models;

use App\Helpers\EventLog;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Lang;
use Lib\BaseModel;

class LanguageString extends BaseModel
{
    protected $table = 'languageStrings';

    public static $staticTable = 'languageStrings'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
                $return = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys', 'validation' => 'required'],
                    'group' => ['validation' => 'required'],
                    'key' => ['validation' => 'required'],
                    'text' => ['type' => 'textarea', 'validation' => 'required'],
                    'originalText' => [],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'created_at' => ['type' => 'display', 'searchType' => 'text'],
                    'updated_at' => ['type' => 'display', 'searchType' => 'text'],
                ];

                return $return;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }
    }

    /* Static */

    public static function getAllEnGroups()
    {
        $files = File::files(base_path('resources/lang/en'));
        $return = [];
        foreach ($files as $file) {
            $return[] = pathinfo($file, PATHINFO_FILENAME);
        }

        return $return;
    }

    public static function getAllTranslationsNeededCounts($language)
    {
        $groups = self::getAllEnGroups();
        natcasesort($groups);
        $return = [];
        foreach ($groups as $group) {
            $englishStrings = self::loadEnGroup($group);
            if (! $englishStrings) {
                continue;
            }
            $translationsNeeded = 0;
            foreach ($englishStrings as $key => $englishText) {
                $languageString = self::getFromDatabaseByKey($group, $key, $language);
                if (! $languageString || $languageString->originalText != $englishText) {
                    $translationsNeeded++;
                }
            }
            $return[$group] = $translationsNeeded;
        }

        return $return;
    }

    public static function getStringsToTranslate($language, $group, &$translationInfo)
    {
        $englishStrings = self::loadEnGroup($group, $translationInfo);
        if (! $englishStrings) {
            return [];
        }

        $return = [];
        foreach ($englishStrings as $key => $englishText) {
            $languageString = self::getFromDatabaseByKey($group, $key, $language);
            $translationIsNeeded = (! $languageString || $languageString->originalText != $englishText);
            $suggested = '';
            if ($translationIsNeeded) {
                // Find any text that was translated based on this same English (such as from the old website)
                $matchingEnglishStrings = self::where('text', $englishText)->where('language', 'en')->get();
                if (! $matchingEnglishStrings->isEmpty()) {
                    foreach ($matchingEnglishStrings as $matchingEnglishString) {
                        $suggested = self::where('group', $matchingEnglishString->group)->
                            where('key', $matchingEnglishString->key)->where('language', $language)->value('text');
                        if ($suggested != null) {
                            break;
                        }
                    }
                }
                if ($suggested == null) { // Didn't find any matching english to use as a suggestion...
                    // Try at least finding a matching key.
                    $keyParts = explode('.', $key);
                    //$suggested = self::where('key', end($keyParts))->where('language', $language)->value('text');
                }
            }
            $return[$key] = [
                'english' => $englishText,
                'current' => $languageString ? $languageString->text : $suggested,
                'translationIsNeeded' => $translationIsNeeded,
            ];
        }

        return $return;
    }

    /*
        Returns the strings in flattened array-dot format.

        '_translationInfo' => [
            'only' => [ 'forms.fieldLabel' ],
            'except' => [ 'forms.fieldLabel.id' ],
            'instructions' => 'sldkfjsldkfjsdlfkjsdlfkjsdf d d f sdf dsf fd'
        ],

        '_translationInfo' info from language files:
            - false - Don't translate.
            - 'only' - Array of only keys to trans (dot notation).
            - 'except' - Array of keys not to include (dot notation).
    */

    public static function loadEnGroup($group, &$translationInfo = null, $ignoreTranslationRules = false, $convertReplacementFormat = true)
    {
        $strings = Lang::getLoader()->load('en', $group, '*');

        if (! $ignoreTranslationRules && ! isset($strings['_translationInfo'])) {
            return false;
        }

        $translationInfo = $strings['_translationInfo'] ?? null;
        unset($strings['_translationInfo']);

        if (! $ignoreTranslationRules) {
            if (isset($translationInfo['except'])) {
                Arr::forget($strings, $translationInfo['except']);
            }
            if (isset($translationInfo['only'])) {
                $result = [];
                // Note: This also partially converts it to array-dot format.
                foreach ($translationInfo['only'] as $key) {
                    $result[$key] = Arr::get($strings, $key);
                }
                $strings = $result;
            }
        }

        $strings = Arr::dot($strings);

        if ($convertReplacementFormat) {
            foreach ($strings as $key => $string) {
                // Convert :foo Laravel-style replacements to our preferred [foo] format.
                $strings[$key] = preg_replace_callback("|\:([a-zA-Z_]+)|", function ($matches) {
                    return '[' . $matches[1] . ']';
                }, $string);
            }
        }

        // We also ignore any strings that are just integer numbers (no need to translate plain numbers)
        if (! $ignoreTranslationRules) {
            $strings = array_filter($strings, function ($value) {
                return (string) intval($value) !== $value;
            });
        }

        return $strings;
    }

    public static function getFromDatabaseByKey($group, $key, $language)
    {
        return self::where('language', $language)->where('group', $group)->where('key', $key)->first();
    }

    public static function updateTranslations($language, $group, $translations, $originalEnglishText, $logChangesAsCategory): void
    {
        foreach ($translations as $key => $text) {
            $text = trim($text);
            if ($text == '') {
                continue;
            }

            $languageString = self::getFromDatabaseByKey($group, $key, $language);
            if ($languageString) {
                if ($languageString->text == $text) {
                    continue;
                } // no change
                $changes = EventLog::describeChanges(['text' => $languageString->text], ['text' => $text]);
            } else {
                $languageString = new self();
                $languageString->language = $language;
                $languageString->group = $group;
                $languageString->key = $key;
                // Only log changes to the 'text' field
                $changes = EventLog::describeChanges(['text' => ''], ['text' => $text]);
            }
            $languageString->userID = auth()->id();
            $languageString->text = $text;
            $languageString->originalText = $originalEnglishText[$key];
            $languageString->save();

            // Log
            if ($logChangesAsCategory != '') {
                EventLog::log($logChangesAsCategory, 'update', 'LanguageString', $languageString->id, $key, $changes);
            }
        }
    }

    /*
        Create /lang/* files from the database for all languages other than English (which is the source template)
    */

    public static function updateLangFiles()
    {
        $output = '';

        $comment = '/* GENERATED BY LanguageString::updateLangFiles() */';

        $englishLanguageFiles = self::getAllEnGroups();

        foreach (Languages::allCodes() as $language) {
            if ($language == 'en') {
                continue;
            } // English is already done manually, so skip it.

            $output .= "$language: ";

            $path = base_path('resources/lang/' . $language);

            if (! File::exists($path)) {
                if (Languages::isCodeUsedOnLiveSite($language)) {
                    throw new Exception("Need to create a directory for the live site language '$language'.");
                }
                $output .= "(directory doesn't exist and not used on live site, skipping.)\n\n";

                continue;
            }

            // Delete old lang files
            foreach (File::files($path) as $file) {
                // File::delete($file);
                $contents = File::get($file);
                if (strpos($contents, $comment) !== false) {
                    File::delete($file);
                }
            }

            foreach ($englishLanguageFiles as $group) {
                $output .= "($group) ";
                $englishStrings = self::loadEnGroup($group);
                if (! $englishStrings) {
                    $output .= '[empty or missing _translationInfo, skipping.] ';

                    continue; // nothing to translate for this file
                }
                $translatedStrings = self::where('language', $language)->where('group', $group)->whereIn('key', array_keys($englishStrings))->get();
                $langFileOutput = [];
                foreach ($translatedStrings as $translatedString) {
                    // Convert [foo] replacements to :foo (Laravel) format.
                    $newText = preg_replace_callback("|(\[[^ \]]+\]+)|", function ($matches) {
                        return ':' . trim($matches[0], '[]');
                    }, $translatedString->text);
                    Arr::set($langFileOutput, $translatedString->key, $newText);
                }
                //if (!$langFileOutput) continue; // nothing was been translated for this file yet
                $data = '<?php ' . $comment . "\n return " . var_export($langFileOutput, true) . ';';
                File::put($path . '/' . $group . '.php', $data);
            }

            $output .= "\n\n";
        }

        return $output;
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'monthly':
                $output .= "Optimimize table.\n";
                DB::statement('OPTIMIZE TABLE ' . self::$staticTable);

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }
}
