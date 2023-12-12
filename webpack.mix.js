const mix = require('laravel-mix');

mix
    // SASS

    .sass('resources/sass/global.scss', 'public/generated-css/global.css')
    .sass('resources/sass/index.scss', 'public/generated-css/index.css')
    .sass('resources/sass/continent.scss', 'public/generated-css/continent.css')
    .sass('resources/sass/cities.scss', 'public/generated-css/cities.css')
    .sass('resources/sass/city.scss', 'public/generated-css/city.css')
    .sass('resources/sass/listingDisplay.scss', 'public/generated-css/listingDisplay.css')
    .sass('resources/sass/booking.scss', 'public/generated-css/booking.css')
    .sass('resources/sass/staff.scss', 'public/generated-css/staff.css')
    .sass('resources/sass/articles.scss', 'public/generated-css/articles.css')
    .sass('resources/sass/theme.scss', 'public/generated-css/new-styles.css')
    .sass('resources/sass/custom.scss', 'public/generated-css/custom.css')

    // Some of these are only actually used for local development (so the site works offline)
    // Otherwise many of these use the live/dev sites just use a CDN URL.
    // These are installed as defined in bower.json by running "bower update".
    // Paths to most of these are used by adding them to resources/httpAssets.php.

    .copy('node_modules/bootstrap-sass/assets/javascripts/bootstrap.min.js', 'public/vendor/bootstrap.min.js')
    .copy('node_modules/bootstrap-sass/assets/fonts/bootstrap', 'public/vendor/bootstrap-fonts')

    .copy('node_modules/jquery/dist/jquery.min.js', 'public/vendor/jquery.min.js')
    .copy('node_modules/jqueryui/jquery-ui.min.js', 'public/vendor/jquery-ui.min.js')
    .copy('node_modules/jqueryui/jquery-ui.theme.min.css', 'public/vendor/jquery-ui-cupertino.min.css') // this isn't really the same theme, but the NPM module doesn't include themes, this isn't used on the live site anyway (the CND is used instead)
    //. .copy('node_modules/jqueryui/ui/i18n', 'public/vendor/jquery-ui-i18n') (doesn't exist in the node_modules version of jqueryui. is it needed?)
    .copy('node_modules/font-awesome/css/font-awesome.min.css', 'public/vendor/font-awesome/css/font-awesome.min.css')
    .copy('node_modules/font-awesome/fonts', 'public/vendor/font-awesome/fonts')
    .copy('node_modules/jquery-mousewheel/jquery.mousewheel.js', 'public/vendor/jquery.mousewheel.min.js')
    .copy('resources/vendor/jquery-inlineedit/jquery.inlineedit.js', 'public/vendor/jquery.inlineedit.js')
    .copy('node_modules/bootbox/bootbox.js', 'public/vendor/bootbox.js')
    .copy('resources/vendor/plupload', 'public/vendor/plupload')
    .copy('resources/vendor/jquery-ui-i18n', 'public/vendor/jquery-ui-i18n')
    .copy('node_modules/devbridge-autocomplete/dist/jquery.autocomplete.min.js', 'public/vendor/jquery.autocomplete.min.js')
    .copy('node_modules/select2/select2.css', 'public/vendor/select2.css')
    .copy('node_modules/select2/select2.js', 'public/vendor/select2.min.js')
    .copy('node_modules/select2/select2-bootstrap.css', 'public/vendor/select2-bootstrap.css')
    .copy('node_modules/chart.js/dist/chart.umd.js', 'public/vendor/Chart.min.js')
    .copy('node_modules/fancybox/dist', 'public/vendor/fancybox')
    .copy('node_modules/pannellum/build/pannellum.css', 'public/vendor/pannellum.css')
    .copy('node_modules/pannellum/build/pannellum.js', 'public/vendor/pannellum.js')
    .version()

