<?php

return [
    '_translationInfo' => [
        'except' => [],
        // 'instructions' => '...'
    ],

    'ArticleTextNotLogin' => 'This is exclusive content for <span class="bg-primary rounded text-white px-2 font-weight-bold">Pluz</span>. The good news? It is entirely free to join. Just login or create your free account now!',
    'SignUpTitle' => 'Sign Up to Access',
    'SignUpText' => 'It is free, it is fun.',
    'forms' => [
        'fieldLabel' => [
            'id' => 'Id',
            'userID' => 'User',
            'status' => 'Status',
            'submitDate' => 'SubmitDate',
            'language' => 'Language',
            'title' => 'Title',
            'metaTitle' => 'Meta Title',
            'metaDescription' => 'Meta Description',
            'authorName' => 'AuthorName',
            'proposal' => 'Proposal',
            'publishDate' => 'PublishDate',
            'updateDate' => 'Updated Date (if any)',
            'placementType' => 'Placement Type',
            'placement' => 'Placement',
            'originalArticle' => 'Original Article',
            'finalArticle' => 'Final Article',
            'notes' => 'Notes',
            'payStatus' => 'Pay Status',
            'comments' => 'Comments',
            'newComment' => 'Reply',
            'newStaffComment' => 'New Staff Comment',
            'newUserComment' => 'New User Comment',
        ],
        'options' => [
            'status' => [
                'proposal' => 'Proposal Waiting for Approval',
                'returnedProposal' => 'Proposal Returned (Alteration Requested)',
                'deniedProposal' => 'Proposal Not Accepted',
                'acceptedProposal' => 'Proposal Accepted',
                'inProgress' => 'New Article In Progress',
                'submitted' => 'Submitted',
                'denied' => 'Denied',
                'returned' => 'Returned (Alteration Requested)',
                'accepted' => 'Accepted',
                'published' => 'Published',
                'removed' => 'Removed',
            ],
            'payStatus' => [
                '' => 'Not Yet Paid',
                'notForPay' => 'Not For Pay',
                'paid' => 'Paid',
            ],
            'newUserComment' => [
                '0' => '(none)',
                '1' => 'NEW REVIEWER COMMENT',
            ],
        ],
        'popover' => [
            'originalArticle' => '<head>...</head> for head insert. Use [pic:(picture name)] for photo insert.',
            'finalArticle' => '<head>...</head> for head insert. Use [pic:(picture name)] for photo insert.',
        ],
    ],
];
