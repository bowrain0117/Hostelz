@props(['reservation', 'active' => true])

<div {{ $attributes->merge(['class' => 'col-12 col-lg-5 py-3']) }}>
    <p class="text-lg font-weight-bold">{{ $reservation->hostelName }}</p>
    <p class="text-sm">
        @include('partials.svg-icon', ['svg_id' => 'map-place', 'svg_w' => '25', 'svg_h' => '24'])
        {{ $reservation->hostelCity }}, {{ $reservation->hostelCountry }}
    </p>
    <p class="text-sm">
        @include('partials.svg-icon', ['svg_id' => $active ? 'calendar' : 'calendar-gray', 'svg_w' => '25', 'svg_h' => '24', 'class' => ''])
        {{ $reservation->startDate }} - {{ $reservation->endDate }}
    </p>

    {{ $slot }}
</div>