<?php
    use App\Models\CityInfo;
    use App\Models\CountryInfo;
?>

@extends('staff/edit-layout', [ 'showCreateNewLink' => false, 'itemName' => "Special Hostels Text" ])


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="{!! $formHandler->model->urlOfSubject() !!}">{{{ $formHandler->model->nameOfSubject() }}}</a></li>
            </ul>
        </div>
    
    @endif

@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
        </div>
        
    @endif
    
@stop


@section('belowForm')

    @if ($formHandler->mode == 'searchAndList' && @$formHandler->inputData['type'])

        <div style="margin-top:10px;">
            <b style="margin-bottom:10px;">Create new text:</b>
            <ul>
            @foreach (['header', 'top', 'middle', 'bottom'] as $placement)
                <li><a href="{!! routeURL('staff-citySpecialText', 'new') !!}?data[subjectType]=cityInfo&data[subjectID]={{ $formHandler->inputData['subjectID'] }}&data[type]={{ $formHandler->inputData['type'] }}&data[subjectString]={{ $placement }}" style="margin: 0px 5px;">{{ $placement }}</a></li>
            @endforeach
            </ul>
        </div>
        <div style="margin-top:20px;">
        	<h2><b>How to create new text fields</b></h2>
        	<p>It is important not to create duplicated text fields. Below you find instructions on how to use the system.</p>
			<ol>
				<li>Click "create new text" - and add the actual content</li>
				<li>Submit Text</li>
				<li>Return to the page that shows all of the text sections for the page</li>
				<li>Choose the new one you just created for editing</li>
			</ol>
			<p><b>Important</b>: When creating a new text, do not use the "back" button in the browser. This may lead to duplicated text fields.</p>
        </div>
        
    @endif

@stop


@section('pageBottom')

    <script>
        showWordCount('data[data]');
    </script>
    
    @parent

@endsection