// Javascript
mix
    .combine([
        './resources/vendor/bootstrap/js/bootstrap.bundle.js',
        './resources/vendor/bootstrap-select/js/bootstrap-select.js',

        './resources/vendor/magnific-popup/jquery.magnific-popup.js',

        './resources/vendor/smooth-scroll/smooth-scroll.polyfills.js',

        './resources/vendor/moment.min.js',
        './resources/vendor/jquery.daterangepicker.js',

        './resources/js/lazysizes.min.js',

        'node_modules/devbridge-autocomplete/dist/jquery.autocomplete.min.js',

        './resources/js/theme.js'
    ], 'public/js/libs.js')

    .combine(['resources/js/global.js'], 'public/js/global.js')

    .combine(['resources/js/bookingSearch/booking-search-listing.js', 'resources/js/bookingSearch/booking-search-header.js', 'resources/js/bookingSearch/booking-search-header-mobile.js', 'resources/js/bookingSearch/booking-main.js'], 'public/js/booking-main.js')
    .combine(['resources/js/bookingSearch/index.js',], 'public/js/index.js')

    .combine(['resources/js/bookingSearch/booking-main.js', 'resources/js/bookingSearch/index.js',], 'public/js/indexMain.js')

    .combine(['resources/js/city.js', 'resources/js/listingsFilter.js'], 'public/js/city.js')
    .combine(['resources/js/cities.js',], 'public/js/cities.js')
    .combine(['resources/js/hostelChains.js',], 'public/js/hostelChains.js')
    .combine(['resources/js/articles.js',], 'public/js/articles.js')

    .combine(['resources/js/admin.js',], 'public/js/admin.js')
    .combine(['resources/js/listingDisplay.js'], 'public/js/listingDisplay.js')
    .combine(['resources/js/comparison.js'], 'public/js/comparison.js')

    .combine(['resources/js/formHandler.js',], 'public/js/formHandler.js')

// Javascript
mix
    .js('resources/js/wishlistIndexPage.js', 'js')
    .js('resources/js/wishlistMain.js', 'js')
    .js('resources/js/translation.js', 'js')
    .js('resources/js/app.js', 'js')
    .js('resources/js/citySlider.js', 'js')
    .sourceMaps()

mix
    .js('resources/js/vue/modules/sliders/comments-slider/app.js', 'public/js/vue/modules/comments-slider.js')
    .js('resources/js/vue/modules/sliders/featured-slider/app.js', 'public/js/vue/modules/featured-slider.js')
    .js('resources/js/vue/modules/sliders/listings-row-slider/app.js', 'public/js/vue/modules/listings-row-slider.js')
    .js('resources/js/vue/modules/sliders/listings-card-slider/app.js', 'public/js/vue/modules/listings-card-slider.js')
    .js('resources/js/vue/modules/sliders/listings-rates-slider/app.js', 'public/js/vue/modules/listings-rates-slider.js')
    .js('resources/js/vue/modules/sliders/listings-features-slider/app.js', 'public/js/vue/modules/listings-features-slider.js')
    .js('resources/js/vue/modules/sliders/listings-slp-slider/app.js', 'public/js/vue/modules/listings-slp-slider.js')
    .js('resources/js/vue/modules/sliders/listings-full-card-slider/app.js', 'public/js/vue/modules/listings-full-card-slider.js')
    .js('resources/js/vue/modules/sliders/top-pics-slider/app.js', 'public/js/vue/modules/top-pics-slider.js')
    .js('resources/js/vue/modules/tabs/cities-tabs/app.js', 'public/js/vue/modules/cities-tabs.js')
    .js('resources/js/vue/modules/listing-reviews/app.js', 'public/js/vue/modules/listing-reviews.js')
    .js('resources/js/vue/modules/comparison/comparison-icon/app.js', 'public/js/vue/modules/comparison-icon.js')
    .js('resources/js/vue/modules/comparison/comparison-section/app.js', 'public/js/vue/modules/comparison.js')
    .js('resources/js/vue/modules/comparison/comparison-dates-section/app.js', 'public/js/vue/modules/comparison-dates.js')
    .js('resources/js/vue/modules/Faqs/app.js', 'public/js/vue/modules/Faqs.js')
    .js('resources/js/slp.js', 'public/js/slp.js')
    .js('resources/js/cityVue.js', 'public/js/cityVue.js')
    .js('resources/js/listingVue.js', 'public/js/listingVue.js')
    .js('resources/js/citiesVue.js', 'public/js/citiesVue.js')
    .vue()
    .version()
