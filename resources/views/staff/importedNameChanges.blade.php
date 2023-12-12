<?php Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts/admin')

@section('header')
    <style>
        #nameChangeTable td {
            padding: 2px 4px;
        }
        
    </style>
    
    @parent
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Imported Name Changes') !!}
        </ol>
    </div>
    
    <div class="container">
    
        @if (Request::input('command') == 'markAllAsCurrent')
            <br>
            <div class="alert alert-success">All marked as current.</div>
            <br>
        @endif
        
    
        <h1>Imported Name Changes</h1>
        <h3>({!! $importeds->count() !!})</h3>

        <table id="nameChangeTable">
            @foreach ($importeds as $imported)
        		<tr>
            		<td><a href="{!! routeURL('staff-listings', $imported->hostelID) !!}">listing {{{ $imported->hostelID }}}</a></td> 
                    <td>{{{ $imported->listing->address }}}, {{{ $imported->listing->city }}}</td> 
                	<td>Current: </td> 
                	<td>{{{ $imported->listing->name }}}</td> 
                    <td rowspan=3>
                        <a href="#" class="renameLink btn btn-lg btn-primary" data-listing-id="{{{ $imported->hostelID }}}" data-imported-id="{{{ $imported->id }}}" data-new-name="{{{ $imported->name }}}">Rename</a>
                    </td> 
                    <td rowspan=3>
                        <a href="#" class="ignoreLink btn btn-lg btn-default" data-imported-id="{{{ $imported->id }}}">Ignore</a>
                    </td>
        		</tr>
        		<tr>
            	    <td><a href="{!! routeURL('staff-importeds', $imported->id) !!}">{{{ $imported->getImportSystem()->shortName() }}}</a></td> 
                    <td>{{{ $imported->address1 }}}, {{{ $imported->city }}}</td> 
                    <td>New:<td nowrap><b>{{{ $imported->name }}}</b></td> 
        		</tr>
            	<tr>
            	    <td>{!! $imported->listing->propertyType !!}</td>
            	    <td><a href="#" class="unlinkLink" data-imported-id="{!! $imported->id !!}">Different Hostel - Unlink</a></td>
            	    @if ($imported->previousName != $imported->listing->name)
                        <td nowrap>(was):</td><td>{{{ $imported->previousName }}}</td></tr>
                    @endif
        		</tr>
        		<tr>
        		    <td colspan=6><hr></td>
        		</tr>
            @endforeach
    	</table>

        @if (Request::input('command') != 'markAllAsCurrent')
            <form method="post">
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                <button class="btn btn-default" type="submit" name="command" value="markAllAsCurrent" onclick="return confirm('Are you sure?');">Mark All as Current</button>
            </form>
            <br>
        @endif
        
    </div>

@stop

@section('pageBottom')

    <script type="text/javascript">
        $(".renameLink").click(function(event) {
            event.preventDefault();
            var theLink = this;
            $.post(window.location.href, 
                { command: "rename", listingID: $(this).data('listingId'), importedID: $(this).data('importedId'), newName: $(this).data('newName'), _token: "{!! csrf_token() !!}" {{-- for CSRF --}} }, 
                function(data) {
                    if (data == 'ok') $(theLink).css('background-color', 'rgb(210, 173, 173)');
    		    }
    	    );
    	});
    	
        $(".ignoreLink").click(function(event) {
            event.preventDefault();
            var theLink = this;
            $.post(window.location.href, 
                { command: "ignore", importedID: $(this).data('importedId'), _token: "{!! csrf_token() !!}" {{-- for CSRF --}} }, 
                function(data) {
                    if (data == 'ok') $(theLink).css('background-color', 'rgb(210, 173, 173)');
    		    }
    	    );
    	});
        
        $(".unlinkLink").click(function(event) {
            event.preventDefault();
            var theLink = this;
            $.post(window.location.href, 
                { command: "unlink", importedID: $(this).data('importedId'), _token: "{!! csrf_token() !!}" {{-- for CSRF --}} }, 
                function(data) {
                    if (data == 'ok') $(theLink).css('background-color', '#dde');
    		    }
    	    );
    	});
    </script>
    
@endsection
