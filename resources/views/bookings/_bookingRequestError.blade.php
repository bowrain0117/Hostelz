{{-- This may be included in the bookingRequest template directly, ?? fetched with AJAX. --}}
<br>
<div class="row">
    <div class="col-md-8">
        <div class="alert alert-danger">
            <i class="fa fa-exclamation-circle"></i> &nbsp;
            @if (@$errorCode != '')
                {!! langGet('bookingProcess.errors.'.$errorCode) !!}
            @endif 
            @if (@$errorText != '')
                {{{ $errorText }}}
            @endif
        </div>
    </div>
</div>