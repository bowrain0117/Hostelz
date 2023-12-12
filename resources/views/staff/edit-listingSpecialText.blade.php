<?php
    use App\Models\CityInfo;
    use App\Models\CountryInfo;
?>

@extends('staff/edit-layout', [ 'showCreateNewLink' => false, 'itemName' => "Special Listing Text" ])


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
            <a href="{!! routeURL('staff-cityInfos', $formHandler->model->subjectID) !!}" class="list-group-item">{!! langGet('Staff.icons.CityInfo') !!} City Info</a>
        </div>
        
    @endif
    
@stop

@section('belowForm')

    <div style="margin-top:20px;">
        <h4><b>Shortcodes we can use:</b></h4>
        <p class="my-2"><code>[otaMainLink]</code></p>
        <p class="small italic my-2">link for reservation</p>
        <p class="my-2"><code>[hostelName]</code></p>
        <p class="small italic my-2">plain hostel name</p>
        <p class="my-2"><code>[hostelNameOrder]</code></p>
        <p class="small italic my-2">changes order of name when: max 3 words</p>
        <p class="my-2"><code>[hostelNameListingLink]</code></p>
        <p class="small italic my-2">link to Hostelz listing</p>

    	<h2><b>How to create new text fields</b></h2>
    	<p>It is important not to create duplicated text fields. Below you find instructions on how to use the system.</p>
		<ol>
			<li>Submit Text</li>
			<li>Return to the listing edit page</li>
		</ol>
		<p><b>Important</b>: When creating a new text, do not use the "back" button in the browser. This may lead to duplicated text fields.</p>
    </div>

@stop


@section('pageBottom')

    <script>
        showWordCount('data[data]');
    </script>
    
    @parent

@endsection
