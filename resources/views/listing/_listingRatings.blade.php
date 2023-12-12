{{--
    Input:
        $ratings - Array or collection of App\Models\Rating objects, which may also have an optional 'systemName' property.
        $withRDFaMarkup - true/false whether to include RDFa markup (should only be used for our own reviews so Google can read them)
--}}

<?php $listingPicsList = $listing->getBestPics(); ?>

@foreach ($ratings as $rating)
    <div class="border-bottom mb-3 pb-3">
        <script type="application/ld+json">
        {
            "@context": "https://schema.org/",
            "@type": "Review",
            "itemReviewed": {
                "@type": "Hostel",
                "image": "@if($listingPicsList->first()){{ url($listingPicsList->first()->url([ 'big', 'originals' ])) }}@else{{routeURL('images', 'logo-hostelz.png', 'absolute')}}@endif",
                "name": "{{{ $listing->name }}}",
                "priceRange":"€€",
                "address" : {
                    "@type" : "PostalAddress",
                    "addressCountry" : "{{{ $listing->country }}}",
                    "addressLocality" : "{{{ $listing->city }}}",
                    "addressRegion" : "{{{ $listing->region }}}",
                    "postalCode" : "{{{ $listing->zipcode }}}",
                    "streetAddress" : "{{{ $listing->address }}}"
                },
                "telephone" :  "{{{ $listing->tel }}}"
             },

             @if($rating->rating)
            "reviewRating": {
                "@type": "Rating",
                "ratingValue": "{{ $rating->rating }}",
                "bestRating": "5",
                "worstRating": "1"
            },
            @endif

            "name": "{{{ $rating->summary }}}",
            "author": {
                "@type": "Person",
                "name": "{{ clearTextForSchema($rating->name) }}"
            },
            "reviewBody": "{{ clearTextForSchema($rating->comment) }}",
            "publisher": {
                "@type": "Organization",
                "name": "Hostelz"
            }
        }
        </script>

        {{--Author--}}
        <div class="row mb-3">
            <div class="col-md-3 d-flex">
                @if ($rating->user && $rating->user->profilePhoto && $rating->name != 'Anonymous')
                    <div>
                        <img src="{{{ $rating->user->profilePhoto->url([ 'thumbnails' ]) }}}" alt="#" class="avatar mr-2">
                    </div>
                @else
                    <div class="avatar mr-2 bg-gray-800 border border-white">
                        @include('partials.svg-icon', ['svg_id' => 'user-icon-dark', 'svg_w' => '44', 'svg_h' => '48'])
                    </div>
                @endif

                <div class="cl-text">
                    <div class="font-weight-600 text-break mb-1">{{{ $rating->name }}}</div>

                    @if ($rating->age != '' && $rating->age >= 16 && $rating->homeCountry != '')
                        <div class="pre-title">
                            <p class="mb-1">@langGet('global.Age') {{{ $rating->age }}} </p>
                            <p>@include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25']) {{{ $rating->homeCountry }}}</p>
                        </div>
                    @elseif ($rating->age != '' && $rating->age >= 16)
                        <div class="pre-title">@langGet('global.Age') {{{ $rating->age }}}</div>
                    @elseif ($rating->homeCountry != '')
                        <div class="pre-title">
                            @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '24', 'svg_h' => '25'])
                            {{{ $rating->homeCountry }}}
                        </div>
                    @endif
                </div>
            </div>

            {{--Comment--}}
            <div class="mb-3 col-md-9">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    @if ($rating->rating)
                        <div>
                            @for ($star = 0; $star < 5; $star++)
                                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="21" viewBox="0 0 22 21"
                                        @class(['ml-2' => $star !== 0, 'pl-1'])
                                >
                                    <g><g><g><path @if($star < $rating->rating) fill="#454545" @else fill="#EFEFEF" @endif d="M21.375 9.506c.43-.419.582-1.034.396-1.606a1.557 1.557 0 0 0-1.265-1.066l-5.29-.77a.691.691 0 0 1-.52-.378L12.33.893A1.557 1.557 0 0 0 10.925.02c-.6 0-1.14.335-1.405.873L7.154 5.687a.692.692 0 0 1-.52.378l-5.291.77A1.557 1.557 0 0 0 .078 7.9a1.557 1.557 0 0 0 .396 1.606l3.828 3.73c.163.16.238.39.2.613l-.904 5.269c-.08.463.042.914.342 1.27.466.554 1.28.723 1.932.381l4.73-2.487a.708.708 0 0 1 .645 0l4.731 2.487c.23.121.476.183.73.183.462 0 .9-.206 1.201-.564.301-.356.422-.808.342-1.27l-.903-5.269a.692.692 0 0 1 .2-.612z"/></g></g></g>
                                </svg>
                            @endfor
                        </div>
                    @endif
                    <div class="pre-title cl-body">
                        {!! carbonGenericFormat($rating->commentDate) !!}
                    </div>
                </div>

                @if ($rating->summary != '')
                    <p class="font-weight-bold">{{{ $rating->summary }}}</p>
                @endif

                @if ($rating->systemName != '')
                    {{-- Imported ratings aren't escaped because we already escaped them and also we add our own HTML to them --}}
                    {!! removeIncorrectCharset(nl2p($rating->comment)) !!}
                @else
                    {!! removeIncorrectCharset(nl2p($rating->comment)) !!}
                @endif

                @if ($rating->ownerResponse != '')
                    <div class="mb-3 ml-3 bg-light p-3">
                        <p class="mb-3 mb-3 font-weight-bold">@include('partials.svg-icon', ['svg_id' => 'speech-bubble', 'svg_w' => '24', 'svg_h' => '24']) @langGet('listingDisplay.ResponseFromOwner')</p>
                        <p class="mb-3">{{{ $rating->ownerResponse }}}</p>
                    </div>
                @endif
            </div>
        </div>
        @if ($rating->livePics && !$rating->livePics->isEmpty())
            <div class="ratingPics">
                @foreach ($rating->livePics as $pic)
                    <img src="{!! $pic->url([ 'thumbnail' ]) !!}" data-fullsize-pic="{!! $pic->url([ 'big', 'originals' ]) !!}" alt="{{{ $pic->caption }}}" title="{{{ $pic->caption }}}" data-pic-group="{!! 'rating'.$rating->id !!}" property="image">
                @endforeach
            </div>
        @endif

    </div>
@endforeach