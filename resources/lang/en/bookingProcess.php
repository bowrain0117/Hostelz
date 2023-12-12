<?php

return [
    '_translationInfo' => [
        'except' => [],
        // 'instructions' => '...'
    ],

    'searchCriteria' => [
        'startDate' => 'Arrival Date',
        'nights' => 'Nights',
        'roomType' => 'Room Type',
        'people' => 'Guests',
        'rooms' => 'Rooms',
        'privateroom' => 'Private Room',
        'dormbed' => 'Dorm Bed',
        'groupType' => 'Group Type',
        'groupAgeRanges' => 'Age Ranges',
        'currency' => 'Currency',
        'clear' => 'CLEAR',
        'today' => 'TODAY',
        'save' => 'SAVE',
        'morefilter' => 'More Filter',
        'options' => [
            'groupType' => ['friends' => 'Friends', 'juniorSchool' => 'Junior/Primary School', 'highSchool' => 'High/Secondary School', 'college' => 'College/University',
                'business' => 'Business Trip', 'party' => 'Stag/Hen/Bachelor Party', 'sports' => 'Sports Group', 'cultural' => 'Cultural Group', ],
            'groupAgeRanges' => ['0to12' => '0-12', '13to17' => '13-17', '18to21' => '18-21', '22to35' => '22-35', '36to49' => '36-49', '50plus' => '50+'],
        ],
    ],

    // * Search Criteria Form *
    'FindAndCompare' => 'Instantly check all of the<br>booking websites at once!',
    'CheckAvailability' => 'Check Availability',
    'ChooseYourDates1' => 'Hostelz.com Online Booking Price Comparison &mdash; Search Every Booking Website at Once',
    'ChooseYourDates2' => 'Choose your dates to get prices',
    'SearchingAll' => 'Hold on, comparing for you the best prices right now...',
    'Checking' => 'Checking...',
    'ComparePrices' => 'Compare Hostel Prices for :city',
    'SorryNoAvailabilityDorms' => 'Sorry, :listingname has no available dorm beds for your dates.',
    'SorryNoAvailabilityRooms' => 'Sorry, :listingname has no available private rooms for your dates.',
    'PleaseDifferentDates' => 'Try different dates or',
    'NoPrivateAvailability' => 'Check Dorm Beds at :listingname',
    'NoDormAvailability' => 'Check Private Rooms at :listingname',
    'SearchAllOfCity' => 'Check more hostels in :city for availability',
    'NotOfferOnlineBooking' => 'This accommodation currently does not offer online booking in any of the major hostel booking systems. You can find their contact details further below.',
    'NoPrivateAvailabilityCity' => 'No Private Rooms available for your dates. Check here for Dorm Beds',
    'NoDormAvailabilityCity' => 'No Dorm Beds available for your dates. Check here for Private Rooms',
    // * Errors / Warnings *
    'errors' => [
        'misc' => 'An error occurred and your request could not be completed. Please try your booking request again.',
        'pastDate' => 'The arrival date must be a future date. Please choose a later date.',
    ],

    'warnings' => [
        'partialAvail' => 'Due to high demand, some of the nights you requested are not available for your chosen room for your number of people or rooms.<br> But the list above shows what is available for you to book now.',
        'tooManyPeopleForRooms' => 'The number of rooms you have selected to book may not be sufficient for the number of people.',
    ],

    // * City Listings List *
    'FoundBedsFrom' => 'Beds Available from',
    'FoundRoomsFrom' => 'Rooms Available from',
    'from' => 'from',

    // * Room Choice *
    'DormBedsResultsTitle' => 'Available Dorm Beds',
    'PrivateRoomsResultsTitle' => 'Available Private Rooms',
    'onlyRoomsLeft' => 'Only 1 room left|Only :numberAvailable rooms left',
    'onlyBedsLeft' => 'Only 1 bed left|Only :numberAvailable beds left',
    'onlyRoomsAvailable' => 'Only 1 room available|Only :numberAvailable rooms available',
    'onlyBedsAvailable' => 'Only 1 bed available|Only :numberAvailable beds available',
    'AvailableOnlyNights' => 'Available for only 1 night.|Available only :numNights nights.',
    'AvailableOnlyPeople' => 'Availability for only 1 person.|Availability for only :numPeople people.',
    'AvailableOnlyRooms' => 'Availability for only 1 person.|Availability for only :numPeople people.',
    'MinNights' => 'Min :numNights nights stay',
    'bookSoon' => 'Book Soon',
    'BookNow' => 'Book Now',
    'Book' => 'Book',
    'noSystemAvailability' => 'No Availability',

    // * Misc *
    'Booking' => 'Booking',
    'OnlyPartialAvail' => 'Only partial availability',
    'ensuite' => 'Ensuite (bathroom in the room)',
    'YouSave' => 'You save',
    'BestPrice' => 'Best Price',
    'TotalBestPrice' => 'Total Best Price',
    'PerBedNight' => 'per bed/night',
    'PerRoomNight' => 'per room/night',

    // *Sidebar *
    'BookingSidebarTitle' => 'How to book...',
    'BookingSidebarText1' => ':hostelName is listed at the following booking sites:',
    'BookingSidebarText2' => 'We just compared for you all availability and prices!',
    'BookingSidebarText3' => 'So get the best deal, save money and travel longer.',

    'BookingSidebarText1Hostel1' => ':hostelName is only listed at the following booking site:',
    'BookingSidebarText2Hostel1' => 'It is the only way to book :hostelName.',
    'BookingSidebarText3Hostel1' => 'So get the best deal, save money and travel longer.',

    // *linkRedirect*
    'linkRedirect' => [
        'Connection' => 'Connecting you to :name',
        'SaveMoney' => 'Please finalize your reservation',
        'Remember' => 'Remember to come back to compare hostels and find the best price.',
    ],

    // 'MinNights' => 'Minimum stay :numNights nights.',
    // 	'FindDormBeds' => 'Find Dorm Beds',
    // 	'FindPrivateRooms' => 'Find Private Rooms',
    // 	'FreeBooking' => 'FREE BOOKING - No Booking Fee!',
    // 	'RoomTypeNotAvail' => '<b>:roomtype</b> are not available.',
    // 	'Book' => 'Book :roomtype',
    // 	'pastDateError' => 'Error: The arrival date must be a future date.  Please choose a later date.',
    // 	'invalidDateError' => 'Error: Invalid date.',
    // 	'PersonRooms' => ':people Person Rooms',
    // 	'BookingSystem' => 'Booking system:',
    // 	'NoBookingFee' => 'No Booking Fee!',

    /* BookingRequest */
    // 	'Date' => 'Date',
    // 	'RoomType' => 'Room Type',
    // 	'People' => 'People',
    // 	'Price' => 'Price',
    // 	'Total' => 'TOTAL:',
    // 	'Deposit' => ':percent% Deposit / Downpayment:',
    // 	'ServiceCharge' => ':system\'s Service Charge:',
    // 	'ServiceChargeNone' => 'Booking Fee: None',
    // 	'DueNow' => 'TOTAL DUE NOW:',
    // 	'DueNowNoServiceCharge' => ':percent% DEPOSIT DUE NOW:',
    // 	'RemainingBalance' => '(The remaining balance of :amount is due on arrival.)',
    // 	'Note' => 'Note:',
    // 	'PleaseEnter' => 'Please enter a value for \':fieldName\'.',
    // 	'NotYou' => '(not you? <a>logout</a>)',
    // 	'HaveAnAccount' => '(have a hostelz.com account? <a>login</a>)',
    // 	'firstName' => 'First Name',
    // 	'lastName' => 'Last Name',
    // 	'email' => 'Email',
    // 	'nationality' => 'Nationality',
    // 	'arrivalTime' => 'Estimated Arrival Time',
    // 	'gender' => 'Gender',
    // 	'Male' => 'Male',
    // 	'Female' => 'Female',
    // 	'MaleAndFemale' => 'Male & Female',
    // 	'phone' => 'Phone',
    // 	'ccName' => 'Card Holder\'s Name (if different)',
    // 	'ccNumber' => 'Card Number',
    // 	'ccType' => 'Card Type',
    // 	'ccExpiration' => 'Expiration',
    // 	'ccCVV' => 'Security Code (CVV)',
    // 	'ccIssueNum' => 'Issue Number',
    // 	'ccStartDate' => 'Start Date',
    // 	'ChargeAmount' => 'Charge Amount:',
    // 	'RemainingBalanceBottom' => 'The remaining balance of :amount is due on arrival (plus taxes if applicable), and may be paid in cash or any payment method accepted by the accommodation.  ',
    // 	'ConfirmPayment' => 'Confirm Booking',
    // 	'WhyHostelz' => 'Why always book with Hostelz.com?',
    // 	'WhyHostelzText' => '&bull; Thanks to our online booking price comparison system, you will always get <b><a>the lowest price available anywhere</a></b> when you make your bookings through Hostelz.com.<p>&bull; Earn <b>reward points</b> for every booking you make.<p>&bull; <b>Online since 2002</b>, Hostelz.com is the most trusted website for hostel bookings and reviews online.',
    // 	'WhatIfCancel' => 'What if I need to cancel my booking later?',
    // 	'WhatIfCancelAnswer' => 'Don\'t worry, you can still cancel your booking later if your plans change.  You would just need to cancel with sufficient advance notice (typically 24-48 hours, unless otherwise stated in the accommodation\'s terms below).  The :depositPercent% booking deposit is non-refundable, but you won\'t be charged any other cancellation fees.',
    // 	'BookingInfo' => ':listingName Booking Information',
    // 	'TermsAndConditions' => 'Terms & Conditions',
    // 	'ThereAreTheirTerms' => 'Hostelz.com searches multiple booking systems to find you the best price.  This booking is being processed using the :system booking system.  The following are their terms and conditions.',
    // 	'error_invalidDate' => 'The date is invalid.',
    // 	'error_noAvail' => 'Sorry, this room is no longer available for the dates selected.',
    // 	'warning_sessionExpired' => 'Session expired.  Please submit your request again.',
    // 	'warning_invalidEmail' => 'Invalid email address.',
    // 	'warning_invalidPhone' => 'Invalid phone number.',
    // 	'error_invalidExpiration' => 'Invalid credit card expiration date.',
    // 	'warning_invalidCC' => 'The credit card transaction could not be processed.  Please ensure all of your credit card information is correct.',
    // 	'Success' => 'Success!',
    // 	'BookingCompleted' => 'Your booking has been completed.',
    // 	'BookingID' => 'Booking ID: ":system :bookingID" ',
    // 	'ConfirmationEmail' => 'You will receive a confirmation email from :system sent to you at <b>:email</b>.'
];
