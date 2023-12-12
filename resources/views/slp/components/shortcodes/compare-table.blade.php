@php
    if ($listings->isEmpty()) {
        return;
    }
@endphp

<div id="compare">
    <section class="container py-3">
        <table class="table align-middle mb-0 bg-white table-striped table-hover table-responsive-xl">
            <thead class="sticky-top bg-white shadow">
                <tr>
                    <th data-toggle="tooltip" data-placement="top" title="" data-original-title="">Hostel</th>
                    <th data-toggle="tooltip" data-placement="top" title="" data-original-title="">Overall Rating</th>
                    <th data-toggle="tooltip" data-placement="top" title="" data-original-title="Based on previous traveler!">Best for <i class="fas fa-info-circle"></i></th>
                    <th data-toggle="tooltip" data-placement="top" title="" data-original-title="">Distance to Center</th>
                    <th data-toggle="tooltip" data-placement="top" title="" data-original-title="">Amenities</th>
                    <th data-toggle="tooltip" data-placement="top" title="" data-original-title="">More info</th>
                </tr>
            </thead>

            <tbody>
            @foreach($listings as $hostel)
            <tr>
                <td>
                    <p class="font-weight-bold mb-1">
                        <a href="{{ $hostel['url'] }}" target="_blank"
                           rel="nofollow" title="{{ $hostel['name'] }}"
                        >
                            {{ $hostel['name'] }}
                        </a>
                    </p>

                    @if($hostel['minPrice'])
                        <p class="mb-0 text-sm">from {{ $hostel['minPrice'] }}</p>
                    @endif
                </td>

                <td><span class="listing-CombinedRating">{{ $hostel['rating'] }}</span></td>

                <td>
                    @if($hostel['goodFor']->isNotEmpty())
                        @foreach($hostel['goodFor'] as $item)
                            <x-check-icon checked="true">{{ $item }}</x-check-icon>
                        @endforeach
                    @endif
                </td>

                <td>
                    {{ $hostel['distance'] ?: '' }}
                </td>

                <td>
                    <div>
                        <x-check-icon :checked="$hostel['hasFreeBreakfast']">Free Breakfast</x-check-icon>

                        <x-check-icon :checked="$hostel['hasKitchen']">Kitchen</x-check-icon>
                    </div>
                </td>

                <td>
                    @if($hostel['moreInfo']->isNotEmpty())
                    <div class="dropdown d-inline-block">
                        <button id="dropdownMenuButton" type="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false" class="btn btn-sm btn-outline-primary dropdown-toggle">
                            More Details
                        </button>
                        <div aria-labelledby="dropdownMenuButton" class="dropdown-menu" x-placement="top-start" style="position: absolute; will-change: transform; top: 0px; left: 0px; transform: translate3d(0px, -268px, 0px);">
                            @foreach($hostel['moreInfo'] as $item)
                                <a href="{{ $item['href'] }}" class="dropdown-item" target="_blank">
                                    {{ $item['systemShortName'] }}
                                </a>
                            @endforeach

                            <div class="dropdown-divider"></div>
                            <a href="{{ $hostel['url'] }}" class="dropdown-item">Compare Prices here</a>
                        </div>
                    </div>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>

        </table>
    </section>
</div>