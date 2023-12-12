<?php
Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', langGet('User.menu.PlaceDescriptions').' - Hostelz.com')

@section('header')
    <style>
        .resultsRow {
            margin: 2px 0;
            background-color: #f8f8f8;
            padding: 2px;
        }
        
        .resultsRow > div {
            padding: 2px 4px;
            line-height: 28px;
        }
    </style>
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            @breadcrumb(langGet('User.menu.PlaceDescriptions'), routeURL('placeDescriptions'))
            {!! breadcrumb('Find') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.CityInfo') !!}</div>

        <h1 class="hero-heading h2">Find Cities/Regions/Countries to Write About</h1>
        
        @if (@$search != '' && !$searchResults)
        
    	    <div class="well">
                {{{ langGet('searchResults.NoMatchesFor', [ 'search' => $search ]) }}}
            </div>
    	   
        @elseif (@$searchResults)
            
            @foreach ($searchResults as $type => $items)
            
                <h2>@langGet('searchResults.'.$type.'Matches')</h2>
                
                @foreach ($items as $item)
                    <div class="row resultsRow">
                        <div class="col-sm-7"><a href="{!! $item['url'] !!}">{{{ $item['name'] }}}</a></div>
                        @if ($item['isAvailable'])
            				<div class="col-sm-3 bg-success text-success text-center">
            				    Available
            				    @if (App\Models\Languages::currentCode() != 'en')
                    			    (for <strong>{!! App\Models\Languages::current()->name !!}</strong>)
                    			@endif
            				</div>
            				<div class="col-sm-2 text-center"><a href="{!! $item['addLink'] !!}" class="btn btn-primary btn-sm">Write Description</a></div>
            			@else
            				<div class="col-sm-3 bg-danger text-danger text-center">Not available</div>
            			@endif 
                    </div>
                @endforeach
                    
            @endforeach
                      
        @endif
        
        <hr>
        <form method="get" class="form-inline">
        	<label>Search for a City/Region/Country:</label>
            <input type="text" class="form-control" name="search" size=25>
            <button class="btn btn-primary" type="submit">Search</button>
            <br><br>
            <p>These countries have regions in our database: {{{ CountryInfo::where('regionType', '!=', '')->pluck('country')->sort()->implode(', ') }}}. You can view the list of regions on the country's page on the website. (Country and city descriptions are accepted for all countries.)</p>
        </form>
        
    </div>

@stop
