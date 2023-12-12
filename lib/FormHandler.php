<?php

namespace Lib;

// Laravel:
use App;
use Exception;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\MessageBag;
use Lang;
use Request;
use Validator;
// not sure why this one needs the full path, but it does.
use View;

/*
Usage:
    	$formHandler = new FormHandler(Listing::query() , Listing::getFieldInfo($searchType));

Foreign table field example (search/display only):

        'listing_name' => [ 'type' => 'display', 'searchType' => '', 'dataAccessMethod' => 'dataType',
            'dataTypeObject' => new \Lib\dataTypes\StringDataType([ 'tableName' => Listing::$staticTable, 'fieldName' => 'name', 'firstTableKeyFieldname' => 'listingID' ]) ],

Foreign table field example (editable):

        'listing_name' => [ 'dataAccessMethod' => 'dataType',
            'dataTypeObject' => new \Lib\dataTypes\StringDataType([ 'tableName' => Listing::$staticTable, 'fieldName' => 'name', 'firstTableKeyFieldname' => 'listingID' ]) ],


Field Info Elements:

    dataType - Name of the DataType class. Defaults to 'StringDataType'. Not that this only works for dataTypes that only require tableName and fieldName.
    dataTypeObject - If assign this to the DataTypeObject object to use (otherwise the data is assumed to be a database field string).  Can optionally define these functions:
        getDefaultComparisonType, searchQuery, addAggregateSelect, getCountsOfAllValues, getArrayOfAllValues, orderBy, addLeftJoinIfNeeded, getValue, setValue (from fieldInfo only)
        - These can also be defined in the fieldInfo by defining a callback functions to use, which overrides the default function from the dataTypeObject. When used this way, the
        first parameter is the $formHandler object.
    dataAccessMethod - How to get/set values when accessing the model (or dataType). Options:
        'model': (default) just use $model->$fieldName (use this if it's a static dataType for the model, since the BaseModel already calls getValue(), etc.
        'dataType': use the dataType getValue/setValue/saving/saved().
        'none': don't touch the model.
    modelPropertyName - To use a different property of the model rather than the key of the fieldInfo element.  Used to set the fieldName when creating our own dataType and when setting
        the model's property when getting/setting it (for 'model' dataAccessMethod).
    subElement - Get/set an element of the field as an array.
    where - custom function
    options - array or "from_existing". For 'select', 'radio', and 'multi' (optional).  formHandlerFormFields will automatically add a '' option unless a searchFormDefaultValue is defined.
    sanitize - "trim", "email", "url", "int", "float", "tags", "dataCorrection" (set dataCorrectionTable, also set dataCorrectContextField1/2), or a callable function.
    validation - Laravel validation rules for insert / update.
    searchValidation - Laravel validation rules for searches.
    validationSometimes / searchValidationSometimes - Array with [ rule, callback function ]. Laravel sometimes() validation.
    autoSearchDefault - 'minMax' to set inputData to min and max values in the data.  Or pass a closure for custom values.
    comparisonType - Expecting value as a string: 'equals', 'notEqual', 'startsWith', 'subtring', 'notSubstring', 'isEmpty', 'notEmpty'. Expecting value as an array: 'minMax', 'matchAny', 'matchAll', 'matchNotAll', 'matchNone'.  Other types may be added that only apply to certain DataTypes.
    specialSearch - Overrides the $formHandler->specialSearch value, just for this field.
    userSortable - if true, sorts if the listSortVar is set for this field.  Also tells the list template to allow sorting by this column.

For form display:
    (see getLanguageText() for label and text language elements)
        Override with fieldInfo:
            "{textType}LangKey" (key for the language file) or "{textType}Text" (actual text). Example: "fieldLabelText"
            Can be an array to match an option if $option is passed
            Or can also specify a different one for each $pageType in an array. Example: 'cityLangKey' => [ 'edit' => 'Listing.foo.city' ... ]
    Types:
        type - Applies to all modes if a more specific type isn't specified. Options: 'text' (default), 'ignore', 'hidden' (use for values that can be set by the form, but aren't displayed), 'display', 'WYSIWYG' (sanitize 'WYSIWYG' recommended), 'textarea', 'select', 'checkbox', 'checkboxes', 'radio', 'datePicker', 'minMax' (searchType only), 'password'. HTML 5 input types: 'email', 'number', 'url'.
        searchType, listType, editType (either update or insert), insertType, updateType, editableListType, etc. - Specific to particular page types / modes.
    displayFormat - Display format used by formatValueForDisplay(). 'int', 'float', 'date'.
    decimals - Number of decimal places when formatting as float.
    --> see getLanguageText() for label text and option text info.
    comparisonTypeOptions
    size
    maxLength
    rows (for textarea or wysiwyg)
    unescaped - (currently only used for 'display' mode fields) Set to true to not escape HTML when displaying.
    placeholder
    keyPlaceholder - For "multi" inputs with keys.
    searchFormDefaultValue - Default value of the field for the search form (default value for insert form fields can be set by the model's constructor or using defaultInputData).
    showBlankOption - Set to false to not automatically include '' as an option for a 'select' type element.
    value - Value to assign when checkbox is checked (used with 'checkbox' type only).
    listValueTruncate - When displaying results lists, truncate the length of the value to this length.
    lastOptionStringInput - For radio inputType, if the last option is selected a string input pops up to get a user-entered value. Value defaults to the option key. The value of lastOptionStringInput specifies what text to use as a label for the input box (currently must be a full path lang file key) (or '' for no label).
    keys - For 'multi' inputType.  Omit keys for no keys (values only). 'keys'=>'' for user-inputted keys.
    optionsDisplay => 'translate', 'translateKeys', 'keys'.  Otherwise the option value is displayed.
    optionCounts - Count of how many items match it option (searchForm mode only).  Set to true to have FormHandler query the DB for the counts.
    formGroupClass
    row - Set to 'start' for <div class="row">, 'end' for </div>, or 'middle' for neither. Used to group a set of form elements on one line, usually using combineFieldsIntoOneRow() method.
    columns - Set to 'start', 'middle', and 'end' to use 'columnCount' number of columns, usually set using the displayFieldsInColumns() method.
    htmlBefore, htmlAfter
    determinesDynamicGroup - (Not needed for 'server' type dynamic methods, but 'server' type needs to set 'submitFormOnChange' to work). The value of this element is used to set the dynamicGroup, which is used to display/hide elements of the form.
    dynamicGroup / dynamicGroupValues - To show the element only if the dynamicGroup value is in dynamicGroupValues (CSV list).
    dynamicMethod - 'remove' (default), 'hide', or 'server'. Use 'hide' to keep the elements but not show them (which means they get submitted with the form).
    maxDate/minDate/defaultDate - Used for datePicker.
*/

class FormHandler
{
    // Basics
    public $fieldInfo;

    public $allowedModes;

    // We don't allow these modes to be set by a GET variable (mostly to avoid CSRF hacks since we typically check POST requests for CSRF but not GET).
    public $modesNotAllowedFromGetRequests = ['insert', 'delete', 'update', 'multiDelete', 'multiUpdate'];

    public $mode = null;

