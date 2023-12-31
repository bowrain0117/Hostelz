<?php

return [
    'menu' => [
        'UserMenu' => 'Dashboard',
        'welcome' => 'Welcome to your Dashboard',
        'Wishlist' => 'Your Wishlist',
        'Wishlists' => 'Your Wishlists',
        'ExclusiveContent' => 'Exclusive Articles',
        'pleaseComplete' => 'Please complete.',
        /*
    	'PleaseCompleteUserInfo' => 'Please complete your user info.',
    	'PleaseAddProfilePic' => 'Please add your profile pic.',
    	'TravelArticles' => 'Travel Articles',
    	'PaymentInfo' => 'Payment Info',
    	*/

        'SpecialAccess' => 'Special Access',
        'YourSpecialAccess' => 'Your account has access to these special access areas...',
        'ListingManagement' => 'Listing Management',
        'AffiliateProgram' => 'Affiliate Program',
        'AffiliateSignupLink' => 'If you have a website and you\'re interested in earning money by referring visitors to try Hostelz.com, you may be interested in our <a>affiliate program</a>.',

        'TravelWriting' => 'Travel Writing',
        'HostelReviews' => 'Paid Hostel Reviews',
        'SubmitCityPics' => 'Upload City Photos',
        'PlaceDescriptions' => 'Place Descriptions',
        'instructions' => '(instructions)',
    ],

    'forms' => [
        'fieldLabel' => [
            'id' => 'ID',
            'status' => 'Status',
            'username' => 'Email / Username',
            'passwordHash' => 'Password Hash',
            'password' => 'Password', // isn't the name of the actual database field, but the label is useful to have
            'access' => 'Access',
            'name' => 'Real Name',
            'nickname' => 'User Name',
            'localEmailAddress' => 'Local Email Address',
            'alsoGetLocalEmailFor' => 'Also Get Local Email For',
            'paymentEmail' => 'Payment Email (optional) or "none"',
            'payAmounts' => 'Pay Amounts',
            'countries' => 'Countries',
            'lastPaid' => 'Last Paid',
            'birthDate' => 'Date of Birth',
            'homeCountry' => 'Home Country',
            'dateAdded' => 'Date Added',
            'formData' => 'Form Values',
            'sessionID' => 'Cookie ID',
            'points' => 'Points',
            'affiliateURLs' => 'Affiliate URLs',
            'mgmtListings' => 'Manages Listings',
            'invalidEmails' => 'Invalid Emails',
            'apiAccess' => 'API Access',
            'apiKey' => 'API Key (enter any random string, current only used for descriptions)',
            'data' => 'Notes',
            'bio' => 'Your Bio',
            'bioSettings' => 'Your Bio - Tell the community about yourself',
            'isPublic' => 'Do you want your own Hostelz Portfolio?',
            'gender' => 'Gender',
            'languages' => 'Languages Speaking',
            'facebook' => 'Facebook Link',
            'instagram' => 'Instagram Link',
            'tiktok' => 'Tiktok Link',
            'dreamDestinations' => 'Dream Destination',
            'favoriteHostels' => 'Favorite Hostels',
            'searchHistory' => 'Search History',
            'slug' => 'Public Page Slug',
        ],
        'placeholder' => [
            'name' => 'Add your Real Name (kept private)',
            'nickname' => 'Choose your Public User Name',
            'birthDate' => 'Add your Birthday',
            'homeCountry' => 'Add your Home Country',
            'facebook' => 'Your Facebook Link',
            'instagram' => 'Your Instagram Link',
            'tiktok' => 'Your Tiktok Link',
            'bio' => 'Share your travel bio to connect with fellow travelers!',
        ],
        'options' => [
            'status' => [
                'ok' => 'OK',
                'disabled' => 'Disabled',
            ],
            'access' => [
                'admin' => 'admin',
                'staff' => 'staff',
                'reviewer' => 'reviewer',
                'staffCityInfo' => 'staffCityInfo',
                'staffEditHostels' => 'staffEditHostels',
                'staffPicEdit' => 'staffPicEdit',
                'staffEditComments' => 'staffEditComments',
                'staffEditCityComments' => 'staffEditCityComments',
                'staffEditReviews' => 'staffEditReviews',
                'staffEmail' => 'staffEmail',
                'staffBookings' => 'staffBookings',
                'staffEditUsers' => 'staffEditUsers',
                'staffTranslation' => 'staffTranslation',
                'staffMarketing' => 'staffMarketing',
                'staffMarketingLevel2' => 'staffMarketingLevel2',
                'staffWriter' => 'staffWriter',
                'placeDescriptionWriter' => 'placeDescriptionWriter',
                'staffEditAttached' => 'staffEditAttached',
                'affiliate' => 'affiliate',
                'affiliateWhiteLabel' => 'affiliateWhiteLabel',
                'affiliateXML' => 'affiliateXML',
                'developer' => 'developer',
            ],
            'apiAccess' => [
                'cityData' => 'cityData',
                'listingData' => 'listingData',
                'listingDescs' => 'listingDescs',
            ],
            'isPublic' => [
                '0' => 'No',
                '1' => 'Yes (recommended)',
            ],
            'gender' => [
                0 => 'not selected',
                1 => 'male',
                2 => 'female',
                3 => 'other',
            ],
        ],
    ],
];
