<?php

return [
    '_translationInfo' => [],
    /*---- Blog Categories and Articles Meta Info is in articles.php ---*/

    /*----Meta Value
    :year = current year e.g. 2023
    :month = current month e.g Jul

    :count = total count of properties in city
    :hostelCount = total count of hostels only in city
    :TotalHostelCount = total count of all hostels in the world (in all cities)
    :hostelchaincount = count of properties in a hostel chain

    :city = name of city
    :area = name of Country
    :country = name of country
    :continent = name of continent
    :lowestDormPrice = lowest price for dorm
    :hostelName = name of single hostel and property if not hostel
    :hostelchain = name of single hostel chain
    ---*/

    /*----Index Blade---*/
    'IndexMetaTitle' => 'Smart Hostel Price Comparison :year - :TotalHostelCount hostels worldwide',
    'IndexMetaDescription' => 'We help you save money on Hostels. Compare Prices from :TotalHostelCount Hostels at Hostelworld and Booking.com with 1 Click.',

    /*----Continents Blade---*/
    'ContinentsMetaTitle' => 'Hostels in :area in :year (with Price Comparison)',
    'ContinentsMetaDescription' => 'Save money on Hostels and compare all BEST and CHEAP Hostels in :area in :year (with all countries). With Backpacker, Luxury, Youth and Party Hostels.',
    'ContinentsCitiesMetaTitle' => 'All Hostels in the World - Hostel Price Comparison',
    'ContinentsCitiesMetaDescription' => 'Smart Hostel Price Comparison for all BEST and CHEAP Hostels from all over the world.',

    /*----Cities/ Country and Region Blade---*/
    'CitiesMetaTitle' => 'Hostels in :area in :year compared (Smart Hostels Price Comparison)',
    'CitiesMetaDescription' => 'Compare all BEST and CHEAPEST Hostels in :area in :year (with all cities). Unique guide to backpacking in :area for Solo-Travel. (Save up to 17%)',

    /*----City Blade---*/
    'CityMetaTitle' => 'Hostels in :city from $:lowestDormPrice/night 😎 (Smart Price Comparison)',
    'CityMetaTitleBackpackers' => 'Hostels in :city for Backpacker - from $:lowestDormPrice/night 😎',
    'CityMetaTitle1Hostel' => 'Hostels in :city from $:lowestDormPrice/night 😎 (Smart Price Comparison)',
    'CityMetaTitleNoHostel' => 'Hostels in :city from $:lowestDormPrice/night 😎 (Smart Price Comparison)',
    'CityMetaTitleFallback' => 'Hostels in :city from $:lowestDormPrice/night 😎 (Smart Price Comparison)',

    'CityMetaDescription' => 'Find best Prices for Hostels in :city, :area with Hostelz.com! 🌍 Starting at just $:lowestDormPrice/night. Make smart choices with our comprehensive price comparison and genuine reviews.',
    'CityMetaDescriptionBackpackers' => 'Price Comparison for :hostelCount Backpacker Hostels in :city, :area. Hostels Prices in :city from only $:lowestDormPrice. (Save up to 17%)',
    'CityMetaDescription1Hostel' => 'FREE Hostel Price Comparison for Hostels in :city, :area (with Reviews). Hostel Prices in :city start from $:lowestDormPrice. [EXTRA: Save up to 14%]',
    'CityMetaDescriptionNoHostel' => 'Find best Prices for Hostels in :city, :area with Hostelz.com! 🌍 Starting at just $:lowestDormPrice/night. Make smart choices with our comprehensive price comparison and genuine reviews.',
    'CityMetaMetaDescriptionFallback' => 'Price Comparison for all BEST and CHEAP Hostels in :city (with Honest Reviews). Hostel Prices in :city start from only $:lowestDormPrice. [EXTRA: Save up to 14%]',

    /*----District Blade---*/
    'DistrictMetaTitle' => ':title :year (Price Comparison from $:minPrice)',
    'DistrictMetaDescription' => 'All Hostels in :districtName in :cityName with Ratings and Price Comparison. Best Hostels for Solo-Traveler and Backpacker in :cityName.',

    /*----Category Family Blade---*/
    'CategoryFamilyTitle' => 'Family-Friendly Hostels in :city in :year',
    'CategoryFamilyMetaTitle' => 'Family-Friendly Hostels in :city in :year (An Insider\'s Guide with Prices)',
    'CategoryFamilyMetaDescription' => 'List of BEST Family Hostels in :city with Games and Family Rooms. Detailed reviews, and location map. BONUS: Price Comparison!',

    /*----Category YouthHostels Blade---*/
    'CategoryYouthTitle' => 'Youth Hostels in :city in :year (for Families and Young Traveler)',
    'CategoryYouthMetaTitle' => 'Youth Hostels in :city in :year (for Families and Young Traveler)',
    'CategoryYouthMetaDescription' => 'List of TOP Youth Hostels in :city. For Groups, Schools, Students. With Big Dorms and cheap prices :year. Central Location. BONUS: Price Comparison!',

    /*----Listing Blade---*/
    'ListingMetaTitle' => ':hostelName, :city - Is it Worth it? NEW Reviews :year',
    'ListingMetaDescription' => 'Complete and Genuine Reviews of :hostelName, :city (including video and photos). Is it worth it? Compare Prices for :hostelName with Hostelz.com for a cheaper Reservation (save up to 17%)',

    /*----BLOG ARTICLES---*/
    'BlogMetaTitle' => 'Hostelz Blog :year - Swiss Knife to Budget Travel, Hostels and Solo-Travel',
    'BlogMetaDescription' => 'Best Hostel Blog in the world. Learn everything you need to know about Hostels, Backpacking Solo and Budget Travel. EXTRA: Exclusive Member Content and Discounts',

    /*----BEST HOSTELS LANDING PAGES---*/
    'BestHostelsMetaTitle' => '7 BEST Hostels in :city in :year (from $:lowestDormPrice)',
    'BestHostelsMetaText' => 'List of 7 TOP-RATED Hostels in :city in :year. Prices start from only $:lowestDormPrice.',

    /*----Hostel Chains---*/
    'HostelChainsMetaTitle' => 'Best Hostel Chains in the World :year - by Hostelz.com',
    'HostelChainsMetaText' => 'Overview of ALL BEST Hostel Chains in the World (Updated :month, :year). Including Prices, Map, and Photos.',

    'HostelChainSingleMetaTitle' => ':hostelchain in Review :year - Is it worth it? Hostelz.com',
    'HostelChainSingleMetaText' => 'Review of :hostelchain, the popular hostel chain with :hostelchaincount properties. Is it worth it? Detailed Overview of :hostelchain :year. EXTRA: Price List',

    /*----Regular Pages - about, contact, faq---*/
    'AboutMetaTitle' => 'About Hostelz.com - Hostel Price Comparison :year',
    'AboutMetaDescription' => 'What is the best Hostel Price Comparison site :year? Hostelz.com is the best way to find cheaper prices for Hostels - since 2002.',
    'ContactMetaTitle' => 'Contact Hostelz.com - Biggest Hostels website worldwide',
    'ContactMetaDescription' => 'Contact Hostelz.com, the biggest hostel database in the world. Hostel Comparison site and Hostel Traveler Community',
    'FAQMetaTitle' => 'FAQ Hostelz.com - Most Common Questions about Hostels Answered :year',
    'FAQMetaDescription' => 'Any questions about Hostels? Find all Answers with Hostelz.com. Biggest Hostel Website worldwide :year. BONUS: Exclusive Hostel Guides',
    'PrivacyPolicyMetaTitle' => 'Privacy Policy - Hostelz.com',
    'PrivacyPolicyMetaDescription' => 'Privacy Policy by Hostelz.com, Hostel Community and Price Comparison Website for Backpackers.',
    'TermsConditionsMetaTitle' => 'Terms & Conditions - Hostelz.com',
    'TermsConditionsMetaDescription' => '',
    'HowItWorksMetaTitle' => 'How it works - Hostelz.com',
    'HowItWorksMetaDescription' => 'How can you compare hostel prices :year? Hostelz.com is your solution. We help you save money on hostels. Save up to 17%. Here is how it works.',

    /*----Login and Sign Up---*/
    'SignUpMetaTitle' => 'Secure Login to Access Exclusive Member Benefits at Hostelz.com',
    'SignUpMetaDescription' => 'Create a Hostelz Account and get access to exclusive hostel discounts, and custom hostel recommendations. Hostel Price Comparison by Hostelz.com',

    'SignUpForgetPWTitle' => 'Forget Login - Hostelz.com',
    'SignUpForgetPWDescription' => '',

    'SignUpResetTitle' => 'Reset Login - Hostelz.com',
    'SignUpResetDescription' => '',

    /*----Comparison---*/
    'ComparisonMetaTitle' => 'Compare hostels - Hostelz.com',
    'ComparisonMetaDescription' => 'Compare any Hostels in a few clicks - per Price and Facilities! This is the best tool to find the Perfect Hostel for You!',
];
