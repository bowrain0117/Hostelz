@extends('staff.edit-layout')

@section('aboveForm')
@stop


@section('nextToForm')
@stop


@section('belowForm')
    <hr>
    <a href="{{ route('staff:district:edit') }}"><i class="fa fa-plus-circle"></i> Create a New District</a>
@stop


@section('pageBottom')

    @parent

@endsection

