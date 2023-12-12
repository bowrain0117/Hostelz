<?php

return [
    '_translationInfo' => [
        'except' => [
            'paidReviewerSignup',
        ],
        // 'instructions' => '...'
    ],

    // Login
    'Login' => 'Login',
    'Email' => 'Your best Email Address',
    'Password' => 'Password',
    'InvalidPassword' => 'Login failed. Invalid password.',
    'ForgotYourPassword' => 'Forgot your password?',
    'PasswordReset' => 'Password Reset',
    'Register' => 'Sign-up',
    'BestEmailSignUp' => 'What is your best email?',
    'RememberMe' => 'Remember me',

    //Footer and Sidebar
    'SignUpFooterTitle' => 'Unlock your Hostel Account to Freedom!',
    'SignUpFooterText' => 'Sign up with Hostelz.com and get access to exclusive hostel content and much more.',
    'SignUpSidebarTitle' => 'Create your Free Account',
    'SignUpSidebarTitle2' => 'Get access to exclusive hostel content',

    // Forgotten Password Reset
    'PasswordReset' => 'Password Reset',
    'unknownEmailAddress' => 'Sorry, we don\'t have any users in our database with the email address ":email".',
    'sentVerifyEmail' => 'An email has been sent to you at ":email".',
    'sentForgotPasswordEmail' => 'An email has been sent to ":email" with instructions on how to set a new password for your account.',
    'passwordResetEmailing' => 'An email will be sent to your email address with instructions on how to set a new password.',
    'emailSubject_forgotPassword' => 'Hostelz.com Password Reset',
    'emailText_forgotPassword' => "<p>A request was submitted to reset the password for your Hostelz.com user account.  If this request was submitted by you, please use this link to set a new password...</p>\n\n<p>:url</p>",
    'forgotPasswordInvalidLink' => 'Sorry, this password reset link is no longer valid. You may have not copied the entire URL correctly, it may have expired, or the password may have already been reset.',
    'chooseNewPassword' => 'Please choose a new password for your account.',
    'passwordUpdated' => 'Your password has been updated.',

    // Signup
    'YourAccount' => 'Your Account: ":username"',
    'EnterYourEmail' => 'Please enter your email address below. We will send you an email with information on how to activate your account.',
    'emailAlreadyExists' => 'The email address ":email" is already registered as a Hostelz.com user. Please login using the "login" link at the top of the page.',
    'tooSoon' => 'An email has already been sent to you at ":email".',
    'mayTakeTime' => 'It may take up to 15 minutes for the email to appear in your mailbox. Be sure to check your "spam" or "junk" mail folder as well. If this happens, please mark our email as safe. This way you will always receive our emails in your right inbox.',
    'returnToTryAgain' => 'If you still haven\'t received it after 15 minutes, please return to this page to try sending the email again.',
    'EmailSent' => 'Welcome to Hostelz.com! We just sent an email to ":email" with instructions on how to activate your account.',
    'WelcomeUser' => 'Welcome ":username".',
    'SetPassword' => 'Set Password',
    'EnterPassword' => 'Enter a password',
    'mustChoosePassword' => 'Please choose a safe password for your account.',
    'AlreadyAccount' => 'Do you already have an account?',
    'AgreePolicyTCs' => 'By signing up you agree to our <a href=":policyurl" target="_blank" title="">Terms and Conditions and Privacy Policy</a>',

    /*
    'emailSubject_writerSignup' => "Hostelz.com Travel Writer Sign-up - Please Verify",
    'emailText_writerSignup' => "<p>Thanks for your interest in being a Hostelz.com travel writer.  Please click this link to activate your account...</p>\n\n<p>:url</p>\n\n<p>Thanks.</p>",
    */

    'userSignup' => [
        'title' => 'New User Sign-up',
        'userAlreadyIs' => 'The email address ":username" is already registered as a Hostelz.com user. Please login using the "login" function.',
        'pageText' => 'Sign-up for a Hostelz.com user account to keep track of your reviews, bookings, and more.',
        'userActivatedTitle' => 'Congrats, Your Account is Now Active!',
        'newUserActivatedText' => 'Get ready to unlock the unique features behind Hostelz.com',
        'verifyEmailSubject' => 'Confirm your Hostelz.com Account',
        'verifyEmailText' => '<h1>Welcome to Hostelz.com!</h1><p>You are just one click away from unleashing "Z" magic.</p><p>Confirm your account. Simply click the link below:</p><p>:url</p><p><b>Important:</b> Ensure our emails land in your inbox by marking them as safe.</p><p>Thank you for joining,<br>Hostelz Team!</p>',
    ],

    'paidReviewerSignup' => [
        'title' => 'Get Paid for Writing Hostel Reviews',
        'userAlreadyIs' => 'Your account (":username") is already registered as a reviewer.  You can access the reviewer menu by clicking on your email address at the top of the page.',
        'pageText' => '', // the signup.blade.php template has special text for reviewers
        'activateThisAccount' => 'Sign-up to be a Paid Reviewer',
        'userActivatedTitle' => 'Your account has been granted reviewer access.',
        'existingUserActivatedText' => 'You can access the reviewer menu by clicking on your email address at the top of the page. <b>Be sure to read the reviewer instructions.</b>',
        'newUserActivatedText' => 'To access the reviewer menu, <a href=":url">login</a> and then click on your email address at the top of the page. <b>Be sure to read the reviewer instructions!</b>',
        'verifyEmailSubject' => 'Hostelz.com Reviewer Sign-up - Please Verify',
        'verifyEmailText' => "<p>Thanks for your interest in reviewing hostels for Hostelz.com. Please click this link to activate your account...</p>\n\n<p>:url</p>\n\n<p>Thanks.</p>",
    ],

    'affiliateSignup' => [
        'title' => 'Affiliate Sign-up',
        'userAlreadyIs' => 'Your account (":username") is already registered as an affiliate. You can access the affiliate menu by clicking on your email address at the top of the page.',
        'pageText' => 'Hostelz.com is the largest hostel guide online, offering a wider selection of hostels all over the world than any other website.  Online for more than 17 years, Hostelz.com is the established leader in hostel reviews and price comparison hostel booking. Now you can help tell people about this important travel resource, and earn income for doing it. <p>When a user clicks a Hostelz.com link from your website, you\'ll earn income for each booking they make. We send your earning to you automatically each month with PayPal. That\'s all there is to it.<p>The advantage of using Hostelz.com over any other hostel booking affiliate program is that we offer more hostels, with more availability, in more locations than any other hostel booking system. And our price comparison system means that users know they are always getting the lowest price available. That means users are more likely to find what they\'re looking for when using Hostelz.com, which means more total bookings.',
        'activateThisAccount' => 'Join the Affiliate Program',
        'userActivatedTitle' => 'Your account has been granted affiliate access.',
        'existingUserActivatedText' => 'You can access the affiliate menu by clicking on your email address at the top of the page.',
        'newUserActivatedText' => 'To access the affiliate menu, <a href=":url">login</a> and then click on your email address at the top of the page.',
        'verifyEmailSubject' => 'Hostelz.com Affiliate Sign-up - Please Verify',
        'verifyEmailText' => "<p>Thanks for your interest in being a Hostelz.com affiliate. Please click this link to activate your account...</p>\n\n<p>:url</p>\n\n<p>Thanks.</p>",
    ],

    'listingMgmtSignup' => [
        'title' => 'Listing Management Sign-up',
        'userAlreadyIs' => 'Your management account ":username" was already activated. You can manage your listings from the dashboard.',
        'pageText' => '',
        'activateThisAccount' => 'Manage the Listing with this Account',
        'userActivatedTitle' => 'Congrats, your management account is now activated.',
        'existingUserActivatedText' => 'You can now manage your listings from the dashboard.',
        'newUserActivatedText' => 'For managing your listings, please <a href=":url">login</a>. You can manage your listings from the dashboard.',
        'verifyEmailSubject' => 'Hostelz.com Listing Management - Please Verify',
        'verifyEmailText' => "<p>You have requested to use this email address to create a Hostelz.com account to manage your listing. Please click this link to activate your account...</p>\n\n<p>:url</p>\n\n<p>Thanks.</p>",
    ],

    'afterBookingSignup' => [
        'verifyEmailSubject' => 'Hostelz.com New User Sign-up - Please Verify',
        'verifyEmailText' => "<p>Thanks for booking with Hostelz.com. To make your future Hostelz.com bookings easier, and to claim your award points, please register your Hostelz.com user account. </p>\n\n<p>Please use this link to activate your account...</p>\n\n<p>:url</p>\n\n<p>Thanks.</p>",
    ],
];
