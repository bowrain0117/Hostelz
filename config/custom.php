<?php

/* This file is something we added for other config settings that are used internally in our own app. */

return [

    // Misc

    'hostelzMailUser' => 'hostelz_mail',
    'hostelzMailPassword' => 'fF348FFuq38q',

    'emailVerificationSalt' => 'F#(8ge11IGsd8*',
    'mgmtSignupSalt' => 'sloppyjoe',

    // Version of these CSS & Javascript files to force them to reload in the user's browser.
    'assetsVersion' => env('ASSETS_VERSION', 2.0101),
    'bookingSearchCriteriaCookie' => env('BOOKING_SEARCH_COOKIE', 'bookingSearch31'), // using 3 because the old website one was incompatible (bookingSearch3/cityPageSettings2)
    'citySearchCriteriaCookie' => env('LISTINGS_SEARCH_COOKIE', 'cityPageSettings2'), // using 3 because the old website one was incompatible (bookingSearch3/cityPageSettings2)
    'originationReferrerCookie' => 'origination',
    'affiliateIdCookie' => 'usrc',

    // Debug

    'debugOutput' => env('DEBUG_OUTPUT'),
    'debugOutputQueries' => env('DEBUG_OUTPUT_QUERIES'), // outputs to debugOutput()
    'debugOutputQueriesExplain' => env('DEBUG_OUTPUT_QUERIES_EXPLAIN'),
    'pageCacheDisableSaving' => env('PAGE_CACHE_DISABLE_SAVING'), // we don't want to save dev pages in the cache (which is shared with the live site)
    'pageCacheDisableClearing' => env('PAGE_CACHE_DISABLE_CLEARING'), // the dev site needs to still be able to clear the live site's cache when something is updated
    'browserCacheHeadersDisabled' => env('BROWSER_CACHE_HEADERS_DISABLED'),
    'eventLogDisabled' => env('EVENT_LOG_DISABLED'),
    'eventLogVerbose' => env('EVENT_LOG_VERBOSE'), // outputs to debugOutput()
    'emailPretend' => env('EMAILER_PRETEND'),
    'emailerAllEmailsTo' => env('EMAILER_ALL_EMAILS_TO'),
    'billingSandbox' => env('BILLING_SANDBOX'),

    // Domain

    'thisStaticDomain' => env('THIS_STATIC_DOMAIN'),
    'thisDynamicDomain' => env('THIS_DYNAMIC_DOMAIN'),
    'globalDomain' => env('GLOBAL_DOMAIN'),
    'adminGroupRoute' => env('USE_HTTPS_FOR_ADMIN_SECTION', true) ? 'https' : '',

    // Booking

    'bookingTestMode' => env('BOOKING_TEST_MODE'),
    'bookingUseAyncConsoleCommands' => env('BOOKING_USE_ASYNC_CONSOLE_COMMANDS'),
    'bookingAvailabilityCache' => env('BOOKING_AVAILABILITY_CACHE'),
    'bookingDebugOutput' => env('BOOKING_DEBUG_OUTPUT'),

    // File Paths

    'userRoot' => env('USER_ROOT'),
    'devRoot' => env('DEV_ROOT'),
    'productionRoot' => env('PRODUCTION_ROOT'),
    'gulpExecutable' => env('GULP_EXECUTABLE'),
    'gulpTemp' => env('GULP_TEMP'),

    // Domain

    'websiteDisplayName' => env('APP_NAME', 'Hostelz.com'),
    'domainName' => env('GLOBAL_DOMAIN', 'hostelz.com'),
    // 'publicStaticDomain' => 'www.hostelz.com', // always the same regardless of dev, local, etc.
    // 'publicDynamicDomain' => 'secure.hostelz.com',
    'publicStaticDomain' => 'http://localhost',
    'publicDynamicDomain' => 'http://localhost',

    // APIs

    'googleApiKey' => [
        // See https://console.cloud.google.com/apis/credentials?authuser=3&folder=&organizationId=&project=espai-kreativ-hostelz
        // (login as support@hostelz.com)

        // Used for things users/clients can see (restricted by referrer of "*.hostelz.com/*"
        'clientSide' => 'AIzaSyBSd4q-v5vOAjLTobz9ZH7gcNrGinN71bg',

        // Used for server-side access (not restricted by referrer because there isn't a referrer when we call it from the server)
        'serverSide' => 'AIzaSyBZaHzwP9ywYUbEHJjkQYWTWJ4SKwal7aE',
    ],

    'paypalUsername' => 'support_api1.hostelz.com',
    'paypalSignature' => 'AnuRngthAcJr76fiAwMxC8IZp7LjACNeyE9UBlTPmxKUsiriFjUXmQnZ',

    'captchaPublicKey' => '6LdmbAoTAAAAAFcSRFFjI4KaJcHmbGXloTAACMvZ',
    'captchaPrivateKey' => '6LdmbAoTAAAAANC9Xwi6FbE-TiVlvHS4KgnYjTSh',

    'awsKey' => env('AWS_KEY'), // (Note: I deleted our AWS access keys since we weren't using them currently. -- David, 2018-03)
    'awsSecret' => env('AWS_SECRET'),

    'mozAccessID' => 'mozscape-cde2c9568',
    'mozSecretKey' => '644c2da7172ecc410ee57f86512d3b54',

    'emailhunterKey' => 'ae1c7659e6572c33e01f61db4f3a8ef7b6ff1733',

    'tinEye' => [
        'privateKey' => 'QcK2pIcYeien995*VBx4dYX3eFtUifNU7*q0LGip',
        'publicKey' => '2ycV1W09Oozs,sNBWH5o',
    ],

    // Email Addresses

    'userSupportEmail' => 'userhelp@hostelz.com',
    'listingSupportEmail' => 'listingsupport@hostelz.com',
    'adminEmail' => 'supportstaff@hostelz.com',
    'adminEmailUserID' => 1,
    'pressSupportEmail' => 'admin@hostelz.com',

    //

    'limitLiveListingsHourlyMaintenanceTasks' => env('LIMIT_LIVE_LISTINGS_HOURLY_MAINTENANCE_TASKS', 40),

    //  HTTP

    'httpUseProxy' => env('HTTP_USE_PROXY', false),
    'httpProxy' => env('HTTP_PROXY', ''),

    // Password
    'devAccess' => env('DEV_PASSWORD'),

    'blockIpList' => explode(',', env('BLOCK_IP_LIST', '')),

    'blockedCountriesCode' => explode(',', env('BLOCKED_COUNTRIES_CODE', '')),

    'minPriceCoefficient' => env('MIN_PRICE_COEFFICIENT', 0.5),

    'page_cache_time' => env('PAGE_CACHE_TIME', env('PAGE_CACHE_DISABLE_SAVING') ? 0 : now()->addDay()),
];
