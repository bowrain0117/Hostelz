@include('wishlist.modalWishlists')

@include('wishlist.modalCreateWishlist')

@include('wishlist.modalLogin')

@include('wishlist.toasts')

<script type="text/javascript">

    $(document).on("hostelz:frontUserData", function (e, data) {
        data.editURLFor = {target: 'listing', id: {{ $listing->id }}};
        return data;
    })

    $(document).on('hostelz:loadedFrontUserData', function (e, data) {
        if (data.editURL) {
            $('.edit-listing').remove();

            $('h1.title-2').after('<a class="d-block text-center text-decoration-underline edit-listing" href="' + data.editURL + '">edit listing</a>');
        }
    });

</script>

@if ($cityInfo)
    <script>
        $(window).on('load', function () {
            {{-- after all the images and everything else is loaded (low priority). --}}
            $.get('@routeURL("getCityAd", $cityInfo->id)', null,
                function (result) {
                    if (result) {
                        $(".asidebar-item")
                            .html(result)
                            .parent().removeClass('asidebar-wrap-hide');
                    }
                }, 'html');
        });
    </script>
@endif

<script type="text/javascript">
    @if ($listing->hasLatitudeAndLongitude() && !in_array('isClosed', $listingViewOptions))
    function doAfterMapScriptIsLoaded() {
        displayMap({!! $listing->latitude !!}, {!! $listing->longitude !!});
    }

    @endif

    $(document).ready(function () {
        initializeListingDisplayPage(
                {!! $listing->id !!},
                {!! in_array('getDynamicDataForListing', $listingViewOptions) ? 'true' : 'false' !!},
                {!! $listing->lastUpdatedTimeStamp() !!}
        );
    });
</script>

@if ($needToFetchContent && !in_array('isClosed', $listingViewOptions))
    {{-- Load data that only changes when the listing changes. (Mostly just used to hide data from Google, or to load data later for faster page loading.
        We add the timestamp just so it re-loads if the listing data is updated. --}}
    <script src="{!! routeURL('listingFetchContent')."?listingID=$listing->id&v=".$listing->lastUpdatedTimeStamp() !!}"
            type="text/javascript"></script>
@endif

@include('js.listingDisplayOptions')

<script src="{{ mix('js/listingVue.js')}}"></script>