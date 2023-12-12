<?php

namespace App\Http\Controllers;

use App\Services\ImportSystems\ImportSystems;
use Illuminate\Http\Request;

class ImportSystemWebhooksController extends Controller
{
    // This is used by some booking systems as a webhook for their system to call to notify us when a booking is made (currently using with BookHostels)
    public function bookingNoticeWebhook(Request $request, $systemName)
    {
        $systemClassName = ImportSystems::findByName($systemName)->getSystemService();

        return $systemClassName::bookingNoticeWebhook($request);
    }
}
