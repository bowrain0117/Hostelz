<?php
    Lib\HttpAsset::requireAsset('indexMain.js');
?>

@extends('layouts/default', ['showHeaderSearch' => true ])

@section('title', 'Access Denied - Hostelz.com')
@section('content')

<section class="pt-3 pb-5 container">
	<div class="row"> 
        <div class="col-12">
        		<!-- Breadcrumbs -->
            	<ul class="breadcrumb px-0 mx-sm-n3 mx-lg-0">
            		{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}	
        		</ul>
        		<h1 class="hero-heading">Sorry, no access.</h1>
        		
        		<div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>
            		Sorry, your user account doesn't have access to this page.
        		</div>
		</div>
	</div>
</section>
@stop 