{{-- Input Variables: $userSettingsHandler  --}}

{{-- Icon --}}
<h2 class="hero-heading">{!! langGet('UserSettingsHandler.icons.'.$userSettingsHandler->action, '') !!} {!! langGet('UserSettingsHandler.actions.'.$userSettingsHandler->action) !!}</h2>

<div class="my-2 text-right">
	@if ($userSettingsHandler->user && $userSettingsHandler->user->isPublic && $userSettingsHandler->user->nickname)
		<a href="{{ $userSettingsHandler->user->pathPublicPage }}" target="_blank" class="cl-primary">
			Your Hostelz Portfolio >
		</a>
	@endif
</div>

@if ($userSettingsHandler->showGenericUpdateSuccessPage)

	<div class="clearfix">
		<div class="alert alert-success"><span class="glyphicon glyphicon-ok"></span> Saved.</div>
	</div>
	<p><a href="{!! $userSettingsHandler->returnToWhenDone !!}">Return to the Menu</a></p>

@elseif ($userSettingsHandler->action === 'settings')
	<div>
		<p class="text-sm">{!! langGet('UserSettingsHandler.settings.realNamePrivate') !!}

			@if ($userSettingsHandler->user->hasPermission('reviewer') || $userSettingsHandler->user->hasPermission('staffWriter'))
				{!! langGet('UserSettingsHandler.settings.penNameDescReviewer') !!}
			@else
				{!! langGet('UserSettingsHandler.settings.penNameDesc') !!}
			@endif
		</p>
	</div>
	@if (!$userSettingsHandler->user->isPublic)
		<div class="my-2">
			Preview:
			<button type="button" class="btn btn-danger btn-sm" data-toggle="modal" data-target="#previewModal">Your
				Hostelz Portfolio
			</button>
		</div>
		<!-- Preview Modal -->
		@include('user.public.preview')
	@endif

	@include('Lib/formHandler/doEverything', [ 'showTitles' => false, 'horizontalForm' => true, 'returnToURL' => $userSettingsHandler->returnToWhenDone ])

	@if ($formHandler->mode === 'updateForm')
		<div class="mt-6">
			<h3 class="mb-4">More Settings</h3>
			<div class="form-group row">
				<label for="name"
				       class="control-label col-md-3  font-weight-600">{!! langGet('UserSettingsHandler.settings.email') !!}</label>
				<div class="col-md-9">
					<p class="form-control-static form-control">{{{ $userSettingsHandler->user->username }}} (<a
								href="changeEmail">{!! langGet('UserSettingsHandler.settings.change') !!}</a>)</p>
				</div>
			</div>

			<div class="form-group row">
				<label for="name"
				       class="control-label col-md-3  font-weight-600">{!! App\Models\User::getLabel('password') !!}</label>
				<div class="col-md-9">
					<p class="form-control-static form-control">*** (<a
								href="changePassword">{!! langGet('UserSettingsHandler.settings.change') !!}</a>)</p>
				</div>
			</div>
		</div>
	@endif

@elseif ($userSettingsHandler->action === 'changeEmail')

	<div class="row">
		<div class="col-md-6">
			<form method="post">
				<input type="hidden" name="_token" value="{!! csrf_token() !!}">

				@if (@$status === 'success')
					<div class="alert alert-success">
						<p>
							<strong>{{{ langGet('UserSettingsHandler.changeEmail.changeEmailSent', [ 'email' => $newEmail ]) }}}</strong>
						</p>
						<p>@langGet('UserSettingsHandler.changeEmail.changeEmailSentNote')</p>
					</div>
					<div class="row">
						<div class="col-md-8"><a href="{!! $userSettingsHandler->returnToWhenDone !!}">Return to the
								Menu</a></div>
					</div>
				@else
					<p>
						@langGet('UserSettingsHandler.changeEmail.CurrentEmailAddress')
						<strong>{{{ $userSettingsHandler->user->username }}}</strong>
					</p>
					<p>@langGet('UserSettingsHandler.changeEmail.text')</p>
					<div class="form-group">
						<label for="newEmail">@langGet('UserSettingsHandler.changeEmail.newEmailLabel')</label>
						<input class="form-control" id="newEmail" name="newEmail" type="email"
						       value="{{{ @$newEmail }}}" autofocus>
						@if (@$errors)
							<div class="text-danger bold italic">{!! $errors->first('newEmail') !!}</div>
						@endif
						@if (@$status === 'emailAlreadyExists')
							<div class="text-danger">{{{ langGet('UserSettingsHandler.changeEmail.emailAddressExists', [ 'email' => $newEmail ]) }}}</div>
						@endif
					</div>
					<button class="btn btn-primary"
					        type="submit">@langGet('UserSettingsHandler.changeEmail.SendVerificationEmail')</button>
				@endif
			</form>
		</div>
	</div>

