<?php

namespace App\Services;

use App\Helpers\EventLog;
use App\Models\Booking;
use App\Models\CityInfo;
use App\Models\CountryInfo;
use App\Models\Review;
use App\Models\User;
use DB;
use Exception;
use Lib\PayPal;

class Payments
{
    public const MIN_AMOUNT_FOR_AUTOMATIC_PAYMENT = 75;

    public static function calculateUserPay($user, &$output, $eventLogIDs = null)
    {
        $output = '';

        // TODO: These subjectTypes have to be updated to the new website's systemTypes (Comment instead of comment, etc.).

        $payTypes = [
            // Writing
            ['name' => 'Hostel Reviews', 'ifHasPermission' => 'reviewer', 'payAmount' => Review::PAY_AMOUNT, 'logWhere' => ['category' => 'staff', 'action' => 'accepted', 'subjectType' => 'Review']],
            ['name' => 'City Descriptions', 'ifHasPermission' => 'placeDescriptionWriter', 'payAmount' => CityInfo::CITY_DESCRIPTION_PAY,
                'logWhere' => ['category' => 'staff', 'action' => 'accepted', 'subjectType' => 'AttachedText', 'subjectString' => 'cityInfo description'], ],
            ['name' => 'Region Descriptions', 'ifHasPermission' => 'placeDescriptionWriter', 'payAmount' => CountryInfo::REGION_DESCRIPTION_PAY,
                'logWhere' => ['category' => 'staff', 'action' => 'accepted', 'subjectType' => 'AttachedText', 'subjectString' => 'region description'], ],
            ['name' => 'Country Descriptions', 'ifHasPermission' => 'placeDescriptionWriter', 'payAmount' => CountryInfo::COUNTRY_DESCRIPTION_PAY,
                'logWhere' => ['category' => 'staff', 'action' => 'accepted', 'subjectType' => 'AttachedText', 'subjectString' => 'country description'], ],
            // [ 'name' => 'City Pics', 'ifHasPermission' => 'placeDescriptionWriter', 'payAmount' => CityInfo::CITY_PIC_PAY ],  (not yet ready to make this work. need to know the dates pics were approved.  maybe log when city pics are approved (if the current user hasn't previously had approved pics for a city)
            // Editing
            ['name' => 'Review Editing', 'payField' => 'reviewEdit', 'logWhere' => ['category' => 'staff', 'action' => 'update', 'subjectType' => 'Review']],
            ['name' => 'Listing Rating Editing', 'payField' => 'commentEdit', 'logWhere' => ['category' => 'staff', 'action' => 'update', 'subjectType' => 'Rating']],
            ['name' => 'City Comments', 'payField' => 'cityComment', 'logWhere' => ['category' => 'staff', 'action' => 'update', 'subjectType' => 'CityComment']],
            ['name' => 'Place Description Editing', 'payField' => 'placeDescriptionApprove', 'logWhere' => ['category' => 'staff',
                'action' => 'update', 'subjectType' => 'AttachedText', ], 'logLike' => ['data' => '%status: "submitted" -> "ok"%']],
            // DB Work
            ['name' => 'Listing Edits', 'payField' => 'hostel', 'logWhere' => ['category' => 'staff', 'action' => 'update', 'subjectType' => 'Listing']],
            ['name' => 'Nonduplicates', 'payField' => 'nonduplicate', 'logWhere' => ['category' => 'staff', 'action' => 'nonduplicates', 'subjectType' => 'Listing']],
            ['name' => 'Listing Merges', 'payField' => 'merge', 'logWhere' => ['category' => 'staff', 'action' => 'merge', 'subjectType' => 'Listing']],
            // Marketing
            ['name' => 'Link Update', 'payField' => 'linkUpdate', 'logWhere' => ['category' => 'staff', 'action' => 'update', 'subjectType' => 'IncomingLink']],
            ['name' => 'Social Message Sent', 'payField' => 'socialMsg', 'logWhere' => ['category' => 'staff', 'action' => 'sent', 'subjectType' => 'socialMsg']],
            ['name' => 'Social Message Favorite', 'payField' => 'socialMsgOther', 'logWhere' => ['category' => 'staff', 'action' => 'liked', 'subjectType' => 'socialMsg']],
            ['name' => 'Social Message Retweet', 'payField' => 'socialMsgOther', 'logWhere' => ['category' => 'staff', 'action' => 'retweet', 'subjectType' => 'socialMsg']],
            // Other
            ['name' => 'Pic Edits', 'payField' => 'picEdit', 'logWhere' => ['category' => 'staff', 'action' => 'edit', 'subjectType' => 'Pic']],
            ['name' => 'Emails', 'payField' => 'email', 'logWhere' => ['category' => 'staff', 'action' => 'sent', 'subjectType' => 'MailMessage']],
            ['name' => 'Translations', 'payField' => 'translation', 'logWhere' => ['category' => 'staff', 'action' => 'translation']],
            ['name' => 'Article Writing (paid per word)', 'payField' => 'articleWriting', 'logWhere' => ['category' => 'staff', 'action' => 'accepted', 'subjectType' => 'Article']],
            ['name' => 'Create Ad', 'payField' => 'adCreate', 'logWhere' => ['category' => 'staff', 'action' => 'insert', 'subjectType' => 'Ad']],

            // Special (special queries that are handled by a switch statement below)
            ['name' => 'Affiliate Bookings', 'ifHasPermission' => ['affiliate', 'staffMarketing']],
            ['name' => 'Balance Adjustment'],

            /*
    		[ 'name' => 'Listing Contact Info', 'payField' => 'hostelEmail', 'logWhere' => "category='staff' AND action='update' AND subjectType='Listing' AND (data like '%email: \"\" ->%' OR data LIKE '%contactStatus: \"0\" -> \"$CONTACT_STATUSES[webForm]\"%')" ],
        	[ 'name' => 'CityInfo Regions Added', 'payField' => 'cityInfo', 'logWhere' => "category='staff' AND action='update' AND subjectType='cityInfo' AND data like '%region: \"\" -> \"%'" ],
            [ 'name' => 'Marketing', 'payField' => '', 'amountQuery' => "SELECT SUM(subjectString) FROM eventLog WHERE action='buy' AND subjectType='incomingLinks' AND ($dbWhere)" ],

            */
        ];

        $output = '';

        // Output pay amounts

        foreach ($payTypes as $payType) {
            if (isset($payType['ifHasPermission']) && ! $user->hasAnyPermissionOf((array) $payType['ifHasPermission'])) {
                continue;
            }

            // payAmount
            if (isset($payType['payAmount'])) {
                $output .= "$payType[name]: \$" . $payType['payAmount'] . "\n";
            }
            // payField
            if (isset($payType['payField'])) {
                if (! in_array($payType['payField'], User::$payAmountTypes, true)) {
                    throw new Exception("Unknown pay type '$payType[payField]'.");
                }
                if (isset($payType['payField']) && isset($user->payAmounts[$payType['payField']])) {
                    $output .= "$payType[name]: \$" . $user->payAmounts[$payType['payField']] . "\n";
                }
            }
        }
        if ($output != '') {
            $output = "Rates:\n\n" . $output . "\n";
        }

        // Output past payment info

        if (! $eventLogIDs) {
            if (! $user->lastPaid) {
                $output .= "Total earnings so far:\n\n";
            } else {
                $output .= "Totals earnings since last payment on $user->lastPaid:\n\n";
            }
        }

        // Calculate actual pay

        $total = 0;

        foreach ($payTypes as $payType) {
            if (isset($payType['ifHasPermission']) && ! $user->hasAnyPermissionOf((array) $payType['ifHasPermission'])) {
                continue;
            }

            if (isset($payType['payField']) && empty($user->payAmounts[$payType['payField']])) {
                continue;
            }

            $logQuery = EventLog::where('userID', $user->id);
            if ($eventLogIDs) {
                $logQuery->whereIn('id', $eventLogIDs);
            } elseif ($user->lastPaid) {
                $logQuery->where('eventTime', '>', $user->lastPaid);
            }

            if (isset($payType['logLike'])) {
                foreach ($payType['logLike'] as $field => $value) {
                    $logQuery->where($field, 'LIKE', $value);
                }
            }

            // Special pay types that don't use the EventLog or $user->payAmounts

            switch ($payType['name']) {
                /*
                case 'City Pics':
                    if ($eventLogIDs) continue 2;
                    $pics = Pic::where('source', $user->id)->where('status', 'ok')->where('subjectType', 'cityInfo')->get();
                    // We only pay once for photos per user per city
                    if ($user->lastPaid) {
                        // $picsAlreadyPaid = $pics->filter(function ($pic) use ($user) { return $pic-> }); (need to know the date a pic was approved)
                    }
                    $amount = $query->sum('affiliateCommission');
        		    $output .= "$payType[name]: \$".number_format($amount, 2)."\n";
    	            $total += $amount;
    	            continue 2;
    	        */
                case 'Affiliate Bookings':
                    if ($eventLogIDs) {
                        continue 2;
                    }
                    $query = Booking::where('affiliateID', $user->id);
                    if ($user->lastPaid) {
                        $query->where('bookingTime', '>', $user->lastPaid);
                    }
                    $amount = $query->sum('affiliateCommission');
                    $output .= "$payType[name]: \$" . number_format($amount, 2) . "\n";
                    $total += $amount;

                    continue 2;

                case 'Balance Adjustment':
                    $amount = $logQuery->where('action', 'balance adjustment')->sum('subjectString');
                    if ($amount) {
                        $output .= "$payType[name]: \$" . number_format($amount, 2) . "\n";
                        $total += $amount;
                    }

                    continue 2;
            }

            // For 'logWhere' pay types

            if ($payType['logWhere']) {
                foreach ($payType['logWhere'] as $field => $value) {
                    $logQuery->where($field, '=', $value);
                }

                switch ($payType['name']) {
                    // Items that are counted in a special way
                    case 'Translations':
                        if (! $user->lastPaid || $eventLogIDs) {
                            $subjectStringsPreviouslyPaidFor = [];
                        } else {
                            // doesn't pay again for any text that was previously translated
                            $subjectStringsPreviouslyPaidFor = EventLog::select('subjectString')->where('userID', $user->id)
                                ->where('category', 'staff')->where('action', 'translation')->where('eventTime', '<=', $user->lastPaid)
                                ->groupBy('subjectString')->pluck('subjectString')->all();
                        }

                        $count = $logQuery->whereNotIn('subjectString', $subjectStringsPreviouslyPaidFor)->groupBy('subjectString')->pluck('data')
                            ->reduce(function ($carry, $translationString) {
                                return $carry + wordcount($translationString);
                            });

                        break;

                    case 'Article Writing (paid per word)':
                        $count = $logQuery->groupBy('subjectID')->pluck('subjectString')
                            ->reduce(function ($carry, $wordCount) {
                                return $carry + intval($wordCount);
                            });

                        break;

                        // All other items
                    default:
                        $count = $logQuery->select(DB::raw('count(DISTINCT action, subjectType, subjectID, subjectString) as count'))->value('count');
                }

                if (isset($payType['payField'])) {
                    $amount = $user->payAmounts[$payType['payField']] * $count;
                } elseif (isset($payType['payAmount'])) {
                    $amount = $payType['payAmount'] * $count;
                } else {
                    throw new Exception('Unknown amount.');
                }
                $output .= "$payType[name]: $count (\$" . number_format($amount, 2) . ")\n";
                $total += $amount;

                continue;
            }

            throw new Exception("Don't know how to handle '$payType[name]'.");
        }

        $total = number_format($total, 2, '.', '');
        $output .= "\nTotal: \$" . number_format($total, 2) . "\n";

        return $total;
    }

    public static function pay($email, $amount, $description, $paymentID, $paymentSystemPassword)
    {
        PayPal::$password = $paymentSystemPassword;
        //PayPal::$sandboxTestMode = true;

        return PayPal::massPay($description, [[
            'email' => $email,
            'amount' => $amount,
            'id' => $paymentID,
            // 'note' => '',
        ]]);
    }

    public static function paymentSystemBalance($paypalPassword)
    {
        PayPal::$password = $paypalPassword;

        return PayPal::balance();
    }
}
