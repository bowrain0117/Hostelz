<?php

return [
    '_translationInfo' => [
        'except' => 'icons',
    ],

    'actions' => [
        'settings' => 'Settings',
        'changeEmail' => 'Change Email Address',
        'verifyChangedEmail' => 'Verify Email Address Change',
        'changePassword' => 'Change Password',
        'profilePhoto' => 'Profile Photo',
        'bookings' => 'My Bookings',
        'ratings' => 'My Ratings',
        'points' => 'My Points',
    ],
    'actionsComplete' => [
        'settings' => 'Select a user name that reflects your Adventures style and create <a href="" data-toggle="modal" data-target="#previewModal">your own Hostelz Portfolio</a>!',
        'profilePhoto' => 'Upload a profile picture that reflects your travel spirit.',
    ],
    'icons' => [
        'settings' => '<i class="fa fa-check-square"></i>',
        'changeEmail' => '<i class="fa fa-check-square"></i>',
        'changePassword' => '<i class="fa fa-check-square"></i>',
        'profilePhoto' => '<i class="fa fa-camera"></i>',
        'bookings' => '<i class="fa fa-book"></i>',
        'ratings' => '<i class="fa fa-star"></i>',
        'points' => '<i class="fa fa-trophy"></i>',
    ],

    // Pages

    'settings' => [
        'realNamePrivate' => 'Your <b>Real Name</b> will be kept private.',
        'penNameDesc' => 'Your "<b>User Name</b>" is your public author name displayed on your profile and next to your reviews. You may want to use your first name and last initial, or just your first name.',
        'penNameDescReviewer' => 'Your "<b>User Name</b>" is your public author name displayed next to your reviews, articles, or place descriptions. You may want to use your full name, your first name and last initial, or just your first name. You can also use a pseudonym (a fake name) for your Pen Name. <i>Please choose a realistic sounding name, not an abstract username</i> (so "Sam" is ok, but not something like "darthvader53").',
        'change' => 'change',
        'email' => 'Email',
    ],

    'changeEmail' => [
        'CurrentEmailAddress' => 'Current Email Address:',
        'text' => 'A confirmation email will be sent to your new email address to verify this change.',
        'newEmailLabel' => 'New Email Address',
        'SendVerificationEmail' => 'Send Verification Email',
        'emailAddressExists' => 'This ":email" already exists. You can reset your password below',
        'changeEmailSent' => 'An email was sent to ":email" to verify this change.',
        'changeEmailSentNote' => 'Please note that your email address will not actually be changed unless you click the link in the verification email.',
        'emailSubject' => 'Hostelz.com Change of Email Address - Please Verify',
        'emailText' => "<p>A request was submitted to change the email address for the user account \":oldEmail\" to \":newEmail\".  To verify this change, please click this link: </p>\n\n<p>:url</p>\n\n<p>If you didn't submit this change or if you want to cancel the change, just ignore this email.</p>",
        'emailChanged' => 'Your Hostelz.com account email address is now ":email".',
    ],

    'changePassword' => [
        'passwordUpdated' => 'Password updated.',
        'currentPassword' => 'Enter Current Password',
        'wrongPassword' => 'The password entered does not match the current password.',
        'newPassword' => 'Enter a New Password',
        'ChangePassword' => 'Change Password',
    ],

    //    'points' => [
    //
    //    ],
];
