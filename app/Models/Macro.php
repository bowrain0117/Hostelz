<?php

namespace App\Models;

use Exception;
use Lib\BaseModel;

class Macro extends BaseModel
{
    protected $table = 'macros';

    public static $staticTable = 'macros';

    protected $guarded = [];

    public $timestamps = false; // no timestamps needed

    public static $statusOptions = ['ok', 'disabled'];

    public static $purposeOptions = ['mail', 'review'];

    /* Static */

    protected static function staticDataTypes()
    {
        static $dataTypes = [];

        if (! $dataTypes) {
            $dataTypes = [
                'supportEmail' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'supportEmail']),
            ];
        }

        return $dataTypes;
    }

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'userID' => ['editType' => ($purpose != 'adminEdit' ? 'ignore' : ''),
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'userHasPermission' => ['type' => ($purpose != 'adminEdit' ? 'ignore' : 'select'), 'options' => User::$accessOptions,
                        'optionsDisplay' => 'translate', 'optionsLangKey' => 'User.forms.options.access', ],
                    'purpose' => ['type' => 'select', 'validation' => 'required', 'options' => self::$purposeOptions],
                    'category' => ['maxLength' => 100],
                    'conditions' => ['type' => 'multi', 'keys' => '', 'keySize' => 25, 'keyMaxLength' => 25, 'size' => 30, 'maxLength' => 30,
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->mode == 'list' || $formHandler->mode == 'searchAndList') && $model->conditions ?
                                json_encode($model->conditions) : // a sort-of better way to display the conditions in list mode (otherwise only the values are displayed)
                                $model->conditions;
                        },
                    ],
                    'name' => ['maxLength' => 100, 'validation' => 'required'],
                    'macroText' => ['type' => 'textarea', 'sanitize' => '' /* keeps it from trimming whitespace which it does by default */],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    public static function getMacrosTextArray($purpose, $user = null, $variableValues = [], $replacementStrings = [])
    {
        $query = self::where('purpose', $purpose)->where('status', 'ok')->orderBy('category');
        if ($user) {
            $query->whereIn('userID', [0, $user->id])->orderBy('userID', 'desc');
        } // orderBy is so we get the user-specific version first

        $macros = $query->get();

        $return = [];

        foreach ($macros as $macro) {
            // Check for user permission
            if ($macro->userHasPermission) {
                if (! $user || ! $user->hasPermission($macro->userHasPermission)) {
                    continue;
                }
            }

            // Check for conditions
            if ($macro->conditions) {
                foreach ($macro->conditions as $variable => $value) {
                    if (! array_key_exists($variable, $variableValues)) {
                        throw new Exception("Unknown condition variable '$variable' in macro {$macro->id}.");
                    }
                    if ($variableValues[$variable] !== $value) {
                        continue 2;
                    }
                }
            }

            if (isset($return[$macro->category][$macro->name])) {
                if ($macro->userID == $return[$macro->category][$macro->name]->$userID) {
                    throw new Exception("Macro {$macro->id} is a duplicate of macro " . $return[$macro->category][$macro->name]->id);
                }

                continue; // already set because there is a user-specific version of this macro
            }

            if ($macro->macroText == '') {
                continue;
            } // macros with no text are used to remove certain macros for certain users

            if ($replacementStrings) {
                $macro->macroText = str_replace(array_keys($replacementStrings), $replacementStrings, $macro->macroText);
            }

            $return[$macro->category][$macro->name] = $macro;
        }

        // Sort by name
        foreach ($return as $category => &$macros) {
            ksort($macros);
        }
        unset($macros); // break the reference with the last element just to be safe

        return $return;
    }

    /* Accessors & Mutators */

    public function getConditionsAttribute($value)
    {
        return $value == '' ? [] : json_decode($value, true);
    }

    public function setConditionsAttribute($value): void
    {
        $this->attributes['conditions'] = ($value ? json_encode($value) : '');
    }

    /* Static */

    /* Misc */

    /* Relationships */

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'userID');
    }
}
