<?php

namespace App\Http\Controllers;

use App;
/*
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
*/

use Event;
use Illuminate\Routing\Controller as BaseController;

abstract class Controller extends BaseController
{
    /* use DispatchesJobs, ValidatesRequests; (probably don't need these except on controllers that actually use them) */
}
