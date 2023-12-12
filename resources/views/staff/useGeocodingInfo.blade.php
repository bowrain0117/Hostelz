<?php
Lib\HttpAsset::requireAsset('staff.css'); ?>

@extends('layouts/admin')

@section('title', 'Use Geocoding Info')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Use Geocoding Info') !!}
        </ol>
    </div>

    @if ($message != '')
        <div class="container">
            <div class="well">{!! $message !!}</div>
        </div>

    @elseif ($formHandler->list)

        <div class="container-fluid">

                <?php
                $listFields = $formHandler->listDisplayFields ? $formHandler->listDisplayFields : $formHandler->listSelectFields;
                ?>

            <h3>{!! $formHandler->list->count() !!} results.</h3>

            <form method="post" class="form-inline">
                <input type="hidden" name="_token" value="{!! csrf_token() !!}">

                <table class="table table-hover table-striped formHandlerList">
                    <thead>
                    <tr>
                        <th class="text-center"><input class="multiSelectAll" type="checkbox"></th>

                        @foreach ($listFields as $fieldName)
                            <th class="listingField listingField_{{{ $fieldName }}}">
                                @include('Lib/formHandler/sortableColumnHeader', [ 'fieldName' => $fieldName, 'label' => $formHandler->getLanguageText('fieldLabel', $fieldName, 'list') ])
                            </th>
                        @endforeach
                        @foreach ($geocodingFields as $fieldName)
                            <th class="geocodingField geocodingField_{{{ $fieldName }}}">
                                <em>Geocoding {!! $fieldName !!}</em>
                            </th>
                        @endforeach
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($formHandler->list as $rowKey => $listing)
                        <tr>
                            <td class="text-center">
                                <div class="form-group"><input name="multiSelect[]" type="checkbox"
                                                               value="{!! $listing->id !!}"></div>
                            </td>

                            @foreach ($listFields as $fieldName)

                                <td class="listingField listingField_{{{ $fieldName }}}"><a
                                            href="{!! routeURL('staff-listings', [ $listing->id ]) !!}">{{{ $listing->$fieldName }}}</a>
                                </td>

                            @endforeach

                            @foreach ($geocodingFields as $fieldName)

                                @if (@$geoInfo[$listing->id])
                                    <td class="geocodingField geocodingField_{{{ $fieldName }}}">
                                        <a style="color: #000"
                                           href="{!! routeURL('staff-listings', [ $listing->id ]) !!}">{{{ $geoInfo[$listing->id][$fieldName] }}}</a>
                                    </td>
                                @endif

                            @endforeach

                        </tr>
                    @endforeach
                    </tbody>
                </table>

                @if (!$formHandler->list->isEmpty())
                    <div class="form-group">
                        <label for="geocodingField">Set Listing Field</label>
                        <select class="form-control" name="setListingField">
                            <option value=""></option>
                            @foreach ($listFields as $fieldName)
                                <option value="{!! $fieldName !!}">{!! \App\Models\Listing\Listing::getLabel($fieldName) !!}</option>
                            @endforeach
                        </select>
                        &nbsp;
                        <label for="geocodingField">To Geocoding Field</label>
                        <select class="form-control" name="toGeocodingField">
                            <option value=""></option>
                            @foreach ($geocodingFields as $fieldName)
                                <option value="{!! $fieldName !!}">{!! $fieldName !!}</option>
                            @endforeach
                        </select>
                    </div>
                    &nbsp;
                    <button type="submit" class="btn btn-primary">Submit</button>

            </form>
            @endif

            @if ($noGeocodingInfo)

                <br>
                <hr>
                <h3>Not geocoded:</h3>
                <table class="table table-striped formHandlerList">
                    @foreach ($noGeocodingInfo as $listing)
                        <tr>
                            <td>
                                <a href="{!! routeURL('staff-listings', [ $listing->id ]) !!}">{{{ $listing->name }}}</a>
                            </td>
                            @foreach ($listFields as $fieldName)
                                <td>
                                    <a href="{!! routeURL('staff-listings', [ $listing->id ]) !!}">{{{ $listing->$fieldName }}}</a>
                                </td>
                            @endforeach
                        </tr>
                    @endforeach
                </table>

            @endif

        </div>

    @else

        <div class="container">
            <h1>Use Geocoding Info</h1>

            @include('Lib/formHandler/doEverything', [ 'itemName' => 'Listing', 'horizontalForm' => false, 'showTitles' => false ])
        </div>

    @endif

@stop

@section('pageBottom')
    <script>
        {{-- multiSelectAll --}}
        $('table.formHandlerList th input.multiSelectAll').change(function (event) {
          var isChecked = $(this).prop('checked');
          $(this).closest('table.formHandlerList').find('td input[name^="multiSelect"]').each(function () {
              {{-- (note:  change() triggers any other jquery events, such as row highlighting) --}}
              $(this).prop('checked', isChecked).change();
          });
        });

        {{-- Highlight selected rows --}}
        $('table.formHandlerList td input[name^="multiSelect"]').change(function (event) {
          $(this).closest('tr').find('td').toggleClass('background-primary-lt', $(this).prop('checked'));

            {{-- Show/hide any multiSelectHiddenIfNone elements. --}}
          var numberSelected = $(this).closest('table.formHandlerList').find('td input[name^="multiSelect"]:checked').length;
          if (numberSelected)
            $(this).closest('form').find('.multiSelectHiddenIfNone').show();
          else
            $(this).closest('form').find('.multiSelectHiddenIfNone').hide();
        });

        {{-- Highlight the columns selected in the form --}}

        $('select[name="setListingField"]').change(function (event) {
          $('.listingField').removeClass('background-primary-md');
          $('.listingField_' + $(this).val()).addClass('background-primary-md');
        });

        $('select[name="toGeocodingField"]').change(function (event) {
          $('.geocodingField').removeClass('background-primary-md');
          $('.geocodingField_' + $(this).val()).addClass('background-primary-md');
        });
    </script>

    @parent
@stop