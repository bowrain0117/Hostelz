<div class="travel-tips mb-3 mb-lg-5 border-bottom pb-3 pt-lg-5 border-bottom-lg-0">
    <h2 class="sb-title cl-text mb-0 d-none d-lg-block"
        id="thingstodo">@langGet('city.TravelTipsTitle', [ 'city' => $cityInfo->translation()->city ])</h2>

    <p class="sb-title cl-text mb-0 d-block d-lg-none cursor-pointer collapse-arrow-wrap collapsed"
       data-toggle="collapse" href="#travel-tips-content">
        {!! langGet('city.TravelTipsTitle', [ 'city' => $cityInfo->translation()->city ]) !!}
        <i class="fas fa-angle-down float-right"></i>
        <i class="fas fa-angle-up float-right"></i>
    </p>

    <div class="mt-3 collapse show d-lg-block" id="travel-tips-content">
        @if ($cityComments)
            <p class="tx-small">@langGet('city.TravelTipsText', [ 'city' => $cityInfo->translation()->city ])</p>

            <div class="vue-comments-slider">
                <comments :data="{{ $cityComments }}"></comments>
            </div>
        @else
            <p class="tx-small">@langGet('city.TravelTipsTextEmpty', [ 'city' => $cityInfo->translation()->city ])</p>
        @endif

        <div class="cityCommentSubmit pt-4 pb-3 mb-3">
            <form action="@routeURL('submitCityComment', $cityInfo->id)"
                  class="shadow-1 border-0 py-3 px-3 bg-white rounded" method="post">
                <div class="">
                    <textarea
                            rows="4" name="data[comment]" id="cityComment"
                            placeholder="@langGet('city.AddYourTips', [ 'city' => $cityInfo->translation()->city ])"
                            required=""
                            class="form-control border-0"
                    ></textarea>
                </div>
                <div class="d-flex flex-column flex-md-row align-items-center justify-content-between mt-3">
                    <p class="pl-sm-3 mb-0 text-gray-600 pr-sm-3">
                        <small>@langGet('city.CityCommentsOnly', [ 'city' => $cityInfo->translation()->city ])</small>
                    </p>
                    <button name="mode" value="insert" type="submit"
                            class="btn btn-lg btn-primary px-5 tt-n mt-2 mt-md-0">@langGet('global.Submit')</button>
                </div>
            </form>
        </div>
    </div>
</div>