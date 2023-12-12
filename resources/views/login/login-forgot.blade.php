@extends('login.auth-layout')

@section('login-title')@langGet('SeoInfo.SignUpForgetPWTitle') @stop

@section('login-description')@langGet('SeoInfo.SignUpForgetPWDescription') @stop

@section('login-header')
    Reset your Password
@stop

@section('login-form')
<form method="post" class="form-validate">
    <input type="hidden" name="_token" value="{!! csrf_token() !!}">                    
    @if (@$status == 'userNotFound')
        <div class="alert alert-danger">
            Sorry, there is no user on Hostelz.com with this email address.
        </div>
        <p><strong><a href="{!! routeURL('userSignup') !!}">Sign up now</a></strong> to get started.</p>
    @elseif (@$status == 'success')
        <div class="alert alert-success">
            Get ready to reset your password! We just sent you an email to {{ $email }} with step-by-step instructions.
        </div>
    @else
        <p>We will send you an email with step-by-step instructions on how to reset your password.</p>
        <div class="form-group">
            <label for="email" class="form-label">Your Email</label>
            <input class="form-control" id="email" type="email" name="email" autofocus required value="{{{ @$email }}}" placeholder="Your Email Address" autocomplete="off" required="" data-msg="Please enter your email">
            @if (@$errors) 
                <div class="text-danger bold italic">{!! $errors->first('email') !!}</div>
            @endif
        </div>
        <button class="btn btn-lg btn-block btn-primary" type="submit">Submit</button>
    @endif
</form>
<hr class="my-4">

<p class="text-left">
    <small>
        <a href="{!! routeURL('login') !!}" class="form-text small">< Back to Login</a>
    </small>
</p>
@stop