@extends('staff.edit-layout')

@section('aboveForm')
@stop


@section('nextToForm')
@stop


@section('belowForm')
    <hr>
    <a href="{{ route('slpStaff:create') }}"><i class="fa fa-plus-circle"></i> Create a New Special Landing Page </a>
@stop


@section('pageBottom')

    @parent

@endsection

