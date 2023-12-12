<div>

    @if ($listing)
        <h4>
            <a href="{!! routeURL('staff-listings', $listing->id) !!}">{!! langGet('Staff.icons.Listing') !!} {{{ $listing->name }}}</a>
            <?php $isLiveOrWhyNot = $listing->isLiveOrWhyNot(); ?>
            [<span class="{!! $isLiveOrWhyNot == 'live' ? 'text-success' : 'text-danger' !!}">{!! langGet('Listing.isLiveOrWhyNot.' . $isLiveOrWhyNot) !!}</span>]
        </h4>
        
        @if (!$booking)
        
            @if ($mail) 
                @if ($user && in_array($listing->id, $user->mgmtListings))
                    <div class="text-success"><i class="fa fa-check-circle"></i> This user manages this listing.</div>
                @elseif ($listing->hasAnyMatchingEmail($mail->senderAddress))
                    <div class="text-success"><i class="fa fa-check-circle-o"></i> Email in listing, but not managing it.</div>
                    <script>
                        $("div#macros a:contains('mgmt signup')").css('background-color', '#ffa');
                    </script>
                @else
                    <div class="text-warning">(Email address not same as listing's.)</div> 
                @endif
            @endif
            
            @foreach ($mail->getNonLocalEmailAddresses() as $nonLocalEmail)
                @if (filter_var($nonLocalEmail, FILTER_VALIDATE_EMAIL) && !in_array($nonLocalEmail, $listing->getAllEmails()))
                    <p class="text-warning">
                        <i class="fa fa-question-circle"></i> 
                        <strong>
                            "{{{ $nonLocalEmail }}}" isn't listed as a contact on this listing.
                            <a href="#" class="addListingEmail" data-listingID="{!! $listing->id !!}" data-email="{{{ $nonLocalEmail }}}">Click here to add it</a>
                        </strong>
                    </p>
                @endif
            @endforeach
        
            @if ($listing->web != '')
                <div>Website: <a href="{{{ $listing->web }}}">{{{ $listing->web }}}</a></div>
            @endif
            
            <p>
                <div class="bold">Booking Systems:</div>
                <div>
                    @forelse ($listing->importeds()->orderBy('status')->get() as $imported)
                        <a href="{!! routeURL('staff-importeds', [ $imported->id ]) !!}" {!! $imported->status == 'active' ? '' : 'class="text-muted" style="text-decoration: line-through"' !!}>{!! $imported->getImportSystem()->shortName() !!}</a>&nbsp;
                    @empty
                        <span class="text-muted">(none)</span>
                    @endforelse
                </div>
            </p>
                
            @if ($listing->comment != '')
                <p><blockquote style="font-size: 13px; padding: 5px 8px;">
                    {{{ Str::limit($listing->comment, 160) }}}
                    @if (strlen($listing->comment) > 160) <a href="{!! routeURL('staff-listings', $listing->id) !!}">more</a> @endif
                </blockquote></p>
            @endif
        
        @endif
            
        @if (!$listingDuplicates->isEmpty())
            <div class="text-danger"><strong>Possible Duplicates:</strong></div>
            @foreach ($listingDuplicates as $duplicate)
                <?php $duplicateListing = ($duplicate->listingID == $listing->id ? $duplicate->otherListingListing : $duplicate->listing); ?>
                <a href="{!!  routeURL('staff-mergeListings', [ 'showThese', $duplicate->listingID.','.$duplicate->otherListing ]) !!}">{!! $duplicateListing->name !!}</a> 
                ({!! $duplicate->status !!} {!! $duplicate->score !!}%)
            @endforeach
        @endif
        
    @endif
    
    @if ($booking)
    
        @if ($listing) <hr> @endif
        
        <h4>Booking</h4>
        <div><a href="{!! routeURL('staff-bookings', $booking->id) !!}">{!! langGet('Staff.icons.Booking') !!} {{{ $booking->getBookingIdDisplayString() }}}</a></div>
        <div>{{{ $booking->displayName() }}}</div>
        <div>{{{ $booking->email }}}</div>
        <div>Start Date: {!! $booking->startDate !!}</div>
        <div>Nights: {{{ $booking->nights }}}, People: {{{ $booking->people }}}</div>
    @endif
    
    @if ($user)
    
        @if ($listing || $booking) <hr> @endif

        <h4>User Account</h4>
        
        <div><a href="{!! routeURL('staff-users', $user->id) !!}">{!! langGet('Staff.icons.User') !!} {{{ $user->username }}} {{{ $user->name }}}</a></div>
        
    @endif
        
    @if ($ratings)
    
        <h4>Ratings</h4>
        
        @foreach ($ratings as $rating)
            <div><a href="{!! routeURL('staff-ratings', $rating->id) !!}">{!! langGet('Staff.icons.Rating') !!} {{{ $rating->listing ? $rating->listing->name : '[unknown listing]' }}} ({!! $rating->formatForDisplay('status') !!})</a></div>
        @endforeach
        
    @endif  
    
    @if ($incomingLinks)
    
        <h4>Incoming Links</h4>
        
        @foreach ($incomingLinks as $incomingLink)
            <div><a href="{!! routeURL('staff-incomingLinks', $incomingLink->id) !!}">{!! langGet('Staff.icons.IncomingLink') !!} {{{ $incomingLink->url }}} ({!! $incomingLink->formatForDisplay('contactStatus') !!})</a></div>
            @foreach ($mail->getNonLocalEmailAddresses() as $nonLocalEmail)
                @if (filter_var($nonLocalEmail, FILTER_VALIDATE_EMAIL) && !in_array($nonLocalEmail, $incomingLink->contactEmails) && !in_array($nonLocalEmail, $incomingLink->invalidEmails))
                    <div class="alert alert-warning alert-dismissible underlineLinks" role="alert">
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                        <i class="fa fa-exclamation-circle"></i> 
                        <strong>
                            "{{{ $nonLocalEmail }}}" isn't listed as a contact on this incoming link.
                            <a href="#" class="addIncomingLinkEmail" data-incomingLinkID="{!! $incomingLink->id !!}" data-email="{{{ $nonLocalEmail }}}">Click here to add it</a>
                        </strong>
                    </div>
                @endif
            @endforeach
        @endforeach
        
    @endif
    
</div>

<script>
    $(".addListingEmail").click(function (event) {
        event.preventDefault();
        var $elementClicked = $(this);
        $.post("@routeURL('staff-listingAddContact')", 
            { listingID: $elementClicked.attr('data-listingID'), email: $elementClicked.attr('data-email'), _token: "{!! csrf_token() !!}" {{-- for CSRF --}} }, 
            function(data) {
                if (data == 'ok') $elementClicked.closest('p').remove();
		    }
	    );
    });
    $(".addIncomingLinkEmail").click(function (event) {
        event.preventDefault();
        var $elementClicked = $(this);
        $.post("@routeURL('staff-incomingLinkAddContact')", 
            { incomingLinkID: $elementClicked.attr('data-incomingLinkID'), email: $elementClicked.attr('data-email'), _token: "{!! csrf_token() !!}" {{-- for CSRF --}} }, 
            function(data) {
                if (data == 'ok') $elementClicked.closest('div.alert').remove();
		    }
	    );
    });
</script>
