@if( !in_array(Route::currentRouteName(), ['userSignup', 'login', 'login-forgot', 'login-forgot-reset', 'login-form']) )
	<section class="js-show-if-not-login" id="signup">
		<div class="dark-overlay">
			<picture>
				<source srcset="{!! routeURL('images', 'signup-hostels.webp') !!}" type="image/webp">
				<img
						class="bg-image"
						src="{!! routeURL('images', 'signup-hostels.jpg') !!}"
						alt="{{ langGet('loginAndSignup.SignUpFooterTitle') }}"
						title="{{ langGet('loginAndSignup.SignUpFooterTitle') }}"
						loading="lazy"
				>
			</picture>

			<div class="container col-12 col-md-8 position-relative overlay-content py-4">
				<div class="align-items-center justify-content-center py-7 text-center text-white">
					<p class="text-white h2">@langGet('loginAndSignup.SignUpFooterTitle')</p>
					<p>@langGet('loginAndSignup.SignUpFooterText')</p>
                    <div class="mb-4">
					    <form method="post" action="{{ routeURL('userSignup') }}">
						    {!! csrf_field() !!}
						    <div class="row">
							    <div class="col-md-6 mx-auto">
								    <div class="form-group">
									    <input type="email" class="form-control" id="email" name="email" placeholder="@langGet('loginAndSignup.BestEmailSignUp')" value="{{{ @$email }}}" required>
							    	</div>
								    <button type="submit" name="submit" value=1 class="btn btn-block bg-primary text-white">@langGet('global.SignUp')</button>
							    </div>
						    </div>
					    </form>
                    </div>
                    <div class="mb-4">
                        <span>@langGet('loginAndSignup.AlreadyAccount')</span>
                        <span><a class="ml-1 font-weight-bold text-white" href="{!! routeURL('login')!!}">@langGet('global.login')</a></span>
                    </div>
				</div>
			</div>
		</div>
	</section>
@endif