<?php

namespace Lib;

use Carbon;
use Exception;
use Illuminate\Database\Eloquent\Model;
use ReflectionClass;
use Schema;

/*

*/

class BaseModel extends Model
{
    //
    // ** DataTypes **
    //

    /* We cache the data for two reasons: First because we have to cache it temporarily until the object is saved so we don't save it until then,
    second for efficiency if there are multiple __get() calls. */

    /*
        We would probably like these two to be private, but it was causing errors in boot()'s saving() function because it was having trouble
        accessing them.  (PHP bug?)
    */
    public $dataTypesDataCache = [];

    public $dataTypesDataCacheIsDirty = [];

    protected $dataTypes = [];

    public static function createTables()
    {
        foreach (static::staticDataTypes() as $dataType) {
            if (method_exists($dataType, 'createStorage')) {
                $dataType->createStorage();
            }
        }
    }

    public static function dropTables()
    {
        foreach (static::staticDataTypes() as $dataType) {
            if (method_exists($dataType, 'removeStorage')) {
                $dataType->removeStorage();
            }
        }
        if (isset(static::$staticTable)) {
            Schema::dropIfExists(static::$staticTable);
        }
    }

    public function save(array $options = [])
    {
        foreach ($this->dataTypes() as $name => $dataType) {
            // If the value has been modified, call its saving() function.
            if (array_key_exists($name, $this->dataTypesDataCacheIsDirty) && method_exists($dataType, 'saving')) {
                $dataType->saving($this, $this->dataTypesDataCache[$name]);
            }
        }

        $saved = parent::save($options);

        foreach ($this->dataTypes() as $name => $dataType) {
            // If the value has been modified, call its saved() function and clear the dirty cache flag.
            if (array_key_exists($name, $this->dataTypesDataCacheIsDirty) && method_exists($dataType, 'saved')) {
                $dataType->saved($this, $this->dataTypesDataCache[$name]);
                unset($this->dataTypesDataCacheIsDirty[$name]);
            }
        }

        return $saved;
    }

    public function delete()
    {
        foreach ($this->dataTypes() as $dataType) {
            if (method_exists($dataType, 'delete')) {
                $dataType->delete($this);
            }
        }
        parent::delete();
    }

    // Just a stub returning nothing.  Only used if dataTypes() isn't defined by the child class.
    protected static function staticDataTypes()
    {
        return [];
    }

    // The dataTypes() array is usually the same as the static staticDataTypes() array,
    // except it can have extra data types that are specific to this particular object instance (such as adding CustomFields).

    public function dataTypes($addAdditionalDataTypes = null)
    {
        if (! $this->dataTypes) {
            $this->dataTypes = static::staticDataTypes();
        }
        if ($addAdditionalDataTypes) {
            $this->dataTypes = array_merge($this->dataTypes, $addAdditionalDataTypes);
        }

        return $this->dataTypes;
    }

    /* Accessors & Mutators */

    /*
    Problem: Want date fields to be Carbon objects, but display as Y-m-d, but setToStringFormat() isn't affects all Carbon objects.

    Solutions:
    * Use ->asDateTime() when Carbon dates are needed.
    - Still set zero dates to ''. (but is MySQL specific)
    - (na) Extend Carbon to stringify as Y-m-d.
    -
    */

    public function __get($key)
    {
        // * Handle DataTypes *

        $dataTypes = $this->dataTypes();

        // If it's not a special dataType, let the parent handle it.
        if (! array_key_exists($key, $dataTypes) || ! method_exists($dataTypes[$key], 'getValue')) {
            return parent::__get($key);
        }

        // Check dataTypesDataCache for the value.
        if (array_key_exists($key, $this->dataTypesDataCache)) {
            debugOutput("dataTypes get($key) from cache.");

            return $this->dataTypesDataCache[$key];
        }

        // Let the dataType get the value and save it to the cache.
        return $this->dataTypesDataCache[$key] = $dataTypes[$key]->getValue($this);
    }

    public function __set($key, $value)
    {
        // * Handle DataTypes *

        $dataTypes = $this->dataTypes();

        if (array_key_exists($key, $dataTypes)) {
            // Save it to dataTypesDataCache (it isn't actually saved until our saved() event handler calls set() on the dataType).
            debugOutput("dataTypes set($key) in cache.");
            $this->dataTypesDataCache[$key] = $value;
            $this->dataTypesDataCacheIsDirty[$key] = true; // signal that it was modified so we know to save it later.
        }

        // If it's not a special dataType, let the parent handle it.
        parent::__set($key, $value);
    }

