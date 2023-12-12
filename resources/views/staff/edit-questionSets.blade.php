@extends('staff/edit-layout')

@section('aboveForm')

@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-questionResults', [ 'questionSetID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.QuestionResult') !!} Results</a>
        </div>
        
    @endif
                        
@stop


@section('belowForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
        <br><br>
        <p>
            <a class="objectCommandPostFormValue btn btn-info btn-xs" data-object-command="duplicate" href="#">Make Duplicate of Question Set</a>
        </p>
        
        <p>
            <ul>
                <li>Triple line spaces may be used as needed to make grouping of questions more clear.</li>
                <li>Put URLs in the question in brackets "[...]" to make URLs clickable.</li>
            </ul>
        </p>
        
        <p>
            Create questionsAsk URL for referenceCode (typically we use the mail ID of their email): 
            <input id="generateQuestionAskUrl" size=50> <div id="genUrlResult"></div>
        </p>
    @endif 
    
@stop

@section('pageBottom')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
        <script type="text/javascript">
            $('#generateQuestionAskUrl').keypress(function (e) {
                if (e.which == 13) {
                    $.get("?objectCommand=generateAskURL&referenceCode="+$(this).val(), function(data) {
                        $('#genUrlResult').html(data);
                    });
                    e.preventDefault();
                }
            });
        </script>
    @endif 
    
    @parent
@stop