    public $defaultComparisonType = null;

    public $specialSearch = null;

    public $count; // Currently only used for multiDelete

    public $callbacks; // Callbacks for 'getModelData', 'setModelData', 'validate', 'saveModel', 'logChanges', 'listRowLink', 'describeChanges'

    // Logging
    public $logChangesAsCategory = null; // record changes to the EventLog as this category.

    // Model / Database
    public $modelName; // Name of the model's class

    public $modelNamespace; // Namespace of the model (without the starting '\')

    public $model; // Used for display/update/insert operations (not for searching).

    public $tableName = null; // Can be set explicitely, otherwise we get it from the model's getTable() function.

    public $parentModel;

    public $parentModelFieldName;

    public $query = null; // Defaults to ModelNameHere::query().  Set to false to not use database queries at all.

    // Lists
    public $listSelectFields = null; // database fields to select when in list mode, or false fetch the whole model object.

    public $listDisplayFields = null; // model/database fields to actually display (if using the formHandlerList template). Leave null to display all $listSelectFields.

    public $list;

    public $listPaginateItems = 25;

    public $editableListPaginateItems = 500;

    public $userSortableDefault = true; // Set to false to make columns not sortable (by clicking) by default (unless 'userSortable' is set to true for the fieldInfo of the field).

    public $listSortVar = 'sort'; // Name of input variable of array used to specify how to sort the list (userSortable must also be set on the field's fieldInfo).

    public $listSort; // Default list sort if a user defined one isn't given ($listSortVar) array of fieldNames -> 'asc' or 'desc'.

    // Handling Input Data
    public $pathParameters;

    public $defaultSanitize = ['trim']; // sanitize to apply to all data (can be a single string or an array of sanitizers).

    public $whereVarName = 'where'; // values passed to the script to narrow the scope of items

    public $whereData = null; // values passed to the script usually as where[] to narrow the results scope (see $whereVarName)

    public $searchVarName = 'search'; // values from the search form submit (data is placed in $inputData).

    public $inputDataVarName = 'data';

    public $inputData; // values passed to the script usually as data[] when submitting an update/inssert form (see $dataVarName)

    public $defaultInputData;

    public $comparisonTypesVarName = 'comparisonTypes';

    public $comparisonTypes;

    public $forcedData = [];

    // Form Display
    public $languageKeyBase; // by default is {modelName}

    public $showFieldnameIfMissingLang = false;

    public $defaultMaxLength = 255; // this is currently only used when displaying the form for input elements. the limit isn't currently enforced.

    public $errors; // MessageBag object with any validation errors.

    public $multiErrors = []; // Array of MessageBag objects with errors (used for multiUpdate)

    public $persistentValues = []; // array of values that need to be included when forms are submitted (where data is automatically added to this array).

    // Private
    private $leftJoins = [];

    public function __construct($modelName, $fieldInfo = null, $pathParameters = null, $modelNamespace = '')
    {
        $this->fieldInfo = $fieldInfo;
        $this->modelName = $modelName;
        $this->modelNamespace = $modelNamespace;
        $this->pathParameters = $pathParameters;

        $temp = $this->namespacedModelName();
        $this->languageKeyBase = $temp::languageKeyBase();
    }

    public function getTableName()
    {
        if ($this->tableName !== null) {
            return $this->tableName;
        }

        // The Eloquent getTable() function requires an instance of the model, so we create a temp one.
        $modelName = $this->namespacedModelName();

        return (new $modelName)->getTable();
    }

    public function getFieldDefaultValue($fieldName)
    {
        $fieldInfo = $this->fieldInfo[$fieldName];
        if ($this->defaultInputData && array_key_exists($fieldName, $this->defaultInputData)) {
            return $this->defaultInputData[$fieldName];
        } // values typically set by prepSearchPage()

        switch ($this->mode) {
            case 'searchForm':
            case 'searchAndList':
                if (array_key_exists('searchFormDefaultValue', $fieldInfo)) {
                    return $fieldInfo['searchFormDefaultValue'];
                }
                break;
        }

        return null;
    }

    public function getModelPropertyName($fieldName, $fieldInfo)
    {
        return array_key_exists('modelPropertyName', $fieldInfo) ? $fieldInfo['modelPropertyName'] : $fieldName;
    }

    public function getModelData($useCallback = true)
    {
        if ($useCallback && @$this->callbacks['getModelData']) {
            return call_user_func_array($this->callbacks['getModelData'], [$this]);
        }

        $results = $this->query->get();
        if (! $results || $results->isEmpty()) {
            App::abort(404);
        }
        if ($results->count() > 1) {
            throw new Exception('Multiple models matched load data query.');
        }
        $this->model = $results->getIterator()->current(); // use the one (and only) matching result
    }

    public function getFieldValueForList($fieldName, $row, $formatForDisplay)
    {
        $fieldInfo = $this->fieldInfo[$fieldName];

        // If it's in listSelectFields, even if it's a foreign table, it should have been fetched using a left join, so use that already-fetched value.
        $forceDataAccessMethod = @$fieldInfo['dataAccessMethod'] != 'none' &&
        $this->listSelectFields && in_array($fieldName, $this->listSelectFields) ? 'model' : false;
        $value = $this->getFieldValue($fieldName, false, $row, $forceDataAccessMethod, false, true);
        if (@$fieldInfo['listValueTruncate']) {
            $value = wholeWordTruncate($value, $fieldInfo['listValueTruncate']);
        }
        if ($formatForDisplay) {
            $value = $this->formatValueForDisplay($value, $fieldName, $this->fieldInfo[$fieldName], 'list', true);
        }

        return $value;
    }

    public function getFieldValue($fieldName, $useInputData = false, $model = null, $forceDataAccessMethod = false, $ignoreFieldElement = false, $ignoreInputType = false)
    {
        if (! $ignoreInputType && $this->determineInputType($fieldName) == 'ignore') {
            throw new Exception("getFieldValue() called for 'ignore' field '$fieldName'.");
        }

        // ForcedData
        if (array_key_exists($fieldName, $this->forcedData)) {
            return $this->forcedData[$fieldName];
        }

        // Use inputData
        if ($useInputData && $this->inputData && array_key_exists($fieldName, $this->inputData)) {
            return $this->inputData[$fieldName];
        }
        if ($model === false) {
            return null;
        } // if $model is false, that means we don't want to use it at all (only inputData), so we're done.

        $fieldInfo = $this->fieldInfo[$fieldName];
        if (! $model) {
            $model = $this->model;
        }

        $dataAccessMethod = ($forceDataAccessMethod ?: @$fieldInfo['dataAccessMethod']);

        if (! $model || ($dataAccessMethod == 'none' && ! array_key_exists('getValue', $fieldInfo))) {
            $value = null;
        } elseif ($dataAccessMethod == 'dataType' || array_key_exists('getValue', $fieldInfo)) {
            $value = $this->callDataTypeFunction($fieldName, 'getValue', [$model]);
        } else {
            $value = $model->{$this->getModelPropertyName($fieldName, $fieldInfo)};
        } // 'model' dataAccessMethod (default)

        if ($value === null) {
            // Default values
            $value = $this->getFieldDefaultValue($fieldName);
            if ($value !== null) {
                return $value;
            }
        }

        // Use subElement
        if (! $ignoreFieldElement && array_key_exists('subElement', $fieldInfo)) {
            $value = @$value[$fieldInfo['subElement']];
        }

        return $value;
    }

