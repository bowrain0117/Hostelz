<?php
?>

<?php 
    Lib\HttpAsset::requireAsset('chart.js'); 
?>

@php
    $navClass = 'navbar-expand-lg';
@endphp

@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])

@section('title', $cityInfo->city . ' Cheap Hostels')

@section('header')
@stop

@section('bodyAttributes') class ="cheaphostels-page" style="" @stop

@section('headerNavBottom')
    <div class="container mb-n2 mb-lg-0">
        <ul class="navbar-nav m-auto flex-row d-none d-md-flex">
            <li class="nav-item mx-4 mx-lg-5" id="all-link">
                <a data-smooth-scroll="" href="#cheaphostels" class="nav-link">Cheap Beds</a>
            </li>
            <li class="nav-item mx-4 mx-lg-5" id="beforeyougo-link">
                <a data-smooth-scroll="" href="#beforeyougo" class="nav-link">Tips before you go</a>
            </li>
            <li class="nav-item mx-4 mx-lg-5" id="stats-link">
                <a data-smooth-scroll="" href="#prices" class="nav-link">Prices</a>
            </li>
            <li class="nav-item mx-4 mx-lg-5" id="privaterooms-link">
                <a data-smooth-scroll="" href="#privaterooms" class="nav-link">Private Rooms</a>
            </li>
            <li class="nav-item mx-4 mx-lg-5" id="cheapest-link">
                <a data-smooth-scroll="" href="#cheapest" class="nav-link">Cheapest hostel</a>
            </li>
        </ul>
    </div>
@stop

@section('content')

<section class="container pt-5 pb-3" id="cheaphostels">
	<h1 class="hero-heading">10 Cheap hostels in {{ $cityInfo->city }}</h1>
	<p>{!! @$texts['header'] !!}</p>
</section>

<section class="container py-3" id="dormbed">
	<h2 class="font-xl font-bold my-4">Here are the cheapest hostels in {{ $cityInfo->city }}</h2>
    <p>{!! @$texts['top'] !!}</p>
    
	<div class="listingsList pt-3">
		<div class="row">
        @foreach ($dormListings as $listing)
        <div data-listing-id="{!! $listing->id !!}" class="col-lg-3 mb-5 hover-animate listing listingLink listing-gridFormat">
            <div class="card h-100 border-0 shadow">
        	<div class="hostel-tile__img bg-cover" style="background-image: url({!! $listing->thumbnailURL() !!});">
                @if ($listing->combinedRating)
                    <div class="rounded-circle bg-primary text-white display-3 p-3 font-weight-bolder m-3 d-inline-block">
                        <span class="listing-CombinedRating">{!! $listing->formatCombinedRating() !!}</span>
                    </div>
                @endif
            </div>
    		<div class="card-body d-flex">
                <div class="w-100">
                    <h3 class="card-title h4">{{ $listing->name }}</h3> 
                    @if ($listing->cityAlt != '')
                    	<div class="neighborhood mb-2"><img src="{!! routeURL('images', 'pin.svg') !!}" alt="Hostels in {{{ $listing->cityAlt }}}, {{{ $listing->name }}}" class="mr-2">{{{ $listing->cityAlt }}}</div>
                	@endif
                	                		
                	<div class="listingInfoLine mb-3">
                        <i class="fa fa-bed fa-fw"></i> Beds available from ${{ round($dormPrices->where('listingID', $listing->id)->first()->priceAverage) }}
                    </div>
                        
                    <div class="listingInfoLine mb-3">
                    	<?php $hwImported = $listing->activeImporteds()->where('system', 'BookHostels')->first(); ?>
                    	@if ($hwImported) 
                    		<a class="" href="{!! 'https://prf.hn/click/camref:1100l3SZe/pubref:c_' . urlencode(strtolower($listing->city)) . '/destination:' . urlencode($hwImported->urlLink) !!}">Check HostelWorld</a>
                    		@else
                    			<div class="">Check HostelWorld</div>
                    		@endif
                    </div>
                    <div class="listingInfoLine mb-3">
                    	<a class="" href="{!! $listing->getURL() !!}">Compare Rates</a>
            		</div>
            	</div>
            	</div>
            </div>
        </div>
    	@endforeach
    	</div>
    </div>
</section>

<section class="container pt-3 pb-5" id="beforeyougo">
    <h2 class="font-xl font-bold my-4">General Info</h2>
    <p>{!! @$texts['middle'] !!}</p>
</section>

<section class="container pt-3 pb-5" id="prices">
	
    <h2 class="font-xl font-bold my-4">When to book a hostel in {{ $cityInfo->city }}?</h2>
    <div py-3>
    	<p>Let us have a closer look on some statistics and graphics. <em>test content</em></p>
    	<ul>
    		<li>The cheapest months to travel to {{ $cityInfo->city }} is January. Hostels in {{ $cityInfo->city }} cost $26 in January.</li>
    		<li>The most expensive month is August with $37.</li>
    		<li>Total number of hostels in Amsterdam: 127</li>
    		<li>Total number of Party Hostels in Amsterdam: 12</li>
    		<li>Total number of hostels in Amsterdam with a +8.5 top-rating: 19</l>
    	</ul>
    	
    </div>
    <div class="row">
    	<div class="col-xl-8 col-lg-10 mx-auto">
    		<div class="m-10 w-1/2 relative">
    		    <h3 class="my-4">Average Dorm Price Per Month</h2>
        		<canvas id="pricePerMonthDorm"></canvas>
    		</div>
    
    		<div class="m-10 w-1/2 relative">
        		<h3 class="my-4">Average Private Room Price Per Month</h2>
        		<canvas id="pricePerMonthPrivate"></canvas>
    		</div>
    	</div>
    </div>
