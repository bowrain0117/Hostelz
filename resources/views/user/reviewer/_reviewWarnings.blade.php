{{-- Warn about issues with the review. --}}

@if ($review->review != '')
    @if (str_word_count($review->review) < App\Models\Review::$minimumWordsAccepted)
        <div class="alert alert-warning">Note: This review is less than {!! App\Models\Review::$minimumWordsAccepted !!} words.</div>
    @endif
    
    @if (array_intersect([ 'I', 'me', 'my' ], preg_split('#[\\s.,]#', $review->review, -1, PREG_SPLIT_NO_EMPTY)))
        <div class="alert alert-warning">Note: This review appears to contain first person words ("I", "me", "my").  See the note about first-person in the Tips.</div>
    @endif
    
    @if (!$review->pics->isEmpty())
        @if ($review->pics->count() < \App\Models\Review::MIN_PIC_COUNT)
            <div class="alert alert-warning">Note: You must upload at least {!! \App\Models\Review::MIN_PIC_COUNT !!} of your own photos of this hostel if you want to be paid for the review.</div>
        @endif
    @endif

@endif
            
@if ($review->listing->propertyType != 'Hostel')
    <div class="alert alert-warning">Note: This listing is currently not listed as a hostel in our system.  Be sure it is a hostel (with dorm rooms).</div>
@endif
            
@if (!$review->listing->supportEmail && !$review->listing->bookingsEmail && !$review->listing->managerEmail && $review->listing->pendingListingCorrections->isEmpty())
    <div class="alert alert-warning"><i class="fa fa-exclamation-circle"></i>&nbsp; No email address known for the listing.  
        Contact info for the hostel is required before we can accept your review.  
        Please submit their email address with the <a href="{!! routeURL('listingCorrection', $review->listing->id) !!}">listing update form</a>.
    </div>
@endif
