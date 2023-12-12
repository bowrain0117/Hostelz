<?php

namespace App\Models;

use Lib\BaseModel;

class Transaction extends BaseModel
{
    protected $table = 'transactions';

    /* Static */

    public static function fieldInfo($purpose = null): void
    {
    }

    public static function maintenanceTasks($timePeriod): void
    {
        /*
        $output = '';

        switch($timePeriod) {
            case 'daily':
                $output .= "\nReset viewsToday.\n";
            	self::where('viewsToday', '>', 0)->update([ 'viewsToday' => 0 ]);
            	break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

    	return $output;
    	*/
    }

    /* Accessors & Mutators */

    /* Static */

    /* Scopes */

    /* Relationships */

    /*
    public function pics()
    {
        return $this->hasMany('App\Pic', 'subjectID')->where('subjectType', 'ads');
    }

    public function incomingLink()
    {
        return $this->belongsTo('App\IncomingLink', 'incomingLinkID');
    }
    */
}
