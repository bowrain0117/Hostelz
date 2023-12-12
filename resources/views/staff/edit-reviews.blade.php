@extends('staff/edit-layout')


@if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    <?php $review = $formHandler->model; /* for convenience */ ?>
@endif



@section('header')
    
    <style>
        #macros {
            font-size: 12px;
            height: 100%;
        }
        #macros b {
            font-size: 12px;
            display: block;
            margin: 5px 0 3px 0;
        }
        #macros a {
            display: inline-block;
            width: 100px;
            margin: 0 0 2px 4px;
            text-decoration: underline;
            font-family: sans-serif;
        }
    
    </style>
    
    @parent

@stop


@section('aboveForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')

        @if (auth()->user()->hasPermission('admin'))
    
            <div class="navLinksBox">
                <ul class="nav nav-pills">
                    <li><a class="objectCommandPostFormValue" data-object-command="doPlagiarismChecks" href="#">Update Plagiarism/Pic Check</a></li>
                </ul>
            </div>
            <br>
                
            {{-- Warn about issues with the review. --}}
            
            @if ($review->plagiarismPercent >= 10) {{-- (We don't bother warning about reviews if they're at least close.) --}}
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Has plagiarism percent of {!! $review->plagiarismPercent !!}%.</div>
            @endif
            
            @if (str_word_count($review->review) < App\Models\Review::$minimumWordsAccepted - 35) {{-- (We don't bother warning about reviews if they're at least close.) --}}
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Is less than {!! App\Models\Review::$minimumWordsAccepted !!} words.</div>
            @endif
            
            {{-- (never mind, not worrying as much about first person words any more)
            @if (array_intersect([ 'I', 'me', 'my' ], preg_split('#[\\s.,]#', $review->review, -1, PREG_SPLIT_NO_EMPTY)))
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Contains first person words.</div>
            @endif
            --}}
            
            @if ($review->pics->isEmpty())
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; No pics.</div>
            @else
                @if ($review->pics->count() < \App\Models\Review::MIN_PIC_COUNT)
                    <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Only {!! $review->pics->count() !!} pics.</div>
                @endif
                
                @foreach ($review->pics as $pic)
                    @if ($pic->originalWidth < App\Models\Review::NEW_PIC_MIN_WIDTH && $pic->originalHeight < App\Models\Review::NEW_PIC_MIN_HEIGHT)
                        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; {!! $pic->originalWidth !!} x {!! $pic->originalHeight !!} pic is too small ({!! App\Models\Review::NEW_PIC_MIN_WIDTH !!} x {!! App\Models\Review::NEW_PIC_MIN_HEIGHT !!} minimum).</div>
                    @endif
                    
                    @if (!$pic->imageSearchCheckDate)
                        <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Image search not done for pic <a href="@routeURL('staff-pics', $pic->id)">{!! $pic->id !!}</a>. Do "Update Plagiarism/Pic Check".</div>
                    @elseif ($pic->imageSearchMatches)
                        <div class="alert alert-warning">
                            <i class="fa fa-exclamation-circle"></i>&nbsp; Pic <a href="@routeURL('staff-pics', $pic->id)">{!! $pic->id !!}</a> matches: 
                            @foreach (explode("\n", $pic->imageSearchMatches) as $picMatchURL)
                                &bull; <a href="{{{ $picMatchURL }}}">{{{ $picMatchURL }}}</a>
                            @endforeach
                        </div>
                    @endif
                @endforeach
            @endif
            
            @if (!$review->listing->isLive())
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Listing isn't live.</div>
            @endif
            
            @if ($review->listing->propertyType != 'Hostel')
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Not a hostel.</div>
            @endif
            
            @if (!$review->listing->supportEmail && !$review->listing->bookingsEmail && !$review->listing->managerEmail)
                <div class="alert alert-warning">
                    <i class="fa fa-exclamation-circle"></i>&nbsp; No email address known for the listing.
                    @if ($review->listing->pendingListingCorrections->isEmpty())
                        (No pending listing corrections.)
                    @else
                        <div class="bold">Note: Has pending listing corrections that were submitted!</div>
                    @endif
                </div>
            @endif
            
            @if ($review->listing->combinedRating && abs($review->ratingAsAPercent() - $review->listing->combinedRating) > 23)
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Review rating very different from listing's Combined Rating ({!! $review->rating !!} stars for a {!! $review->listing->formatCombinedRating() !!} combined rating listing).</div>
            @endif
            
            <p>
                <strong>{{{ $review->user->username }}}'s Other Reviews:</strong>
                <?php $hasOtherReviews = false; ?>
                @foreach ($review->user->reviews as $otherReview)
                    @if ($otherReview->id != $review->id)
                        <a href="{!! routeURL('staff-reviews', [ $otherReview->id ]) !!}">{{{ $otherReview->status }}}</a>
                        <?php if ($otherReview->status != 'newHostel') $hasOtherReviews = true; ?>
                    @endif
                @endforeach
            </p>
            
            @if (!$hasOtherReviews)
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; User has no other reviews.</div>
            @endif

            @if ($review->listing->lastEditSessionID != '' && $review->listing->lastEditSessionID == $review->user->sessionID)
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; User's last session ID matches the last listing edit session ID (reviewer is the owner?).</div>
            @endif
            
            @if ($review->listing->isLiveOrNew() && !\App\Models\Review::listingIsAvailabileForReviewing($review->listing, $review->id, $review->language))
                <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; The listing already has a live review in this language.</div>
            @endif

            @if ($review->listing->isLive() &&  $review->listing->hasImportSystemWithOnlineBooking())
                @if (!$review->user->bookings()->where('listingID', $review->listing->id)->exists() &&
                    ($review->bookingInfo == '' || $review->listing->bookings->where('bookingID', $review->bookingInfo)->isEmpty()))
                    <div class="alert alert-warning">
                        <i class="fa fa-exclamation-circle"></i>&nbsp; Booking for this user not found.
                        @if ($review->bookingInfo == '')
                            Booking Confirmation Code is empty.
                        @else
                            Booking Confirmation Code: <strong>{{{ $review->bookingInfo}}}</strong>.
                        @endif

                        <h3>Recent Bookings:</h3>
                        @foreach ($review->listing->bookings()->where('bookingTime', '>', Carbon::now()->subYears(1))->orderBy('bookingTime', 'desc')->get() as $booking)
                            <p>[{!! $booking->getImportSystem()->shortName() !!} {!! $booking->bookingTime !!}: {{{ $booking->bookingID }}} {{{ $booking->name }}}, {{{ $booking->email }}}]<p>
                        @endforeach
                    </div>
                @else 
                    <p class="text-success">(Confirmed booking!)</p>
                @endif 
            @endif
            
        @endif
        
        @if (@$fileList && auth()->user()->hasPermission('staffPicEdit'))
        
            @include('Lib/fileListHandler', [ 'fileListMode' => 'photos', 'fileListShowStatus' => true ])
            <br>
        @endif
     
    @endif
    
