@if ($review->status == 'newHostel')
    @if ($review->review != '') 
        <div class="bg-warning text-warning">
            DRAFT (not yet submitted) &mdash; Expires {!! carbonFromDateString($review->expirationDate)->diffForHumans() !!}
            &nbsp; [<a href="{!! routeURL('reviewer:reviews')."?renew=$review->id" !!}">renew</a>]
        </div>
    @else
        <div>
            Holding for Review until {!! carbonFromDateString($review->expirationDate)->diffForHumans() !!}
        	&nbsp; [<a href="{!! routeURL('reviewer:reviews')."?renew=$review->id" !!}">renew</a>]
        </div>
    @endif
@elseif($review->status == 'newReview')
	@if ($review->pics()->exists())
		<div class="bg-info text-info">Review Awaiting Staff Approval</div>
	@else 
		<div class="bg-warning text-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Waiting for Pictures to be Submitted</div>
	@endif
@elseif($review->status == 'markedForEditing' || $review->status == 'staffEdited')
	<div class="bg-success text-success"><i class="fa fa-check-circle-o"></i>&nbsp; Review Approved, Queued for Processing</div>
@elseif($review->status == 'publishedReview' || $review->status == 'staffEdited' || $review->status == 'removedReview')
	<div class="bg-success text-success"><i class="fa fa-check-circle-o"></i>&nbsp; Review Approved</div>
@elseif($review->status == 'deniedReview')
    <div class="bg-danger text-danger">Review Not Accepted</div>
@elseif($review->status == 'postAsRating')
    <div class="bg-danger text-danger">No Longer Live</div>
@elseif($review->status == 'returnedReview')
	<div class="bg-warning text-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; Review Edit Requested</div>
@endif
