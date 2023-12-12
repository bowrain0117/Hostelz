@extends('staff/edit-layout')


@section('aboveForm')
    
    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        <div class="navLinksBox">
            <ul class="nav nav-pills">
                <li><a href="{!! routeURL('staff-mailMessages', 'new') !!}?composingMessage[recipient]={{{ $formHandler->model->getEmailAddress() }}}">Send Email</a></li>
                @if ($formHandler->model->isAllowedToLogin())
                    <li><a href="{!! routeURL('staff-user-autoLogin', $formHandler->model->id) !!}">Login as User</a></li>
                @else
                    <li class="disabled"><a href="#">(Inactive User - Login Disabled)</a></li>
                @endif
            </ul>
        </div>
    
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            @if (auth()->user()->hasPermission('admin'))
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'userID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} User's Log</a>

                <a href="{!! Lib\FormHandler::searchAndListURL('staff-bookings', [ 'affiliateID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span> Affiliate Earnings</a>
            @endif
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-bookings', [ 'userID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Booking') !!} Bookings</a>

            <a href="{!! Lib\FormHandler::searchAndListURL('staff-ratings', [ 'userID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Rating') !!} Ratings</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-reviews', [ 'reviewerID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Review') !!} Paid Reviews</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-attachedTexts', [ 'userID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.CityInfo') !!} Place Descriptions</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-articles', [ 'userID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Article') !!} Articles</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-incomingLinks', [ 'userID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.IncomingLink') !!} Incoming Links</a>
            @if ($formHandler->model->hasPermission('staffEmail'))
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'userID' => $formHandler->model->id, 'spamFilter' => true ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.MailMessage') !!} Mailbox</a>
            @endif
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'senderOrRecipientEmail' => $formHandler->model->username, 'spamFilter' => false ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.MailMessage') !!} Emails</a>
            <a href="{!! routeURL('staff-user-pay', [ $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span> <i class="fa fa-money"></i> Pay</a>
            @if ($formHandler->model->mgmtListings)
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-listings', [ 'id' => $formHandler->model->mgmtListings ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!} Managed Listings</a>
            @endif
        </div>
        <div class="list-group">
            <a href="#" class="list-group-item active">User Settings</a>
            @foreach ([ 'changePassword', 'profilePhoto' ] as $manageAction)
                <a href="{!! routeURL('staff-user-settings', [ $formHandler->model->id, $manageAction ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('UserSettingsHandler.icons.'.$manageAction) !!} {!! langGet('UserSettingsHandler.actions.'.$manageAction) !!}</a>
            @endforeach
        </div>
    @endif
                        
@stop


@section('belowForm')

    {{--
    @if ($formHandler->mode == 'searchAndList')
        
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'editableList'], ['page']) !!}">Multiple Edit/Delete</a></p>
            
    @elseif ($formHandler->mode == 'editableList')
                
        <p><a href="{!! currentUrlWithQueryVar(['mode'=>'searchAndList'], ['page']) !!}">Return to the Regular List</a></p>

    @endif
    --}}
    
    @if ($formHandler->mode == 'updateForm' && auth()->user()->hasPermission('admin'))
        <hr>
        <form method=post class="form-inline">
            {!! csrf_field() !!}
            <div class="input-group">
                <span class="input-group-addon">$</span>
                <input type="text" class="form-control" placeholder="Amount (+/-)" name="amount">
            </div>
            <input type="text" class="form-control" placeholder="Reason/Description" name="reason">
            <button class="btn btn-primary" type="submit" name="objectCommand" value="balanceAdjustAmount">Add Pay Balance Adjustment</button>
        </form>
    @endif
    
@stop


@section('pageBottom')

    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'mgmtListings', 'placeholderText' => "Search by listing ID, name, or city.", 'allowClear' => false ])
    
    @parent

@endsection