@elseif ($userSettingsHandler->action === 'verifyChangedEmail')

	@if ($status === 'success')
		<div class="alert alert-success">
			<h3><i class="fa fa-check-circle" style="font-size: 45px"></i>
				&nbsp; {{{ langGet('UserSettingsHandler.changeEmail.emailChanged', [ 'email' => $newEmail ]) }}}</h3>
		</div>
		<h3><a href="{!! routeURL('login') !!}">Login</a></h3>
	@elseif ($status === 'invalidVerifyLink')
		<div class="alert alert-danger"><i
					class="fa fa-exclamation-circle"></i> {!! langGet('global.invalidVerificationEmailURL') !!}</div>
	@endif

@elseif ($userSettingsHandler->action === 'changePassword')

	<div class="row">
		<div class="col-md-5">
			<form method="post">
				<input type="hidden" name="_token" value="{!! csrf_token() !!}">

				@if (@$status === 'success')
					<div class="alert alert-success">@langGet('UserSettingsHandler.changePassword.passwordUpdated')</div>
					<div class="row">
						<div class="col-md-8"><a href="{!! $userSettingsHandler->returnToWhenDone !!}">Return to the
								Menu</a></div>
					</div>
				@else
					@if (!auth()->user()->hasPermission('admin'))
						<div class="form-group">
							<label for="currentPassword">@langGet('UserSettingsHandler.changePassword.currentPassword')</label>
							<input class="form-control" id="currentPassword" name="currentPassword" type="password"
							       autofocus>
							@if (@$invalidCurrentPassword)
								<div class="text-danger">@langGet('UserSettingsHandler.changePassword.wrongPassword')</div>
							@endif
						</div>
					@endif

					<div class="form-group">
						<label for="currentPassword">@langGet('UserSettingsHandler.changePassword.newPassword')</label>
						<input class="form-control" id="newPassword" name="newPassword" type="password">
						@if (@$errors)
							<div class="text-danger bold italic">{!! $errors->first('newPassword') !!}</div>
						@endif
					</div>

					<button class="btn btn-primary"
					        type="submit">@langGet('UserSettingsHandler.changePassword.ChangePassword')</button>
				@endif
			</form>
		</div>
	</div>

@elseif ($userSettingsHandler->action === 'profilePhoto')

	<p>You can upload a photo of yourself to your user account. A small thumbnail of the photo will appear next to your
		reviews, articles, and comments that you post on Hostelz.com.</p>

	@include('Lib/fileListHandler', [ 'fileListMode' => 'photos' ])

	<h3>Upload New Photo</h3>

	@include('Lib/fileUploadHandler')

@elseif ($userSettingsHandler->action === 'points')

	<div class="row">
		<div class="col-md-12 mb-4">
			<div class="alert alert-info"><h3 style="margin: 0 0 ">Your current Hostelz.com points:
					<strong>{!! $userSettingsHandler->user->points !!}</strong></h3></div>
		</div>
	</div>

	<h4 class="hero-heading">Earning Points</h4>
	<p>These are some of the ways you can earn points:</p>
	<ul>
		<li>Post a review of any hostel you've stayed in (points are awarded after your review is approved and published
			on the site).
		</li>
		<li>Post a comment or travel tip using the comment form at the bottom of any city page.</li>
		<li>Make a booking.</li>
		<li>Upload a <a class="underline" href="@routeURL('user:settings', 'profilePhoto')">profile photo</a>.</li>
	</ul>

	<h4 class="hero-heading">Rewards and Achievement Levels</h4>
	<p>We're working on setting up special access and exclusive features for people who have earned a certain of points.
		Stay tuned.</p>

@endif



@section('pageBottom')

	@parent
@stop