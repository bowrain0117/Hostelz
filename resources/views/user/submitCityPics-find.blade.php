<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true, 'showHeaderSearch' => false ])

@section('title', langGet('User.menu.SubmitCityPics').' - Hostelz.com')

@section('header')
    @parent
@stop

@section('content')

@include('user.navbarDashboard')

<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb(langGet('User.menu.SubmitCityPics')) !!}
        </ol>
    </div>
    
    <h1 class="hero-heading h2">{!! langGet('Staff.icons.Pic') !!} @langGet('User.menu.SubmitCityPics')</h1>
    
        @if (@$search != '' && !$searchResults)
        
    	    <div class="well">{{{ langGet('searchResults.NoMatchesFor', [ 'search' => $search ]) }}}</div>
    	   
        @elseif (@$searchResults)
            
            @foreach ($searchResults as $type => $items)
            
                <h2>@langGet('searchResults.'.$type.'Matches')</h2>
                
                <table>
                @foreach ($items as $item)
                    <tr>
                        <td><a href="{!! $item['url'] !!}" target="_blank" class="mr-2">{{{ $item['name'] }}}</a></td>
        				<td><a href="{!! $item['addLink'] !!}" class="btn btn-primary btn-sm">Upload New Photos</a></td>
                    </tr>
                    <tr><td colspan=2><hr></td></tr>
                @endforeach
                </table>
                
            @endforeach
                      
        @endif
        
        <form method="get" class="form-inline">
        	<label class="mr-3">Search for a City: </label>
            <input type="text" class="form-control mr-3" name="search" size=25>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
</div>
@stop