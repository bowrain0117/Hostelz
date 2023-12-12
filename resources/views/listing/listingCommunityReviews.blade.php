@if ($importedReviews || $ratings)
    <div id="vue-listing-reviews">
        <listing-reviews
                :reviews="{{ $reviews }}"
                :listing="{{ $listing }}"
                :sort-by="{{ json_encode($sortBy) }}"
                :sort-options="{{ json_encode(__('listingDisplay.sortOptions')) }}"
                :pages-number="{{ $pagesNumber }}"
        ></listing-reviews>
    </div>
@endif