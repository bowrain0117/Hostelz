<div class="modal" id="createWishlistModal" tabindex="-1" role="dialog" aria-labelledby="createWishlistModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" >@langGet('wishlist.createList')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="createWishListForm" action="@routeURL('wishlist:store')" method="POST">
                    {{ csrf_field() }}

                    <div class="form-group">
                        <label for="name" class="form-label">@langGet('wishlist.listName')</label>
                        <input id="name" name="name" type="text" placeholder="Name" class="form-control" required value="{{ old('value') }}">
                        <div class="invalid-feedback"></div>
                    </div>

                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">@langGet('global.Cancel')</button>
                <button type="submit" class="btn btn-success" form="createWishListForm">
                    <span class="spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    @langGet('global.Save')
                </button>
            </div>
        </div>
    </div>
</div>