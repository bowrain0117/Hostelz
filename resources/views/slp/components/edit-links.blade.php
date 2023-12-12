<div class="my-3 p-3 bg-gray-300">
    <a href="{{$links['text']}}" class="text-info" target="_blank">edit text</a>
    <div style="display: block;" class="js-show-if-login mt-3">
        Helpful links for Editor:
        <a class="text-info" href="{{$links['listing']}}" target="_blank">Edit Listing</a>
        @if($links['booking'])
            | <a class="text-info" href="{{$links['booking']}}" rel="nofollow" target="_blank">Booking.com</a>
        @endif
        @if($links['map'])
            | <a class="text-info" href="{{$links['map']}}" rel="nofollow" target="_blank">Google Maps Location</a>
        @endif
    </div>
</div>