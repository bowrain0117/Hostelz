<?php

use App\Models\MailMessage;

Lib\HttpAsset::requireAsset('staff.css');
?>

@extends('layouts/admin')

@section('title', 'Listing Corrections')

@section('content')

    <div class="breadcrumbs">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}

            @if ($formHandler->mode == 'display')
                {!! breadcrumb('Listing Correction') !!}
            @else
                {!! breadcrumb('Listing Corrections') !!}
            @endif
        </ol>
    </div>

    <div class="container">

        @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update')

            <div class="navLinksBox">
                <ul class="nav nav-pills">
                    <li><a href="{!! routeURL('staff-listings', $formHandler->model->targetListing) !!}">Edit
                            Listing</a></li>
                        <?php
                        $correctionContact    = $formHandler->model->getBestEmail('listingIssue');
                        $listingContact       = $formHandler->model->targetListingListing->getBestEmail('listingIssue');
                        $contactsAreDifferent = ($correctionContact != '' && $listingContact != '' && $correctionContact != $listingContact);
                        ?>

                    @if ($correctionContact != '')
                        <li>
                            <a href="{!! routeURL('staff-mailMessages', 'new') !!}?composingMessage[recipient]={{{ $correctionContact }}}&composingMessage[cc]={{{ $contactsAreDifferent ? $listingContact : '' }}}&composingMessage[bodyText]={!! urlencode(MailMessage::quoteText(trim($formHandler->model->comment, '"'))."\n\n") !!}&composingMessage[subject]=question">Email
                                Note to {{{ $correctionContact }}}</a></li>
                    @elseif ($listingContact != '')
                        <li>
                            <a href="{!! routeURL('staff-mailMessages', 'new') !!}?composingMessage[recipient]={{{ $listingContact }}}&composingMessage[bodyText]={!! urlencode(MailMessage::quoteText(trim($formHandler->model->comment, '"'))."\n\n") !!}&composingMessage[subject]=question">Email
                                Note to {{{ $listingContact }}}</a></li>
                    @endif
                </ul>
            </div>

        @endif


        @if (@$message != '')
            <br>
            <div class="well">{!! $message !!}</div>
        @endif

        {{-- Icon --}}

        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Listing', '') !!}</div>

        <div class="staffForm">

            @if ($formHandler->mode == 'updateForm')

                <form method="post" action="{!! Request::fullUrl() !!}" class="formHandlerForm form-horizontal">

                    <input type="hidden" name="_token" value="{!! csrf_token() !!}">

                    <h1>Listing Correction</h1>

                    @include('Lib/formHandler/form', [ 'horizontalForm' => true ])

                    <br>
                    <div class="text-center">

                        @if ($formHandler->model->verified != \App\Models\Listing\Listing::$statusOptions['listingCorrectionFlagged'])
                            <button class="btn setValueAndSubmit" data-name-of-field="data[verified_hidden_input]"
                                    data-value-of-field="{!! \App\Models\Listing\Listing::$statusOptions['listingCorrectionFlagged'] !!}">
                                Flag
                            </button>
                        @else
                            <button class="btn setValueAndSubmit" data-name-of-field="data[verified_hidden_input]"
                                    data-value-of-field="{!! \App\Models\Listing\Listing::$statusOptions['listingCorrection'] !!}">
                                Un-Flag
                            </button>
                        @endif

                        <button class="btn btn-danger" name="mode" value="delete" type="submit"
                                onClick="javascript:return confirm('Delete.  Are you sure?')">Delete
                        </button>
                        <a class="btn btn-success"
                           href="{!! routeURL('staff-mergeListings', [ 'showThese', $formHandler->model->targetListing.','.$formHandler->model->id ]) !!}">Continue
                            Merging...</a>

                    </div>

                    {{-- Hidden update button (used by setValueAndSubmit javascript to submit the form) --}}
                    <button style="display:none" name="mode" value="update" type="submit">#</button>

                </form>

            @else

                {{-- Form/Results --}}


                @include('Lib/formHandler/doEverything', [ 'itemName' => 'Listing Correction', 'horizontalForm' => true ])

            @endif

        </div>


        {{--
        @if ($formHandler->mode == 'searchAndList')

            <p><a href="{!! currentUrlWithQueryVar(['mode'=>'editableList'], ['page']) !!}">Multiple Edit/Delete</a></p>

        @elseif ($formHandler->mode == 'editableList')

            <p><a href="{!! currentUrlWithQueryVar(['mode'=>'searchAndList'], ['page']) !!}">Return to the Regular List</a></p>

        @endif
        --}}

    </div>

@stop

@section('pageBottom')

    {{-- "setValueAndSubmit" buttons --}}

    <script>
      $("button.setValueAndSubmit").click(function (event) {
        event.preventDefault();
        $("[name='" + $(this).data('nameOfField') + "']").val($(this).data('valueOfField'));
        $("form.formHandlerForm button[value='update']").click();
      });
    </script>

    @parent

@endsection
