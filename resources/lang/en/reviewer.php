<?php

/*

Reviewer menu text.

No need to translate this since reviewers are only writing English reviews for now.

*/

return [
    'forms' => [
        'fieldLabel' => [
            'language' => 'Language',
            'rating' => 'Rating',
            'review' => 'Review',
            'bookingInfo' => 'Booking Confirmation Code',
            'comments' => ' ',
            'newComment' => 'Comments to Hostelz.com Staff',
        ],
        'options' => [
            'rating' => [
                '0' => 'None',
                '1' => '*',
                '2' => '**',
                '3' => '***',
                '4' => '****',
                '5' => '*****',
            ],
            'newReviewerComment' => [
                '0' => '',
                '1' => 'NEW REVIEWER COMMENT',
            ],
        ],
        'popover' => [
            'bookingInfo' => "This is to help verify that you stayed at the hostel. Bookings should be made using Hostelz.com's availability search whenever possible.  If the hostel doesn't offer booking through Hostelz.com's booking systems, put a note about that here.",
        ],
    ],
];
