<?php
Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', langGet('User.menu.PlaceDescriptions').' - Hostelz.com')

@section('header')

    <style>
        .status div {
            padding: 4px 8px;
            font-weight: 600;
            text-align: center;
        }
        
        .wordCount {
            font-size: 13px;
            color: #222;
            font-weight: 700;
        }
    </style>

    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            @if ($formHandler->mode == 'list')
                {!! breadcrumb(langGet('User.menu.PlaceDescriptions')) !!}
            @else
                @breadcrumb(langGet('User.menu.PlaceDescriptions'), routeURL('placeDescriptions'))
                {!! breadcrumb('Place Description') !!}
            @endif
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">@langGet('Staff.icons.CityInfo')</div>

        @if ($formHandler->list)
        
            <h1 class="hero-heading h2">@langGet('User.menu.PlaceDescriptions')</h1>
            
            <h3 class="text-danger"><strong>Note:</strong> We are ending our Place Descriptions program soon.  After January 10th, 2019 we will no longer accept any new Place Descriptions.</h3>
            
            <h4>Be sure to read the <a href="{!! routeURL('placeDescriptions:instructions') !!}"><strong>instructions</strong></a> before you begin.</h4>
            <br>
            
            @if ($message != '')
                <div class="well">{!! $message !!}</div>
            @endif
        
            @if ($formHandler->list->isEmpty())
            
        		<p>You don't yet have any descriptions in your list.</p>

            @else
        
                <table class="table table-hover table-striped">
                    
                    @foreach ($formHandler->list as $attachedText)
                        <tr>
                            <td><a href="/{!! Request::path() !!}/{!! $attachedText->id !!}">{!! $attachedText->nameOfSubject() !!}</a></td>
                            <td>({!! with(new Languages($attachedText->language))->name !!})</td>
                            <td class="status">@include('user/placeDescriptions/_status')</td>
                        </tr>
                    @endforeach
                    
                </table>
            @endif
            
            {!! $formHandler->list->links() !!}
            
    	    @if ($formHandler->list->where('status', 'draft')->count() >= $MAX_DRAFT_DESCRIPTIONS)
    	        <p class="text-center background-primary-lt" style="padding: 5px">
    	            You can only have up to {!! $MAX_DRAFT_DESCRIPTIONS !!} drafts at one time.  Please complete or delete some of the drafts before adding new ones.
    	        </p>
    	    @else
    	        <h3 class="text-center background-primary-lt underlineLinks" style="padding: 5px">
    	            <a href="{!! routeURL('placeDescriptions:findCities') !!}">Find Cities/Regions/Countries to Write About</a>
    	        </h3>
    	    @endif
            
            <p>We typically approve descriptions about once a month, so it may take up to a month for yours to be approved. You can view your payment balance and payment information on the <a href="{!! routeURL('user:yourPay') !!}"><strong>Your Pay</strong></a> page.</p>
            <p>Draft or returned descriptions will be deleted if they haven't been updated in more than {!! AttachedText::DAYS_TO_HOLD_DRAFT_DESCRIPTIONS !!} days.</p>
            
        @elseif ($formHandler->mode == 'updateForm' || $formHandler->mode == 'display')
        
            <?php $attachedText = $formHandler->model; /* for convenience */ ?>

            <h1 class="hero-heading h2">{{{ $attachedText->nameOfSubject() }}} Description</h1>
            <p>View Hostelz.com's <a href="{!! $attachedText->urlOfSubject() !!}" class="underline">{{{ $attachedText->nameOfSubject() }}}</a> page.</p>
            <hr>
            <h3 class="status">@include('user/placeDescriptions/_status', [ 'attachedText' => $attachedText ])</h3>
            <hr>
            <h4>Be sure to read the <a href="{!! routeURL('placeDescriptions:instructions') !!}"><strong>instructions</strong></a> before you begin.</h4>

            <br>
            
            <div class="staffForm">
                <div class="row">
                    <div class="col-md-10">

                        @if ($formHandler->mode == 'updateForm')
                    
                            @include('user/placeDescriptions/_warnings')
    
                            <form method="post" class="formHandlerForm form-horizontal">
                                
                                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                    
                                @include('Lib/formHandler/form', [ 'horizontalForm' => true ])
                                
                                <p class="text-center">
                                    <button class="btn btn-info submit" name="updateAndSetStatus" value="draft" type="submit">Save Draft</button>
                                    <button class="btn btn-primary submit" name="updateAndSetStatus" value="submitted" type="submit">Submit Completed Text</button>
                                
                                    <br>
                                    <button class="btn btn-xs btn-danger submit" name="mode" value="delete" type="submit" onClick="javascript:return confirm('Delete.  Are you sure?')">Delete</button>
                                </p>
                            
                            </form>
                        
                        @else
                        
                            @include('Lib/formHandler/form', [ 'horizontalForm' => true ])

                        @endif
                        
                    </div>
                        
                    <div class="col-md-2">
                        
                        @if ($attachedText->subjectType == 'cityInfo')
                            <div class="well">
                                We also appreciate any photos you want to submit of the hostel's city! 
                                <b>To upload city photos of {!! $attachedText->nameOfSubject() !!}, click <a href="{!! routeURL('submitCityPics', $attachedText->subjectID) !!}">here</a></b>.
                            </div>
                        @endif
                    </div>
                </div>
            </div>
                
        @else
            <div class="clearfix"></div>
            
            @if ($formHandler->mode == 'update') 
                <div class="row"><div class="col-md-6">
                    @include('user/placeDescriptions/_warnings', [ 'attachedText' => $formHandler->model ])
                </div></div>
            @endif
            
            @include('Lib/formHandler/doEverything', [ 'itemName' => 'Description', 'horizontalForm' => true, 'showTitles' => false, 'returnToURL' => routeURL('placeDescriptions') ])
            
        @endif
        
    </div>

@stop

@section('pageBottom')

    @if ($formHandler->mode == 'updateForm')
    
        <script>
            {{-- Word Counts --}}            
            showWordCount('data[data]');
        </script>
    
    @endif
    
    @parent

@endsection
