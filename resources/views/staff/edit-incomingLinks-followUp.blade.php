@extends('layouts/admin')

@section('title', 'Link Follow-Ups')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Link Follow-ups') !!}
        </ol>
    </div>
    
    <div class="container">
    
        <h2>Send Follow-up Emails</h2>
    
        {{-- Error / Info Messages --}}
        
        @if (@$message != '')
            <br><div class="well">{!! $message !!}</div>
        @endif
        
        @if ($mode == 'list' && $incomingLinks->isEmpty())
        
            <br><div class="well">No follow-ups are currently due.</div>
        
        @elseif ($mode == 'list' && $incomingLinks)
        
            <form method="post">
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
                
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th><input class="toggleAllCheckboxes" type="checkbox" CHECKED></th>
                            <th>URL</th>
                            <th>First Contacted</th>
                            <th>Contact Emails</th>
                            <th>Notes</th>
                            <th>Database Link</th>
                        </tr>
                    </thead>
                    
                    <tbody>
                        @foreach ($incomingLinks as $link)
                            <tr>
                                <td><input type="checkbox" name="selectedLinks[]" value="{!! $link->id !!}" CHECKED></td>
                                <td>{{{ $link->url }}}</td>
                                <td>{!! $link->lastContact !!}</td>
                                <td>{{{ implode(', ', $link->contactEmails) }}}</td>
                                <td>{{{ $link->notes }}}</td>
                                <td><a href="{!! routeURL('staff-incomingLinks', $link->id) !!}">[edit]</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                
                <p>
                    <button class="btn btn-primary submit" type="submit">Send Emails Now</button>
                </p>
                
            </form>
            
        @elseif ($mode == 'done')
                
            <table class="table table-striped listingsList">
                @foreach ($linksDone as $link)
                    <tr>
                        <td class="text-success bold">Email Sent</td>
                        <td>{{{ $link->url }}}</td>
                        <td>{!! $link->lastContact !!}</td>
                        <td>{{{ implode(', ', $link->contactEmails) }}}</td>
                        <td><a href="{!! routeURL('staff-incomingLinks', $link->id) !!}">[edit]</a></td>
                    </tr>
                @endforeach
            </table>
            
            <h3>Done.</h3>
        
        @endif
    
    </div>
        
@stop

@section('pageBottom')

    <script>
    
        {{-- Select/Deselect All Checkbox (for renew, delete listings, etc.) --}}
        $('input.toggleAllCheckboxes').change(function(event) {
            $(this).closest('table').find('tbody tr td input[type=checkbox]').prop('checked', $(this).prop('checked'));
        });

    </script>
    
    @parent
@stop
