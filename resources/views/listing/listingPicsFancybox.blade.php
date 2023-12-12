{{--
    Input:
        $picRows - As returned by Pic::createLayout().
        $picGroup - Name of the group of pics (to keep them separate when displaying the lightbox).
--}}


{{-- Pic Styling (to set the width/height of listing and review photos) --}}

<style type="text/css" scoped>
    @media (min-width: 700px) { {{-- this size must match the media query for .picRow in listingDisplay.scss and the $(window).width() comparison in js/listingDisplay.blade.php. --}}
        @foreach ($picRows as $picRow)
            @foreach ($picRow as $pic)
                .pic{!! $pic['pic']->id !!} {
        width: {!! 100/count($picRow) !!}%;
        height: {!! $pic['height'] !!}px;
        @if ($pic['topClip']) margin-top:{!! -$pic['topClip'] !!}px; @endif
                }
        @if (@$pic['additionalPics'])
                    {{-- The "+XX" overlap has to be moved down by topClip pixels also --}}
                    .pic{!! $pic['pic']->id !!} .additionalPicsOverlay {
            top: {!! $pic['topClip'] !!}px;
        }
    @endif
@endforeach
@endforeach
}
</style>

@foreach ($picRows as $picRow)
    <div class="picRow">
        @foreach ($picRow as $pic)
            <div class="pic{!! $pic['pic']->id !!}">
                <img
                    src="{!! $pic['pic']->url([ 'big', 'originals' ]) !!}" {{-- (if we eventually have larger size images that we want to use just for the lightbox, set those with data-fullsize-pic="...") --}}
                    data-fullsize-pic="{!! $pic['pic']->url([ 'big', 'originals' ]) !!}"
                    title="@if ($pic['pic']->caption){{{ $pic['pic']->caption }}}@else{{{ $listing->name }}}, {!! $cityData->city !!}@endif"
                    alt="@if ($pic['pic']->caption){{{ $pic['pic']->caption }}}@else{{{ $listing->name }}}, {!! $cityData->city !!}@endif"
                    data-pic-group="{!! $picGroup !!}" rel="{!! $picGroup !!}" property="image">
                @if (@$pic['additionalPics'])
                    <div class="additionalPicsOverlay">
                        <div><div>+{!! count($pic['additionalPics'])+1 !!}</div></div>
                    </div>
                    @foreach ($pic['additionalPics'] as $pic)
                        {{-- Hidden image info just so the lightbox knows to display these other images --}}
                        <span src="{!! $pic->url([ 'big', 'originals' ]) !!}" title="{{{ $listing->name }}}, {!! $cityData->city !!}" alt="{{{ $listing->name }}}, {!! $cityData->city !!}" data-pic-group="{!! $picGroup !!}"></span>
                    @endforeach
                @endif
            </div>
        @endforeach
    </div>
@endforeach

