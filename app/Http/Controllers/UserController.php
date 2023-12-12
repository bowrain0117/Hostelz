<?php

namespace App\Http\Controllers;

use App\Helpers\EventLog;
use App\Helpers\UserSettingsHandler;
use App\Models\Comparison;
use App\Models\Listing\Listing;
use App\Models\User;
use App\Services\Payments;
use App\Services\UserAdminService;
use Emailer;
use Hash;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Authenticatable $user)
    {
        /** @var User $user */
        $message = ''; // not yet used
        $userSettingsHandler = new UserSettingsHandler($user->id);
        $userSettingsValidations = $userSettingsHandler->getValidationStatuses(['settings', 'profilePhoto', 'points' /* 'bookings', 'ratings', 'rewardPoints' (todo) */]);

        return view('user/index', compact('userSettingsValidations', 'message', 'user'));
    }

    /* For user settings, profile pic, bookings, etc. */

    public static function userSettings($action)
    {
        $handler = new UserSettingsHandler(
            auth()->id(),
            $action,
            'user/settings',
            routeURL('user:settings', 'settings')
        );
        $handler->nonLoggedInRouteName = 'userSettingsNonLoggedIn';

        return $handler->go();
    }

    public static function userSettingsNonLoggedInActions($action)
    {
        $handler = new UserSettingsHandler(null, $action, 'user/settings', routeURL('user:menu'));
        $handler->nonLoggedInRouteName = 'userSettingsNonLoggedIn';

        return $handler->nonLoggedInActionsGo();
    }

    public static function yourPay()
    {
        $pastPayments = EventLog::where('userID', auth()->id())->where('action', 'payment')->orderBy('eventTime', 'desc')->get();
        $amountDue = Payments::calculateUserPay(auth()->user(), $payDetails);

        $status = $errors = '';

        if (($paymentEmail = Request::input('paymentEmail')) != '') {
            // Update the paymentEmail address
            $validator = Validator::make(['paymentEmail' => $paymentEmail], ['paymentEmail' => 'email']);
            if ($validator->fails()) {
                $errors = $validator->messages();
            } else {
                $status = 'success';
                auth()->user()->updateAndLogEvent(['paymentEmail' => $paymentEmail], true, 'yourPay', 'user');
            }
        } else {
            $paymentEmail = (auth()->user()->paymentEmail != '' ? auth()->user()->paymentEmail : auth()->user()->username);
        }

        return view('user/your-pay', compact('status', 'errors', 'paymentEmail', 'pastPayments', 'payDetails', 'amountDue'));
    }

    public function login()
    {
        // Double-check to make sure that if no user is logged in, our special loginInfo cookie isn't set.
        // (to keep the user's name from appearing at the top of the page on the login screen if they aren't really logged in)
        if (! auth()->check() && getMultiCookie('loginInfo')) {
            unsetMultiCookie('loginInfo');
        }

        if (Request::getMethod() !== 'POST') {
            return view('login/login');
        }

        // Get all the inputs
        $userdata = [
            'username' => Request::input('username'),
            'password' => Request::input('password'),
        ];

        $messages = [
            'username.required' => 'The Email field is required.',
            'password.required' => 'The Password field is required.',
        ];

        Log::channel('login')->info('user_login username: ' . $userdata['username']);

        // Validate the inputs.
        $validator = Validator::make($userdata, ['username' => 'Required', 'password' => 'Required'], $messages);
        if ($validator->fails()) {
            Log::channel('login')->warning('user_login validation fails ' . $validator->messages());

            if (request()->ajax()) {
                return response()->json([
                    'status' => 'error',
                    'errors' => $validator->messages(),
                ]);
            }

            return view('login/login', $userdata)->with('rememberMe', Request::input('rememberMe'))->with('errors', $validator->messages());
        }

        // Make sure the use has login permission
        $user = User::firstWhere('username', Request::input('username'));

        // Try to log the user in.
        if (! $user || ! $user->isAllowedToLogin() || ! auth()->attempt($userdata, Request::input('rememberMe') == '' ? false : true)) {
            $loginFailedMessage = $this->getLoginFailedMasseg($user);

            Log::channel('login')->warning('user_login fails try to login, message: ' . $loginFailedMessage['message']);

            //  todo: test
            if (request()->ajax()) {
                return response()->json([
                    'status' => 'loginFailed',
                    'errors' => ['username' => $loginFailedMessage['message']],
                ]);
            }

            return view('login/login', $userdata)
                ->with('rememberMe', Request::input('rememberMe'))
                ->with('loginFailedMessage', $loginFailedMessage)
                ->with('user', $user);
        }

        // Check to see if the passwords needs to be re-hashed (when the hash technique is different than when originally saved)
        if (Hash::needsRehash($user->password)) {
            $user->setPassword(Request::input('password'));
            $user->save();
        }

        Log::channel('login')->info('user_login success');

        if (request()->ajax()) {
            return response()->json([
                'status' => 'success',
            ]);
        }

        if (Request::has('returnTo')) {
            return Redirect::to(Request::input('returnTo'));
        }

        return Redirect::intended(routeURL('user:menu'));
    }

    protected function getLoginFailedMasseg($user): array
    {
        if (! $user) {
            return [
                'message' => 'Login failed - Unknown or invalid email address.',
                'status' => 'warning',
            ];
        }

        if ($user->isAllowedToLogin()) {
            return [
                'message' => langGet('loginAndSignup.InvalidPassword'),
                'status' => 'warning',
            ];
        }

        return [
            'message' => 'Login failed - Invalid account.',
            'status' => 'danger',
        ];
    }

    public function logout()
    {
        if (auth()->check()) {
            Log::channel('login')->info('user_logout username: ' . auth()->user()->username);

            auth()->logout();
        } else {
            // Uh-oh, they were already logged out.  Probably a good idea to re-unset our own special cookies.
            User::unsetOurLoginCookies();
        }

        return Redirect::route('home');
    }

    public function forgotLogin($token = '')
    {
        if ($token) {
            return $this->forgotLoginResetLink($token);
        }

        if (Request::getMethod() != 'POST') {
            return view('login/login-forgot');
        }

        $userdata = ['email' => trim(mb_strtolower(Request::input('email')))];

        // Validate the inputs.
        $validator = Validator::make($userdata, ['email' => 'required|email']);
        if ($validator->fails()) {
            return view('login/login-forgot', $userdata)->with('errors', $validator->messages());
        }

        // Find the user
        $user = User::where('username', '=', $userdata['email'])->first();
        if (! $user || ! $user->isAllowedToLogin()) {
            return view('login/login-forgot', $userdata)->with('status', 'userNotFound');
        }

        // Send verification email
        $verificationURL = routeURL(Route::currentRouteName(), $user->createPasswordChangeToken(), 'absolute') .
            '?' . http_build_query(['u' => $user->id]);
        $emailText = langGet('loginAndSignup.emailText_forgotPassword', [
            'url' => "<a href=\"$verificationURL\">$verificationURL</a>",
        ]);
        Emailer::send($user, langGet('loginAndSignup.emailSubject_forgotPassword'), 'generic-email', ['text' => $emailText]);

        return view('login/login-forgot', $userdata)->with('status', 'success');
    }

    // Emailed link clicked

    private function forgotLoginResetLink($token)
    {
        $status = $errors = '';

        $user = User::find(Request::input('u'));

        if (! $user || ! $user->isAllowedToLogin() || ! $user->isPasswordChangeTokenValid($token)) {
            $status = 'invalidLink';
        } elseif (($password = Request::input('password')) != '') {
            // Validate the inputs.
            $userdata = ['password' => $password];
            $validator = Validator::make($userdata, ['password' => User::PASSWORD_VALIDATION]);
            if ($validator->fails()) {
                $errors = $validator->messages();
            } else {
                // Update password (and reset the token)
                $user->setPassword($password);
                $user->save();
                $user->deletePasswordChangeToken();
                $status = 'success';
            }
        }

        return view('login/login-forgot-reset', compact('status', 'errors'));
    }

    public function userSignup($emailVerificationToken = '')
    {
        return $this->handleSignup(
            'userSignup',
            auth()->check(),
            null,
            $emailVerificationToken
        );
    }

    // Info-only page (on the static site, indexable by search engines)

    public function paidReviewerInfo()
    {
        return view('paid-reviewer');
    }

    // On the dynamic site (not indexable by search engines)

    public function paidReviewerSignup($emailVerificationToken = '')
    {
        return $this->handleSignup(
            'paidReviewerSignup',
            auth()->check() && auth()->user()->hasPermission('reviewer'),
            function ($user): void {
                $user->becomePaidReviewer();
            },
            $emailVerificationToken
        );
    }

    public function affiliateSignup($emailVerificationToken = '')
    {
        return $this->handleSignup(
            'affiliateSignup',
            auth()->check() && auth()->user()->hasPermission('affiliate'),
            function ($user): void {
                $user->becomeAffiliate();
            },
            $emailVerificationToken
        );
    }

    public function listingMgmtSignup($emailVerificationToken = '')
    {
        $listingID = Request::input('l');
        $listing = Listing::areNotListingCorrection()->where('id', $listingID)->first();
        if (! $listing) {
            abort(404);
        }

        view()->share('listing', $listing);

        if ($emailVerificationToken == '') {
            // Note: We don't need the mgmtSignupVerificationToken if they have a $emailVerificationToken since we just check that instead
            $mgmtSignupVerificationToken = Request::input('m');
            if (User::mgmtSignupVerificationToken($listingID) != $mgmtSignupVerificationToken) {
                // We just output the same 'invalidVerifyLink' message that 'user/signup' does.
                return view('user/signup', ['status' => 'invalidVerifyLink', 'signupType' => 'listingMgmtSignup']);
            }
        }

        return $this->handleSignup(
            'listingMgmtSignup',
            auth()->check() && in_array($listingID, auth()->user()->mgmtListings),
            function ($user) use ($listingID): void {
                $user->addMgmtListingIDs($listingID, true, true);
            },
            $emailVerificationToken,
            "listingMgmtSignup:$listingID",
            ['l' => $listingID]
        );
    }

    /*
        $grantAccess - A call-back function to grant access for $user to whatever this signup is for. Or null if it's just a regular sign-up with no special access.
        $emailVerificationTokenSpecifics - Stuff to include when computing the email verification token. If null, defaults to the $signupType.
    */

    private function handleSignup(
        $signupType,
        $userAlreadyIs,
        $grantAccess,
        $emailVerificationToken = '',
        $emailVerificationTokenSpecifics = null,
        $extraQueryVariables = []
    ) {
        $status = $email = $errors = '';

        if ($emailVerificationTokenSpecifics === null) {
            $emailVerificationTokenSpecifics = $signupType;
        } // default

        if ($emailVerificationToken != '') { // Verification email link clicked
            $email = Request::input('e');
            if ($email == '' || User::emailVerificationToken($email, $emailVerificationTokenSpecifics) != $emailVerificationToken) {
                $status = 'invalidVerifyLink';
            } elseif (User::where('username', $email)->exists()) {
                $status = 'emailAlreadyExists';
            } elseif ($password = Request::input('chosenPassword')) {
                $validator = Validator::make(['password' => $password], ['password' => User::PASSWORD_VALIDATION]);
                if ($validator->fails()) {
                    $errors = $validator->messages();
                    $status = 'choosePassword';
                } else {
                    $user = User::createNewFromSignup(['username' => $email], $password);
                    $user->associateEmailAddressWithUser();
                    EventLog::log('user', 'insert', 'User', $user->id, $signupType, $email);
                    if ($grantAccess) {
                        $grantAccess($user);
                    }
                    $status = 'newUserActivated';
                }
            } else {
                $status = 'choosePassword';
            }
        } elseif ($userAlreadyIs) { // Current user is already signed up for this
            $status = 'userAlreadyIs';
        } elseif (auth()->check() && $grantAccess) { // A user is logged in
            if (Request::input('activateThisAccount') == auth()->id()) { // They clicked to activate their account
                $grantAccess(auth()->user());
                $status = 'existingUserActivated';
            } else {
                // Show "do you want to activate your account?" page
                $status = 'activateCurrentUser';
            }
        } elseif (Request::has('submit')) { // Email address submitted
            $email = Request::input('email');
            if ($email != '') {
                $validator = Validator::make(['email' => $email], ['email' => 'required|email']);
                if ($validator->fails()) {
                    $errors = $validator->messages();
                } elseif (User::where('username', $email)->exists()) {
                    $status = 'emailAlreadyExists';
                } else {
                    if (User::sendUserSignupVerificationEmail(
                        $signupType,
                        Route::currentRouteName(),
                        $email,
                        15 * 60,
                        $emailVerificationTokenSpecifics,
                        $extraQueryVariables
                    ) === 'tooSoon') {
                        $status = 'tooSoonSinceLastEmail';
                    } else {
                        $status = 'emailSent';
                    }
                }
            }
        }

        return view('user/signup', compact('signupType', 'status', 'email', 'errors'));
    }

    public function frontUserData()
    {
        $comparisonListingsCount = 0;
        $comparisonIds = [];

        $loggedUser = auth()->user();
        if (! $loggedUser) {
            if (session()->has(Comparison::SESSION_COMPARE_KEY)) {
                $comparisonListingsCount = count(session()->get(Comparison::SESSION_COMPARE_KEY));
                $comparisonIds = session()->get(Comparison::SESSION_COMPARE_KEY);
            }

            return response()
                ->json([
                    'comparisonListingsCount' => $comparisonListingsCount,
                    'comparisonIds' => $comparisonIds,
                    'isLogged' => false,
                    'csrf' => csrf_token(),
                ]);
        }

        $avatar = isset($loggedUser->profilePhoto) ?
            $loggedUser->profilePhoto->url(['thumbnails']) :
            '';

        $userName = $loggedUser->name ?? $loggedUser->nickname ?? '';

        $comparisonIds = $loggedUser->comparisons->pluck('id')->toArray();
        $comparisonListingsCount = count($comparisonIds);

        $language = $this->languageFromUrl(url()->previous());
        $localCurrency = Session::get('localCurrency') ?? 'USD';

        $response = [
            'isLogged' => true,
            'comparisonIds' => $comparisonIds,
            'comparisonListingsCount' => $comparisonListingsCount,
            'localCurrency' => $localCurrency,
            'headerSettings' => view('user.headerUserSettings', compact('avatar', 'language', 'comparisonListingsCount'))->render(),
            'editURL' => getEditURL(request()->input('editURLFor', '')),
            'avatar' => view('user.reviewUserAvatar', compact('avatar'))->render(),
            'userName' => view('user.createAccountText', compact('userName'))->render(),
            'csrf' => csrf_token(),
        ];

        return response()
            ->json($response);
    }

    private function languageFromUrl($url)
    {
        $arr = explode('/', $url);
        $language = 'en';

        if (array_search('l', $arr, true)) {
            $language = $arr[4];
        }

        return $language;
    }

    public function searchHistory(UserAdminService $adminService)
    {
        $loggedUser = auth()->user();
        if (! $loggedUser) {
            return '';
        }

        $validator = \Illuminate\Support\Facades\Validator::make(request()->all(), [
            'category' => 'required|string|max:50',
            'query' => 'required|string|max:255',
            'itemID' => 'required|numeric',
        ]);

        if ($validator->fails()) {
            logError($validator->messages());

            return '';
        }

        $adminService->storeUserSearch($validator->valid());
    }
}
