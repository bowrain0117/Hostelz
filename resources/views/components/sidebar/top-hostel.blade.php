@props(['topHostel', 'city', 'isSticky' => false])

@php
    if(empty($topHostel['url'])) return;
@endphp


<div style="top: 50px;" @class([
   'p-3 p-sm-4 shadow-1 rounded mb-5',
   'sticky-elem' => $isSticky,
])>
    <p class="title-3 mb-3 font-weight-bold">{{ $topHostel['title'] }}</p>
    <p class="h3 mb-3 text-center">{{ $topHostel['name'] }}!</p>
    <a class="hover-animate position-relative mb-4 d-block"
       href="{{ $topHostel['url'] }}"
       title="{{ $topHostel['name'] }}" target="_blank" rel="nofollow">
        <img src="{{ $topHostel['pic'] }}" title="{{ $topHostel['name'] }}" alt="{{ $topHostel['name'] }}"
             class="display-block w-100">
    </a>
    <p class="mb-5 mb-lg-0 text-center">
        <span id="topHostelSidebar" class="sticky-sm-top-hostel toogleOnScroll">
            <a href="{{ $topHostel['url'] }}"
               class="btn btn-danger rounded px-5"
               target="_blank"
               rel="nofollow">
                Book {{ $topHostel['name'] }} here
            </a>
            <button type="button" href="#" id="topHostelHideButton" class="bg-light btn-clear">
                @include('partials.svg-icon', ['svg_id' => 'close-icon-2', 'svg_w' => '15', 'svg_h' => '15'])
            </button>
        </span>
    </p>
</div>