{{-- Used to fetch some content of the listing as javascript.  (Mostly just used to hide data from Google, ?? to load data later for faster page loading.) --}}


{{-- Imported Reviews --}}

@section('importedReviews')
    @if ($importedReviews)

        <div class="" >
            <h4 class="font-weight-600 cl-text tx-body">{{ langGet('listingDisplay.importedReviewsTitle', [ 'hostelName' => $listing->name ]) }}</h4>

            <div class="no-last-bb no-last-mpb">

                @if (App::environment('dev')) <div class="text-white bg-danger p-3 rounded mb-4">[fetched]</div>  @endif

                @include('listing._listingRatings', [ 'ratings' => $importedReviews, 'withRDFaMarkup' => false ])

            </div>
        </div>

    @endif
@stop

@setVariableFromSection('importedReviews')

$( document ).ready(function() {
    $('#importedRatingsInsertedHere').html({!! json_encode(trimLines(convertIncorrectCharset($importedReviews))) !!}).trigger('hostelz:importedReviewsDone');

    {{-- Description / Location --}}

    @if ($description != '')
        $('#descriptionInsertedHere').html({!! json_encode((App::environment('dev') ? '<div class="text-white bg-danger p-3 rounded mb-4">[fetched]</div>' : '') . nl2br(trim($description))) !!});
    @endif

    @if (!empty($location))
        $('#locationInsertedHere').html({!! json_encode((App::environment('dev') ? '<div class="text-white bg-danger p-3 rounded mb-4">[fetched]</div>' : '') . trimNonUTF8(paragraphsAsBullets($location))) !!});
    @else
        $('#listing-location-contact').addClass('d-none');
    @endif
});

