<?php

return [
    'forms' => [
        'fieldLabel' => [
            'id' => 'ID',
            'status' => 'Status',
            'listingID' => 'Listing 1',
            'otherListing' => 'Listing 2',
            'source' => 'Source',
            'score' => 'Score',
            'priorityLevel' => 'Priority Level',
            'maxChoiceDifficulty' => 'Choice Difficulty',
            'userID' => 'User ID',
            'notes' => 'Notes',
            'propertyType' => 'Property Type',
        ],
        'options' => [
            'status' => [
                'flagged' => 'Flagged',
                'hold' => 'Hold',
                'nonduplicates' => 'Nonduplicates',
                'suspected' => 'Suspected',
            ],
            'propertyType' => [
                'Hostel' => 'Hostel',
                'Hotel' => 'Hotel',
                'Guesthouse' => 'Guesthouse',
                'Apartment' => 'Apartment',
                'Campsite' => 'Campsite',
                'Other' => 'Other',
            ],
            'maxChoiceDifficulty' => [
                1 => 'Easy',
                2 => 'Medium',
                3 => 'Difficult',
            ],
        ],
    ],
];
