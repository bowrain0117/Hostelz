{{--

Special inputs:
    $showModelIcon - true/false to whether to display the large icon (defaults to true).
    
--}}

<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@php
	$title = isset($title) ? $title : $formHandler->modelName;
@endphp

@extends('layouts/admin')

@section('title', $title . ' Edit')

@section('content')

	<div class="breadcrumbs">
		<ol class="breadcrumb" typeof="BreadcrumbList">
			{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
			{!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
			{!! breadcrumb('Staff', routeURL('staff-menu')) !!}

			@if ($formHandler->mode == 'searchForm')
				{!! breadcrumb(langGet('Staff.databaseModelNames.' . $title) . ' Search', routeURL(Route::currentRouteName())) !!}
			@elseif ($formHandler->mode == 'list' || $formHandler->mode == 'editableList')
				{!! breadcrumb(langGet('Staff.databaseModelNames.' . $title) . ' Results', routeURL(Route::currentRouteName())) !!}
			@else
				{!! breadcrumb(langGet('Staff.databaseModelNames.' . $title), routeURL(Route::currentRouteName())) !!}
			@endif
		</ol>
	</div>

	<div class="container">

		{{-- Error / Info Messages --}}

		@if (@$message != '')
			<br>
			<div class="well">{!! $message !!}</div>
		@endif

		@yield('aboveForm')

		{{-- Icon --}}

		@if (@$showModelIcon !== false)
			<div class="pull-right"
			     style="font-size: 60px">{!! langGet('Staff.icons.'.$formHandler->modelName, '') !!}</div>
		@endif

		{{-- Form/Results --}}

		<div class="staffForm">
			@if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
				<div class="row">
					<div class="col-md-10">
						@include('Lib/formHandler/doEverything', [ 'itemName' => langGet('Staff.databaseModelNames.' . $title), 'horizontalForm' => true ])

						@if ($formHandler->mode == 'update')
							<p><a href="/{!! Request::path() !!}">Back to edit the form</a></p>
						@endif
					</div>

					<div class="col-md-2">
						@yield('nextToForm')
					</div>
				</div>
			@else

				@include('Lib/formHandler/doEverything', [ 'itemName' => langGet('Staff.databaseModelNames.' . $title), 'horizontalForm' => isset($horizontalForm) ? $horizontalForm : true, 'showSearchOptionsByDefault' => false ])

			@endif
		</div>

		{{-- Below Form/Results stuff --}}

		@yield('belowForm')

	</div>

@stop

@section('pageBottom')

	@include('Lib/_postFormValue', [ 'valueName' => 'objectCommand' ])

	<script>
		@include('Lib/_setValueAndSubmit')
	</script>

	@parent

@endsection
