<?php

return [
    'forms' => [
        'fieldLabel' => [
            'id' => 'ID',
            'reviewerID' => 'User',
            'hostelID' => 'Listing',
            'status' => 'Status',
            'notes' => 'Notes',
            'expirationDate' => 'Expiration Date',
            'reviewDate' => 'Review Date',
            'language' => 'Language',
            'rating' => 'Rating',
            'review' => 'Original Review',
            'editedReview' => 'Edited Review',
            'ownerResponse' => 'Response from the Owner',
            'author' => 'Author',
            'bookingInfo' => 'Booking Confirmation Code',
            'payStatus' => 'Pay Status',
            'rereviewWanted' => 'Rereview Wanted',
            'comments' => 'Comments',
            'newComment' => 'Reply',
            'newReviewerComment' => 'New Reviewer Comment',
            'plagiarismCheckDate' => 'Plagiarism Check Date',
            'plagiarismPercent' => 'Plagiarism Percent',
            'plagiarismInfo' => 'Plagiarism Info',
            'newStaffComment' => 'New Staff Comment',
            'hasPics' => 'Has Photos',
        ],
        'options' => [
            'status' => [
                'newHostel' => 'Review Hold',
                'newReview' => 'Submitted',
                'markedForEditing' => 'Marked for Editing',
                'staffEdited' => 'Staff Edited',
                'publishedReview' => 'Published',
                'postAsRating' => 'Posted as a Rating',
                'removedReview' => 'Removed',
                'deniedReview' => 'Denied',
                'returnedReview' => 'Returned',
            ],
            'rating' => [
                '0' => 'None',
                '1' => '* (Awful)',
                '2' => '** (Not So Great)',
                '3' => '*** (Average)',
                '4' => '**** (Good)',
                '5' => '***** (Excellent!)',
            ],
            'newReviewerComment' => [
                '0' => '-',
                '1' => 'NEW REVIEWER COMMENT',
            ],
            'newStaffComment' => [
                '0' => '-',
                '1' => 'New Staff Comment',
            ],
            'payStatus' => [
                '' => 'Not Yet Paid',
                'notForPay' => 'Not For Pay',
                'paid' => 'Paid',
            ],
            'rereviewWanted' => [
                '0' => 'No',
                '1' => 'Yes',
            ],
            'hasPics' => [
                '0' => 'No',
                '1' => 'Yes',
            ],
        ],
    ],
];
