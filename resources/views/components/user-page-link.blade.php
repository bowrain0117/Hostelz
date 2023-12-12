@props(['user'])

@if ($user->nickname)
    @if($user->pathPublicPage)
        <a href="{{ $user->pathPublicPage }}" class="cl-primary" >{{ $user->nickname }}</a>
    @else
        {{ $user->nickname }}
    @endif
@else
    Hostelz Writer
@endif
