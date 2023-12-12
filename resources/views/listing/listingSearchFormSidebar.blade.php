@if($siteBarActiveImports->isEmpty())
    <div class="p-3 p-sm-4 shadow-1 rounded mr-sm-n3 ml-sm-n3 ml-lg-0 mb-4 sticky-top mb-6" style="top: 50px;">
        <h5 class="title-3 cl-dark mb-3">{{ __('bookingProcess.BookingSidebarTitle') }}</h5>

        <p class="font-weight-600 cl-text mb-3">your hostels in {{ $listing->city }}</p>

        @php
            $system = $contactActiveImports->get('BookHostels')
        @endphp

        <a href="{!! $system['href'] !!}"
           onclick="ga('send', 'event', 'single', 'Sidebar', '{{{ $listing->name }}}, {!! $listing->city !!} - {{ $system['systemShortName'] }}')"
           title="{{ $listing->city }} {!! langGet('listingDisplay.At') !!} {{ $system['systemShortName'] }}"
           target="_blank" class="btn btn-light rounded mb-3 w-100" rel="nofollow">

            @include('partials.svg-icon', ['svg_id' => strtolower($system['systemName']) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
            {{ $system['systemShortName'] }}
        </a>

        @if(in_array('isClosed', $listingViewOptions))
            <p class="cl-text mb-0">{{ __('listingDisplay.checkAlternatives', ['system' => 'Hostelworld.com']) }}</p>
        @else
            <p class="cl-text mb-0">{{ __('listingDisplay.bookHostelOnline') }}</p>
            <p class="cl-text mb-0">{{ __('listingDisplay.checkOutSystem', ['system' => 'Hostelworld.com']) }}</p>
        @endif

    </div>

@else

    <div class="p-3 p-sm-4 shadow-1 rounded mr-sm-n3 ml-sm-n3 ml-lg-0 mb-4 sticky-top mb-6" style="top: 50px;">
        <h5 class="title-3 cl-dark mb-3">{{ __('bookingProcess.BookingSidebarTitle') }}</h5>

        <p class="font-weight-600 cl-text mb-3">{{{ $listing->name }}} is listed at the following booking sites:</p>

        @foreach ($siteBarActiveImports as $item)
            <a href="{!! $item['href'] !!}"
               onclick="ga('send', 'event', 'single', 'Sidebar', '{{{ $listing->name }}}, {!! $listing->city !!} - {{ $item['systemShortName'] }}')"
               title="{{{ $listing->name }}} {!! langGet('listingDisplay.At') !!} {{ $item['systemShortName'] }}"
               target="_blank" class="btn btn-light rounded mb-3 w-100" rel="nofollow">

                @include('partials.svg-icon', ['svg_id' => strtolower($item['systemName']) . '-icon-sm', 'svg_w' => '22', 'svg_h' => '22'])
                {{ $item['systemShortName'] }}
            </a>
        @endforeach

        @if( count($siteBarActiveImports) > 1 )
            <p class="cl-text mb-3">We just compared for you all availability and prices!</p>
        @else
            <p class="cl-text mb-3">It is the only way to book {{{ $listing->name }}}</p>
        @endif

        <p class="cl-text mb-0">So get the best deal, save money and travel longer.</p>
    </div>

@endif