<?php Lib\HttpAsset::requireAsset('chart.js'); ?>

@extends('layouts/admin')

@section('header')
    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Data Mining') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <canvas id="chartCanvas" width="800" height="300"></canvas>

    </div>

@stop

@section('pageBottom')

    @parent
    
    <script>
    
        $(document).ready(function() {
            
            {{-- Per Month --}}
            
            var data = {
                labels : [
                    @foreach ($data as $key => value)
                        '{!! $key !!}'
                        {{-- @if ($key != count($perMonthData)-1) , @endif --}}
                    @endforeach
                ],
            	datasets : [
            		{
            			fillColor : "rgba(151,187,205,0.5)",
            			strokeColor : "rgba(151,187,205,1)",
                 		{{-- for line graphs:
                            pointColor : "rgba(151,187,205,1)",
                			pointStrokeColor : "#fff",
                        --}}
                        
            			data : [
                            @foreach ($perMonthData as $key => $month)
                                {!! $month['count'] !!}
                                {{-- @if ($key != count($perMonthData)-1) , @endif --}}
                            @endforeach
                        ]
            		}
            	]
            }
            
            var ctx = $("#chartCanvas").get(0).getContext("2d");
            new Chart(ctx).Bar(data, { });
            
        });
    </script>
    
    
@endsection