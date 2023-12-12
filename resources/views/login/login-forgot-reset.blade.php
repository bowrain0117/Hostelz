@extends('login.auth-layout')

@section('login-title')@langGet('SeoInfo.SignUpResetTitle') @stop

@section('login-description')@langGet('SeoInfo.SignUpResetDescription') @stop

@section('login-header')
    Reset your Password
@stop

@section('login-form')
    <form method="post">
        <input type="hidden" name="_token" value="{!! csrf_token() !!}">

        @if (@$status == 'invalidLink')
            <div class="alert alert-danger">
                <p><i class="fa fa-exclamation-circle"></i> Sorry, this link is no longer valid. Request a new <a href="{!! routeURL('login-forgot') !!}">password here</a></p>
            </div>
        @elseif (@$status == 'success')
            <div class="alert alert-success">Your password has been updated.</div>
        @else
            <p>Please choose a new password for your account.</p>
            <div class="form-group">
                <label for="password" class="form-label">Enter new password</label>
                <input class="form-control" id="password" name="password" type="password" autofocus required
                    placeholder="Enter new password" autocomplete="off"
                    data-msg="Please choose a new password for your account.">
                @if (@$errors)
                    <div class="text-danger bold italic">{!! $errors->first('password') !!}</div>
                @endif
            </div>

            <button class="btn btn-primary my-3" type="submit">Submit</button>
        @endif
    </form>
    <hr class="my-4">

    <p class="text-left">
        <small>
            <a href="{!! routeURL('login') !!}" class="form-text small">Back to Login</a>
        </small>
    </p>

@stop