    public function formatValueForDisplay($value, $fieldName, $fieldInfo, $pageType, $convertsArrayToString = false)
    {
        $temp = $this->namespacedModelName();

        return $temp::formatValueForDisplay($fieldName, $value, $convertsArrayToString, $fieldInfo);
    }

    public function getLanguageText($textType, $fieldName, $pageType, $option = null, $defaultText = null)
    {
        $temp = $this->namespacedModelName();
        $text = $temp::getFieldLanguageText($textType, $fieldName, $this->fieldInfo[$fieldName],
            $option, $defaultText, $this->languageKeyBase);

        // Text can also be divided up by page type...
        // pageType ('list', 'search', 'edit'): (field name) => [ (pageType) => (text), ... ]

        if (is_array($text)) {
            $text = @$text[$pageType];
        }

        // Text not found, use default...
        if ($text === null) {
            if ($defaultText !== null) {
                $text = $defaultText;
            } else {
                if ($this->showFieldnameIfMissingLang) {
                    $text = ucfirst($fieldName);
                } else {
                    throw new Exception("No language text for (pageType:$pageType) " . $this->languageKeyBase . '.forms.' . $textType . '.' . $fieldName . " (option:$option).");
                }
            }
        }

        if (is_array($text)) {
            throw new Exception("Array not text string found in language file for ($pageType) " . $this->languageKeyBase . '.forms.' . $textType . '.' . $fieldName . " ($option).");
        }

        return $text;
    }

    public function getDisplayTextForOption($fieldInfo, $fieldName, $option, $key)
    {
        $temp = $this->namespacedModelName();

        return $temp::getDisplayTextForOption($fieldInfo, $fieldName, $option, $key);
    }

    // sets mode and allowedModes.
    public function determineMode($allowedModes = null, $defaultMode = null, $forceMode = null)
    {
        // Allowed Modes

        if ($allowedModes) {
            $this->allowedModes = $allowedModes;
        } elseif (! $this->allowedModes) {
            $this->allowedModes = ['searchForm', 'list', 'searchAndList', 'editableList', 'display', 'insertForm', 'insert', 'updateForm', 'update', 'multiUpdate', 'delete', 'multiDelete'];
        } // all modes

        // Determine the mode

        if ($forceMode !== null) {
            $this->mode = $forceMode;
        } elseif ($this->mode === null) {
            // Mode Variables in the Input

            if (($mode = Request::input('mode')) != null) {
                if (! Request::isMethod('post') && in_array($mode, $this->modesNotAllowedFromGetRequests)) {
                    throw new Exception("Mode '$mode' is not allowed for a non-POST request.");
                }

                if ($mode == 'list' && ! in_array('list', $this->allowedModes) && in_array('searchAndList', $this->allowedModes)) {
                    $this->mode = 'searchAndList';
                } else {
                    $this->mode = $mode;
                }
            }

            if ($this->mode == null) {
                // Determine mode from the pathParameters
                if ($this->pathParameters == 'new') {
                    $this->mode = 'insertForm';
                } elseif (intval($this->pathParameters)) {
                    $this->mode = in_array('updateForm', $this->allowedModes) ? 'updateForm' : 'display';
                }
            }

            if ($this->mode == null) {
                // Use default mode
                if ($defaultMode !== null) {
                    $this->mode = $defaultMode;
                } else {
                    // Determine the best default mode
                    if (in_array('searchAndList', $this->allowedModes) && $this->whereData) {
                        // (If whereData() is set, we default to 'searchAndList' if it's allowed, otherwise 'searchForm' is the first default.)
                        $this->mode = 'searchAndList';
                    } elseif (in_array('searchForm', $this->allowedModes)) {
                        $this->mode = 'searchForm';
                    } elseif (in_array('searchAndList', $this->allowedModes)) {
                        $this->mode = 'searchAndList';
                    } elseif (in_array('insertForm', $this->allowedModes)) {
                        $this->mode = 'insertForm';
                    } elseif (in_array('updateForm', $this->allowedModes) && $this->model) {
                        // This is a special case for when the model was explicitely set by the calling code.
                        $this->mode = 'updateForm';
                    } elseif (in_array('list', $this->allowedModes)) {
                        $this->mode = 'list';
                    } else {
                        throw new Exception('Unable to determine mode.');
                    }
                }
            }
        }

        // Make sure the mode is allowed

        if (! in_array($this->mode, $this->allowedModes) && $this->mode != 'fatalError') {
            App::abort(404); // unauthorized (ideally we would use accessDenied(), but we have no way to return a view from this function).
        }
    }

    public function namespacedModelName()
    {
        return ($this->modelNamespace != '' ? '\\' . $this->modelNamespace . '\\' : '') . $this->modelName;
    }

    public function initializeQuery()
    {
        // (Note: If $this->query === false, that means that they don't want to use query at all.)
        if (! $this->query && $this->query !== false) {
            $temp = $this->namespacedModelName();
            $this->query = $temp::query();
        }
    }

    public function getWhereDataFromInput()
    {
        $this->whereData = Request::input($this->whereVarName);
    }

    public function setComparisonTypesFromInput()
    {
        if (Request::input($this->comparisonTypesVarName)) {
            if ($this->comparisonTypes) {
                $this->comparisonTypes = array_merge($this->comparisonTypes, Request::input($this->comparisonTypesVarName));
            } else {
                $this->comparisonTypes = Request::input($this->comparisonTypesVarName);
            }
        }
    }

    /*
        Do everything.

        view - If null, the page isn't displayed (script can later call ->display() manually).
    */

