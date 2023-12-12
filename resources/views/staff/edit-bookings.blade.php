@extends('staff/edit-layout')

@section('aboveForm')
    @if ($formHandler->mode == 'searchAndList')

        <div class="clearfix">
            <div class="" style="text-align: center;">


                <h3 style="font-weight: bold;">Commission generated:</h3>
                <p>in US Dollar: <strong>$<?php echo number_format(($commissionTotal), 2, ',', '.'); ?></strong></p>
                <p><?php
                       $commissionTotalEUR = 0.82 * $commissionTotal;

                       echo 'in EUR: <strong>' . number_format(($commissionTotalEUR), 2, ',', '.') . '€</strong>';
                       ?></p>
                <details class="details">
                    <summary><i class="fa fa-info"></i> info</summary>
                    <ul>
                        <li>includes cancellations</li>
                        <li>includes not finally confirmed/ upcoming stays</li>
                        <li>initial commission shown is since launch 2002</li>
                    </ul>
                </details>
            </div>


        </div>

    @elseif ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">

                {{-- todo: remove if not using--}}

                {{-- TODO: When resendConfirmationEmail works we can use <a class="objectCommandPostFormValue" data-object-command="resendConfirmationEmail" href="#"> --}}
                {{-- <li><a href="https://old-secure.hostelz.com/staff/editBookings.php?m=d&w[id]={!! $formHandler->model->id !!}&resendConfirmationEmail=1" onClick="javascript:return confirm('Resend the confirmation email?');">Re-send Confirmation Email</a></li>

                 @if ($formHandler->model->getImportSystem()->cancellationMethod == 'API')
                     <li><a class="objectCommandPostFormValue" data-object-command="cancelBooking" href="#" onClick="javascript:return confirm('Cancel booking.  Are you sure?');">Cancel Booking</a></li>
                 @endif--}}
            </ul>
        </div>

    @endif

@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}"
               class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!}
                History</a>
            @if ($formHandler->model->listingID)
                <a href="{!! routeURL('staff-listings', [ $formHandler->model->listingID ]) !!}"
                   class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!}
                    Listing</a>
            @else
                <a href="#" class="list-group-item disabled">(No listing)</a>
            @endif
            @if ($formHandler->model->importedID)
                <a href="{!! routeURL('staff-importeds', [ $formHandler->model->importedID ]) !!}"
                   class="list-group-item"><span
                            class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Imported') !!} Imported</a>
            @else
                <a href="#" class="list-group-item disabled">(No Imported ID)</a>
            @endif
            @if (auth()->user()->hasPermission('staffEditUsers'))
                @if ($formHandler->model->userID)
                    <a href="{!! routeURL('staff-users', [ $formHandler->model->userID ]) !!}"
                       class="list-group-item"><span
                                class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
                @else
                    <a href="#" class="list-group-item disabled">(No user ID)</a>
                @endif
            @endif
            @if ($formHandler->model->email != '')
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'senderOrRecipientEmail' => $formHandler->model->email, 'spamFilter' => false ]) !!}"
                   class="list-group-item"><span
                            class="pull-right">&raquo;</span>{!! langGet('Staff.icons.MailMessage') !!} Emails</a>
            @endif
            @if ($formHandler->model->affiliateID && auth()->user()->hasPermission('staffEditUsers'))
                <a href="{!! routeURL('staff-users', [ $formHandler->model->affiliateID ]) !!}" class="list-group-item"><span
                            class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} Affiliate</a>
            @endif
        </div>

    @endif

@stop


@section('belowForm')

    {{-- TO DO
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'display')
       {if $bookingDetails}
        {config_load file="language_$LANGUAGE.config" section='bookingRequest'}
        {config_load file="language_$LANGUAGE.config" section='bookingConfirmationEmail'}
        <link href="{cssPath f="bookingRequest"}" type="text/css" rel="stylesheet">
        <div class="billTable">
            {include file="bookingDetails.inc.html"}
        </div>
        {/if}
    @endif
    --}}

@stop


@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name." ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'listingID', 'placeholderText' => "Search by listing ID, name, or city." ])

    @parent

@endsection
