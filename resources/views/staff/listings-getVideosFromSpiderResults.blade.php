@extends('layouts/admin')

@section('title', 'Get Videos From Spider Results - Hostelz.com')

@section('header')

    <style>

    </style>

    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Get Videos') !!}
        </ol>
    </div>
    
    <div class="container">
    
        @if (@$message != '')
            <div class="well">{!! $message !!}</div>
        @endif
        
        <h2><a href="{!! $listing->getURL() !!}">{{{ $listing->name }}}</a> - {{{ $listing->city }}}</h2>
    
        <p>
            {!! $videoEmbedHTML !!}
        </p>
    
        <p>
            Is this a video about this hostel? 
            <form method="post">
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                <input type="hidden" name="listingID" value="{!! $listing->id !!}">
                <input type="hidden" name="videoEmbedHTML" value="{{{ base64_encode($videoEmbedHTML) }}}">
                <input type="hidden" name="video" value="{{{ $video }}}">
                <input type="hidden" name="spiderResultID" value="{!! $spiderResult->id !!}">
                <button class="btn btn-lg btn-success" type="submit" name="answerSubmit" value="yes">Yes</button>
                &nbsp;
                <button class="btn btn-lg btn-danger" type="submit" name="answerSubmit" value="no">No</button>
            </form>
        </p>
        
        <br><br>({!! $remainingToDo !!})
    </div>

@stop
