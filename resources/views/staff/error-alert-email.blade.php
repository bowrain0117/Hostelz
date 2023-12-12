@extends('layouts/email')

@section('content')

    <p>{{{ $errorText }}}</p>
    
    @if ($context)
        <p>
            @foreach ($context as $key => $value)
                {{{ $key }}}: {{{ $value }}}<br>
            @endforeach
        </p>
    @endif
        
@stop
