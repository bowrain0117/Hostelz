@if ($attachedText->status == 'draft')
    <div class="bg-warning text-warning">
        DRAFT (not yet submitted) &mdash;
        Expires {!! $attachedText->expirationDate()->diffForHumans() !!}
    </div>
@elseif ($attachedText->status == 'submitted')
	<div class="bg-info text-info">Awaiting Staff Approval</div>
@elseif ($attachedText->status == 'ok')
	<div class="bg-success text-success"><i class="fa fa-check-circle-o"></i>&nbsp; Approved</div>
@elseif ($attachedText->status == 'denied')
	<div class="bg-danger text-danger">Not Accepted</div>
@elseif ($attachedText->status == 'returned')
	<div class="bg-warning text-warning">
	    <i class="fa fa-exclamation-circle"></i>&nbsp; Returned &mdash; See Comments &mdash;
        Expires {!! $attachedText->expirationDate()->diffForHumans() !!}
	</div>
@endif
