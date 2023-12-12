{{--
Optional Variables to Pass:
    
    - addOpenFormTag / addCloseFormTag (default: true)
    - returnToURL - URL to return to after completing and action.

    - listContainerClass
    - formClass
    - horizontalForm - true/false
    - formGroupClass (used by form.blade.php)
    - showResetFormLink - Set to false to not show reset form link.
    - showCreateNewLink - Set to false to not show create new link.
    - showAdvancedSearchLink
    - buttonContainerClass
    
    - itemName
    - showTitles / showListTitle - Set to false to not show titles.    
    - showSearchOptionsByDefault - True or false to show/hide the search panel by default.
    
--}}

<?php

use Illuminate\Support\Arr;

?>

@if (!isset($buttonContainerClass) && @$horizontalForm) 
    <?php $buttonContainerClass = ''; ?>
@endif

@if ($formHandler->shouldUseFormTags())

    @if (@$addOpenFormTag !== false)
        {{-- Note: When searching method has to be "get" in order for pagination links to work. --}}
        <form 
            @if ($formHandler->mode == 'searchForm' || $formHandler->mode == 'searchAndList') 
                method="get" action="/{!! Request::path() !!}"
            @else 
                method="post" action="{!! Request::fullUrl() !!}" {{-- we keep any GET variables in the URL if posting the form --}}
            @endif 
            
            @if (isset($formClass)) 
                class="{!! $formClass !!}"
            @else
                class="formHandlerForm @if ($formHandler->mode == 'editableList') form-inline @elseif (@$horizontalForm) form-horizontal @endif" 
            @endif
        >
    @endif
    
    @if (in_array('csrf', Route::getCurrentRoute()->middleware()) && (in_array($formHandler->mode, [ 'insertForm', 'updateForm', 'editableList'] ) || $formHandler->mode == 'display' && $formHandler->isDeleteAllowed()))
        <input type="hidden" name="_token" value="{!! csrf_token() !!}">
    @endif
    
    {!! htmlHiddenInputFromArray($formHandler->persistentValues) !!}
    
@elseif ($formHandler->mode == 'display' && @$horizontalForm)

    <div class="form-horizontal">

@endif

