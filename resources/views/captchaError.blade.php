@extends('layouts/default')

@section('title', 'Captcha Error - Hostelz.com')

@section('content')


<section class="pt-3 pb-5 container">
	<div class="row">
        <div class="col-12">
        		<!-- Breadcrumbs -->
				<ol class="breadcrumb text-dark no-border mb-0">
            		{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}	
        		</ol>
        		
        		<div class="alert alert-danger">
            		<h1><i class="fa fa-exclamation-circle"></i> Captcha Error</h1>
           			<p>Be sure to check the "I am not a robot" captcha box before submitting the form. Please use the browser to go back to the form.</p>
        		</div>
		</div>
	</div>
</section>
@stop