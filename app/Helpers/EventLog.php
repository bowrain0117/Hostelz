<?php

namespace App\Helpers;

use Illuminate\Support\Str;
use Route;

class EventLog extends \Lib\EventLog
{
    public function subjectEditFormURL()
    {
        if ($this->subjectType == '') {
            return '';
        }
        $routeName = 'staff-' . Str::plural(lcfirst($this->subjectType));
        if (! Route::has($routeName)) {
            $routeName = 'staff-' . lcfirst($this->subjectType);
        }
        if (! Route::has($routeName)) {
            return '';
        }

        return routeURL($routeName, [$this->subjectID]);
    }
}
