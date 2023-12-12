@extends('layouts/admin')

@section('content')

    <p>Description: {!! Request::input('description') !!}</p>
    <p>Invoice Date: {!! Request::input('date') !!}</p>
    <p>Amount: {!! Request::input('currency') !!} {!! Request::input('amount') !!}</p>
        
@stop