    //
    // ** Misc **
    //

    public function shortClassName()
    {
        return with(new ReflectionClass($this))->getShortName();
    }

    public function updateAndLogEvent($newValues, $andSave = true, $subjectString = '', $logCategory = 'user')
    {
        $oldValues = [];
        foreach ($newValues as $name => $value) {
            $oldValues[$name] = $this->$name;
            $this->$name = $value;
        }
        $changeDescription = EventLog::describeChanges($oldValues, $newValues);
        if ($changeDescription == '') {
            return;
        } // no changes made
        if ($andSave) {
            $this->save();
        }
        if (! $this->id) {
            throw new Exception("Can't log event because we need the object's ID (it wasn't save yet?).");
        }
        EventLog::log($logCategory, 'update', $this->shortClassName(), $this->id, $subjectString, $changeDescription);
    }

    // Get actual attribute value (not using any accessors)

    public function getActualAttribute($key)
    {
        return $this->getAttributeFromArray($key);
    }

    // Set actual attribute value (not using any mutators)

    public function setActualAttribute($key, $value)
    {
        $this->attributes[$key] = $value;
    }

    public static function classBasename()
    {
        return class_basename(get_called_class());
    }

    public static function languageKeyBase()
    {
        return static::classBasename();
    }

    public function replicate(array $except = null)
    {
        $instance = parent::replicate($except);

        $instance->dataTypesDataCache = $this->dataTypesDataCache;
        $instance->dataTypesDataCacheIsDirty = $this->dataTypesDataCacheIsDirty;

        return $instance;
    }

    //
    // ** Field Info **
    //

    public static function getLabel($fieldName)
    {
        return langGet(static::languageKeyBase() . '.forms.fieldLabel.' . $fieldName);
    }

    /*
        Format $value into the display format suggested by the fieldInfo.
    */

    public static function formatValueForDisplay($fieldName, $value, $convertsArrayToString = true, $fieldInfo = null)
    {
        if ($fieldInfo === null) {
            $fieldInfo = static::fieldInfo()[$fieldName];
        }

        // Handle arrays

        if (is_array($value)) {
            foreach ($value as $k => $v) {
                $value[$k] = static::formatValueForDisplay($fieldName, $v, $convertsArrayToString, $fieldInfo);
            }
            if ($convertsArrayToString) {
                $value = implode(', ', $value);
            }

            return $value;
        }

        if (is_object($value) && enum_exists($value::class)) {
            return $value->value;
        }

        // Options (if used for this field)

        if (array_key_exists('options', $fieldInfo)) {
            return static::getDisplayTextForOption($fieldName, $fieldInfo, $value);
        }

        // Number Format

        $displayFormat = $fieldInfo['displayFormat'] ?? null;

        if ($displayFormat == null) {
            // Guess the format
            if (isset($fieldInfo['decimals']) || (isset($fieldInfo['sanitize']) && $fieldInfo['sanitize'] === 'float')) {
                $displayFormat = 'float';
            } elseif (isset($fieldInfo['sanitize']) && $fieldInfo['sanitize'] === 'int') {
                $displayFormat = 'int';
            }
        }

        switch ($displayFormat) {
            case 'int':
                return $value === '' || $value === null ? '' : (int) $value;

            case 'formattedInt': // has comma-separated thousands
                return $value === '' || $value === null ? '' : number_format((int) $value);

            case 'float':
                if ($value === '' || $value === null) {
                    return '';
                }
                if (array_key_exists('decimals', $fieldInfo)) {
                    return number_format((float) $value, $fieldInfo['decimals'], '.', '');
                } else {
                    return (float) $value;
                }

            case 'formattedFloat': // has comma-separated thousands
                if ($value === '' || $value === null) {
                    return '';
                }
                if (array_key_exists('decimals', $fieldInfo)) {
                    return number_format((float) $value, $fieldInfo['decimals']);
                } else {
                    return numberFormatKeepDecimals((float) $value);
                }

            case 'date':
                return $value;

            case null:
                return $value;

            default:
                throw new Exception("Unknown displayFormat '$displayFormat'.");
        }
    }

