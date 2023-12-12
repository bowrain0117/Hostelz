@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', 'Hostelz.com')

@section('header')
    <style>
        .hiddenQuestion {
        display: none;
        }
        .visibleQuestion {
        text-align: left;
        margin: 25px 0px 20px 0px;
        }
        button.next {
        display: block;
        margin: 18px 0px 0px 0px;
        }
        div.answers {
        margin: 5px 0px 0px 10px;
        }
        div.answer {
        margin: 5px 0px 0px 0px;
        }
        
        h3 {
         font-weight: 700;
        }
    </style>
@stop

@section('content')

<section class="pt-3 pb-5 container">
	<div class="row">
        <div class="col-12">
        			        
        @if ($message)
        	<div class="visibleQuestion">{!! $message !!}</div>
        @else 
        	<form method="post" name="theForm">
        	<?php $questionNum = 0; ?>
        	@foreach ($questions as $key => $question)
        		<input type="hidden" name="answers[{!! $key !!}][time]" id="answerTime{!! $key !!}">{{-- value set by doNext() --}}
        		<div class="hiddenQuestion" id="question{!! $key !!}">
        		@if ($question['type'] != '')
        			<?php $questionNum = $questionNum+1; ?>
        			<h3>Question {!! $questionNum !!} of {!! $questionCount !!}</h3>
        		@endif 
        		<div class="underlineLinks">{!! $question['questionText'] !!}</div>
        		<div class="answers">
        			@if ($question['type'] == 'string')
        				<input size="{!! $question['size'] !!}" maxlength="{!! $question['size'] !!}" name="answers[{!! $key !!}][answer]" onKeyPress="javascript:enableContinue({!! $key !!});return disableEnterKey(event)">
        			@elseif ($question['type'] == 'textarea')
        				<textarea name="answers[{!! $key !!}][answer]" onClick="javascript:enableContinue({!! $key !!})" rows=4 cols=70></textarea>
        			@elseif ($question['type'] == 'radio')
        				@foreach ($question['answers'] as $answerKey => $answer)
        					<div class="answer">
        					@if ($showPoints) ({!! $answer['points'] !!}) @endif 
        					<input type="radio" name="answers[{!! $key !!}][answer]" value="{!! $answerKey !!}" onClick="javascript:enableContinue({!! $key !!})" id="answer{!! $key !!}_{!! $answerKey !!}"> <label for="answer{!! $key !!}_{!! $answerKey !!}">{!! $answer['text'] !!}</label>
        					</div>
        				@endforeach 
        			@endif 
        		</div>
        		<button onClick="javascript:return doNext({!! $key !!}, @if ($key < count($questions)-1) {!! $key+1 !!} @else -1 @endif )" class="next" id="continue{!! $key !!}" @if ($question['type'] != '') disabled="disabled" @endif >Continue</button>
        		</div>
        	@endforeach 
        	</form>
        @endif 
 
		</div>
	</div>
</section>        
@stop

@section('pageBottom')

    <script type="text/javascript">
    
        @if (count($questions) > 1 && $message == '') 
            window.onbeforeunload = function() {  
                return "Not yet finished.";  
            };
        @endif
    
        $(document).ready(function() {
            doNext(-1,0);
        });
    
        var lastAnswerTime;
        
        function doNext(currentQuestion, nextQuestion) {
        	var tmp = new Date();
        	var currentTime = tmp.getTime();
        
        	if (currentQuestion == -1) {
        	} else {
        		answerTimeElement = document.getElementById("answerTime"+currentQuestion);
        		answerTimeElement.value = Math.round((currentTime-lastAnswerTime)/1000);
        		currentQuestDiv = document.getElementById("question"+currentQuestion);
        		currentQuestDiv.className = 'hiddenQuestion';
        	}
        	if (nextQuestion == -1) {
        	    window.onbeforeunload = function() { return null; };
        		return true;
        		/* document.theForm.submit(); */
        	} else {
        		nextQuestDiv = document.getElementById("question"+nextQuestion);
        		nextQuestDiv.className = 'visibleQuestion';
        	}
        
        	lastAnswerTime = currentTime;
        	return false;
        }
        
        function disableEnterKey(evt) {
        	var key;
        	if(window.event)
              key = window.event.keyCode; //IE
        	else
              key = evt.which; //firefox
        	return (key != 13);
        }
        
        function enableContinue(num) {
        	document.getElementById("continue"+num).disabled = false;
        }
    </script>

    @parent

@endsection
