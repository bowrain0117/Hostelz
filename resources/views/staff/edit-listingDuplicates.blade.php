@extends('staff/edit-layout')


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="{!! routeURL('staff-mergeListings', [ 'showThese', $formHandler->model->listingID.','.$formHandler->model->otherListing ]) !!}">Merge</a></li>
            </ul>
        </div>
        
    @endif
        
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            
            @if ($formHandler->model->listing)
                <a href="{!! routeURL('staff-listings', [ $formHandler->model->listingID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!} {{{ $formHandler->model->listing->name }}}</a>
            @else
                <a href="#" class="list-group-item disabled">(Listing {!! $formHandler->model->listingID !!} missing!)</a>
            @endif

            @if ($formHandler->model->otherListingListing)
                <a href="{!! routeURL('staff-listings', [ $formHandler->model->otherListing ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!} {{{ $formHandler->model->otherListingListing->name }}}</a>
            @else
                <a href="#" class="list-group-item disabled">(Other listing {!! $formHandler->model->otherListing !!} missing!)</a>
            @endif            
            
            @if (auth()->user()->hasPermission('staffEditUsers'))
                @if ($formHandler->model->userID)
                    <a href="{!! routeURL('staff-users', [ $formHandler->model->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
                @else
                    <a href="#" class="list-group-item disabled">(No user ID)</a>
                @endif
            @endif
        </div>
        
    @endif
                        
@stop


@section('belowForm')
    
@stop


@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'listingID', 'placeholderText' => "Search by listing ID, name, or city." ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'otherListing', 'placeholderText' => "Search by listing ID, name, or city." ])
    
    @parent

@endsection
