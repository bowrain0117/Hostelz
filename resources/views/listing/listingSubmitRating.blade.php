@if (!in_array('isClosed', $listingViewOptions))
<div class="mb-5">
    <h4 class="sb-title cl-text mb-3" id="submitreview">@langGet('submitRating.submitRatingTitle', [ 'hostelName' => $listing->name ])</h4>
    <div id="createAccountText">
        <p class="mb-3">@langGet('submitRating.submitRatingText')</p>
        <p>
            @langGet('submitRating.createAccount', false, ['url' => route('login')])
        </p>
    </div>

    <form action="@routeURL('submitRating', $listing->id)" method="post" class="d-flex comment-form">
        <div id="reviewUserAvatar" class="mr-4 pb-3">
            <div class="avatar mr-2 bg-gray-800 border border-white">
                @include('partials.svg-icon', ['svg_id' => 'user-icon-dark', 'svg_w' => '44', 'svg_h' => '48'])
            </div>
        </div>

        <div class="col-lg-11 p-0 comment-form-review">
            <div class="d-flex mb-2">
                <div class="d-flex flex-row-reverse own-rate">
                    @foreach(@langGet("Rating.forms.options.ratingStars") as $key => $value)
                        <input type="radio" name="data[rating]" value="{{ $key }}" id="{{ $key }}" class="d-none">
                        <label for="{{ $key }}">
                            <span class="unchecked">@include('partials.svg-icon', ['svg_id' => 'star-unchecked', 'svg_w' => '24', 'svg_h' => '24'])</span>
                            <span class="checked" data-toggle="tooltip" data-placement="top" title="" data-original-title="{{ $value }}">@include('partials.svg-icon', ['svg_id' => 'star-checked', 'svg_w' => '24', 'svg_h' => '24'])</span>
                        </label>
                    @endforeach
                </div>
            </div>
            <div class="form-group">
                <label for="ratingSummary" class="mb-2 pre-title cl-text">@langGet('submitRating.summary')</label>
                <input type="text" name="data[summary]" id="ratingSummary" placeholder="@langGet('submitRating.summaryplaceholder')" required="required" class="form-control">
            </div>

            <button type="submit" class="btn btn-lg btn-primary d-flex m-auto px-5 tt-n" name="mode" value="insert">@langGet('global.Continue')</button>
        </div>
    </form> 
</div>
@endif