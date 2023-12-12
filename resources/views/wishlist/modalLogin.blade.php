<div class="modal" id="wishlistLoginModal" tabindex="-1" role="dialog" aria-labelledby="wishlistLoginModal" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" >@langGet('loginAndSignup.Login')</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">

                @include('login.login-form')

            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-dismiss="modal">@langGet('global.Cancel')</button>
                <button type="submit" class="btn btn-success" form="wishlistLoginForm">
                    <span class="spinner spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
                    @langGet('global.Save')
                </button>
            </div>
        </div>
    </div>
</div>