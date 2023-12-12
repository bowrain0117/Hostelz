<?php

use App\Models\CityInfo;
use App\Models\CountryInfo;

?>

@extends(
    'staff/edit-layout',
    [
		'showCreateNewLink' => false,
		'itemName' => "City Category Page Description",
  	]
)

@section('aboveForm')

	@if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

		<div class="navLinksBox">
			<ul class="nav nav-pills">
				<li>
					<a href="{!! $formHandler->model->urlOfSubject() !!}">{{{ $formHandler->model->nameOfSubject() }}}</a>
				</li>
			</ul>
		</div>

	@endif

@stop

@section('nextToForm')

	@if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

		<div class="list-group">
			<a href="#" class="list-group-item active">Related</a>
			<a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}"
			   class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!}
				History</a>
			<a href="{!! routeURL('staff-cityInfos', $formHandler->model->subjectID) !!}"
			   class="list-group-item">{!! langGet('Staff.icons.CityInfo') !!} City Info</a>
		</div>

	@endif

@stop

@section('pageBottom')

	<script>
        showWordCount('data[data]');
	</script>

	@parent

@endsection
