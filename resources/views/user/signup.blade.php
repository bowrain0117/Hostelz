@extends('login.auth-layout')

@section('login-title')
    Sign Up with Hostelz.com for Free - Hostel Travel Community
@stop

@section('login-description')
    Sign Up for Free with Hostelz.com and get access to Hostel Discounts, hostel tips and track your Bookings. BONUS: Exclusive Travel Guides for Members Only.
@stop
 
@section('login-form')

    @if ($status == '')
    @endif

    @if (@$listing)
        @section('login-header') {{{ $listing->name }}} @stop
    @endif

    @if ($status == 'userAlreadyIs')

        <div class="well">
            {{{ langGet("loginAndSignup.$signupType.userAlreadyIs", [ 'username' => auth()->user()->username ]) }}}
        </div>
 
    @elseif ($status == '' || $status == 'activateCurrentUser')
        @section('login-header')
            Sign Up and Create Your Ultimate Hostel Portfolio!
        @stop
        <form method="post">
            {!! csrf_field() !!}

            @if ($status == 'activateCurrentUser')
                <br>
                <p>
                    <b>{!! langGet('loginAndSignup.YourAccount', [ 'username' => auth()->user()->username ]) !!}</b>
                </p>
                <button type="submit" name="activateThisAccount" value={!! auth()->id() !!} class="btn btn-lg btn-primary">@langGet("loginAndSignup.$signupType.activateThisAccount")</button>
            @else
                <div class="row">
                    <div class="col-12 my-3">
                        @if ($errors)
                            <div class="alert alert-danger">
                                {!! $errors->first() !!}
                                <svg class="denied-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="denied-circle" cx="26" cy="26" r="25" fill="none"/><path class="denied-x" fill="none" d="M16 16, 36 36M36 16, 16 36"/></svg>
                            </div>
                        @endif
                        <div class="form-group">
                            <label for="email">@langGet('loginAndSignup.Email') <span data-toggle="tooltip" data-placement="top" title="We recommend using the email you book with on Hostelworld & Booking.com">
                                <i class="fas fa-info-circle"></i>
                            </span></label>
                            <input class="form-control" id="email" name="email" placeholder="@langGet('loginAndSignup.BestEmailSignUp')" value="{{{ @$email }}}" autofocus required>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <div class="custom-control custom-checkbox">
                        <input id="flexSwitchCheckDefault" type="checkbox" class="custom-control-input" name="tc" required>
                        <label for="flexSwitchCheckDefault" class="custom-control-label"> <span class="text-sm mr-2">By creating an account, you agree with our <a href="{!! routeURL('termsConditions') !!}" target="_blank" rel="nofollow" class="cl-link">Terms & conditions</a> and <a href="{!! routeURL('privacy-policy') !!}" target="_blank" rel="nofollow" class="cl-link">Privacy policy</a></span></label>
                    </div>
                </div>
                
                <!-- Submit-->
                <button class="btn btn-lg btn-block btn-primary" type="submit" name="submit" value=1 >Sign Up</button>

                <hr class="my-4">
                <p class="text-center"><small>Enter your email, and we'll send you an activation link.</small></p>
            
                @endif
         </form>

    @elseif ($status == 'emailAlreadyExists')

        @section('login-header')
            Email already registered
        @stop
        <div class="well">The email address "{{ $email }}" is already registered as a Hostelz.com user. <a href="{!! routeURL('login') !!}" class="cl-link">Login</a> here.</div>
  
    @elseif ($status == 'emailSent' || $status == 'tooSoonSinceLastEmail') 
        @section('login-header')
            You're Almost There! 
        @stop
        <div class="alert alert-success">
            <p class="mb-3">We've sent an activation link to "{{ $email }}"</p>
            <svg class="checkicon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"> <circle class="checkicon__circle" cx="26" cy="26" r="25" fill="none"/> <path class="checkicon__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
            <p class="mb-3"><b>Important:</b> Be sure to check your "<em>spam</em>" folder, too. Please mark our email as safe to ensure you receive our updates in your inbox!</p>
            <p class="mb-3">If you still haven't received it after 15 minutes, please return to this page to try sending the email again.</p>
        </div>

    @elseif($status == 'invalidVerifyLink')
        @section('login-header')
            Invalid URL
        @stop
        <div class="alert alert-danger">
            {!! langGet('global.invalidVerificationEmailURL') !!}
            <svg class="denied-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="denied-circle" cx="26" cy="26" r="25" fill="none"/><path class="denied-x" fill="none" d="M16 16, 36 36M36 16, 16 36"/></svg>  
        </div>

    @elseif ($status == 'choosePassword')

        @section('login-header')
            Set Your Password
        @stop

        <div class="form-text mb-4">
            <p>Let’s make sure your account is safe and secure! Please create a strong password that meets the following requirements:</p>
            <ul>
                <li class="mb-2"> 
                    <span class="password-requirement">
                        Add at least one number (0-9)
                        <span class="password-requirement-check" style="display: none;">@include('partials.svg-icon', ['svg_id' => 'green-check', 'svg_w' => '24', 'svg_h' => '24'])</span>
                    </span>
                </li>
                <li class="mb-2">
                    <span class="password-requirement">
                        Minimum of 7 characters
                        <span class="password-requirement-check" style="display: none;">@include('partials.svg-icon', ['svg_id' => 'green-check', 'svg_w' => '24', 'svg_h' => '24'])</span>
                    </span>
                </li>
            </ul>
        </div>
        

        <form method="post">
            {!! csrf_field() !!}
            <input type="hidden" name="e" value="{{{ $email }}}">
        
            <div class="row">
                <div class="col-md-12">
                    @if ($errors)
                    <div class="mt-3">
                        {!! $errors->first() !!}
                        <svg class="denied-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"><circle class="denied-circle" cx="26" cy="26" r="25" fill="none"/><path class="denied-x" fill="none" d="M16 16, 36 36M36 16, 16 36"/></svg>
                    </div>
                    @endif
                    <div class="form-group">
                        <label for="chosenPassword" class="form-label">Enter a strong password</label>
                        <input class="form-control" id="chosenPassword" name="chosenPassword" type="password" placeholder="Enter a password" autofocus required autocomplete="off" oninput="checkPasswordStrength(this.value)">
                    </div>
                </div>
            </div>
        
            <!-- Password strength feedback -->
            <div id="password-strength-feedback" class="mb-3"></div>
        
            <!-- Submit button (initially disabled) -->
            <button class="btn btn-lg btn-block btn-primary" type="submit" id="submitButton" disabled>BEGIN YOUR JOURNEY</button>
        </form>
        

        <script>
            function checkPasswordStrength(password) {
                const feedbackElement = document.getElementById('password-strength-feedback');
                const strength = calculatePasswordStrength(password);
                const feedbackMessages = ['Very Weak', 'Weak', 'Moderate', 'Strong', 'Very Strong'];
                const requirements = document.querySelectorAll('.password-requirement');
            
                feedbackElement.textContent = `Password Strength: ${feedbackMessages[strength]}`;
                feedbackElement.style.color = ['#FF0000', '#FF4500', '#FFA500', '#008000', '#228B22'][strength];
            
                // Enable the submit button if the password is strong (strength level >= 3)
                const submitButton = document.getElementById('submitButton');
                submitButton.disabled = strength < 3;
            
                // Check each requirement and show a check icon if fulfilled
                requirements.forEach((requirement, index) => {
                    const checkIcon = requirement.querySelector('.password-requirement-check');
                    if (checkRequirement(password, index)) {
                        checkIcon.style.display = 'inline';
                    } else {
                        checkIcon.style.display = 'none';
                    }
                });
            }
            
            function calculatePasswordStrength(password) {
                // Implement your own password strength calculation logic here
                // Return a value between 0 and 4 based on the strength
                // Example logic (adjust as needed):
                if (password.length < 6) {
                    return 0; // Very Weak
                } else if (password.length < 8) {
                    return 1; // Weak
                } else if (/[0-9]/.test(password)) {
                    return 3; // Strong (contains a number)
                } else {
                    return 2; // Moderate
                }
            }
            
            function checkRequirement(password, index) {
                // Implement logic to check each requirement here
                // Return true if the requirement is fulfilled, false otherwise
                switch (index) {
                    case 0:
                        // At least one number
                        return /[0-9]/.test(password);
                    case 1:
                        // Minimum of 7 characters
                        return password.length >= 7;
                    case 2:
                        // At least one letter (A-Z or a-z)
                        return /[a-zA-Z]/.test(password);
                    default:
                        return false;
                }
            }
            </script>
            
            
            
        
    @elseif ($status == 'existingUserActivated')
        @section('login-header')
            @langGet("loginAndSignup.$signupType.userActivatedTitle")
        @stop
        <div class="alert alert-success">
            <p>@langGet("loginAndSignup.$signupType.existingUserActivatedText")</p>
            <svg class="checkicon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"> <circle class="checkicon__circle" cx="26" cy="26" r="25" fill="none"/> <path class="checkicon__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
        </div>

    @elseif ($status == 'newUserActivated')
        @section('login-header')
            @langGet("loginAndSignup.$signupType.userActivatedTitle")
        @stop            
            <p>{!! langGet("loginAndSignup.$signupType.newUserActivatedText", [ 'url' => routeURL('login') ]) !!}</a></p>
            <svg class="checkicon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 52 52"> <circle class="checkicon__circle" cx="26" cy="26" r="25" fill="none"/> <path class="checkicon__check" fill="none" d="M14.1 27.2l7.1 7.2 16.7-16.8"/></svg>
        <h3 class="mb-3">Login Now to Begin Your Journey!</h3>
        @include('login.login-form')

    @else

        <?php throw new Exception("Unknown status '$status'."); ?>

    @endif

    <hr class="my-4">
    <p class="text-center"><small>Do you already have an account? <a href="{!! routeURL('login') !!}">Login</a></small></p>

@stop