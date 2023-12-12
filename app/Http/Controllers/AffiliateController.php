<?php

namespace App\Http\Controllers;

use App\Helpers\EventLog;
use App\Models\Booking;
use Illuminate\Support\MessageBag;
use Lib\FormHandler;

class AffiliateController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $query = Booking::where('affiliateID', $user->id);
        if ($user->lastPaid) {
            $query->where('bookingTime', '>', $user->lastPaid);
        }
        $currentEarnings = $query->sum('affiliateCommission');
        $pastPayments = EventLog::where('action', 'payment')->where('userID', $user->id)->orderBy('eventTime')->get();

        /*
        // Charts

        $charts = new OurCharts;

        $rows = dbGetAll("SELECT DATE_FORMAT(bookingTime, '%d %b %Y') as date, ROUND(SUM(affiliateCommission)) as earnings, DATE_FORMAT(bookingTime, '%Y-%m-%U') as week FROM bookings WHERE affiliateID=".$login->userid." GROUP BY week");
        //end($rows); unset($rows[key($rows)]); // ignore latest incomplete time period

        $charts->addChart('earningsChart', 'LineChart',
            array(
        		array('title'=>'Date', 'type'=>'string'),
        		array('title'=>'Earnings $', 'type'=>'number'),
        		array('title'=>'', 'type'=>'ignore'),
        	),
        	$rows,
        	0, 0, "smoothLine: true, width: 400, height: 200, legend: 'none', title: 'Earnings (US $ per week)'", '');

        $smarty->assign('useOldChartsAPI', true); // the new API is ok, but old still has more features and less quirks.
        $charts->prepareDisplay();
        */

        return view('user/affiliate', compact('user', 'currentEarnings', 'pastPayments'));
    }

    public function editURLs()
    {
        $fieldInfo = [
            'affiliateURLs' => ['editType' => 'multi', 'validation' => 'urlList'],
        ];

        $formHandler = new FormHandler('User', $fieldInfo, auth()->id(), 'App\Models');
        $formHandler->allowedModes = ['updateForm', 'update'];
        $formHandler->logChangesAsCategory = 'user';
        $formHandler->callbacks = [
            'validate' => function ($formHandler, $useInputData, $fieldInfoElement) {
                if ($formHandler->model) {
                    $affiliateURLs = $formHandler->getFieldValue('affiliateURLs', $useInputData);
                    $bannedURLs = ['http://www.google.com', 'https://www.google.com', 'http://www.hostelz.com', 'https://www.hostelz.com'];
                    foreach ($affiliateURLs as $url) {
                        foreach ($bannedURLs as $bannedURL) {
                            if (stripos($url, $bannedURL) === 0) {
                                return new MessageBag(['affiliateURLs' => "'$bannedURL' is not allowed."]);
                            }
                        }
                    }
                }

                return $formHandler->validate($useInputData, $fieldInfoElement, false);
            },
        ];

        return $formHandler->go('user/affiliate-editURLs');
    }
}
