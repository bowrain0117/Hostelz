<div class="position-fixed bottom-0 end-0 ml-5 m-5" style="z-index: 1050; min-width: 250px;">
    <div id="addedWishlistToast" data-delay="3000" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="mr-auto">@langGet('wishlist.Listing')</strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body">
            @langGet('wishlist.savedTo') <a class="font-weight-bold" href="" target="_blank" id="addedWishlistToastLink"></a>
        </div>
    </div>
</div>

<div class="position-fixed bottom-0 end-0 ml-5 m-5" style="z-index: 1050; min-width: 250px;">
    <div id="removedWishlistToast" data-delay="3000" class="toast hide" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="toast-header">
            <strong class="mr-auto">@langGet('wishlist.Listing')</strong>
            <button type="button" class="ml-2 mb-1 close" data-dismiss="toast" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="toast-body">
            @langGet('wishlist.removedFrom') <a class="font-weight-bold" href="" target="_blank" id="removedWishlistToastLink"></a>
        </div>
    </div>
</div>