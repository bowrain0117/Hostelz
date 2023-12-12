@props(['reservation'])

<div {{ $attributes->merge(['class' => 'col-12 col-lg-5 align-self-center hover-animate']) }}>
    <p class="text-sm text-gray-600 text-center">Our Special {{ $reservation->hostelCity }} Guide for You: <br/>Coming
        soon ...</p>

    {{--<p class="text-lg font-weight-bold">Our Special Amsterdam Guide for You:</p>
    <p class="text-sm text-gray-600">Thanks for using our reservation link. As a thank you,
        we sat down.</p>

    <img class="w-100" style=""
         src="https://blog.placeit.net/wp-content/uploads/2015/12/Mockup_Book_Cover_Travel.jpg">--}}
</div>