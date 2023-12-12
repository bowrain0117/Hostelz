@extends('staff/edit-layout')


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a class="objectCommandPostFormValue" data-object-command="preview" href="#">Preview</a></li>
                @if ($formHandler->model->placement != '' && $formHandler->model->status == 'published')
                    <li><a target="_blank" href="{!! $formHandler->model->url() !!}">View Article</a></li>
                @endif
            </ul>
        </div>
        
        @if (@$fileList)
    		<p>@include('Lib/fileListHandler', [ 'fileListMode' => 'photos' ])</p>
            
            <h3>Upload New Photos</h3>
            @include('Lib/fileUploadHandler')
        @endif
    
    @endif
        
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            @if (auth()->user()->hasPermission('staffEditUsers'))
                @if ($formHandler->model->userID)
                    <a href="{!! routeURL('staff-users', [ $formHandler->model->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
                @else
                    <a href="#" class="list-group-item disabled">(No user ID)</a>
                @endif
            @endif
            {{-- <a href="{!! routeURL('staff-cityPics', $formHandler->model->id) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Pic') !!} City Pics</a> --}}
        </div>
        
    @endif
                        
@stop


@section('belowForm')


    {{--
    $smarty->assign('macros',
    	array(
    		array('name'=>'tooShort','text'=>"This article is shorter than our minimum of ____ words.  It would need to be longer and more detailed to be considered as a paid article."),
    	)
    );
    
        {foreach from=$macros item=macro}
        	{if $macro.name}{cycle values="<tr><td>,<td>"}<span style="text-decoration:underline" onClick="javascript:void(document.theForm.addComment.value=document.theForm.addComment.value+'{$macro.text|replace:'{hostelID}':$qf->data.hostelID|replace:'"':'&#34;'|escape:'javascript'}')">{$macro.name}</span>{/if}{* "|replace:"\n":'\n' *}
    	{/foreach}
        
    --}}

    {{--
    @if ($formHandler->mode == 'searchAndList')
        
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'editableList'], ['page']) !!}">Multiple Edit/Delete</a></p>
            
    @elseif ($formHandler->mode == 'editableList')
                
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'searchAndList'], ['page']) !!}">Return to the Regular List</a></p>
    @endif
    --}}
    
      @if ($formHandler->mode == 'updateForm')
        <div class="row" style="margin-top: 20px;">
            <div class="col-md-10 text-center">
                {{-- (Handled by javascript in edit-layout.blade.php) --}}
                @if ($formHandler->model->status != 'accepted')
                    <button class="btn btn-success setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="accepted">Approve</button>
                @endif
            </div>
        </div>
        <br>
        <h3><b>How to edit...</b></h3>
        <p><b>Insert Photos:</b> Use <code>[pic:caption]</code> - When uploading a photo, you can add a "caption" to the photo". This is what you need to add here.</p>
        <p><b>Current Year</b>: Use <code>[year]</code> for current year. It works in text and title.</p>
        <p style="margin-top: 30px;">Below you find HTML examples to add lists</p>
        <p><b>Ordered List/ Numbers:</b> &lt;ol&gt;list items&lt;/ol&gt;</p>
        <p><b>Unordered List/ Bullet List:</b> &lt;ul&gt;list items&lt;/ul&gt;</p>
        <p><b>Add List Item within the Unordered or Ordered list:</b> &lt;li&gt;text&lt;/li&gt;</p>
        <p><b>The final code should looke like</b> <br> &lt;ol&gt; <br>&lt;li&gt;liste item 1&lt;/li&gt;<br>&lt;li&gt;liste item 2&lt;/li&gt;<br>&lt;li&gt;liste item 3&lt;/li&gt;<br>&lt;/ol&gt;</p>

        <hr>

        @if (auth()->user()->hasPermission('admin'))
            <p><strong>Private preview:</strong> <a target="_blank" href="@routeURL('article-private-preview', [ $formHandler->model->id, $formHandler->model->privatePreviewPassword() ], 'publicSite')">@routeURL('article-private-preview', [ $formHandler->model->id, $formHandler->model->privatePreviewPassword() ], 'publicSite')</a></p>
        @endif
    @endif
    
@stop


@section('pageBottom')

    <script>
        showWordCount('data[originalArticle]');
        showWordCount('data[finalArticle]');
    </script>

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])
    
    @parent
    
@endsection
