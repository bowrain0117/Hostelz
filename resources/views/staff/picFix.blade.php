@extends('layouts/admin')

@section('title', 'Pic Fix')

@section('header')
    <style>
        .picBox {
        }
        
        img.thePics {
            margin: 5px 10px 5px 0px;
            border: 4px solid black;
            float: left;
            width: {!! $previewWidth !!}px;
        }
        
        div.setOfButtons {
            padding: 10px 0px 0px 0px;
        }
        
        .picBox {
            margin: 15px 0px 0px 0px;
            clear: both;
        }
        
        .picBox p {
            margin-top: 10px;
        }
        
        input.imageParameter {
            margin: 4px 1px 1px 1px;
        }
        
        table.parametersTable td {
            text-align: center;
            padding: 2px 2px;
        }
        
        table.parametersTable .btn-group label {
            width: 3em;
        }
        
    </style>
@stop

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Pic Fix') !!}
        </ol>
    </div>
    
    <div class="container">
    
        @if ($message != '')
            <br><div class="well">{!! $message !!}</div>
        @endif
                
        ({{ $pics->count() }} of {!! $totalCount !!} total pics ready for editing.)

        <br>

        <div class="pt-5 d-flex justify-content-center">
            {{ $pagination }}
        </div>
        
        @foreach ($pics as $pic)
            <div class="picBox" data-pic-id="{{{ $pic->id }}}">{{-- only needed for grouping of the radio buttons --}}
                
                <a href="@routeURL('staff-pics', $pic->id)">
                    <img src="?command=displayPic&picID={{{ $pic->id }}}{!! $pic->edits ? '&' . http_build_query([ 'edits' => $pic->edits ]) : '' !!}" class="thePics">
                </a>
                
                <table class="parametersTable">
                    @foreach ($parameters as $parameterName => $parameter)
                        <tr>
                            <td>
                                <strong>{!! $parameter['label'] !!}:</strong>&nbsp;
                            </td>
                            <td>
                                <div class="btn-group" data-toggle="buttons">
                                    @foreach ($parameter['options'] as $optionLabel => $optionValue)
                                        <?php
                                            if ($pic->edits && isset($pic->edits[$parameterName]))
                            		            $isChecked = ($optionValue == $pic->edits[$parameterName]);
                            		        else
                                		        $isChecked = ($optionValue == $parameter['defaultValue']);
                                        ?>
                                        <label class="btn btn-default @if ($isChecked) active @endif">
                            		        <input class="imageParameter" type="radio"
                            		            name="{!! $parameterName . $pic->id !!}" {{-- Just needed for radio button grouping --}}
                            		            data-parameter-name="{!! $parameterName !!}"
                            		            value="{!! $optionValue !!}" 
                            		            @if ($isChecked) checked="checked" @endif
                            		        >
                            		        {!! $optionLabel !!}
                        		        </label> 
                                    @endforeach
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </table>
            
                @if ($pic->caption != "") <p>"{{{ $pic->caption }}}"</p> @endif 
            </div>
        @endforeach

        
        @if (!$pics->isEmpty())
            <div style="clear:both">
                <br><br><br>
                <form method="post" class="submitAllPicChanges">
                    {!! csrf_field(); !!}
                    <input type="hidden" name="command" value="submitAllEdits">
                    <input type="hidden" name="allPicEdits" value=""> {{-- Value set by javascript --}}
                    <button class="btn btn-primary" type="submit">Save & Continue</button>
                </form>
            </div>
        @endif

        <div class="pt-5 d-flex justify-content-center">
            {{ $pagination }}
        </div>

    </div>

@stop


@section('pageBottom')

    <script type="text/javascript">
        $(document).ready(function() {
            // Set data-original-url
            $("img.thePics").each(function() { $(this).attr("data-original-url", $(this).attr("src")); });
            // Border turns black when the image is done updating
            $("img.thePics").load(function() { $(this).css("border-color", "black"); } )
            
            $("input.imageParameter").change(function () {
                var $picBox = $(this).closest('.picBox');
            	var originalURL = $picBox.find("img.thePics").attr("data-original-url") + "&edits["+$(this).attr('data-parameter-name')+"]="+$(this).val();
            	$picBox.find("input.imageParameter:checked").each( function () { originalURL += "&edits["+$(this).attr('data-parameter-name')+"]="+$(this).val(); } );
            	$picBox.find("img.thePics").attr("src", originalURL).css("border-color", "red");
            });
            
            $(".submitAllPicChanges button").click(function () {
                var allPicEdits = { };
                
                $(".picBox").each( function () {
                    var thisPicEdits = { };
                    $(this).find("input.imageParameter:checked").each(function () {
                        if ($(this).val() != 0)
                            thisPicEdits[$(this).attr('data-parameter-name')] = $(this).val();
                    });
                    allPicEdits[$(this).attr('data-pic-id')] = thisPicEdits;
                });
                $(".submitAllPicChanges input[name='allPicEdits']").val(JSON.stringify(allPicEdits));
            });
        });
    </script>
    
    @parent

@endsection
