
        @if ($mode == 'showFiles')
        
            <h2>Select Files</h2>
            <form method="post">
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
            
        @elseif ($mode == 'doSync')
        
            <h2>Result</h2>
        
        @endif
        
        
        @if ($copyList)
            
            <h3>Copy Files</h3>
                                
            <table class="table table-striped listingsList">
                <thead>
                    <tr>
                        @if ($mode == 'showFiles')
                            <th><input class="toggleAllCheckboxes" type="checkbox" CHECKED></th>
                        @endif
                        <th>Source File</th>
                        <th> </th>
                        <th>Destination File</th>
                    </tr>
                </thead>
                
                <tbody>
                    @foreach ($copyList as $file)                    
                        <tr>
                            @if ($mode == 'showFiles')
                                <td><input type="checkbox" name="copyList[]" value="{{{ serialize($file) }}}" CHECKED></td>
                            @endif
                            <td>{{{ $fileSet['source'] }}}{{{ $file['path'] }}}/{{{ $file['filename'] }}}</td>
                            <td><i class="fa fa-long-arrow-right"></i></td>
                            <td>{{{ $fileSet['destination'] }}}{{{ $file['path'] }}}/{{{ $file['filename'] }}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
            
        @else
            
            <br><div class="well">No new files to copy.</div>
        
        @endif
            
        @if ($deleteList)
            
            <h3>Delete Files</h3>
                                
            <table class="table table-striped listingsList">
                <thead>
                    <tr>
                        @if ($mode == 'showFiles')
                            <th><input class="toggleAllCheckboxes" type="checkbox" CHECKED></th>
                        @endif
                        <th>File</th>
                    </tr>
                </thead>
            
                <tbody>
                    @foreach ($deleteList as $file)                    
                        <tr>
                            @if ($mode == 'showFiles')
                                <td><input type="checkbox" name="deleteList[]" value="{{{ serialize($file) }}}" CHECKED></td>
                            @endif
                            <td>{{{ $fileSet['destination'] }}}{{{ $file['path'] }}}/{{{ $file['filename'] }}}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        
        @else
        
            <br><div class="well">No files to delete.</div>
    
        @endif
        
        @if (@$commandOutput != '')
                <br><pre>{{{ $commandOutput }}}</pre>
        @endif
            
        @if ($mode == 'showFiles' && ($copyList || $deleteList))
                <p><button class="btn btn-primary" name="mode" value="doSync" type="submit">Sync Now</button></p>
            </form>
        @elseif ($mode == 'doSync')
            @if (App::environment() != 'production')
                <br><div class="alert alert-warning">Note:  Production cache not cleared because this script isn't running on the production server.</div>
            @endif
        @endif
        

@section('pageBottom')

    <script>
    
        {{-- Select/Deselect All Checkbox (for renew, delete listings, etc.) --}}
        $('input.toggleAllCheckboxes').change(function(event) {
            $(this).closest('table').find('tbody tr td input[type=checkbox]').prop('checked', $(this).prop('checked'));
        });

    </script>
    
    @parent
@stop