@extends('staff/edit-layout')


@section('header')
    <style>
		 .question {
			text-align: left;
			margin: 10px 0px 5px 0px;
		 }
		 div.answer {
			margin: 0px 0px 0px 5px;
			font-weight: 700;
		 }
		 .rightAnswer {
			color: green !important;
		 }
		 .wrongAnswer {
			color: red !important;
		 }
    </style>
@stop

@section('aboveForm')

@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! routeURL('staff-questionSets', [ $formHandler->model->questionSetID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.QuestionSet') !!} Question Set</a>             <a href="{!! routeURL('staff-mailMessages', [ $formHandler->model->referenceCode ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.MailMessage') !!} Mail (from reference)</a>           
            @if (auth()->user()->hasPermission('staffEditUsers') && $formHandler->model->userID)
                <a href="{!! routeURL('staff-users', [ $formHandler->model->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
            @endif
        </div>
        
    @endif
                        
@stop


@section('belowForm')

    <h2>Results</h2>
        
    @if (($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display') && $formHandler->model->results)
    	<?php 
    	    $resultsDisplay = $formHandler->model->prepResultsForDisplay();
        ?>
        
    	@foreach ($resultsDisplay['answers'] as $answer)
    		<h3>{!! $answer['question']['category'] !!}</h3>
    		<div class=question>
    		    {{{ wholeWordTruncate(strip_tags($answer['question']['questionText']), 300) }}}
    		</div>
    		
    		<div class="answer @if ($answer['points'] > 0) rightAnswer @elseif ($answer['points'] < 0) wrongAnswer @else neutralAnswer @endif ">
    			[{!! $answer['time'] !!}s] 
    			({{{ $answer['points'] }}}) {{{ $answer['answerText'] }}}
    		</div>
    	@endforeach 
    	
    	<h2>Totals</h2>
    	<table class=qfForm>
        	@foreach ($resultsDisplay['categoryTotals'] as $category => $points)
        		<tr><td class=qfLabel>{{{ $category }}}<td class="qfField rightAnswer">{{{ @$points['positive'] }}}<td class="qfField wrongAnswer">{{{ @$points['negative'] }}}<td class=qfLabel>Total: {!! @$points['positive'] + @$points['negative'] !!}
        	@endforeach 
        	<tr><td colspan=3>&nbsp;<td class=qfLabel>Overall Total: <b>{{{ $resultsDisplay['totalPoints'] }}}</b>
    	</table>
    @endif 

@stop

@section('pageBottom')
    
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])

    @parent
    
@endsection
