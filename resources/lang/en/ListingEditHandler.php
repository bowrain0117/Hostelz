<?php

return [
    '_translationInfo' => [
        'except' => 'icons',
    ],

    'uniqueText' => '<b>Smart Tip:</b> Rank your hostel higher with a fully unique text and description (instead of copy-pasting existing text)',
    'NotAllowedText' => '<b>Please note:</b> HTML and external links will not be displayed.',
    'actions' => [
        'preview' => 'Preview Listing',
        'basicInfo' => 'Contact Info',
        'features' => 'Features / Details',
        'description' => 'Description',
        'location' => 'Location / Directions',
        'mapLocation' => 'Map Location',
        'pics' => 'Photos',
        'panoramas' => 'Panoramas',
        'video' => 'Video',
        'backlink' => 'Backlink',
        'ratings' => 'Ratings & Reviews',
        'reviews' => 'Reviews',
        'sticker' => 'Recommended Hostel',
        // 'bookings' => 'Bookings',
    ],

    'icons' => [
        'basicInfo' => '<i class="fa fa-phone-square"></i>',
        'features' => '<i class="fa fa-check-square"></i>',
        'description' => '<i class="fa fa-align-left"></i>',
        'location' => '<i class="fa fa-bus"></i>',
        'mapLocation' => '<i class="fa fa-map-marker"></i>',
        'pics' => '<i class="fa fa-camera"></i>',
        'panoramas' => '<i class="fa fa-camera"></i>',
        'video' => '<i class="fa fa-video"></i>',
        'backlink' => '<span class="fas fa-link"></span>',
        'ratings' => '<i class="fa fa-star"></i>',
        'bookings' => '<i class="fa fa-book"></i>',
        'sticker' => '<i class="fa fa-trophy"></i>',
    ],

    'basicInfo' => [
        'pageDescription' => 'Note: Some fields such as the property name and address can\'t be edited here. Please contact us if you need to request corrections to those fields.',
    ],

    //    'features' => [
    //    ],

    'description' => [
        'pageDescription' => 'Please enter a description. This will appear in the the listing and it should include information on facilities, atmosphere, etc., the more information the better. Please also mention any other restrictions on who can stay or when.  You may enter just an English text, or also information in other languages if you have translated versions.  HTML code is not allowed and will be stripped out.',
    ],

    'location' => [
        'pageDescription' => 'Please enter location information and directions to the accommodation. You may enter just an English text, or also information in other languages if you have translated versions.  HTML code is not allowed and will be stripped out.',
    ],

    'mapLocation' => [
        'clickOrDrag' => 'Click or drag the marker to your exact location on the map.',
        'zoomInToPlace' => 'Zoom in to place the marker as precisely as possible.',
        'findAddress' => 'Find City or Address:',
        'FindOnMap' => 'Find on Map',
        'cancelAndReset' => 'Reset Location to Default',
        'CantGeocode' => 'Sorry, we were unable to locate that address.',
    ],

    //    'pics' => [
    //    ],

    'video' => [
        'CurrentVideo' => 'Your Current Video:',
        'VideoInstructions' => 'Copy and paste the URL of your accommodation\'s video here. The video may be hosted on YouTube, Vimeo, Viddler, or other video websites.  We will embed the video into your listing on Hostelz.com.',
        'SubmitNewVideo' => 'Submit New Video URL:',
        'VideoAdded' => 'The video has been added to your listing.',
        'SorryUnableToFindVideo' => 'Sorry, we were not able to find the video on that page.',
    ],

    'backlink' => [
        'BacklinkInfo' => 'Hostelz.com is the only worldwide hostels guide that lets you include your direct contact information in your listing, including your phone number and website.  In return, the only thing we ask is that you put a link back to Hostelz.com somewhere on your website.  You can link to any page on Hostelz.com (including the homepage or your own listing).',
        'BacklinkExample' => 'Link to your listing with HTML like this:',
        'PleaseEnterURL' => 'Once you have added a link to Hostelz.com from your website, please enter the URL of the page on your website where you linked to Hostelz.com.',
        'CurrentLink' => 'Your Current Verified Link:',
        'UrlOfPage' => 'URL of a page on your website with a link to Hostelz.com:',
        'SorryUnableToFindBacklink' => 'Sorry, we were not able to find the link to Hostelz.com on that page.',
        'BacklinkVerified' => 'Your backlink has been verified. Thanks!',
    ],

    'ratings' => [
        'pageDescription' => "These are all of the user-submitted reviews that are currently posted to your accommodation's listing page.  You can click on one and add an official \"response from the owner\" if you wish.",
        'HowScoreCalculated' => 'This score is calculated from many factors, including your ratings on both Hostelz.com, as well as other websites.  The score is based on not just your review scores, but also the total number of reviews that users have submitted.  Ratings on other review websites are a factor, but reviews that users submit directly to Hostelz.com a weighted more heavily, so it may be worth reminding your guests to post a review on Hostelz.com after their stay.',
        'WhenScoreUpdated' => 'The score is not immediately updated when new ratings are accepted.  The score is updated typically about once a month.',
        'TipToImprove' => 'Tip to Improve Your Hostelz.com Rating Score',
        'ToHaveHighRating' => 'To have a high rating score on Hostelz.com, both your average rating and the <i>number of ratings</i> is considered.  So to improve your score, you can encourage your guests to submit a review.  One good way to do this is to post a small note by your check-out desk reminding your guests to submit a review of their experience to Hostelz.com.',
    ],

    'reviews' => [
        'pageDescription' => "These are all of the official Hostelz.com Reviewer-submitted reviews that are currently posted to your accommodation's listing page.  You can click on one and add an official \"response from the owner\" if you wish.",
    ],

    'bookings' => [
        'pageDescription' => '<b>Booking System Note:</b> Hostelz.com is an independent hostels guide and is not affiliated with any other website or booking company.  We do have contracts with several booking websites and to link to their booking systems (including Hostelworld, HostelBookers, and HostelsClub).  So bookings for your accommodation on Hostelz.com may be provided through some or all of those booking systems.', // to do: add booking sign-up link.
    ],

    'sticker' => [
        'pageDescription' => 'Your ratings have earned an award.  We\'ll send it to you at this address. Be sure to put the manager or owner\'s name as the recipient.',
        'adhesiveType' => 'Adhesive Type',
        'stickerSize' => 'Sticker Size',
    ],
];
