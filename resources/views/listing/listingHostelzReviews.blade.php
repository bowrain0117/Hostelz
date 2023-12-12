@if ($review)
    <div id="hostelzreview">

        <h3 role="alert" class="alert alert-success tx-body" id="hostelzreview">@include('partials.svg-icon', ['svg_id' => 'verify-woman-user', 'svg_w' => '24', 'svg_h' => '24']) @langGet('listingDisplay.HostelzReview')</h3>
        
        {{--shows date and name when review younger than 1 1/2 years on top--}}
        @if(strtotime($review->reviewDate) >= strtotime('-550 days'))
        <div class="row border-top border-bottom my-3 py-3">
            @if ($review->user)
                <div class="col-sm-6">
                    <div class="d-flex align-items-center">
                        @if ($review->user->profilePhoto)
                            <div>
                                <img src="{{{ $review->user->profilePhoto->url([ 'thumbnails' ]) }}}" alt="#" class="avatar mr-2">
                            </div>
                        @endif

                        <div class="cl-text">
                            <div class="font-weight-600">{{{ $review->user->getNicknameOrName('Mystery Reviewer') }}}</div>
                            <div class="pre-title">Hostelz.com Staff Reviewer</div>
                        </div>
                    </div>
                </div>
                @if ($review->reviewDate)
                    <div class="col-sm-6 d-flex align-items-center">
                        <div class="ml-auto bg-light rounded-lg py-1 px-3 pre-title">
                            Published on {!! carbonGenericFormat($review->reviewDate) !!}
                        </div>
                    </div>
                @endif
            @else
                <p class="mb-0">@langGet('listingDisplay.ExclusiveReview')</p>
            @endif
        </div>
        @endif

        @if(!empty(trim($review->editedReview)))
            <div class="text-content mb-3" property="reviewBody">
                {!! nl2br(trim($review->editedReview)) !!}
            </div>
        @endif

        @if ($review->ownerResponse != '')
            <p class="mb-3">@langGet('listingDisplay.ResponseFromOwner') {{{ $review->ownerResponse }}}</p>
        @endif

        @if ($reviewPics)
            <div class="font-weight-600 co-text mb-3">@include('partials.svg-icon', ['svg_id' => 'vintage-camera-1', 'svg_w' => '24', 'svg_h' => '24']) @langGet('listingDisplay.ExclusivePhotoFull')</div>
            @include('listing/listingPicsFancybox', [ 'picRows' => $reviewPics, 'picGroup' => 'reviewPics' ])
        @endif

        @if(strtotime($review->reviewDate) < strtotime('-550 days'))
        <div class="row border-top mt-3 pt-3">
            @if ($review->user)
                <div class="col-sm-6">
                    <div class="d-flex align-items-center">
                        @if ($review->user->profilePhoto)
                            <div>
                                <img src="{{{ $review->user->profilePhoto->url([ 'thumbnails' ]) }}}" alt="#" class="avatar mr-2">
                            </div>
                        @endif

                        <div class="cl-text">
                            <div class="font-weight-600">{{{ $review->user->getNicknameOrName('Mystery Reviewer') }}}</div>
                            <div class="pre-title">Hostelz.com Staff Reviewer</div>
                        </div>
                    </div>
                </div>
                @if ($review->reviewDate)
                    <div class="col-sm-6 d-flex align-items-center">
                        <div class="ml-auto bg-light rounded-lg py-1 px-3 pre-title">
                            Published on {!! carbonGenericFormat($review->reviewDate) !!}
                        </div>
                    </div>
                @endif
            @else
                <p class="mb-0">@langGet('listingDisplay.ExclusiveReview')</p>
            @endif
        </div>
        @endif
    </div>
@endif