@if ($formHandler->mode == 'searchForm' || $formHandler->mode == 'searchAndList')

    @if (@$showTitles !== false)
        <h1>{!! $itemName !!} Search</h1>
    @endif
    
    @if ($formHandler->mode == 'searchAndList')
        <?php
            if (isset($showSearchOptionsByDefault)) {
                $showSearchOptions = $showSearchOptionsByDefault;
            } else {
                $showSearchOptions = (! Request::has('mode') && ! Request::has('page') && $formHandler->list->total() > $formHandler->listPaginateItems);
            }
        ?>
        <div class="panel panel-default">
            <div class="panel-heading" id="showHideSearchOptions">
                <i class="fa @if ($showSearchOptions) fa-caret-square-o-up @else fa-caret-square-o-down @endif"></i>
                <a class="accordion-toggle" data-toggle="collapse" href="#searchOptions">Show/Hide Search Options</a>
            </div>
            <div id="searchOptions" class="panel-collapse collapse @if ($showSearchOptions) in @endif">
                <div class="panel-body">
    @endif
    
    @if (@$showAdvancedSearchLink)
        <p>
            Switch to 
            @if ($advanced)
                <a href="/{!! Request::path() !!}">Simple Search</a>
                <input type="hidden" name="advanced" value=1>
            @else
                <a href="/{!! Request::path() !!}{!! $formHandler->persistentValuesQueryString([ 'advanced' => 1 ]) !!}">Advanced Search</a>
            @endif
        </p>
    @endif
    
    @include('Lib/formHandler/form')
    
    @if (@$buttonContainerClass != '') <div class="{!! $buttonContainerClass !!}"> @endif
        <button class="btn btn-primary submit" name="mode" @if (in_array('searchAndList', $formHandler->allowedModes)) value="searchAndList" @else value="list" @endif type="submit">Search</button>
     @if (@$buttonContainerClass != '') </div> @endif
   
    @if (@$showResetFormLink !== false)
        {{-- Note:  We include the mode in the persistant value in case it's search vs. searchAndList, etc. --}}
        <a class="pull-right resetFormLink" href="/{!! Request::path() !!}{!! $formHandler->persistentValuesQueryString([ 'mode' => $formHandler->mode ]) !!}">Reset Form</a>
    @endif
    
    @if (in_array('insert', $formHandler->allowedModes) && @$showCreateNewLink !== false)
        <br><br><a href="/{!! Request::path() !!}/new"><i class="fa fa-plus-circle"></i> @if (@$itemName != '')Create a New {!! $itemName !!} @else Create New @endif</a>
    @endif
    
    @if ($formHandler->mode == 'searchAndList')
        </div></div></div>
    @endif

@endif

@if ($formHandler->mode == 'list' || $formHandler->mode == 'editableList' || $formHandler->mode == 'searchAndList')
    
    @if (@$addFormTags !== false && $formHandler->shouldUseFormTags() && $formHandler->mode == 'searchAndList' && in_array('multiDelete', $formHandler->allowedModes))
        {{-- Close the form tag from the search form, and open a new form for multiDelete (because it has to be POST, not GET). --}}
        </form>
        <form method="post">
        <input type="hidden" name="_token" value="{!! csrf_token() !!}">
    @endif
    
    @if (@$showTitles !== false && @$showListTitle !== false)
        @if ($formHandler->mode == 'list' || $formHandler->mode == 'editableList')
            <h1>{!! @$itemName == '' ? 'List' : Str::plural($itemName) !!}</h1>
        @endif
        @if ($formHandler->list)
            <p><small class="text-muted">{!! $formHandler->list->total() !!} results found.</small></p>
        @endif
        <br>
    @endif
    
    <div class="row">
        <div class="@if (@$listContainerClass) {!! $listContainerClass !!} @else col-md-12 @endif">
            {!! $formHandler->list->appends(Arr::except(Request::query(), 'page'))->links() !!}

            @if ($formHandler->mode == 'editableList' && $formHandler->list && !$formHandler->list->isEmpty())
                @include('Lib/formHandler/editableList')
                <p>
                    @if (in_array('multiDelete', $formHandler->allowedModes))
                        <button class="btn btn-danger submit" name="mode" value="multiDelete" type="submit" onClick="javascript:return confirm('Delete all selected.  Are you sure?')">Delete Selected</button>
                    @endif
                    <button class="btn btn-primary submit" name="mode" value="multiUpdate" type="submit">Submit Changes</button>
                </p>
            @else
                @include('Lib/formHandler/list')
                
                @if (in_array('multiDelete', $formHandler->allowedModes))
                    <p class="multiSelectHiddenIfNone" style="display: none">
                        <button class="btn btn-danger" name="mode" value="multiDelete" type="submit" onClick="javascript:return confirm('Delete all selected.  Are you sure?')">Delete Selected</button>
                        <br>
                    </p>
                @endif
            @endif
            
            {!! $formHandler->list->appends(Arr::except(Request::query(), 'page'))->links() !!}

        </div>
    </div>
    
    @if (in_array('insert', $formHandler->allowedModes) && @$showCreateNewLink !== false)
        <a href="/{!! Request::path() !!}/new"><i class="fa fa-plus-circle"></i> @if (@$itemName != '')Create a New {!! $itemName !!} @else Create New @endif</a>
    @endif
    
@elseif ($formHandler->mode == 'insertForm') 

    @if (@$showTitles !== false)
        <h1>New {!! $itemName !!}</h1>
        <br>
    @endif
    
    @include('Lib/formHandler/form')
    
    @if (@$buttonContainerClass != '') <div class="{!! $buttonContainerClass !!}"> @endif
        <button class="btn btn-primary submit" name="mode" value="insert" type="submit">Submit</button>
    @if (@$buttonContainerClass != '') </div> @endif
    
    @if (@$showResetFormLink !== false)
        <a class="pull-right resetFormLink" href="/{!! Request::path() !!}{!! $formHandler->persistentValuesQueryString() !!}">Reset Form</a>
    @endif
    
@elseif ($formHandler->mode == 'updateForm' || $formHandler->mode == 'display')

    @if (@$showTitles !== false) 
        @if ($formHandler->mode == 'updateForm')
            <h1>Edit {!! @$itemName !!}</h1> 
        @else
            <h1>{!! @$itemName !!}</h1> 
        @endif
    @endif
    
    @if ($formHandler->mode == 'updateForm' && Lang::has($formHandler->languageKeyBase.'.forms.pageDescription.edit'))
        <p>{!! langGet($formHandler->languageKeyBase.'.forms.pageDescription.edit') !!}</p>
    @endif
    
    <br>
    
    @include('Lib/formHandler/form')
    
    @if (@$buttonContainerClass != '') <div class="{!! $buttonContainerClass !!}"> @endif
    
        @if (in_array('update', $formHandler->allowedModes))
            <button class="btn btn-primary submit" name="mode" value="update" type="submit">Update</button>
        @endif
    
        @if ($formHandler->isDeleteAllowed())
            <br>
            <button class="btn btn-xs btn-danger submit" name="mode" value="delete" type="submit" onClick="javascript:return confirm('Delete.  Are you sure?')">Delete</button>
        @endif
        
     @if (@$buttonContainerClass != '') </div> @endif


@elseif ($formHandler->mode == 'insert')

    <br>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-success"><i class="fa fa-check-circle"></i>&nbsp; {!! @$itemName !!} Created Successfully.</div>
        </div>
    </div>
    
    @if (@$returnToURL != '')
        <a href="{!! $returnToURL !!}">Return</a>
    @endif

@elseif ($formHandler->mode == 'update')

    <br>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-success"><i class="fa fa-check-circle"></i>&nbsp; {!! @$itemName !!} Updated Successfully.</div>
        </div>
    </div>
    
    @if (@$returnToURL != '')
        <a href="{!! $returnToURL !!}">Return</a>
    @endif

@elseif ($formHandler->mode == 'multiUpdate')

    <br>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-success"><i class="fa fa-check-circle"></i>&nbsp; Updated Successfully.</div>
        </div>
    </div>

    @if (@$returnToURL != '')
        <a href="{!! $returnToURL !!}">Return</a>
    @endif

@elseif ($formHandler->mode == 'delete')

    <br>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-warning">{!! @$itemName !!} Deleted.</div>
        </div>
    </div>
    
    @if (@$returnToURL != '')
        <a href="{!! $returnToURL !!}">Return</a>
    @endif

@elseif ($formHandler->mode == 'multiDelete')

    <br>
    <div class="row">
        <div class="col-md-6">
            @if ($formHandler->count)
                <div class="alert alert-warning">
                    @if ($formHandler->count > 1) 
                        The {!! $formHandler->count !!} selected {!! Str::plural(@$itemName) !!} have been deleted.
                    @else 
                        The selected {!! $itemName !!} has been deleted.
                    @endif
                </div>
            @else
                 <div class="alert alert-danger">No items selected.</div>
            @endif
        </div>
    </div>
    
    @if (@$returnToURL != '')
        <a href="{!! $returnToURL !!}">Return</a>
    @endif

@elseif ($formHandler->mode == 'fatalError')

    <br>
    <div class="row">
        <div class="col-md-6">
            <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i> {!! langGet('global.fataError') !!}</div>
        </div>
   </div>

    @if (@$returnToURL != '')
        <a href="{!! $returnToURL !!}">Return</a>
    @endif

@endif

@if (@$addCloseFormTag !== false && $formHandler->shouldUseFormTags())
    </form>
@elseif ($formHandler->mode == 'display' && @$horizontalForm)
    </div>
@endif
