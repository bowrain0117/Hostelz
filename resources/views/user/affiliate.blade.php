<?php
Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => true ])

@section('title', 'Reviewer - Hostelz.com')

@section('content')
<div class="pt-3 pb-5 container">
    <div class="breadcrumbs">
        <ol class="breadcrumb black" typeof="BreadcrumbList">
            
        </ol>
    </div>
    
    <div class="alert alert-warning">
        <h1 class="hero-heading h2">Our Affiliate is on Hold</h1>
        <p class="font-weight-bold">Thank you for your interesting in teaming up with Hostelz.com. Our affiliate system is currently on hold.</p>
        <p>We will get in touch with you as soon as there are any more news.</p>
        <p>Talk soon and bon voyage, <br>The Hostelz Team</p> 
    </div>
</div>
@stop