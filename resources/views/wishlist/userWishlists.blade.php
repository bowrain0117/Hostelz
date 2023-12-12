<div class="card mb-3 wishlistItem wishlistItemCreate" style="cursor: pointer;" >
    <div class="row no-gutters">
        <div class="col-4 d-flex align-items-center justify-content-center bg-dark card-img">
            <i class="fas fa-plus text-white"></i>
        </div>
        <div class="col-8">
            <div class="card-body">
                <h5 class="card-title">@langGet('wishlist.createNewList')</h5>
            </div>
        </div>
    </div>
</div>

@foreach($wishlists as $wishlist)
<div class="card mb-3 wishlistItem" data-wishlist="{{ $wishlist->id }}" style="cursor: pointer;">
    <div class="row no-gutters">
        <div class="col-4 d-flex">
            @if ($wishlist->image)
                <img src="{!! $wishlist->image !!}" class="card-img" alt="{!! $wishlist->name !!}" title="{!! $wishlist->name !!}">
            @else
                <img src="@routeURL('images', 'noImage.jpg')" class="card-img" alt="{!! $wishlist->name !!}" title="{!! $wishlist->name !!}">
            @endif
        </div>
        <div class="col-8">
            <div class="card-body">
                <h5 class="card-title">{!! $wishlist->name !!}</h5>
                <p class="card-text">

                    @if($wishlist->listingsCount)
                        {{ $wishlist->listingsCount }} {{ trans_choice('wishlist.stays', $wishlist->listingsCount) }}
                    @else
                        @langGet('wishlist.nothingSaved')
                    @endif

                </p>
            </div>
        </div>
    </div>
</div>
@endforeach

