@section('header')
    <style>
        td.suggestions a {
            display: inline-block;
            border: 1px solid #ddd; 
            border-radius: 3px; 
            padding: 1px 3px;
            margin: 1px;
            font-size: 13px;
        }
    </style>
@stop

@if ($correctionsOutput != '')
    <div class="well">
        <h3>Making Corrections</h3>
        <p>{!! $correctionsOutput !!}</p>
    </div>
@endif

@if (@$rows)

    <form method="post" action="/{!! Request::path() !!}">
        <input type="hidden" name="_token" value="{!! csrf_token() !!}">
        
        <table class="table table-striped">
            <thead>
                @if (property_exists(reset($rows), 'contextValue1'))
                    <th @if (property_exists(reset($rows), 'contextValue2')) colspan=2 @endif>Context</th>
                @endif
 
                <th>Value</th>
                <th>Correct Value</th>
                <th>Suggestions</th>
                <th>Skip</th>
            </thead>
            <tbody>
            @foreach ($rows as $rowNum => $row)
                <tr>
                    @if (property_exists($row, 'contextValue1'))
                        <td>
                            <input type="hidden" name="data[{!! $rowNum !!}][context1]" value="{{{ $row->contextValue1 }}}">
                            {{{ $row->contextValue1 }}}
                        </td>
                    @endif
                    @if (property_exists($row, 'contextValue2'))
                        <td>
                            <input type="hidden" name="data[{!! $rowNum !!}][context2]" value="{{{ $row->contextValue2 }}}">
                            {{{ $row->contextValue2 }}}
                        </td>
                    @endif
                    <td><a href="{!! $editURL !!}?search[{!! $actualDbField !!}]={{{ $row->value }}}&mode=list">{{{ $row->value }}}</a></td>
                    <td>
                        <input type="hidden" name="data[{!! $rowNum !!}][old]" value="{{{ $row->value }}}">
                        <input type="text" name="data[{!! $rowNum !!}][new]" value="{{{ $row->value }}}" size=50>
                    </td>
                    <td class="suggestions">
                        @if (@$row->suggestions)
                            @foreach ($row->suggestions as $suggestion)
                                <a href="#">{{{ $suggestion }}}</a>
                            @endforeach
                        @endif
                    </td>
                    <td style="white-space: nowrap">
                        <label><input type="checkbox" name="data[{!! $rowNum !!}][skip]" value="1" @if ($skipByDefault) CHECKED @endif> Skip</label>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
        
        <button class="btn btn-primary" type="submit" onClick="javascript:return confirm('Submit these values.  Are you sure?');">@langGet('global.Submit')</button>
    </form>
    
@elseif (isset($rows) && !$rows)

    <div>No new items.</div>

@elseif (isset($inserts)) 

    <div class="alert alert-success bold">Data Saved.</div>
    
    @if ($inserts)
        @foreach ($inserts as $row)
            <div>{{{ $row['oldValue'] }}} -&gt; {{{ $row['newValue'] }}}</div>
        @endforeach
    @else
        (No new data submitted.)
    @endif

@endif


@section('pageBottom')
    <script>
        $('td.suggestions a').click(function (e) {
            e.preventDefault();
            $(this).closest('tr').find('input[type="text"]').val($(this).text());
        });
    </script>
@stop