    public function go($view = null, $allowedModes = null, $defaultMode = null, $forceMode = null)
    {
        $this->initializeQuery();

        // we get the whereData now because it may be used by determineMode()
        if ($this->whereData == null) {
            $this->getWhereDataFromInput();
        }

        $this->determineMode($allowedModes, $defaultMode, $forceMode);

        // Handle the mode
        switch ($this->mode) {
            case 'searchForm':
            case 'searchAndList':
                $this->setComparisonTypesFromInput();
                if ($this->whereData) {
                    $this->persistentValues['where'] = $this->whereData;
                }
                $this->queryWhereFromArray($this->whereData);
                $this->inputData = $this->sanitizeData(Request::input($this->searchVarName));
                $this->prepServerSideDynamic(); // do this before any form prep since it may remove some fields (more efficient)
                $this->prepSearchPage();
                if ($this->mode !== 'searchAndList') {
                    break;
                }

            case 'list':
            case 'editableList':
                if ($this->mode !== 'searchAndList') { // (if searchAndList, these things where already done above.)
                    $this->setComparisonTypesFromInput();
                    if ($this->whereData) {
                        $this->persistentValues['where'] = $this->whereData;
                    }
                    $this->queryWhereFromArray($this->whereData);
                    $this->inputData = $this->sanitizeData(Request::input($this->searchVarName));
                }

                $this->errors = $this->validate(true, 'searchValidation');
                if ($this->errors->any() && $this->mode !== 'searchAndList') {
                    return $this->go($view, $allowedModes, $defaultMode, 'searchForm');
                }

                $this->queryWhereFromArray($this->inputData); // usually search[] (inputData was set above to the search data)

                if (! empty($this->inputData['verified']) && $this->isHostelgeeksTable()) {
                    $this->queryWhereFromSelectedVerifiedOption();
                }

                $this->listQuerySelect();
                $this->listQuerySort();

                if (! empty($this->callbacks['beforeRunQuery']) && is_callable($this->callbacks['beforeRunQuery'])) {
                    call_user_func($this->callbacks['beforeRunQuery'], $this);
                }

                if ($this->mode === 'editableList' && $this->editableListPaginateItems) {
                    $this->list = $this->query->paginate((int) $this->editableListPaginateItems);
                } elseif ($this->listPaginateItems) {
                    $this->list = $this->query->paginate((int) $this->listPaginateItems);
                } else {
                    $this->list = $this->query->get();
                }

                $this->prepListPage();
                break;

            case 'display':
                $this->queryWhereFromPathParameters();
                $this->queryWhereFromArray($this->whereData); // only sometimes used on this type of page
                if (! $this->model) {
                    $this->getModelData();
                }
                $this->prepDisplayPage();
                break;

            case 'updateForm':
                $this->queryWhereFromPathParameters();
                $this->queryWhereFromArray($this->whereData); // only sometimes used on this type of page
                if (! $this->model) {
                    $this->getModelData();
                }
                $this->prepServerSideDynamic(); // do this before any form prep since it may remove some fields (more efficient)
                $this->prepUpdatePage();
                break;

            case 'update':
                $this->queryWhereFromPathParameters();
                $this->queryWhereFromArray($this->whereData); // only sometimes used on this type of page
                if (! $this->model) {
                    $this->getModelData();
                }
                $this->inputData = $this->sanitizeData(Request::input($this->inputDataVarName));
                $this->errors = $this->validate();
                if ($this->errors->any()) {
                    return $this->go($view, $allowedModes, $defaultMode, 'updateForm');
                }
                $this->update($this->inputData);
                break;

            case 'multiUpdate': // (Update multiple records at a time, usually from a editableList submit.)
                $baseQuery = clone $this->query;
                $this->queryWhereFromArray($this->whereData); // only sometimes used on this type of page
                $this->multiErrors = [];
                $multiInputData = $this->sanitizeData(Request::input($this->inputDataVarName));
                if (! is_array($multiInputData)) {
                    throw new Exception('No input data for multiUpdate.');
                }
                foreach ($multiInputData as $id => $inputData) {
                    $this->query = clone $baseQuery;
                    $this->query->where('id', $id);
                    $this->getModelData();
                    $this->inputData = $this->sanitizeData($inputData);
                    $errors = $this->validate();
                    if ($errors->any()) {
                        $this->multiErrors[$id] = $errors;
                    } else {
                        $this->update($this->inputData);
                    }
                }
                $this->query = $baseQuery; // reset the query back to the original list query
                if ($this->multiErrors) {
                    return $this->go($view, $allowedModes, $defaultMode, 'editableList');
                }
                break;

            case 'multiDelete': // (Delete multiple records at a time.)
                $baseQuery = clone $this->query;
                $this->queryWhereFromArray($this->whereData); // only sometimes used on this type of page
                $multiSelected = Request::input('multiSelect');
                $this->count = is_array($multiSelected) ? count($multiSelected) : 0;
                if (! is_array($multiSelected)) {
                    break;
                } // If nothing was selected, we do nothing.
                foreach ($multiSelected as $id) {
                    $this->query = clone $baseQuery;
                    $this->query->where('id', $id);
                    $this->getModelData();
                    if ($this->model) {
                        $this->model->delete();
                    }
                }
                $this->model = null; // because the formHandler model field shouldn't really be set to anything for multiDelete mode.
                $this->query = $baseQuery; // reset the query back to the original list query
                break;

            case 'insertForm':
                // (Get inputData if any... only happens if some initial values were specified in the url.)
                if (! $this->inputData) {
                    $this->inputData = $this->sanitizeData(Request::input($this->inputDataVarName));
                }
                $this->createNewModel(); // a blank model is created but not actually saved
                $this->prepServerSideDynamic(); // do this before any form prep since it may remove some fields (more efficient)
                $this->prepInsertPage();
                break;

            case 'insert':
                $this->inputData = $this->sanitizeData(Request::input($this->inputDataVarName));
                $this->errors = $this->validate();
                if ($this->errors->any()) {
                    return $this->go($view, $allowedModes, $defaultMode, 'insertForm');
                }
                $this->insert($this->inputData);
                break;

            case 'delete':
                $this->queryWhereFromPathParameters();
                $this->queryWhereFromArray($this->whereData); // only sometimes used on this type of page
                if (! $this->model) {
                    $this->getModelData();
                }
                $this->delete();
                break;

            default:
                throw new Exception("Unknown mode '" . $this->mode . "'.");
        }

        if ($view !== null) {
            return $this->display($view);
        }
    }

    public function display($view, $otherViewData = [])
    {
        return view($view, $otherViewData)->with('formHandler', $this);
    }

    /* Prep Page Functions */

    public function prepSearchPage()
    {
        // Create Aggregate Query

        $queryTemp = null;

        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if ($this->inputData && array_key_exists($fieldName, $this->inputData)) {
                continue;
            } // don't overwrite any values submitted by the user
            if (! array_key_exists('autoSearchDefault', $fieldInfo)) {
                continue;
            }

            switch ($fieldInfo['autoSearchDefault']) {
                case 'minMax':
                    if (! $queryTemp) {
                        $queryTemp = clone $this->query;
                    }
                    $this->callDataTypeFunction($fieldName, 'addAggregateSelect', [$queryTemp, 'max', 'max__' . $fieldName]);
                    $this->callDataTypeFunction($fieldName, 'addAggregateSelect', [$queryTemp, 'min', 'min__' . $fieldName]);
                    break;
            }
        }

        // Perform Aggregate Query