    public function formatForDisplay($fieldName, $convertsArrayToString = true, $fieldInfo = null)
    {
        return static::formatValueForDisplay($fieldName, $this->$fieldName, $convertsArrayToString, $fieldInfo);
    }

    public static function getDisplayTextForOption($fieldName, $fieldInfo, $option, $key = null)
    {
        if ($fieldInfo === null) {
            $fieldInfo = static::fieldInfo()[$fieldName];
        }

        if ($key === null) {
            $key = array_search($option, $fieldInfo['options']);
            if ($key === false) {
                if ($option == '') {
                    return '';
                } // we always allow the option of '' and display it as ''
                throw new Exception("Unknown option '$option' for $fieldName.");
            }
        }

        switch ($fieldInfo['optionsDisplay'] ?? null) {
            case 'translate':
                return static::getFieldLanguageText('options', $fieldName, $fieldInfo, $option);

            case 'translateKeys':
                return static::getFieldLanguageText('options', $fieldName, $fieldInfo, $key);

            case 'keys':
                return $key;

            case '':
                return $option;

            default:
                throw new Exception("Unknown optionsDisplay '$fieldInfo[optionsDisplay]'.");
        }
    }

    /*
        Sections currently used in $modelName's language file, 'form' section:

            Language file Text Types:
                'fieldLabel', 'fieldDescription', 'optionTranslations', 'placeholder', 'keyPlaceholder', 'popover' (also 'pageDescription', but not with this function).

            Each element:  (field name) => (text)

        $defaultText - Used when no translation is found.  If false, causes an Exception to be throw.  Otherwise is the default text to use if language isn't found.
    */

    public static function getFieldLanguageText($textType, $fieldName, $fieldInfo = null, $optionOrOptions = null, $defaultText = false, $languageKeyBase = null)
    {
        if ($fieldInfo === null) {
            $fieldInfo = static::fieldInfo()[$fieldName];
        }
        if ($languageKeyBase === null) {
            $languageKeyBase = static::languageKeyBase();
        }

        if (is_array($optionOrOptions)) {
            // If $option is an array, we translate each option and return an array of the translated texts.
            $translatedOptions = [];
            foreach ($optionOrOptions as $o) {
                $translatedOptions[] = static::getFieldLanguageText($textType, $fieldName, $fieldInfo, $o, $defaultText);
            }

            return $translatedOptions;
        } else {
            $option = $optionOrOptions;
        }

        if (array_key_exists($textType . 'Text', $fieldInfo)) {
            // Use fieldInfo's "{textType}Text" as actual text
            $text = $fieldInfo[$textType . 'Text']; // Use fieldInfo's actual text (not translated)
            if ($option !== null) {
                $text = $text[$option];
            }
        } else {
            if (array_key_exists($textType . 'LangKey', $fieldInfo)) {
                $key = $fieldInfo[$textType . 'LangKey']; // Use fieldInfo's "{textType}LangKey" as language key
                if ($option !== null) {
                    if (is_array($key)) {
                        $key = $key[$option]; // if option and key is an array, we assume it's an array of keys for each option
                        $text = langGet($key, null);
                    } else {
                        $text = langGet($key . '.' . $option, null);
                    }
                } else {
                    $text = langGet($key, null);
                }
            } else {
                $text = langGet($languageKeyBase . '.forms.' . $textType . '.' . $fieldName, null); // Try the default language file

                if ($option !== null) {
                    if ($text === null) {
                        $text = langGet($languageKeyBase . '.forms.' . $textType . '.' . $option, null); // Try the option, not specific to a particular $fieldName.
                    } else {
                        $text = ($option === '' ? '' : ($text[$option] ?? null)); // We assume the language contains an array of options
                    }

                    if ($text === null) {
                        $text = langGet("{$languageKeyBase}.forms.{$textType}.{$fieldName}.{$option}", null);
                    }

                    if ($text === null && strpos($option, '.') !== false) {
                        // The option value (or key) can also be used as the full absolute key to the translation.
                        $text = langGet($option, null);
                    }
                }
            }
        }

        // Text not found, use default (which might be null)...
        if ($text === null) {
            if ($defaultText === false) {
                throw new Exception("No language text for '$textType', '$fieldName', '$optionOrOptions'.");
            }

            return $defaultText;
        }

        return $text;
    }
}
