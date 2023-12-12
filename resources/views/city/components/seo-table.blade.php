@props(['items', 'cityName'])

@php
if (is_null($items)) {
    return;
}
@endphp

<div class="mb-3 mb-lg-5 pb-3 pb-lg-5 border-bottom">
    <h2 class="sb-title cl-text pt-lg-3">{!! langGet('city.StatsTitle', [ 'city' => $cityName]) !!}</h2>

    <div class="tx-small mb-3">{!! langGet('city.StatsText', [ 'city' => $cityName]) !!}</div>

    <table class="table table-striped">
        <tbody>

        @if($items['totalNumber'])
        <tr>
            <td scope="row">{!! langGet('city.TotalNumber') !!}</td>
            <td class="text-center font-weight-bold">{{ $items['totalNumber'] }}</td>
        </tr>
        @endif

        @if($items['average']['dorm'])
        <tr>
            <td scope="row">{!! langGet('city.AverageDormPrice') !!}</td>
            <td class="text-center font-weight-bold">${{ $items['average']['dorm'] }}</td>
        </tr>
        @endif

        @if($items['average']['private'])
        <tr>
            <td scope="row">{!! langGet('city.AveragePrivatePrice') !!}</td>
            <td class="text-center font-weight-bold">${{ $items['average']['private'] }}</td>
        </tr>
        @endif

        @if($items['cheapest'])
        <tr>
            <td scope="row">
                @if($items['slp'])
                    <a class="tx-small text-primary d-block"
                       href="{{ $items['slp'] }}"
                       target="_blank"
                       title="Cheapest hostel in {{ $cityName }}"
                    >
                        {!! langGet('city.CheapestHostelInCity', ['city' => $cityName]) !!}
                    </a>
                @else
                    Cheapest Hostel in {{ $cityName }}
                @endif
            </td>
            <td class="text-center font-weight-bold">
                <a class="cl-primary" href="{!! $items['cheapest']['url'] !!}">
                    {{ $items['cheapest']['name'] }}
                </a> with ${{ $items['cheapest']['price'] }}
            </td>
        </tr>
        @endif

        @if($items['partyHostels'])
            <tr>
                <td scope="row">
                    {!! langGet('city.PartyHostelsInCity', ['city' => $cityName]) !!}
                </td>
                <td class="text-center font-weight-bold">
                    <a class="cl-primary" href="{{ $items['partyHostels']['best']['url'] }}">
                        {{ $items['partyHostels']['best']['name'] }}
                    </a> ({{ $items['partyHostels']['count'] }} in total)
                </td>
            </tr>
        @endif

        @if($items['neighborhood'])
            <tr>
                <td scope="row">
                    {!! langGet('city.MostNeighborhoods', ['city' => $cityName]) !!}
                </td>
                <td class="text-center font-weight-bold">{{ $items['neighborhood'] }}</td>
            </tr>
        @endif

        @if($items['cityAVGHostelsRating'])
            <tr>
                <td scope="row">{!! langGet('city.AverageRating') !!}</td>
                <td class="text-center font-weight-bold">{{ $items['cityAVGHostelsRating'] }}</td>
            </tr>
        @endif

        </tbody>
    </table>
</div>