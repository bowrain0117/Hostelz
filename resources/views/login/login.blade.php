@extends('login.auth-layout')

@section('login-title')@langGet('SeoInfo.SignUpMetaTitle') @stop

@section('login-description')@langGet('SeoInfo.SignUpMetaDescription') @stop

@section('login-header')
    Login to Access Your Ultimate Hostel Portfolio!
@stop

@section('login-form')
    @include('login.login-form')
@stop
