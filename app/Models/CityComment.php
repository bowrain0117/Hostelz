<?php

namespace App\Models;

use Exception;
use Lib\BaseModel;

class CityComment extends BaseModel
{
    protected $table = 'cityComments';

    public static $staticTable = 'cityComments'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    public static $statusOptions = ['removed', 'flagged', 'new', 'approved'];

    public function save(array $options = []): void
    {
        parent::save($options);
        $this->clearRelatedPageCaches();
    }

    public function delete(): void
    {
        parent::delete();
        $this->clearRelatedPageCaches();
    }

    /* Static */

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    'cityID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return ($formHandler->isListMode() || $formHandler->determineInputType('cityID') == 'display')
                                && $model->cityInfo ? $model->cityInfo->fullDisplayName() : $model->cityID;
                        }, ],
                    'language' => ['type' => 'select', 'options' => Languages::allCodesKeyedByName(), 'optionsDisplay' => 'keys'],
                    'name' => ['maxLength' => 80, 'validation' => 'required'],
                    'comment' => ['type' => 'textarea', 'rows' => 15],
                    'ipAddress' => ['maxLength' => 50, 'validation' => 'required'],
                    'sessionID' => [],
                    'userID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'commentDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateDataType', 'maxLength' => 80, 'validation' => 'required'],
                    'bayesianBucket' => ['searchType' => 'select', 'type' => 'display', 'options' => self::$statusOptions],
                    'bayesianScore' => ['searchType' => 'minMax', 'type' => 'display'],
                    'notes' => ['type' => 'textarea', 'rows' => 3],
                ];

                if ($purpose == 'staffEdit') {
                    $useFields = ['id', 'status', 'language', 'name', 'comment'];
                    $displayOnly = ['cityID', 'commentDate'];
                    $fieldInfos['id']['type'] = 'ignore';
                    array_walk($fieldInfos, function (&$fieldInfo, $fieldName, $displayOnly): void {
                        if (in_array($fieldName, $displayOnly)) {
                            $fieldInfo['editType'] = 'display';
                        }
                    }, $displayOnly);

                    return array_intersect_key($fieldInfos, array_flip(array_merge($useFields, $displayOnly)));
                }

                break;

            case 'submitComment':
                $fieldInfos = [
                    'name' => ['maxLength' => 80, 'validation' => 'required', 'fieldLabelLangKey' => 'city.YourName'],
                    'comment' => ['type' => 'textarea', 'rows' => 7, 'validation' => 'unique:' . self::$staticTable . '|required|min:30|doesnt_contain_urls', 'fieldLabelLangKey' => 'city.YourCityComment'],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    /* Accessors & Mutators */

    /* Static */

    /* Misc */

    // weight is relative importance to other sourceTypes
    public static $spamSourceTypeWeights = [
        // split by any non-word initial characters, whitespace (and any surrounding non-word characters), any trailing non-word characters.
        // see http://www.php.net/manual/en/regexp.reference.unicode.php
        'name' => ['weight' => 1, 'reasonableNumOfHits' => 2, 'tokenRegex' => ''],
        'ipAddress' => ['weight' => 3, 'reasonableNumOfHits' => 0.5, 'tokenRegex' => ''],
        'originalComment' => ['weight' => 5, 'reasonableNumOfHits' => 8, 'tokenRegex' => '/(^\W+|\W*\s\W*|\p{Cc}|\p{Cf}|\p{Cn}|\p{Co}|\p{Z}|;|!|\W+$|<|>)/u'],
    ];

    /*
    function bayesianTrainCityComment($id, $bucket, $verbose = false)
    {
        require_once 'bayesianFilter.inc.php';

    	$row = dbGetRow("SELECT * FROM cityComments WHERE id=$id");
    	if (!$row) {
            triggerError("Couldn't get cityComment id $id.");
            return false;
    	}

        if ($row['originalComment'] == '') $row['originalComment'] = $row['comment']; // mostly only needed for old comments

        $bayFilter = new BayesianFilter($verbose);

    	foreach ($GLOBALS['CITY_COMMENT_SPAM_WEIGHTS'] as $sourceType => $sourceParams) {
    		$bayFilter->train($row[$sourceType], 'cityComments-'.$sourceType, $sourceParams['tokenRegex'], $bucket);
    	}
    }

    function bayesianEvaluateCityComment($id, $updateCommentDB = true, $verbose = false)
    {
        require_once 'bayesianFilter.inc.php';

    	$row = dbGetRow("SELECT * FROM cityComments WHERE id=$id");
        if (!$row) {
            triggerError("Couldn't get cityComment id $id.");
            return false;
    	}

        if ($row['originalComment'] == '') $row['originalComment'] = $row['comment']; // mostly only needed for old comments

        $bayFilter = new BayesianFilter($verbose);
        $results = $bayFilter->evaluateMultipleFields($row, $GLOBALS['CITY_COMMENT_SPAM_WEIGHTS'], 'cityComments-');

        if (!$results) return false;

        $topBucketScore = round(reset($results) * 100);
        $topBucket = key($results);
        if ($verbose) echo "<br><b>topBucket for entire cityComments: $topBucket $topBucketScore%</b><br><br>";
        if ($updateCommentDB) dbQuery("UPDATE cityComments SET bayesianBucket=".dbQuote($topBucket).", bayesianScore=$topBucketScore WHERE id=$id");
    }
    */

    public function clearRelatedPageCaches(): void
    {
        if ($this->cityInfo) {
            $this->cityInfo->clearRelatedPageCaches();
        }
    }

    /* Scopes */

    public function scopeAreLive($query)
    {
        return $query->where('status', 'approved');
    }

    /* Relationships */

    public function cityInfo()
    {
        return $this->belongsTo(\App\Models\CityInfo::class, 'cityID');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'userID');
    }
}
