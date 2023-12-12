<?php

namespace App\facades;

use Illuminate\Support\Facades\Facade;

class Emailer extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'emailer'; /* the IoC name the was binded */
    }
}
