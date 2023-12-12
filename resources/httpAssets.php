<?php

/* This defines assets for use with our Lib\HttpAsset class. */

use App\Models\Languages;

$USE_LOCAL_ASSETS = App::environment('local');

$return = [

    'assetsVersion' => config('custom.assetsVersion'),

    'assets' => [
        // (We prefer to host most packages locally rather than on a CDN because we set the browse cache header expiration so that everything only gets loaded once every few days.)

        // [ 'path', 'dependencies', 'type' (optional, can usually be determined automatically) ]

        // Bootstrap
        // (the Bootstrap CSS is now included in global.css)
        // Could use a CDN for this, but too much trouble to make sure the JS version always matches the CSS (which we compile a cusotm version of on our own server.
        'bootstrap-js' => ['path' => '/vendor/bootstrap.min.js', 'dependencies' => ['jquery']],

        // Other (Note: We're avoiding using googleapis.com because it's blocked in China!)

        'jquery' => ['path' => '/vendor/jquery.min.js'],

        'jquery-ui' => ['path' => '/vendor/jquery-ui.min.js', 'dependencies' => ['jquery', 'jquery-ui-theme']],

        'jquery-ui-theme' => ['path' => '/vendor/jquery-ui-cupertino.min.css', 'dependencies' => ['jquery', 'jquery-ui'], 'location' => 'bottom'],

        'fontAwesome' => ['path' => $USE_LOCAL_ASSETS ? '/vendor/font-awesome/css/font-awesome.min.css' :
            '//maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css', // this version should match the local-dev one installed with composer (see package.json)
            'location' => 'bottom',
        ],
        'fontAwesome5' => ['path' => '//use.fontawesome.com/releases/v5.8.1/css/all.css', 'location' => 'bottom'],
        'jquery-mousewheel' => ['path' => '/vendor/jquery.mousewheel.min.js'],
        'inlineEdit' => ['path' => '/vendor/jquery.inlineedit.js', 'dependencies' => ['jquery']],

        'bootbox' => ['path' => '/vendor/bootbox.js', 'dependencies' => ['jquery']],

        'tinymce' => ['path' => 'https://cdn.tiny.cloud/1/p50y1gggq6kwvzuew7dcy8qbma491dt85icr2zsnoyyhu1rm/tinymce/6/tinymce.min.js'],

        // Two options for autocomplete / advanced-input/select: Use either 'autocomplete' or 'select2-bootstrap'.
        'autocomplete' => ['path' => '/vendor/jquery.autocomplete.min.js', 'dependencies' => ['jquery']],
        'select2-css' => ['path' => $USE_LOCAL_ASSETS ? '/vendor/select2.css' :
            '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.css', 'dependencies' => ['jquery'],
        ],
        'select2' => ['path' => $USE_LOCAL_ASSETS ? '/vendor/select2.min.js' :
            '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2.min.js', 'dependencies' => ['select2-css'],
        ],
        // Bootstrap-compatible version of Select2 (use this one)
        'select2-bootstrap' => ['path' => $USE_LOCAL_ASSETS ? '/vendor/select2-bootstrap.css' :
            '//cdnjs.cloudflare.com/ajax/libs/select2/3.5.2/select2-bootstrap.min.css', 'dependencies' => ['select2'],
        ],

        'chart.js' => ['path' => '/vendor/Chart.min.js'],

        'libs.js' => ['path' => '/js/libs.js', 'dependencies' => ['jquery']],
        //        'main.js' => [ 'path' => '/js/main.js', 'dependencies' => [ 'jquery' ] ],
        'index.js' => ['path' => '/js/index.js', 'dependencies' => ['jquery']],

        'indexMain.js' => ['path' => '/js/indexMain.js', 'dependencies' => ['jquery', 'booking-options.js', 'booking-main.js']], // todo

        'documentation.js' => ['path' => '/js/documentation.js', 'dependencies' => ['jquery', 'formHandler-WYSIWYG.js']],

        // FancyBox
        'fancybox.css' => ['path' => '/vendor/fancybox/css/jquery.fancybox.css'],
        'fancybox' => ['path' => '/vendor/fancybox/js/jquery.fancybox.pack.js', 'dependencies' => ['jquery', 'jquery-mousewheel', 'fancybox.css']],
        // Optional FancyBox Helpers
        'fancybox-thumbs.css' => ['path' => '/vendor/fancybox/helpers/jquery.fancybox-thumbs.css'],
        'fancybox-thumbs' => ['path' => '/vendor/fancybox/helpers/jquery.fancybox-thumbs.js', 'dependencies' => ['fancybox-thumbs.css']],
        'fancybox-buttons.css' => ['path' => '/vendor/fancybox/helpers/jquery.fancybox-buttons.css'],
        'fancybox-buttons' => ['path' => '/vendor/fancybox/helpers/jquery.fancybox-buttons.js', 'dependencies' => ['fancybox-buttons.css']],

        // Pannellum
        'pannellum.css' => ['path' => '/vendor/pannellum.css'],
        'pannellum' => ['path' => '/vendor/pannellum.js', 'dependencies' => ['pannellum.css']],

        // Our Own Global Assets
        'global.css' => ['path' => routeURL('generated-css', 'global')], // (includes bootstrap)

        'new-styles.css' => ['path' => routeURL('generated-css', 'new-styles')],
        'custom.css' => ['path' => routeURL('generated-css', 'custom')],

        'global.js' => ['path' => '/js/global.js', 'dependencies' => ['jquery']],
        'formHandler.js' => ['path' => routeURL('js', ['formHandler']), 'dependencies' => ['jquery']],
        'formHandler-WYSIWYG.js' => ['path' => routeURL('js', ['formHandler-WYSIWYG']), 'dependencies' => ['formHandler.js', 'tinymce']],

        // Our own assets for certain pages
        // (we use 'dependencies' => [ 'global.css' ] so that these are inserted *after* global.css, not before)
        'listingDisplay.css' => ['path' => routeURL('generated-css', 'listingDisplay'), 'dependencies' => ['global.css']],
        'listingDisplay.js' => ['path' => routeURL('js', 'listingDisplay'), 'dependencies' => ['global.js']],

        'articles.css' => ['path' => routeURL('generated-css', 'articles'), 'dependencies' => ['global.css']],
        'articles.js' => ['path' => routeURL('js', 'articles'), 'dependencies' => ['global.js', 'booking-main.js']],

        'booking.css' => ['path' => routeURL('generated-css', 'booking'), 'dependencies' => []],
        'booking.js' => ['path' => routeURL('js', 'booking'), 'dependencies' => ['global.js', 'jquery-ui', 'libs.js']],

        'booking-options.js' => ['path' => routeURL('js', 'booking-options'), 'dependencies' => ['global.js', 'jquery-ui', 'libs.js']],
        'booking-main.js' => ['path' => '/js/booking-main.js', 'dependencies' => ['booking-options.js']],

        'cities.css' => ['path' => routeURL('generated-css', 'cities'), 'dependencies' => []],
        'cities.js' => ['path' => routeURL('js', 'cities'), 'dependencies' => ['global.js', 'booking-main.js']],

        'hostelChains.js' => ['path' => routeURL('js', 'hostelChains'), 'dependencies' => ['global.js', 'booking-main.js']],

        'city.css' => ['path' => routeURL('generated-css', 'city'), 'dependencies' => []],
        'city.js' => ['path' => routeURL('js', 'city'), 'dependencies' => ['global.js', 'booking-main.js']],

        'comparison.js' => ['path' => routeURL('js', 'comparison'), 'dependencies' => ['global.js', 'booking-main.js']],

        'wishlistIndexPage.js' => ['path' => routeURL('js', 'wishlistIndexPage'), 'dependencies' => ['jquery'], 'is_module' => true],
        'wishlistMain.js' => ['path' => routeURL('js', 'wishlistMain'), 'dependencies' => ['jquery'], 'is_module' => true],

        //  admin
        'admin.js' => ['path' => '/js/admin.js', 'dependencies' => ['global.js']],
        'translation.js' => ['path' => routeURL('js', 'translation'), 'dependencies' => ['global.js']],

        'continent.css' => ['path' => routeURL('generated-css', 'continent'), 'dependencies' => ['global.css']],
        'index.css' => ['path' => routeURL('generated-css', 'index'), 'dependencies' => ['global.css']],
        'staff.css' => ['path' => routeURL('generated-css', 'staff'), 'dependencies' => ['global.css']],
    ],
];

if (Languages::currentCode() != 'en') {
    // (Have to make sure that the main script is included *before* the il8n one, so the il8n requires the actual plupload.)
    // (Hopefully there are plupload translations of all of our langues, if not we should just revert to the English one.)
    $return['assets']['plupload'] = ['path' => '/vendor/plupload/i18n/' . Languages::currentCode() . '.js', 'dependencies' => ['plupload-actual']];
    $return['assets']['plupload-actual'] = ['path' => '/vendor/plupload/plupload.full.min.js'];
} else {
    $return['assets']['plupload'] = ['path' => '/vendor/plupload/plupload.full.min.js'];
}

return $return;
