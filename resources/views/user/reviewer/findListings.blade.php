<?php
Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Reviewer - Hostelz.com')

@section('content')

<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('My Reviews', routeURL('reviewer:reviews')) !!}
            {!! breadcrumb('Find Hostels') !!}
        </ol>
    </div>
    <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Review') !!}</div>

    <h1 class="hero-heading h2">Find Hostels to Review</h1>
        
    <div class="alert alert-warning"><b>Note: We currently no longer pay for reviews for hostels that are not located in either North America, Australia/Oceania, or Europe.  We hope to eventually accept reviews from all countries again.  But until further notice, after November 1st we will only pay for reviews for hostels in those areas. If there are hostels in other countries that you just want to review anyway even if it isn't for pay, we will absolutely still gladly accept and publish reviews for hostels in other countries, but those would be non-paid reviews.</b></div>
        
        @if (@$search != '' && !$searchResults)
        	<div class="well">{{{ langGet('searchResults.NoMatchesFor', [ 'search' => $search ]) }}}</div>
    	   
        @elseif (@$searchResults)
            
            @foreach ($searchResults as $resultType => $items)
            
                {{-- Note: Some result types, such as regions and countries are ignored. --}} 
                
                @if ($resultType == 'cities')
                
                    <h2>Cities</h2>
                    
                    @foreach ($items as $cityInfo) 
                    
                        <p style="margin-left: 20px">
                            <strong><a href="?cityID={!! $cityInfo->id !!}">{!! $cityInfo->translation()->city !!}
                                {!! ($cityInfo->translation()->cityAlt != '' ? "(".$cityInfo->translation()->cityAlt.") " : '') . 
                                ($cityInfo->translation()->region != '' ? $cityInfo->translation()->region.', ' : '') . 
                                $cityInfo->translation()->country !!}</a></strong>
                        </p>
                        
                    @endforeach
                
                @elseif ($resultType == 'listings')
                
                    <h2>Accommodation Matches</h2>
                    
                    <p><em>Please be sure there aren't any duplicates of this hostel listed in the same city under a different name before you reserve it for reviewing.</em></p>
                    <br>
                    
                    <table style="margin-left: 20px" class="table">
                    
                        @foreach ($items as $listing) 
                            <tr>
                                <?php
                                    $listingAddress = '<div>'.$listing->address.'</div><div>'.$listing->city.', ' .
                                        ($listing->region != '' ? $listing->region.', ' : '' ) .
                                        $listing->country . '</div>'; 
                                ?>
                                <td nowrap><strong>{{{ $listing->name }}}</strong></td>
                                @if ($listing->isLive())
                                    <td>[<a href="{!! $listing->getURL() !!}">view</a>]</td>
                                @else
                                    <td class="text-muted">(not yet live)</td>
                                @endif
                                <td>{!! $listingAddress !!}</td>

                                @if (! in_array($listing->continent, [ 'Western Europe', 'UK & Ireland', 'North America', 'Eastern Europe & Russia', 'Australia & Oceania' ]))
                    				<td class="text-danger">Not accepting reviews in this country</td>
                    			@elseif ($listing->propertyType != 'Hostel')
                    				<td class="text-danger">Not available (not a hostel).</td>
                    			@elseif (!\App\Models\Review::listingIsAvailabileForReviewing($listing))
                    				<td class="text-danger">Not available (already reviewed/reserved).</td>
                    			@else 
                    				<td>
                    				    <div><a href="{!! routeURL('reviewer:reviews') !!}?addListing={!! $listing->id !!}" class="btn btn-primary">Add to My Review List</a></div>
                    				    @if (App\Models\Languages::currentCode() != 'en')
                    				        (Available for review in <strong>{!! App\Models\Languages::current()->name !!}</strong>.)
                    				    @endif
                    				</td>
                    			@endif 
                            </tr>
                            
                        @endforeach
                
                    </table>
                    
                @endif
            
            @endforeach
            
        @endif
        
        @if (@$search != '')
            <br><div class="well">Is the hostel you want to review not yet listed? <strong>You can <a href="{!! routeURL('reviewer:submitNewListing') !!}">Add a New Hostel</strong></a>.</div>
        @endif
        
        <form method="get" class="form-inline">
        	<label>Search for a Hostel Name (or part of the name) or City:</label>
            <input type="text" class="form-control" name="search" size=25>
            <button class="btn btn-primary" type="submit">Search</button>
        </form>
        
        @if (@$search == '')
            <br><p>(If you want to add a hostel that isn't listed on Hostelz.com yet, first search for the name or city of the hostel, then you will have the option to add a new hostel.)</p>
        @endif 
</div>
@stop
