<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;

class ImportsPageStaffController extends Controller
{
    public function __invoke()
    {
        return view('staff/imports/index');
    }
}
