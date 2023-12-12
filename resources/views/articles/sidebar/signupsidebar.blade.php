<div @class([
   'card border-0 shadow mb-5 js-show-if-not-login',
   'sticky-elem' => $isSticky ?? true,
])>
    <div class="card-header bg-gray-100 py-4 border-0">
        <div class="media align-items-center">
            <div class="media-body">
                <p class="subtitle text-sm text-primary">@langGet('loginAndSignup.SignUpSidebarTitle2')</p>
                <h4 class="mb-0">@langGet('loginAndSignup.SignUpSidebarTitle')</h4>
            </div>
            @include('partials.svg-icon', ['svg_id' => 'verify-woman-user', 'svg_w' => '50', 'svg_h' => '50'])
        </div>
    </div>

    <div class="card-body px-0 dark-overlay mb-4">
		<img alt="@langGet('global.SignUp')" src="{!! routeURL('images', 'signup-hostels.jpg') !!}" class="bg-image">
		<div class="position-relative overlay-content">
			<div class="align-items-center justify-content-center py-7 text-center text-white">
			    <form method="post" action="{{ routeURL('userSignup') }}">
                	{!! csrf_field() !!}
        	        <div class="container">
                            <div class="form-group">
                                <input type="email" class="form-control" id="email" name="email" placeholder="@langGet('loginAndSignup.BestEmailSignUp')" value="{{{ @$email }}}" required>
                            </div>
                            <button type="submit" name="submit" value=1 class="btn btn-block bg-primary text-white">@langGet('global.SignUp')</button>
                    </div>
                </form>
		    </div>
	    </div>
    </div> 
</div>