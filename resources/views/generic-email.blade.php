@extends('layouts/email')

@section('content')

    @if (!@$plainText)
        <p><img src="{!! $message->embed(public_path().'/images/logo-header.png') !!}"></p>
    @endif
    
    {!! $text !!}
    
@stop
