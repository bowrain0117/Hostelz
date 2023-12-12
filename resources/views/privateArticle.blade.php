@extends('layouts/default', [ 'returnToThisPageAfterLogin' => true ])
@section('title', 'Article Preview - Hostelz.com')
@section('header')
	{!! $articleText['headerInsert'] !!}
@stop
@section('content')
<section class="pt-3 pb-5 container">
	<div class="row">
        <div class="col-12">
        	<!-- Breadcrumbs -->
			<ol class="breadcrumb black text-dark no-border mb-0">
				{!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
        	</ol>
    		<h1 class="hero-heading">{{{ $article->getArticleTitle() }}}</h1>
    		<div class="article">
    			<div class="article">{!! $articleText['text'] !!}</div>
        	</div>
		</div>
	</div>
</section>
@stop