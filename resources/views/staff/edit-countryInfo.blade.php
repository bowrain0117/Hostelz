@extends('staff/edit-layout', [ 'horizontalForm' => false ])


@section('aboveForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                @if ($formHandler->model->country != '')
                    <li><a href="{!! $formHandler->model->getURL() !!}">View Country</a></li>
                @endif
                @if (auth()->user()->hasPermission('admin'))
                    <li><a class="objectCommandPostFormValue" data-object-command="searchRank" href="#">Search Rank</a></li>
                @endif
                <li><a href="http://wikipedia.org/w/wiki.phtml?search={!! urlencode($formHandler->model->city) !!}">Wikipedia Search</a></li>
                <li><a href="http://wikitravel.org/wiki/en/index.php?go=Go&search={!! urlencode($formHandler->model->city) !!}">Wikitravel Search</a></li>
                {{-- <a href="/admin/gpx.php?cityID={$qf->data.id}">GPX</a> --}}
            </ul>
        </div>
    
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'subjectType' => 'countryInfo', 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.AttachedText') !!} Descriptions</a>
            <a href="@routeURL('staff-useGeocodingInfo')?mode=list&search[country]={!! urlencode($formHandler->model->country) !!}&search[region]=&comparisonTypes[region]=isEmpty&search[isLive]=1" class="list-group-item"><span class="pull-right">&raquo;</span><i class="fa fa-map-pin"></i>Use Geocoding for Regions</a>
            
            @if (auth()->user()->hasPermission('admin'))
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-ads', [ 'placeID' => $formHandler->model->id, 'placeType' => 'CountryInfo' ], 'search') !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Ad') !!} Ads</a>
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-searchRank', [ 'placeID' => $formHandler->model->id, 'placeType' => 'CountryInfo' ], 'search') !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.SearchRank') !!} Search Ranks</a>
            @endif
             
        </div>
        
    @endif
    
@stop


@section('belowForm')

    {{--
    @if ($formHandler->mode == 'searchAndList')
        
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'editableList'], ['page']) !!}">Multiple Edit/Delete</a></p>
            
    @elseif ($formHandler->mode == 'editableList')
                
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'searchAndList'], ['page']) !!}">Return to the Regular List</a></p>

    @endif
    --}}
    
@stop
