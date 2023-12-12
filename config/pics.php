<?php

use App\Models\HostelsChain;
use App\Models\Listing\Listing;

return [
    'picsSpacesPath' => env('PICS_SPACES_PATH', '/mnt/storage1/hostelz/pics'),

    'hostelsChainOptions' => [
        'originals' => [],
        'thumbnails' => [
            'saveAsFormat' => 'jpg',
            'outputQuality' => 75,
            'absoluteWidth' => HostelsChain::IMAGE_WIDTH,
            'absoluteHeight' => HostelsChain::IMAGE_HEIGHT,
            'cropVerticalPositionRatio' => 0.2,
        ],
        'medium' => [
            'saveAsFormat' => 'jpg',
            'outputQuality' => 75,
            'absoluteWidth' => HostelsChain::IMG_MEDIUM_WIDTH,
            'absoluteHeight' => HostelsChain::IMG_MEDIUM_HEIGHT,
            'cropVerticalPositionRatio' => 0.2,
        ],
        'webp_thumbnails' => [
            'saveAsFormat' => 'webp',
            'outputQuality' => 75,
            'absoluteWidth' => HostelsChain::IMAGE_WIDTH,
            'absoluteHeight' => HostelsChain::IMAGE_WIDTH,
            'cropVerticalPositionRatio' => 0.2,
        ],
        'webp_medium' => [
            'saveAsFormat' => 'webp',
            'outputQuality' => 75,
            'absoluteWidth' => HostelsChain::IMG_MEDIUM_WIDTH,
            'absoluteHeight' => HostelsChain::IMG_MEDIUM_HEIGHT,
            'cropVerticalPositionRatio' => 0.2,
        ],
        'tiny' => [
            'saveAsFormat' => 'jpg',
            'outputQuality' => 75,
            'absoluteWidth' => 10,
            'absoluteHeight' => 0,
            'cropVerticalPositionRatio' => 0.2,
            'blur' => 1,
        ],
    ],

    'importedOptions' => [
        'big' => [
            'saveAsFormat' => 'jpg',
            'outputQuality' => 75,
            'maxWidth' => Listing::BIG_PIC_MAX_WIDTH,
            'maxHeight' => Listing::BIG_PIC_MAX_HEIGHT,
            'storageType' => 'local',
        ],
        'webp_big' => [
            'saveAsFormat' => 'webp',
            'outputQuality' => 65,
            'maxWidth' => Listing::BIG_PIC_MAX_WIDTH,
            'maxHeight' => Listing::BIG_PIC_MAX_HEIGHT,
            'storageType' => 'local',
        ],
        'thumbnails' => [
            'saveAsFormat' => 'jpg',
            'outputQuality' => 75,
            'absoluteWidth' => Listing::IMG_THUMBNAIL_WIDTH,
            'absoluteHeight' => Listing::IMG_THUMBNAIL_HEIGHT,
            'cropVerticalPositionRatio' => 0.2,
            'storageType' => 'local',
        ],
        'webp_thumbnails' => [
            'saveAsFormat' => 'webp',
            'outputQuality' => 75,
            'absoluteWidth' => Listing::IMG_THUMBNAIL_WIDTH,
            'absoluteHeight' => Listing::IMG_THUMBNAIL_HEIGHT,
            'cropVerticalPositionRatio' => 0.2,
            'storageType' => 'local',
        ],
        'tiny' => [
            'saveAsFormat' => 'jpg',
            'outputQuality' => 85,
            'absoluteWidth' => 10,
            'absoluteHeight' => 0,
            'cropVerticalPositionRatio' => 0.2,
            'blur' => 1,
            'storageType' => 'local',
        ],
    ],

];
