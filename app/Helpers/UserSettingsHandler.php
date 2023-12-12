<?php

namespace App\Helpers;

/*

This is called from controllers to handle editing of users.

Can be used for:

- Admin/staff editing user details.
- Manager/owner editing own user's details.
- User submitting a new user.

*/

use App;
use App\Models\User;
use App\Services\AjaxDataQueryHandler;
use Emailer;
use Exception;
use Hash;
use Illuminate\Support\Collection;
use Lib\FormHandler;
use Request;
use Validator;
use View;

class UserSettingsHandler
{
    // Actions that require a logged in or authorized user
    public static $actions = ['settings', 'changeEmail', 'changePassword', 'profilePhoto', 'bookings', 'ratings', 'points'];

    // Actions that don't require a logged in user (such as verifying their email address for an email address change)
    public static $nonLoggedInActions = ['verifyChangedEmail'];

    public $returnToWhenDone; // route to return to after editing is complete, tab is picked based on the user. (or can be a URL)

    public $user;

    public $view;

    public $action;

    public $nonLoggedInRouteName;

    public $showGenericUpdateSuccessPage = false;

    public $relaxedValidation = false; // Set to true for admin users so some fields are required, etc.

    public function __construct($userID = 0, $action = null, $view = null, $returnToWhenDone = null)
    {
        $this->action = $action;
        $this->view = $view;
        $this->returnToWhenDone = $returnToWhenDone;

        if ($userID) {
            $this->user = User::find($userID);
            if (! $this->user) {
                App::abort(404);
            }
        }
    }

    public function getValidationStatuses($forActions)
    {
        $return = [];

        foreach ($forActions as $action) {
            if (! in_array($action, self::$actions)) {
                throw new Exception("Unknown action '$action'.");
            }
            $return[$action] = $this->$action(true, false);
        }

        return $return;
    }

    public function go()
    {
        View::share('userSettingsHandler', $this);

        if (
            ! $this->user
            || auth()->id() != $this->user->id
            && ! auth()->user()->hasPermission('staffEditUsers')
        ) {
            return accessDenied();
        }
        if (! in_array($this->action, self::$actions)) {
            App::abort(404);
        }

        $action = $this->action;

        return $this->$action(false, true);
    }

    public function nonLoggedInActionsGo()
    {
        View::share('userSettingsHandler', $this);

        if (! in_array($this->action, self::$nonLoggedInActions)) {
            App::abort(404);
        }

        $action = $this->action;

        return $this->$action(false, true);
    }

    public function getURL($userID, $action = '', $queryVariables = null)
    {
        $parameters = Route::getCurrentRoute()->parameters();
        $parameters['userID'] = $userID;
        if ($action != '') {
            $parameters['userAction'] = $action;
        } else {
            unset($parameters['userAction']);
        }

        return routeURL(Route::currentRouteName(), $parameters) .
            ($queryVariables ? makeUrlQueryString($queryVariables) : '');
    }

    /*
    *** Actions ***
    */

