@extends('layouts/default')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
        </ol>
    </div>
    
    <div class="container">
    
        <br>
        
        <div class="alert alert-danger"><i class="fa fa-exclamation-circle"></i>
             alert-danger
        </div>
        
        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>
            alert-warning
        </div>  
        
        <div class="alert alert-info"><i class="fa fa-exclamation-circle"></i>
            alert-info
        </div>    
        
        <div class="alert alert-success"><i class="fa fa-exclamation-circle"></i>
            alert-success
        </div>        
        
        <div class="well"><i class="fa fa-exclamation-circle"></i>
            well
        </div>
        
        {{-- no longer using?
        <div class="background-primary-xlt well">
            background-primary-xlt
        </div>
        
        <div class="background-primary-lt well">
            background-primary-lt
        </div>
        
        <div class="background-primary-md well">
            background-primary-md
        </div>

        <div class="background-secondary-xlt well">
            background-primary-xlt
        </div>
        
        <div class="background-secondary-lt well">
            background-primary-lt
        </div>
        
        <div class="background-secondary-md well">
            background-primary-md
        </div>
        --}}

                <button class="btn btn-primary setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="ok">Approve</button>
                <button class="btn btn-default setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="ok">Approve</button>
                <button class="btn btn-success setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="ok">Approve</button>
                <button class="btn btn-info setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="flagged">Flag</button>
                <button class="btn btn-warning setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="returned">Return</button>
                <button class="btn btn-danger setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="denied">Deny</button>
                
        
        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="#">Nav Link</a></li>
                <li><a href="#">Nav Link</a></li>
            </ul>
        </div>
        
        <br>
        
        <div class="panel panel-default">
            <div class="panel-heading">panel-default</div>
            <div class="panel-body">Panel content</div>
        </div>
        
        <div class="panel panel-primary">
            <div class="panel-heading">panel-primary</div>
            <div class="panel-body">Panel content</div>
        </div>
        
        <div class="panel panel-success">
            <div class="panel-heading">panel-success</div>
            <div class="panel-body">Panel content</div>
        </div>

        <div class="panel panel-info">
            <div class="panel-heading">panel-info</div>
            <div class="panel-body">Panel content</div>
        </div>

        <div class="panel panel-warning">
            <div class="panel-heading">panel-warning</div>
            <div class="panel-body">Panel content</div>
        </div>

        <div class="panel panel-danger">
            <div class="panel-heading">panel-danger</div>
            <div class="panel-body">Panel content</div>
        </div>

    
    </div>
@stop
