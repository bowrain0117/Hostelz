<div class="py-5 py-lg-7">
    <div class="container">
        <p class="subtitle text-primary">Still not sure? Pick this!</p>
        <h2 id="toprated" class="h2 mb-5"><i class="fa fa-trophy" aria-hidden="true"></i> {{ $title }}</h2>

        <div class="row">
            <div class="col-lg-7">
                <p class="">{{ $text }}
                    <span class="listing-CombinedRating"><b>The overall rating is {{ $hostel->rating }}. You cannot go wrong here</b>.</span>
                </p>
                <p class="">It is your safest bet in case you are not sure which hostel to pick.</p>
                <p class="mb-5">The price for a dorm at {{ $hostel->name }} starts from {{ $hostel->minPrice }}.</p>
                <p class="mb-5 mb-lg-0">
                    <a href="{{ $hostel->url }}" class="btn btn-danger" title="{{ $hostel->name }}" target="_blank">
                        Book {{ $hostel->name }} here
                    </a>
                </p>
            </div>

            <div class="col-lg-5 ml-auto">
                <div class="hover-animate position-relative">
                    <div class="ribbon ribbon-warning">{{ $ribonText }}</div>
                    <a href="{{ $hostel->url }}"
                       title="{{ $hostel->name }}"
                       target="_blank">
                        <img src="{{ $hostel->pic }}" title="{{ $hostel->name }}"
                             alt="{{ $hostel->name }}" class="img-fluid">
                    </a>
                </div>
            </div>
        </div>

    </div>
</div>