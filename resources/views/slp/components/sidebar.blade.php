<div class="slp-sidebar col-12 col-lg-4">

    {{--<x-sidebar.written-by :name="$authorName" :avatar="$authorAvatar" :date="$date"/>--}}

    <div class="mb-5">
        <div class="bg-info-light border-0 card-body">
            <p class="h5 mb-3">Want to stay Flexible with your Bookings?</p>
            <div class="media align-items-center">
                <div class="media-body">
                    <p class="text-sm text-dark opacity-8 mb-0">
                        <a href="{{ $HWLink }}" target="_blank">Hostelworld.com</a> offers flexible bookings for many
                        hostels.
                    </p>
                </div>
                <svg class="svg-icon svg-icon svg-icon-dark w-3rem h-3rem ml-2 text-dark">
                    <use xlink:href="#diploma-1"></use>
                </svg>
            </div>
        </div>
    </div>

    @include('articles.sidebar/video')

    @if(0)
        <div style="top: 50px;" class="p-3 p-sm-4 shadow-1 rounded mr-sm-n3 ml-sm-n3 ml-lg-0 mb-4 mb-5">
            <img src="https://images.unsplash.com/flagged/photo-1570533136641-42082acf8d0c?ixlib=rb-1.2.1&amp;ixid=MnwxMjA3fDB8MHxzZWFyY2h8Mnx8YmFyY2Vsb25hfGVufDB8fDB8fA%3D%3D&amp;auto=format&amp;fit=crop&amp;w=900&amp;q=60"
                 class="w-100 mb-4">
            <p class="cl-text mb-0">So get the best deal, save money and travel longer.</p>
        </div>
    @endif

    @guest
        @include('articles.sidebar/signupsidebar', ['isSticky' => false])
    @endguest

    <x-sidebar.top-hostel :$topHostel :$city isSticky="true"/>

</div>