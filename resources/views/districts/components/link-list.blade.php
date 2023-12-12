<section class="my-5">
    <p>Looking for hostels in a special neighborhood or near a popular landmark? Check out our special guides:</p>
    <ul>
        @foreach($districts as $district)
            <li><a href="{{ $district->path }}" title="{{ $district->title }}"
                   class="cl-link">{{ $district->title }}</a></li>
        @endforeach
    </ul>
</section>