@stop


@section('nextToForm')

    @if ($formHandler->mode == 'updateForm' || $formHandler->mode == 'update' || $formHandler->mode == 'display')
    
        <div class="list-group">
            <a href="#" class="list-group-item active">Related</a>
            <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $formHandler->model->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
            <a href="{!! routeURL('staff-listings', [ $review->hostelID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!} Listing</a>
            @if ($review->reviewerID && auth()->user()->hasPermission('staffEditUsers'))
                <a href="{!! routeURL('staff-users', [ $review->reviewerID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} User</a>
            @else
                <a href="#" class="list-group-item disabled">(No user ID)</a>
            @endif
        </div>
        
    @endif
                        
@stop


@section('belowForm')

    @if ($formHandler->mode == 'updateForm')
    
        <br>
        <div class="row">
            <div class="col-md-10 text-center">
                {{-- (Handled by javascript in edit-layout.blade.php) --}}
                @if (auth()->user()->hasPermission('admin'))
                    @if ($review->status == 'newReview' || $review->status == 'returnedReview')
                        <button class="btn btn-success setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="markedForEditing">Approve for Editing</button>
                        <button class="btn btn-warning setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="returnedReview">Return</button>
                        <button class="btn btn-danger setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="deniedReview">Deny</button>
                    @endif
                    @if ($review->status == 'newReview' || $review->status == 'markedForEditing'|| $review->status == 'returnedReview')
                        <button class="btn btn-info setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="staffEdited">Accept As Is</button>
                    @endif
                    @if ($review->status != 'postAsRating')
                        <button class="btn btn-info setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="postAsRating">Make Into a Rating</button>
                    @endif
                @else
                    <button class="btn btn-info setValueAndSubmit" data-name-of-field="data[status]" data-value-of-field="staffEdited">Editing Complete</button>
                @endif
            </div>
        </div>
        
        <br>
        <div class="well">
            <h3>Sub-titled Sections:</h3>
            <p>
                &lt;b>The Location&lt;/b><br>&lt;b>Rooms and Bathrooms&lt;/b><br>&lt;b>Common Spaces&lt;/b><br>&lt;b>Summary&lt;/b>
            </p>
        </div>
        
        @if (auth()->user()->hasPermission('admin'))
            @foreach ($macros as $macroCategory => $categoryMacros)
                {{-- Note: jQuery later moves this to just under the reply field. --}}
                <div id="macros">
                    <b>{!! $macroCategory !!}</b>
                    @foreach ($categoryMacros as $macro)
                    	<a href="#" data-macro-text="{{{ $macro->macroText }}}">{{{ $macro->name }}}</a>
                    @endforeach 
                </div>
            @endforeach
        @endif
        
    @endif

@stop

@section('pageBottom')

    @if ($formHandler->mode == 'updateForm')
    
        <script>
            {{-- Macros --}}
            
            $('#macros').insertAfter('textarea[name="data[newComment]"]'); {{-- move the macro links --}}
            
            $("div#macros a[data-macro-text!='']").click(function(event) {
                event.preventDefault();
                var $textareaField = $(this).parent().siblings('textarea');
                $textareaField.val($textareaField.val() + $(this).data('macroText'));
            });
            
            {{-- Word Counts --}}
            
            showWordCount('data[review]');
            showWordCount('data[editedReview]');
        </script>
    
    @endif
    
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'reviewerID', 'placeholderText' => "Search by ID, username or name." ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'hostelID', 'placeholderText' => "Search by listing ID, name, or city." ])

    @parent

@endsection