    private function settings($justGetValidationStatus, $showValidation)
    {
        $response = AjaxDataQueryHandler::handleCityInfoSearch(Request::input('dreamDestinations_selectorIdFind'), Request::input('dreamDestinations_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleFavoriteHostelsSearch(Request::input('favoriteHostels_selectorIdFind'), Request::input('favoriteHostels_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        $formHandler = new FormHandler('User', User::fieldInfo('userSettings'), null, 'App\Models');
        $formHandler->model = $this->user;
        $formHandler->logChangesAsCategory = 'user';
        $formHandler->callbacks = [
            'validate' => function ($formHandler, $useInputData, $fieldInfoElement) {
                //  prevent language duplication
                if (isset($formHandler->inputData['languages']) && is_array($formHandler->inputData['languages'])) {
                    $formHandler->inputData['languages'] = array_unique($formHandler->inputData['languages']);
                }

                return $formHandler->validate($useInputData, $fieldInfoElement, false);
            },
        ];

        if ($justGetValidationStatus) {
            $formHandler->mode = 'updateForm';

            return ! $formHandler->validate(false)->any();
        }

        return $formHandler->go($this->view, ['updateForm', 'update']);
    }

    public function changeEmail()
    {
        if (Request::getMethod() == 'GET') {
            return view($this->view);
        }

        // Validate the inputs.
        $newEmail = Request::input('newEmail');
        if (User::where('username', $newEmail)->exists()) {
            return view($this->view, compact('newEmail'))->with('status', 'emailAlreadyExists');
        }
        $validator = Validator::make(['newEmail' => $newEmail], ['newEmail' => 'required|email']);
        if ($validator->fails()) {
            return view($this->view, compact('newEmail'))->with('errors', $validator->messages());
        }

        // Send verification email
        $verificationURL = routeURL($this->nonLoggedInRouteName, 'verifyChangedEmail', 'publicSite') .
            '?' . http_build_query(['e' => $newEmail, 'u' => $this->user->id, 't' => User::emailVerificationToken($newEmail, $this->user->id),
            ]);
        $emailText = langGet('UserSettingsHandler.changeEmail.emailText', [
            'oldEmail' => htmlentities($this->user->username),
            'newEmail' => htmlentities($newEmail),
            'url' => "<a href=\"$verificationURL\">$verificationURL</a>",
        ]);
        Emailer::send($newEmail, langGet('UserSettingsHandler.changeEmail.emailSubject'), 'generic-email', ['text' => $emailText]);

        return view($this->view, compact('newEmail'))->with('status', 'success');
    }

    /* When emailed link clicked to verify their changed email address. */

    public function verifyChangedEmail()
    {
        $user = User::find(Request::input('u'));
        $newEmail = Request::input('e');
        if (! $user || User::emailVerificationToken($newEmail, $user->id) != Request::input('t')) {
            return view($this->view)->with('status', 'invalidVerifyLink');
        }

        // Most likely this would only happen if they already changed their email address, so just show the success message again.
        if (User::where('username', $newEmail)->exists()) {
            return view($this->view, compact('newEmail'))->with('status', 'success');
        }

        $user->updateAndLogEvent(['username' => $newEmail], true, 'verifyChangedEmail');
        $user->associateEmailAddressWithUser();
        if (auth()->check()) {
            auth()->logout();
        } // force them to login again to update cookies, etc.

        return view($this->view, compact('newEmail'))->with('status', 'success');
    }

    public function changePassword()
    {
        // For staff, we only let them change the password of users that don't have any
        // permissions that they themselvs don't have.
        if (auth()->id() != $this->user->id && ! auth()->user()->hasAllPermissions($this->user->access)) {
            return accessDenied();
        }

        if (Request::getMethod() == 'GET') {
            return view($this->view);
        }

        // Check Current Password
        if (! auth()->user()->hasPermission('admin') && ! Hash::check(Request::input('currentPassword'), $this->user->passwordHash)) {
            return view($this->view)->with('invalidCurrentPassword', true);
        }

        // Validate the inputs.
        $userdata = ['newPassword' => Request::input('newPassword')];
        $validator = Validator::make($userdata, ['newPassword' => User::PASSWORD_VALIDATION]);
        if ($validator->fails()) {
            return view($this->view, $userdata)->with('errors', $validator->messages());
        }

        // Update password
        $this->user->updateAndLogEvent(['passwordHash' => Hash::make($userdata['newPassword'])], true, 'changePassword');

        return view($this->view)->with('status', 'success');
    }

    private function profilePhoto($justGetValidationStatus, $showValidation)
    {
        $existing = $this->user->profilePhoto;

        if ($justGetValidationStatus) {
            return (bool) $existing;
        }

        $existingAsCollection = Collection::make($existing ? [$existing] : []);

        // FileList

        $fileList = new \Lib\FileListHandler($existingAsCollection, null, null, true);
        $fileList->picListSizeTypeNames = ['originals'];
        $response = $fileList->go();
        if ($response !== null) {
            return $response;
        }

        // FileUpload

        $fileUpload = new \Lib\FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], 15, 1, $existing ? 1 : 0);
        $fileUpload->minImageWidth = User::PROFILE_PHOTO_WIDTH;
        $fileUpload->minImageHeight = User::PROFILE_PHOTO_HEIGHT;
        $user = $this->user;
        $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($user): void {
            $user->setProfilePhoto($filePath);
            $user->recalculatePoints();
        });
        if ($response !== null) {
            return $response;
        }

        return view($this->view, compact('fileList', 'fileUpload', 'showValidation'));
    }

    private function bookings($justGetValidationStatus, $showValidation)
    {
        // todo
        return true;
    }

    private function ratings($justGetValidationStatus, $showValidation)
    {
        // todo
        return true;
    }

    private function points($justGetValidationStatus, $showValidation)
    {
        if ($justGetValidationStatus) {
            return true;
        }

        return view($this->view);
    }
}
