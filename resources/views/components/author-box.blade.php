@props(['user'])

<div class="article-author-bio bg-gray-800 p-4 p-md-5 mt-5 mb-3">
    <div class="media">
        <div class="mr-2 mr-md-4 p-1">
            <img title="Hostel Blog Writer @if ($user->nickname){{ $user->nickname }}@endif"
                 alt="Hostel Blog Writer @if ($user->nickname){{ $user->nickname }}@endif"
                 src="
                 @if ($user->profilePhoto)
                    {!! $user->profilePhoto->url([ 'thumbnails' ]) !!}
                 @else
                    {!! routeURL('images', 'hostelz-blogger-writer.jpg') !!}
                 @endif
            "
                 class="avatar avatar-lg">

            @if($user->isAdmin())
                <span style="margin-left: -20px; z-index: 1; position: relative;">
                    @include('partials.svg-icon', ['svg_id' => 'verified-user-hostelz-white', 'svg_w' => '24', 'svg_h' => '24', 'class' => 'align-top'])
                </span>
            @endif
        </div>

        <div class="media-body text-white">
            <h4 class="text-white">{!! langGet('articles.AboutAuthor') !!}:
                <x-user-page-link :user="$user"/>
            </h4>

            @if ($user->bio)
                <p class="text mb-2">{!! nl2br($user->bio) !!}</p>
            @else
                <p class="text mb-2">{{{ langGet('articles.DefaultBio') }}}</p>
            @endif
        </div>
    </div>
</div>