@props(['name', 'date', 'avatar'])

<div style="" class="p-3 p-sm-4 shadow-1 rounded mr-sm-n3 ml-sm-n3 ml-lg-0 mb-4 mb-6">
    <img src="https://www.hostelz.com/pics/cityInfo/user/37/4990137.jpg" class="w-100 mb-4">
    <div class="text-center" style="margin-top: -75px;">
        <div>
            <img alt="TEXT" title="TEXT" src="{{ $avatar }}" class="avatar avatar-xl p-2 mb-2 text-center">
        </div>
        <p class="text-uppercase text-sm">Written by {{ $name }}</p>
        <p class="text-sm">Last update {{ $date }}</p>
    </div>
</div>