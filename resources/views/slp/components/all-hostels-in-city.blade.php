@props(['city'])

<div class="my-5">
    <h3>Unleash the Power of Hostel Price Comparison</h3><p>Welcome to Hostelz.com, your reliable gateway to finding the best prices for hostels worldwide! Our platform allows you to compare prices from major booking portals, saving you valuable time and money in the process.</p>

    <p>Rest assured, there are <b>no hidden costs or fees</b> - our services are completely free for you to use. We take pride in maintaining transparent and unbiased pricing information, and we never manipulate prices or rates.</p>
    <p>So go ahead and start comparing prices and availability for <a target="_self" rel="alternate" href="{{ $city->getUrl() }}" title="all hostels in {{ $city->city }} - with prices">all hostels in {{ $city->city }}</a> with us!</p>
</div>