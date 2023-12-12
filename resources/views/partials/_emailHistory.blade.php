@if ($mailMessages && !$mailMessages->isEmpty())

    @foreach ($mailMessages as $email)
        <h4><a href="{!! routeURL('staff-mailMessages', $email->id) !!}" class="{!! @$originalMail && $email->transmitTime >= $originalMail->transmitTime ? 'text-danger' : '' !!}">{!! $email->transmitTime !!} ({!! $email->transmitTime->diffForHumans() !!}) [{!! $email->formatForDisplay('status') !!}] "{{{ $email->subject }}}"</a></h4>
        <table>
            <tr><td>From: &nbsp;<td><strong>{{{ $email->sender }}}</strong></tr>
            <tr><td>To: &nbsp;<td>{{{ $email->recipient }}}</tr>
            @if ($email->cc != '') <tr><td>CC: &nbsp;<td>{{{ $email->cc }}}</tr> @endif
            @if ($email->bcc != '') <tr><td>BCC: &nbsp;<td>{{{ $email->bcc }}}</tr> @endif
        </table>
        <br>
        <div class="{!! $email->status == 'outgoing' ? '' : 'text-info' !!}">
            {!! nl2br(trim(htmlspecialchars(Str::limit($email->bodyTextWithoutQuotedText(), 2000)))) !!}
        </div>
        <hr>
    @endforeach

@else

    (None.)

@endif
