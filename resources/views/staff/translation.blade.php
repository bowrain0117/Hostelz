<?php
    Lib\HttpAsset::requireAsset('staff.css');
    Lib\HttpAsset::requireAsset('translation.js');
?>

@extends('layouts/admin')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            @if (isset($group))
                {!! breadcrumb(\App\Models\Languages::get($language)->name . ' Translation', routeURL('staff-translation', $language)) !!}
                {!! breadcrumb($group . ' Translation') !!}
            @else
                {!! breadcrumb(\App\Models\Languages::get($language)->name) !!} Translation</li>
            @endif
        </ol>
    </div>
    
    <div class="container">
    
        <h1>{!! \App\Models\Languages::get($language)->name !!} Translation</h1>
        
        @if (!isset($group))
        
            {{-- Groups List --}}
            
            <div class="panel menuOptionsPanel">
                       
            @foreach ($groups as $group => $translationsNeeded)
                <ul class="list-group">
                    <a href="{!! $language !!}/{{{ $group }}}">{{{ $group }}}</a>
                    @if ($translationsNeeded)
                        <span class="text-danger">
                    @else
                        <span class="text-muted">
                    @endif
                        ({{{ $translationsNeeded }}})
                    </span>
                </ul>
            @endforeach
            
            </div>
        
        @elseif (@$updated)
        
            <br>
            <div class="row">
                <div class="col-md-6">
                    <div class="alert alert-success">Translations Saved Successfully.</div>
                </div>
            </div>
            
        @else

            <form method="post">
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">
        
                @if (isset($translationInfo['instructions']))
                    <div class="well">{!! $translationInfo['instructions'] !!}</div>
                @endif
                
                <br>

                @if($language !== 'en')
                    <div class="mb-4 d-flex justify-content-end">
                        <div class="spinner spinner-border mr-2" role="status">
                            <span class="sr-only">Loading...</span>
                        </div>
                        <a href="#" title="search All translation" class="translationGetAll p-1 text-warning">
                            <i class="fa fa-search" aria-hidden="true"></i> Search all empty strings
                        </a>
                        <a href="#" title="insert All translation to the field" class="translationInsertAll p-1 text-info">
                            <i class="fa fa-pencil" aria-hidden="true"></i> Add all empty strings
                        </a>
                    </div>
                @endif
            
                @forelse ($strings as $key => $string)

                    <div class="translationWrap mb-4">
                        <input type="hidden" class="translationData" name="originalEnglishText[{{{ $key }}}]" value="{{{ $string['english'] }}}">

                        <label @if ($string['translationIsNeeded']) class="text-danger control-label translationLabel" @else class="control-label translationLabel" @endif >
                            "{{{ $string['english'] }}}"
                        </label>

                        @if($language !== 'en')
                            <div class="pull-right">
                                <a href="#" title="search translation" class="translationGet p-1 text-warning">
                                    <i class="fa fa-search" aria-hidden="true"></i>
                                </a>
                                <a href="#" title="insert translation to the field" class="translationInsert p-1 text-info">
                                    <i class="fa fa-pencil" aria-hidden="true"></i>
                                </a>
                            </div>
                        @endif

                        @if (strlen($string['english']) > 40)
                            <textarea id="field-{{ $loop->iteration }}" data-field-id="{{ $loop->iteration }}"
                                      class="form-control translationField" name="translations[{{{ $key }}}]">{{{ $string['current'] }}}</textarea>
                        @else
                            <input id="field-{{ $loop->iteration }}" data-field-id="{{ $loop->iteration }}"
                                   class="form-control translationField" name="translations[{{{ $key }}}]" value="{{{ $string['current'] }}}">
                        @endif

                        @if($language !== 'en')
                            <div class="transitionTargetWrap" style="display: none;">
                                <div class="d-flex mt-2 border-warning border p-2 rounded-3 border-2">
                                    <div id="target-{{ $loop->iteration }}" class="transitionTarget" style="cursor: not-allowed;" ></div>
                                </div>
                                <div class="d-none transitionString" data-field-id="{{ $loop->iteration }}">{!! $string['english'] !!}</div>
                            </div>
                        @endif

                    </div>
                @empty
                
                    <div class="well">Nothing to translate for this group.</div>
                    
                @endforelse
                
                <br>
                <button class="btn btn-primary" type="submit" onClick="javascript:return confirm('Save translations?');">Submit</button>
                
            </form>
        
        @endif
            
    </div>

@stop

@section('pageBottom')
    <script type="text/javascript">
        var translationOptions = {
            languageTo: '{{ $language }}'
        };
    </script>
@endsection
