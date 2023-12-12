<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\EventLog;
use App\Helpers\ListingEditHandler;
use App\Models\Listing\Listing;
use App\Models\PaymentMethod;
use App\Models\User;
use Illuminate\Http\Request;

class MgmtController extends Controller
{
    public function index()
    {
        if (! auth()->user()->mgmtListings) {
            return accessDenied();
        }

        $subjectString = 'featured listing opt-in';
        $listings = [];
        foreach (auth()->user()->getMgmtListings() as $listing) {
            if (! auth()->user()->userCanEditListing($listing->id)) {
                continue;
            }
            $handler = new ListingEditHandler($listing->id);
            $listings[] = [
                'listing' => $listing,
                'isLiveOrWhyNot' => $listing->isLiveOrWhyNot(),
                'validations' => $handler->getValidationStatusForAll(),
                'otherMmgmtUsers' => User::areMgmtForListing($listing->id)->
                    where('id', '!=', auth()->id())->areAllowedToLogin()->get(),
                'hasNotified' => EventLog::where([
                    ['SubjectString', $subjectString],
                    ['SubjectID', $listing->id],
                ])->exists(),
            ];
        }

        return view('mgmt-index', ['listings' => $listings]);
    }

    public function chooseStickerType($listingID, $verificationCode)
    {
        //$listing->listingEmailVerificationToken()

        return view('mgmt-chooseStickerType');
    }

    /* Used for editing listing features, pics, etc. */

    public function listingManage($listingID, $action, $extraParameter = null)
    {
        if (! in_array($listingID, auth()->user()->mgmtListings) || ! auth()->user()->userCanEditListing($listingID)) {
            App::abort(404);
        }
        $handler = new ListingEditHandler($listingID, $action, $extraParameter, 'mgmt-listing-manage', routeURL('mgmt:menu'));

        return $handler->go();
    }

    public function paymentMethod(Request $request)
    {
        $paymentMethod = auth()->user()->activeAndDeactivatedPaymentMethods()->first();

        $submitStatus = null;

        // (This is hard-coded now, but if we have multiple options later, the user could select
        // the payment processor type.)
        $paymentProcessorType = 'Stripe';

        $errorMessage = '';

        if ($paymentMethod && $request->input('delete')) {
            $success = $paymentMethod->delete();
            if ($success) {
                $paymentMethod = null;
                $submitStatus = 'deleted';
            } else {
                $errorMessage = "Couldn't remove the card."; // shouldn't happen
            }
        } elseif ($paymentMethod && $request->input('reactivate') && $paymentMethod->status == 'deactivated') {
            $paymentMethod->reactivate();
            // $this->selectedOrg->autoRechargeIfNeeded();
            $submitStatus = 'reactivated';
        } elseif ($request->isMethod('post')) {
            $response = PaymentMethod::createNewFromInput($paymentProcessorType, auth()->user(), $request->all());
            if (is_string($response)) {
                // An error occurred.
                $errorMessage = $response;
            } else {
                $paymentMethod = $response;
                // $this->selectedOrg->autoRechargeIfNeeded(); // If they owe us money, charge them now.
                $submitStatus = 'success';
            }
        }

        return view('mgmt-payment-method', compact('paymentMethod', 'submitStatus', 'errorMessage'));
    }

    public function featureListing(Request $request, Listing $listing)
    {
        $subjectString = 'featured listing opt-in';

        $hasNotified = false;

        if ($request->input('optIn')) {
            EventLog::log('management', 'update', 'Listing', $listing->id, $subjectString);
            $optedIn = true;
        } else {
            //  checking is log added
            $hasNotified = EventLog::where([
                ['SubjectString', $subjectString],
                ['SubjectID', $listing->id],
            ])->exists();

            $optedIn = false;
        }

        return view('mgmt-feature-listing', compact('optedIn', 'hasNotified'));
    }
}
