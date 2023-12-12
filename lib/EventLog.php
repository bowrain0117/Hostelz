<?php

namespace Lib;

use Carbon\Carbon;
use Exception;
use Illuminate\Support\Facades\Request;

class EventLog extends BaseModel
{
    protected $table = 'eventLog';

    public static $staticTable = 'eventLog'; // just here so we can get the table name without needing an instance of the object

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    protected $casts = [
        'eventTime' => 'datetime',
    ]; // Laravel Carbon dates / datetimes

    private static $verbose = false;

    private static $disabled = false;

    const MAX_FIELD_STRING_SIZE = 100;

    const MAX_TOTAL_DATA_SIZE = 1000;

    /*

    $subjectType - Normally the model name ("User", "Org", etc.)
    $userID - User ID of the current user.  0 for no user, null to use the current logged in user (if any).

    */

    public static function log($category, $action, $subjectType = '', $subjectID = 0, $subjectString = '', array|string $data = '', $userID = null)
    {
        if (self::$disabled) {
            return;
        }
        if ($action === '') {
            throw new Exception('Missing action.');
        }

        $data = is_array($data)
            ? json_encode($data)
            : $data;

        // Limit data size
        if ($data !== '' && strlen($data) > self::MAX_TOTAL_DATA_SIZE) {
            $data = mb_strimwidth($data, 0, self::MAX_TOTAL_DATA_SIZE, '...');
        }

        // userID
        if ($userID === null) {
            $userID = auth()->check() ? auth()->id() : 0;
        }

        $insert = array_merge(compact('category', 'action', 'subjectType', 'subjectID', 'subjectString', 'data', 'userID'), [
            'ipAddress' => Request::server('REMOTE_ADDR'), 'sessionID' => session()->getId(),
            'eventTime' => Carbon::now(),
        ]);

        if (self::$verbose) {
            debugOutput('EventLog(' . implode(', ', $insert) . ')');
        }

        self::insert($insert);
    }

    public static function describeChanges($old, $new)
    {
        $changes = [];

        foreach ($old as $key => $oldValue) {
            $newValue = $new[$key];
            if ((is_array($newValue) && is_array($oldValue) && ! arraysHaveEquivalentValues($oldValue, $newValue)) || (! is_array($newValue) && $oldValue != $newValue)) {
                $change = "$key: ";
                $oldValueString = (is_array($oldValue) ? implode(', ', $oldValue) : $oldValue);
                if ($oldValueString != '') {
                    $change .= '"' . mb_strimwidth($oldValueString, 0, self::MAX_FIELD_STRING_SIZE, '...') . '" -> ';
                }
                $newValueString = (is_array($newValue) ? implode(', ', $newValue) : $newValue);
                $change .= '"' . mb_strimwidth($newValueString, 0, self::MAX_FIELD_STRING_SIZE, '...') . '"';
                $changes[] = $change;
            }
        }

        return $changes ? implode(', ', $changes) : '';
    }

    public static function setVerbose($verbose)
    {
        self::$verbose = $verbose;
    }

    public static function disable()
    {
        self::$disabled = true;
    }

    public static function fieldInfo()
    {
        $return = [
            'id' => ['isPrimaryKey' => true, 'type' => 'ignore'],
            'eventTime' => ['searchType' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateTimeDataType'],
            'category' => ['type' => 'select', 'options' => ['system', 'user', 'admin', 'staff', 'management']],
            'action' => [],
            'subjectType' => ['comparisonType' => 'equals'],
            'subjectID' => ['dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
            'subjectString' => [],
            'ipAddress' => ['type' => 'display'],
            'data' => ['type' => 'textarea'],
            'userID' => [], // how this is handled depends on the app's User model, so this fieldInfo is set by the controller
        ];

        return $return;
    }

    /* Relationships */

    public function user()
    {
        // This works only if the app uses App\Models\User with an id field.
        return $this->belongsTo(\App\Models\User::class, 'userID');
    }
}
