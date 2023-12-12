<div class="modal" id="wishlistModal" tabindex="-1" role="dialog" aria-labelledby="wishlistModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" >@langGet('wishlist.saveToList')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="spinner-wrap d-flex justify-content-center">
                    <div class="spinner-border" role="status" style="width: 3rem; height: 3rem;">
                        <span class="sr-only">Loading...</span>
                    </div>
                </div>
                <div id="wishlistModalBody"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">@langGet('global.Cancel')</button>
            </div>
        </div>
    </div>
</div>