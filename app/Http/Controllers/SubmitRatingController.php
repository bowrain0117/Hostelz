<?php

namespace App\Http\Controllers;

use App;
use App\Models\Booking;
use App\Models\Languages;
use App\Models\Listing\Listing;
use App\Models\Rating;
use Emailer;
use Lib\Captcha;
use Lib\FormHandler;
use Request;
use Session;
use View;

class SubmitRatingController extends Controller
{
    public function submitRating($listingID)
    {
        if (! auth()->check()) {
            $captcha = new Captcha();
            if (Request::isMethod('post') && Request::has('data.name') && ! $captcha->verify()) {
                return view('captchaError');
            }
            View::share('captcha', $captcha);
        }

        $listing = Listing::areLive()->where('id', $listingID)->first();
        if (! $listing) {
            App::abort(404);
        }

        $fieldInfo = Rating::fieldInfo('submitRating');
        if (auth()->check()) {
            unset($fieldInfo['email'], $fieldInfo['name'], $fieldInfo['homeCountry'], $fieldInfo['age']);
        }

        $formHandler = new FormHandler('Rating', $fieldInfo, null, 'App\Models');
        $formHandler->allowedModes = ['insertForm', 'insert'];

        $defaultName = (auth()->check() ? auth()->user()->getNicknameOrName() : 'Add your name');
        $userAvatar = '';

        if (auth()->check()) {
            $userAvatar = isset(auth()->user()->profilePhoto) ? auth()->user()->profilePhoto->url(['thumbnails']) : '';
        }

        $formHandler->defaultInputData = [
            'name' => $defaultName,
        ];

        $formHandler->callbacks = [
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use ($listing) {
                $this->setMiscRatingAttributes($formHandler->model, $listing, $data);
                if (auth()->check()) {
                    $formHandler->model->emailVerified = true;
                    $formHandler->model->email = auth()->user()->getEmailAddress();
                    $formHandler->model->name = auth()->user()->getNicknameOrName();
                    $formHandler->model->homeCountry = auth()->user()->homeCountry;
                    $formHandler->model->age = auth()->user()->getUserAge() ?: 0;
                }

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
            'saveModel' => function ($formHandler) use ($listing): void {
                $formHandler->model->save();
                if (! auth()->check()) {
                    $this->sendRatingVerifyEmail($listing, $formHandler->model->email, $formHandler->model);
                }
            },
        ];

        $formHandler->go();

        return $formHandler->display('submitRating', compact('listing', 'userAvatar'));
    }

    private function setMiscRatingAttributes($rating, $listing, $data): void
    {
        $rating->hostelID = $listing->id;
        $rating->status = 'new';
        $rating->userID = (auth()->check() ? auth()->id() : 0);
        $rating->originalComment = $data['comment'];
        $rating->ipAddress = Request::ip();
        $rating->sessionID = Session::getId();
        $rating->language = Languages::currentCode();
        $rating->commentDate = date('Y-m-d');
    }

    private function sendRatingVerifyEmail($listing, $emailAddress, $rating): void
    {
        $verificationURL = routeURL('verifyRating', [$rating->id, $rating->emailVerificationCode()], 'publicSite');
        $emailText = str_replace('<br>', "<br>\n", langGet('submitRating.emailText', ['hostelName' => $listing->name, 'verifyURL' => "<a href=\"$verificationURL\">$verificationURL</a>"]));
        Emailer::send($emailAddress, langGet('submitRating.emailSubject'), 'generic-email', ['text' => $emailText]);
    }

    public function verifyRating($ratingID, $verificationCode)
    {
        $message = '';

        $rating = Rating::where('id', $ratingID)->first();

        if (! $rating || $rating->emailVerificationCode() != $verificationCode) {
            return view('error', ['errorMessage' => langGet('global.invalidVerificationEmailURL')]);
        }

        if ($rating->status != 'new') {
            return view('error', ['errorMessage' => langGet('submitRating.AlreadyEdited')]);
        }

        if ($rating->emailVerified) {
            $message = langGet('submitRating.AlreadyVerified');
        }

        $listing = $rating->listing;
        if (! $listing) {
            App::abort(404);
        }

        $fieldInfo = Rating::fieldInfo('submitRating');
        unset($fieldInfo['email']);

        $formHandler = new FormHandler('Rating', $fieldInfo, null, 'App\Models');
        $formHandler->model = $rating;
        $formHandler->allowedModes = ['updateForm', 'update'];

        $formHandler->callbacks = [
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use ($listing) {
                $this->setMiscRatingAttributes($formHandler->model, $listing, $data);
                if (! $formHandler->model->emailVerified) { // if it wasn't already verified...
                    $formHandler->model->emailVerified = true;
                    $formHandler->model->automaticallyAssignToUserWithMatchingEmail();
                    // Note:  We don't call awardPoints() here because we don't award points for non-booking Ratings until the Rating is approved.
                }

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
        ];

        $formHandler->go();

        return $formHandler->display('submitRating', compact('listing', 'message'));
    }

    public function afterBookingRating($bookingID, $verificationCode)
    {
        $booking = Booking::where('id', $bookingID)->first();
        $alreadySubmittedRating = Rating::where('ourBookingID', $bookingID)->exists();

        if (! $booking || $alreadySubmittedRating || $booking->afterBookingCommentVerificationCode() != $verificationCode) {
            return view('error', ['errorMessage' => langGet('global.invalidVerificationEmailURL')]);
        }

        $listing = $booking->listing;
        if (! $listing) {
            App::abort(404);
        }

        $fieldInfo = Rating::fieldInfo('submitRating');
        unset($fieldInfo['email']);
        unset($fieldInfo['bookingID']);

        $formHandler = new FormHandler('Rating', $fieldInfo, null, 'App\Models');
        $formHandler->allowedModes = ['insertForm', 'insert'];

        $defaultName = (auth()->check() ? auth()->user()->getNicknameOrName() : 'Anonymous');
        $formHandler->defaultInputData = [
            'name' => $defaultName,
        ];

        $formHandler->callbacks = [
            'setModelData' => function ($formHandler, $data, &$dataTypeEventValues) use ($listing, $booking) {
                $rating = $formHandler->model;
                $this->setMiscRatingAttributes($rating, $listing, $data);
                $rating->ourBookingID = $booking->id;
                $rating->bookingID = $booking->bookingID;
                $rating->email = $booking->email;
                $rating->emailVerified = true;
                if (! $rating->userID) {
                    $rating->automaticallyAssignToUserWithMatchingEmail();
                }
                $rating->awardPoints(); // this is sort of temporary, when their rating gets approved, we recalculate their score anyway.

                return $formHandler->setModelData($data, $dataTypeEventValues, false);
            },
        ];

        $formHandler->go();

        return $formHandler->display('submitRating', compact('listing'));
    }
}