</section>

<section class="container py-3" id="privaterooms">
	<h2 class="font-xl font-bold my-4">Hostels with Private Rooms {{ $cityInfo->city }}</h2>
	<p>Hostels with Private Rooms in {{ $cityInfo->city }} cost on average $127.</p>
	<div class="listingsList pt-3">
		<div class="row">
    
        @foreach ($privateListings as $listing)
        
        <div data-listing-id="{!! $listing->id !!}" class="col-lg-3 mb-5 hover-animate listing listingLink listing-gridFormat">
        	<div class="card h-100 border-0 shadow">
        		<div class="hostel-tile__img bg-cover" style="background-image: url({!! $listing->thumbnailURL() !!});">
                @if ($listing->combinedRating)
                    <div class="rounded-circle bg-primary text-white display-3 p-3 font-weight-bolder m-3 d-inline-block">
                        <span>{!! $listing->formatCombinedRating() !!}</span>
                    </div>
                @endif
            </div>
    			
                <div class="card-body d-flex">
                    <div class="w-100">
                        <h3 class="card-title h4">{{ $listing->name }}</h3> 
                        @if ($listing->cityAlt != '')
                    		<div class="neighborhood mb-2"><img src="{!! routeURL('images', 'pin.svg') !!}" alt="Hostels in {{{ $listing->cityAlt }}}, {{{ $listing->name }}}" class="mr-2">{{{ $listing->cityAlt }}}</div>
                		@endif
                		                		
                		<div class="listingInfoLine mb-3">
                            Private Rooms from ${{ round($privatePrices->where('listingID', $listing->id)->first()->priceAverage) }}
                        </div>
                        
                        <div class="listingInfoLine mb-3">
                    		<?php $hwImported = $listing->activeImporteds()->where('system', 'BookHostels')->first(); ?>
                    		@if ($hwImported) 
                    			<a class="" href="{!! $hwImported->urlLink !!}">
                    				Check HostelWorld
                    			</a>
                    		@else
                    			<div class="">Check HostelWorld</div>
                    		@endif
                    	</div>
                    	<div class="listingInfoLine mb-3">
                    		<a class="" href="{!! $listing->getURL() !!}">Compare Rates</a>
            			</div>
            		</div>
            	</div>
            </div>
        </div>
        

        @endforeach
        
    	</div>
    </div>
</section>
 
<section class="container pt-3 pb-5" id="cheapest">    
    <h2>The cheapest hostel in {{ $cityInfo->city }}?</h2>
    <p>{!! @$texts['bottom'] !!}</p>
</section>
        
    
    
@stop


@section('pageBottom')

    @parent

    <script>

        $(document).ready(function() {
            
            {{-- Per Year --}}
            
            var data = {
                dorm: {
                    labels : [
                        @foreach ($pricePerMonth['dorm'] as $key => $month)
                            '{!! $month['month'] !!}'
                            @if ($key != count($pricePerMonth['dorm'])-1) , @endif
                        @endforeach
                    ],
                	datasets : [
                		{
                			backgroundColor : "rgba(151,187,205,0.5)",
                			borderColor : "rgba(151,187,205,1)",
                			borderWidth: 1,
                			label: 'Price',
                			data : [
                                @foreach ($pricePerMonth['dorm'] as $key => $month)
                                    {!! $month['priceAverage'] !!}
                                    @if ($key != count($pricePerMonth['dorm'])-1) , @endif
                                @endforeach
                            ]
                		}
                	]
                },
                
                private: {
                    labels : [
                        @foreach ($pricePerMonth['private'] as $key => $month)
                            '{!! $month['month'] !!}'
                            @if ($key != count($pricePerMonth['private'])-1) , @endif
                        @endforeach
                    ],
                	datasets : [
                		{
                			backgroundColor : "rgba(151,187,205,0.5)",
                			borderColor : "rgba(151,187,205,1)",
                			borderWidth: 1,
                			label: 'Price',
                			data : [
                                @foreach ($pricePerMonth['private'] as $key => $month)
                                    {!! $month['priceAverage'] !!}
                                    @if ($key != count($pricePerMonth['private'])-1) , @endif
                                @endforeach
                            ]
                		}
                	]
                }
            };
            
            
            new Chart($("#pricePerMonthDorm").get(0).getContext("2d"), {
                type: 'bar',
                data: data.dorm,
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value, index, values) {
                                    return '$' + value;
                                }
                            }
                        }]
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                // This adds the $
                                return data.datasets[tooltipItem.datasetIndex].label +': $' + tooltipItem.yLabel;
                            }
                        }
                    }
                }
            });
            
            new Chart($("#pricePerMonthPrivate").get(0).getContext("2d"), {
                type: 'bar',
                data: data.private,
                options: {
                    scales: {
                        yAxes: [{
                            ticks: {
                                beginAtZero: true,
                                callback: function(value, index, values) {
                                    return '$' + value;
                                }
                            }
                        }]
                    },
                    legend: {
                        display: false
                    },
                    tooltips: {
                        callbacks: {
                            label: function(tooltipItem, data) {
                                // This adds the $
                                return data.datasets[tooltipItem.datasetIndex].label +': $' + tooltipItem.yLabel;
                            }
                        }
                    }
                }
            });
            

        });
    </script>

@stop
