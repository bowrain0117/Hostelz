<form id="wishlistLoginForm" class="form-validate" method="post" action="@routeURL('login')">
    <input type="hidden" name="_token" value="{!! csrf_token() !!}">
    @isset($loginFailedMessage)
        <br><div class="alert alert-{{ $loginFailedMessage['status'] }}">{!! $loginFailedMessage['message'] !!}</div>
    @endisset

    <div class="form-group">
        <label for="username" class="form-label">Your best Email Address</label>
        <input name="username" id="username" type="email" placeholder="Your best Email Address" autocomplete="off" required="" data-msg="Please enter your email" class="form-control" autofocus value="{{{ @$username }}}">
       
        <div class="invalid-feedback"></div>
        @if (@$errors)
            <div class="text-danger bold italic">{!! $errors->first('username') !!}</div>
        @endif
    </div>

    <div class="form-group mb-4">
        <div class="row">
            <div class="col">
                <label for="password" class="form-label"> Password</label>
            </div>
            <div class="col-auto"><a href="{!! routeURL('login-forgot') !!}" class="form-text small">Forgot password?</a></div>
        </div>
        <input name="password" id="password" placeholder="Password" type="password" required="" data-msg="Please enter your password" class="form-control">
        <div class="invalid-feedback"></div>
        @if (@$errors) <div class="text-danger bold italic">{!! $errors->first('password') !!}</div> @endif
    </div>

    <div class="form-group mb-4">
        <div class="custom-control custom-checkbox">
            <input id="loginRemember" type="checkbox" class="custom-control-input" name="rememberMe" @if (@$rememberMe) CHECKED @endif>
            <label for="loginRemember" class="custom-control-label"> <span class="text-sm">Remember me</span></label>
        </div>
    </div>

    <!-- Submit-->
    <button class="btn btn-lg btn-block btn-primary" type="submit">Log In</button>
    <hr class="my-4">
    <p class="text-center"><small>Don't have an account yet? <a href="{!! routeURL('userSignup') !!}">Sign Up </a></small></p>

</form>