        if ($queryTemp) {
            $result = $queryTemp->first();

            foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
                if ($this->inputData && array_key_exists($fieldName, $this->inputData)) {
                    continue;
                } // don't overwrite any values submitted by the user

                switch (@$fieldInfo['autoSearchDefault']) {
                    case 'minMax':
                        $this->defaultInputData[$fieldName]['min'] = $result["min__$fieldName"];
                        $this->defaultInputData[$fieldName]['max'] = $result["max__$fieldName"];
                        break;
                }
            }
        }

        // Callable autoSearchDefault
        // (we do these after the aggregate functions above are done because the callable may use the values we found above (such as converting size from sqft to meters)

        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if ($this->inputData && array_key_exists($fieldName, $this->inputData)) {
                continue;
            } // don't overwrite any values submitted by the user
            if (@$fieldInfo['autoSearchDefault'] && is_closure($fieldInfo['autoSearchDefault'])) {
                $this->defaultInputData[$fieldName] = $fieldInfo['autoSearchDefault']($this, clone $this->query);
            }
        }

        // Set optionCounts if needed.

        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if (@$fieldInfo['optionCounts'] === true) {
                $this->fieldInfo[$fieldName]['optionCounts'] = $this->callDataTypeFunction($fieldName, 'getCountsOfAllValues', [$this->query]);
            }
        }

        // Do other prop for forms (Note: this must come after we get the optionCounts above).
        $this->prepForAnyFormPage();
    }

    public function prepDisplayPage()
    {
    }

    public function prepUpdatePage()
    {
        $this->prepForAnyFormPage();
    }

    public function prepInsertPage()
    {
        $this->prepForAnyFormPage();
    }

    public function prepForAnyFormPage()
    {
        // Options from Existing Values

        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if (@$fieldInfo['options'] == 'from_existing') {
                if (is_array(@$fieldInfo['optionCounts'])) {
                    // Might as well use the optionCounts array.
                    $this->fieldInfo[$fieldName]['options'] = array_keys($fieldInfo['optionCounts']);
                } else {
                    $this->fieldInfo[$fieldName]['options'] = $this->callDataTypeFunction($fieldName, 'getArrayOfAllValues', [$this->query]);
                }
            }
        }
    }

    public function prepListPage()
    {
    }

    /* Other Functions */

    public function isFieldUserSortable($fieldName)
    {
        if (! array_key_exists($fieldName, $this->fieldInfo)) {
            return false;
        }

        return array_key_exists('userSortable', $this->fieldInfo[$fieldName]) ? $this->fieldInfo[$fieldName]['userSortable'] : $this->userSortableDefault;
    }

    public function listRowLink($row, $fieldName)
    {
        if (isset($this->callbacks['listRowLink'])) {
            return call_user_func($this->callbacks['listRowLink'], $this, $row, $fieldName);
        }

        return '/' . Request::path() . '/' . $row->id;
    }

    private function listQuerySelect()
    {
        if (! $this->listSelectFields) {
            // We specifically select just the table's fields so any joins that where added (for searchability) don't overwrite the regular fields.
            // Note that if listSelectFields isn't set, we'll need to fetch any foreign table fields individually for each row later.
            $this->query->select($this->getTableName() . '.*');

            return;
        }

        $selects = $this->listSelectFields;

        if ($this->isHostelgeeksTable()) {
            $this->selectForHostelgeeksTable();

            return;
        }

        // Also automatically add primary key(s) since we'll need those also.
        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if (@$fieldInfo['isPrimaryKey'] && ! in_array($fieldName, $selects)) {
                $selects[] = $fieldName;
            }
        }

        foreach ($selects as $fieldName) {
            $this->callDataTypeFunction($fieldName, 'addSelect', [$this->query, $fieldName]);
        }
    }

    private function listQuerySort()
    {
        // Handle userSortable fields

        if (Request::has($this->listSortVar)) {
            $sortInput = Request::input($this->listSortVar);
            if (is_array($sortInput)) {
                $this->persistentValues[$this->listSortVar] = $sortInput;
                foreach ($sortInput as $fieldName => $sortDirection) {
                    if ($this->isFieldUserSortable($fieldName)) {
                        // We the addition operator so that it precedes any existing listSort values (so it takes high precidence when sorting)
                        $this->listSort = [$fieldName => $sortDirection] + ($this->listSort ? $this->listSort : []);
                    }
                }
            }
        }

        if (is_array($this->listSort)) {
            foreach ($this->listSort as $fieldName => $sortDirection) {
                if ($fieldName === 'verified' && in_array('featured', $this->listSelectFields, true)) {
                    $this->query->orderBy('verifiedOption', $sortDirection);

                    return;
                }

                if (! array_key_exists($fieldName, $this->fieldInfo)) {
                    // If it isn't in our fieldInfo, we allow sorting by it anyway...
                    $this->query->orderBy($fieldName, $sortDirection);
                } else {
                    $this->callDataTypeFunction($fieldName, 'orderBy', [$this->query, $sortDirection]);
                }
            }
        }
    }

    private function selectForHostelgeeksTable(): void
    {
        $this->query
            ->select(
                'listings.*',
                DB::raw("(CASE
                       WHEN listings.continent = '' THEN 'Not Live (unknown country)'
                       WHEN listings.verified in (-40, -30) THEN 'Not Live (removed)'
                       WHEN listings.verified < 0 THEN 'Not Live (not yet approved)'
                       WHEN listings.onlineReservations = 1 THEN 'Live'
                       WHEN listings.propertyType not in ('Hostel', 'Campsite', 'Other') THEN 'Not Live (not a hostel & no booking)'
                       WHEN listings.web = '' or listings.webStatus <= 0 THEN 'Not Live (no booking & no valid website)'
                       ELSE 'Live'
                       END) as verifiedOption")
            );
    }

    private function queryWhereFromSelectedVerifiedOption(): void
    {
        $ids = $this->query
            ->get()
            ->filter(fn ($listing) => strtolower($listing->isLiveOrWhyNot()) === strtolower($this->inputData['verified'])
            )->pluck('id')->toArray();

        $this->query->whereIn('id', $ids);
    }

    private function isHostelgeeksTable(): bool
    {
        return $this->getTableName() === 'listings' && in_array('featured', $this->listSelectFields ?? [], true);
    }

    public function getSortByLink($fieldName)
    {
        if ($this->listSort) {
            // Note: We only consider the status of the *first* sort field if there are multiple.
            reset($this->listSort);
            $direction = (key($this->listSort) == $fieldName && $this->listSort[$fieldName] == 'asc' ? 'desc' : 'asc');
        } else {
            $direction = 'asc';
        }

        return Request::url() . '?' .
            http_build_query(array_merge(Request::except($this->listSortVar),
                [$this->listSortVar . "[$fieldName]" => $direction]));
    }

    public function persistentValuesQueryString($addValues = [])
    {
        $values = $this->persistentValues;
        if ($addValues) {
            $values = array_merge($values, $addValues);
        }

        return $values ? '?' . http_build_query($values) : '';
    }

    // Returns a MessageBag of any validation errors (always returns a MessageBag, even if it's empty).

    public function validate($useInputData = true, $fieldInfoElement = 'validation', $useCallback = true)
    {
        if ($useCallback && ! empty($this->callbacks['validate'])) {
            return call_user_func_array($this->callbacks['validate'], [$this, $useInputData, $fieldInfoElement]);
        }

        $data = [];
        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if ($this->determineInputType($fieldName) == 'ignore') {
                continue;
            }
            $data[$fieldName] = $this->getFieldValue($fieldName, $useInputData);
        }

        $messageBag = null;

        $rules = $sometimes = [];
        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if ($this->determineInputType($fieldName) == 'ignore') {
                continue;
            }
            if (! empty($fieldInfo[$fieldInfoElement])) {
                $rules[$fieldName] = $fieldInfo[$fieldInfoElement];
            }
            if (! empty($fieldInfo[$fieldInfoElement . 'Sometimes'])) {
                $sometimes[$fieldName] = $fieldInfo[$fieldInfoElement . 'Sometimes'];
            }
        }

        $attributeTranslations = null;
        if (Lang::has($this->languageKeyBase . '.forms.fieldLabel')) {
            // Get translations of all field names.  (We don't get option translations because it doesn't translate field values.)
            $attributeTranslations = (langGet($this->languageKeyBase . '.forms.fieldLabel', '') ?: []);
            // Add in any special labels specified in fieldInfo[]
            foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
                if (array_key_exists('fieldLabelLangKey', $fieldInfo)) {
                    $attributeTranslations[$fieldName] = langGet($fieldInfo['fieldLabelLangKey']);
                } elseif (array_key_exists('fieldLabelText', $fieldInfo)) {
                    $attributeTranslations[$fieldName] = $fieldInfo['fieldLabelText'];
                }
            }
        }

        if ($rules) {
            $validator = Validator::make($data, $rules, langGet($this->languageKeyBase . '.forms.validatorMessages', '') ?: []);
            foreach ($sometimes as $fieldName => $s) {
                $validator->sometimes($fieldName, $s[0], $s[1]);
            }
            if ($attributeTranslations) {
                $validator->setAttributeNames($attributeTranslations);
            }
            if ($validator->fails()) {
                $messageBag = $validator->messages();
            }
        }

        // Our own special validations

        $ourMessages = [];

        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            // If options are given, make sure the value is a valid option...
            if (array_key_exists($fieldName, $data) && ! empty($fieldInfo['options']) && is_array($fieldInfo['options']) && ! array_key_exists('lastOptionStringInput', $fieldInfo)) {
                $dataAsArray = (is_array($data[$fieldName]) ? $data[$fieldName] : [$data[$fieldName]]);
                foreach ($dataAsArray as $value) {
                    if ($value != '' && ! in_array(is_a($value, 'BackedEnum') ? $value->value : $value, $fieldInfo['options'])) {
                        if ($fieldInfo['type'] === 'select-key' && array_key_exists($value, $fieldInfo['options'])) {
                            continue;
                        }

                        $ourMessages[$fieldName] = langGet('validation.in', ['attribute' => $attributeTranslations[$fieldName]]);
                        break;
                    }
                }
            }
        }

        if ($messageBag == null) {
            $messageBag = new MessageBag($ourMessages);
        } else {
            $messageBag->merge($ourMessages);
        }

        return $messageBag;
    }

    // Determines the page type ('search', 'list', 'edit', or 'display') based on the mode.
    // (Page type is similar to the mode, but less specific.)

    public function determinePageType()
    {
        $modeToPageTypeMap = [
            'searchForm' => 'search', 'searchAndList' => 'search',
            'list' => 'list', 'editableList' => 'list', 'multiUpdate' => 'list', 'multiDelete' => 'list',
            'insertForm' => 'edit', 'insert' => 'edit', 'updateForm' => 'edit', 'update' => 'edit',
            'display' => 'display',
        ];

        return @$modeToPageTypeMap[$this->mode];
    }

    public function isListMode()
    {
        return in_array($this->mode, ['list', 'searchAndList']);
    }

    /*
        Usually the 'type' of the field is just based on the 'type' element of the fieldInfo.
        But there can also be more specific types for certain types of pages by using 'searchType', 'editType', or 'listType'.
        Or even more specific types with the mode as the prefix ('insertType', etc.);
        Optionally pass $pageType if it's known, otherwise the $pagetype is determined based on the mode.
    */

    public function determineInputType($fieldName, $pageType = '', $ignoreIfInWhereData = false)
    {
        $fieldInfo = $this->fieldInfo[$fieldName];
        if ($pageType === '') {
            $pageType = $this->determinePageType();
        }

        // Do ignoreIfInWhereData...

        // $ignoreIfInWhereData is used to not show fields on the search or list pages if the value of the field is limited by where[] anyway.
        if ($ignoreIfInWhereData && ($pageType === 'search' || $pageType === 'list') && $this->whereData && array_key_exists($fieldName, $this->whereData)) {
            return 'ignore';
        }

        // Try a type based on the mode...

        // Certain modes use the type of another mode (such as 'updateType' is used when in 'update' and 'updateForm' mode).
        $modeMap = ['updateForm' => 'update', 'insertForm' => 'insert'];
        $mode = $this->mode;
        if (array_key_exists($mode, $modeMap)) {
            $mode = $modeMap[$mode];
        }

        $inputType = null;

        if (array_key_exists($mode . 'Type', $fieldInfo)) {
            $inputType = $fieldInfo[$mode . 'Type'];
        } elseif ($pageType !== '' && array_key_exists($pageType . 'Type', $fieldInfo)) { // Try a type based on the pageType...
            $inputType = $fieldInfo[$pageType . 'Type'];
        } else { // Finally, just use 'type'...
            $inputType = @$fieldInfo['type'];
        }

        // Ignore 'display' type fields on search forms.
        if ($pageType === 'search' && $inputType === 'display') {
            return 'ignore';
        }

        // For 'display' pageType, we only use the suggested inputType if it's 'ignore', otherwise it's always 'display'.
        if ($pageType === 'display' && ! in_array($inputType, ['ignore', 'hidden'])) {
            return 'display';
        }

        return $inputType;
    }

    // If $this->logChangesAsCategory is set, this returns a desription of what changed.

    public function setModelData($data, &$dataTypeEventValues, $useCallback = true)
    {
        $dataTypeEventValues = [];

        if ($useCallback && @$this->callbacks['setModelData']) {
            return call_user_func_array($this->callbacks['setModelData'], [$this, $data, &$dataTypeEventValues]);
        }

        $oldValues = $newValues = []; // for logging changes

        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if (array_key_exists($fieldName, $this->forcedData)) {
                $value = $this->forcedData[$fieldName];
            } elseif (array_key_exists($fieldName, $data) && ! in_array($this->determineInputType($fieldName, 'edit'), ['ignore', 'display'])) {
                $value = $data[$fieldName];
            } else {
                continue;
            }

            // For logging changes
            if ($this->logChangesAsCategory !== null) {
                if ($this->determineInputType($fieldName) == 'ignore') {
                    continue;
                }
                $oldValues[$fieldName] = $this->getFieldValue($fieldName, false);
                $newValues[$fieldName] = $value;
            }

            // Set the model field

            if (array_key_exists('subElement', $fieldInfo)) {
                // (We get the whole array, set the element, and save the whole array, because this way it works with Eloquent accessors/mutators.)
                $temp = $this->getFieldValue($fieldName, false, null, false, true);
                $temp[$fieldInfo['subElement']] = $value;
                $value = $temp;
            }

            if (array_key_exists('setValue', $fieldInfo)) {
                $this->callDataTypeFunction($fieldName, 'setValue', [$this->model, $value]);
            } elseif (@$fieldInfo['dataAccessMethod'] == 'dataType') {
                $dataTypeEventValues[$fieldName] = $value;
            } // save the value so it can later be passed to dataType saving()/saved() event handlers
            elseif (@$fieldInfo['dataAccessMethod'] != 'none') {
                $this->model->{$this->getModelPropertyName($fieldName, $fieldInfo)} = $value;
            } // 'model' dataAccessMethod (default)
        }

        return $this->logChangesAsCategory !== null && $newValues ? $this->describeChanges($oldValues, $newValues) : '';
    }

    public function describeChanges($oldValues, $newValues)
    {
        if (@$this->callbacks['describeChanges']) {
            return call_user_func_array($this->callbacks['describeChanges'], [$this, $oldValues, $newValues]);
        } else {
            return EventLog::describeChanges($oldValues, $newValues);
        }
    }

    private function callDataTypeSaveEventHandlers($dataTypeEventValues, $functionName)
    {
        foreach ($dataTypeEventValues as $fieldName => $value) {
            $this->callDataTypeFunction($fieldName, $functionName, [$this->model, $value]);
        }
    }

    private function createNewModel()
    {
        $temp = $this->namespacedModelName();
        $this->model = new $temp;
    }

    public function insert($data)
    {
        $this->createNewModel();

        $changes = $this->setModelData($data, $dataTypeEventValues);

        $this->callDataTypeSaveEventHandlers($dataTypeEventValues, 'saving');

        if (@$this->callbacks['saveModel']) {
            call_user_func_array($this->callbacks['saveModel'], [$this]);
        } else {
            if ($this->parentModel != '') {
                $parentField = $this->parentModelField;
                $this->parentModel->$parentField()->save($this->model);
            } else {
                $this->model->save();
            }
        }

        $this->callDataTypeSaveEventHandlers($dataTypeEventValues, 'saved');

        if ($this->logChangesAsCategory !== null) {
            $this->logEvent('insert', $this->modelName, $this->model->id, '', $changes);
        }
    }

    public function update($data)
    {
        if (! $this->model) {
            throw new Exception("Model wasn't loaded.");
        }
        $changes = $this->setModelData($data, $dataTypeEventValues);

        $this->callDataTypeSaveEventHandlers($dataTypeEventValues, 'saving');

        if (@$this->callbacks['saveModel']) {
            call_user_func_array($this->callbacks['saveModel'], [$this]);
        } else {
            $this->model->save();
        }

        $this->callDataTypeSaveEventHandlers($dataTypeEventValues, 'saved');

        if ($changes != '' && $this->logChangesAsCategory !== null) {
            $this->logEvent('update', $this->modelName, $this->model ? $this->model->id : 0, '', $changes);
        }
    }

    public function delete()
    {
        $this->model->delete();
        if ($this->logChangesAsCategory !== null) {
            $this->logEvent('delete', $this->modelName, $this->model->id);
        }
    }

    public function logEvent($action, $subjectType, $subjectID = 0, $subjectString = '', $data = '')
    {
        if (@$this->callbacks['logEvent']) {
            call_user_func_array($this->callbacks['logEvent'], [$this->logChangesAsCategory, $action, $subjectType, $subjectID, $subjectString, $data]);
        } else {
            EventLog::log($this->logChangesAsCategory, $action, $subjectType, $subjectID, $subjectString, $data);
        }
    }

    // Uses $formTabs[] from the user input and looks for 'formTabGroup' in the fieldInfo to prep $this->formTabGroups[] and remove non-selected fields from $this->fieldInfo[].
    // Should be called after inputData is set, but before any form prep or searching done users fields that we might be removing anyway.

    public function prepServerSideDynamic()
    {
        $dynamicGroupsSelected = [];

        foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
            if (@$fieldInfo['dynamicMethod'] != 'server') {
                continue;
            }

            $dynamicGroup = $fieldInfo['dynamicGroup'];
            $values = explode(',', $fieldInfo['dynamicGroupValues']);
            $inputData = @$this->inputData[$dynamicGroup];

            if ($inputData != '') {
                if (in_array($inputData, $values)) {
                    continue;
                } // this is the selected group, so leave it as is.
            } else {
                $defaultGroup = $this->getFieldDefaultValue($dynamicGroup);
                if ($defaultGroup != '' && in_array($defaultGroup, $values)) {
                    continue;
                } // the select group is the default
            }

            // Not using this field for this group
            unset($this->fieldInfo[$fieldName]);
        }
    }

    public function queryWhereFromPathParameters()
    {
        if ((int) $this->pathParameters) {
            $this->query->where('id', (int) $this->pathParameters);
        }
    }

    public function queryWhereFromArray($where)
    {
        if (! $where || ! is_array($where)) {
            return;
        }

        foreach ($where as $fieldName => $value) {
            if ($fieldName === 'verified' && ! empty($value) && $this->isHostelgeeksTable()) {
                continue;
            }

            $fieldInfo = @$this->fieldInfo[$fieldName];
            if ($fieldInfo === null) {
                continue;
            } // ignore unknown fields (users/hackers could add variables for non-fields)

            if (data_get($fieldInfo, 'skipQuery', false) === true) {
                continue;
            }

            if (array_key_exists('comparisonType', $fieldInfo)) {
                $comparisonType = $fieldInfo['comparisonType'];
            } elseif ($this->comparisonTypes && array_key_exists($fieldName, $this->comparisonTypes)) {
                $comparisonType = $this->comparisonTypes[$fieldName];
            } elseif ($this->defaultComparisonType !== null) {
                $comparisonType = $this->defaultComparisonType;
            } else {
                // Base the default search operation on the type of field
                $inputType = $this->determineInputType($fieldName, 'search');
                $comparisonType = $this->callDataTypeFunction(
                    $fieldName,
                    'getDefaultComparisonType',
                    [$inputType, $value, @$fieldInfo['isPrimaryKey']]
                );
            }

            $this->callDataTypeFunction(
                $fieldName,
                'searchQuery',
                [
                    $this->query,
                    $value,
                    $comparisonType,
                    array_key_exists('specialSearch', $fieldInfo) ? $fieldInfo['specialSearch'] : $this->specialSearch,
                ]
            );
        }
    }

    // * Private Functions *

    public function sanitizeData($values)
    {
        if (is_array($values)) {
            foreach ($this->fieldInfo as $fieldName => $fieldInfo) {
                if (! isset($values[$fieldName])) {
                    continue;
                }
                $value = $values[$fieldName];

                if (is_array($value)) {
                    foreach ($value as $k => $v) {
                        if ($v === '' || $v === null) {
                            unset($values[$fieldName][$k]); // Remove empty elements.
                            continue;
                        }
                        $values[$fieldName][$k] = $this->sanitizeValue($v, isset($fieldInfo['sanitize']) ? $fieldInfo['sanitize'] : $this->defaultSanitize, $fieldName, $fieldInfo);
                    }
                } else {
                    $values[$fieldName] = $this->sanitizeValue($value, isset($fieldInfo['sanitize']) ? $fieldInfo['sanitize'] : $this->defaultSanitize, $fieldName, $fieldInfo);
                }
            }
        }

        return $values;
    }

    public function sanitizeValue($v, $sanitizeTypes, $fieldName, $fieldInfo)
    {
        if ($sanitizeTypes == '') {
            return $v;
        }

        if (is_closure($sanitizeTypes)) {
            return $sanitizeTypes($fieldName, $fieldInfo, $v);
        }

        if (! is_array($sanitizeTypes)) {
            $sanitizeTypes = [$sanitizeTypes];
        }

        foreach ($sanitizeTypes as $sanitizeType) {
            switch ($sanitizeType) {
                case 'trim':
                    $v = mb_trim($v);
                    break;

                case 'email': // (rarely used... usually we'll use validate email instead of sanitize)
                    $v = filter_var($v, FILTER_SANITIZE_EMAIL);
                    break;

                case 'url':
                    if ($v != '' && strpos($v, 'http://') !== 0 && strpos($v, 'https://') !== 0 &&
                        ($this->mode == 'update' || $this->mode == 'insert')) {
                        $v = 'http://' . $v;
                    }
                    $v = filter_var($v, FILTER_SANITIZE_URL);
                    break;

                case 'int':
                    if ($v != '') {
                        // We do a float sanitize and then intval() it because otherwise 1.5 becomes 15 when sanitized, etc.)
                        $v = intval(filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION));
                    }
                    break;

                case 'float':
                    $v = filter_var($v, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                    break;

                case 'tags':
                    $v = strip_tags($v);
                    break;

                case 'WYSIWYG':
                    $v = cleanTextFromWYSIWYG($v);
                    break;

                case 'dataCorrection':
                    $v = DataCorrection::getCorrectedValue($fieldInfo['dataCorrectionTable'], $fieldName, $v,
                        array_key_exists('dataCorrectContextField1', $fieldInfo) ? $this->getFieldValue($fieldInfo['dataCorrectContextField1'], true) : '',
                        array_key_exists('dataCorrectContextField2', $fieldInfo) ? $this->getFieldValue($fieldInfo['dataCorrectContextField2'], true) : '');
                    break;
            }
        }

        return $v;
    }

    /*
    Note: callDataTypeFunction() is used for database functions so that special database operations can be used depending on the dataType/dataTypeObject.
    But we don't use it for get/set from the model.  Instead, the model is expected to use accessors/mutators as needed to do any
    magic related to getting/setting fields.  For special dataTypes, models can use our BaseModel's dataTypes() function to make that automatic.
    */

    private function callDataTypeFunction($fieldName, $function, $parameters)
    {
        $fieldInfo = $this->fieldInfo[$fieldName];

        // Call function defined in the fieldInfo (if any).
        if (array_key_exists($function, $fieldInfo)) {
            array_unshift($parameters, $this); // add $this as the first parameter.

            return call_user_func_array($fieldInfo[$function], $parameters);
        }

        if (! isset($fieldInfo['dataTypeObject'])) { // If there isn't already a dataTypeObject defined...
            // If dataType class is named, create the dataType class object, otherwise defaults to 'StringDataType'.
            $dataTypeClassName = (array_key_exists('dataType', $fieldInfo) ? $fieldInfo['dataType'] : 'Lib\dataTypes\StringDataType');
            // Note that this only works for dataTypes that only require tableName and fieldName.
            // Note that we also save it to the $this->fieldInfo[] array.
            $fieldInfo['dataTypeObject'] = $this->fieldInfo[$fieldName]['dataTypeObject'] = new $dataTypeClassName(['tableName' => $this->getTableName(),
                'fieldName' => $this->getModelPropertyName($fieldName, $fieldInfo), ]);
        }

        return call_user_func_array([$fieldInfo['dataTypeObject'], $function], $parameters);
    }

    /* Display-related Methods */

    public function shouldUseFormTags()
    {
        if ($this->mode == 'display' && $this->isDeleteAllowed()) {
            return true;
        } // special case: display with delete button

        return in_array($this->mode, ['searchForm', 'searchAndList', 'updateForm', 'insertForm', 'editableList']);
    }

    public function isDeleteAllowed()
    {
        return in_array('delete', $this->allowedModes);
    }

    /* Static Methods */

    // Sets row 'start', 'middle', and 'end' on $fieldInfo elements to make it display as multiple columns in each row in forms
    // Note: If the form has different size elements, the browser may add extra white space between items in some columns,
    // displayFieldsInColumns() works better because it doesn't have that issue (but may not work with some browsers).

    public static function combineFieldsIntoOneRow($fieldInfo, $setFormGroupClass = false)
    {
        if (count($fieldInfo) < 2) {
            return $fieldInfo;
        } // nothing to do if it's not at least two elements

        $elementNum = $rowNum = 0;
        foreach ($fieldInfo as $key => $field) {
            $elementNum++;

            if (array_key_exists('row', $field)) {
                throw new Exception("'row' already previously set for $key.");
            }

            $rowNum++;

            if ($rowNum == 1) {
                $fieldInfo[$key]['row'] = 'start';
            } elseif ($elementNum == count($fieldInfo)) {
                $fieldInfo[$key]['row'] = 'end';
            } else {
                $fieldInfo[$key]['row'] = 'middle';
            }

            if ($setFormGroupClass !== false) {
                $fieldInfo[$key]['formGroupClass'] = $setFormGroupClass;
            }
        }

        return $fieldInfo;
    }

    // Note: This uses the CSS property "column-count" which may not yet be supported by some browsers (so can use combineFieldsIntoOneRow() instead if the layout is crucial).

    public static function displayFieldsInColumns($fieldInfo, $columnCount, $setFormGroupClass = false)
    {
        if (count($fieldInfo) < 2) {
            return $fieldInfo;
        } // nothing to do if it's not at least two elements

        $elementNum = $rowNum = 0;
        foreach ($fieldInfo as $key => $field) {
            $elementNum++;

            if (array_key_exists('columns', $field)) {
                throw new Exception("'columns' already previously set for $key.");
            }

            $rowNum++;

            if ($rowNum == 1) {
                $fieldInfo[$key]['columns'] = 'start';
                if ($columnCount !== false) {
                    $fieldInfo[$key]['columnCount'] = $columnCount;
                }
            } elseif ($elementNum == count($fieldInfo)) {
                $fieldInfo[$key]['columns'] = 'end';
            } else {
                // (The only purpose for having a "middle" indicator is so form.blade.php will set "break-inside: avoid-column" on those rows also.
                $fieldInfo[$key]['columns'] = 'middle';
            }

            if ($setFormGroupClass !== false) {
                $fieldInfo[$key]['formGroupClass'] = $setFormGroupClass;
            }
        }

        return $fieldInfo;
    }

    /* URLs */

    // $searchType: 'where' or 'search'

    public static function searchAndListURL($route, $searchValues, $searchType = 'search', $mode = 'searchAndList')
    {
        return routeURL($route) . '?' . http_build_query([$searchType => $searchValues]) . '&mode=' . $mode;
    }

    public function isRequiredField($fieldInfo): bool
    {
        return ! empty($fieldInfo['validation']) &&
            (is_array($fieldInfo['validation']) ? in_array('required', $fieldInfo['validation']) : str_contains($fieldInfo['validation'], 'required'));
    }

    public function isNotSearchForm(): bool
    {
        return ! in_array($this->mode, ['searchAndList', 'searchForm']);
    }
}
