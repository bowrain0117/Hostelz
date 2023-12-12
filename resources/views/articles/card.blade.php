<div class="col-lg-4 col-sm-6 mb-4 hover-animate">
    <div class="card shadow border-0 h-100">

        <a href="{!! $item->url() !!}" title="{{{ $item->getArticleTitle() }}}"><img src="{!! $item->thumbnailUrl('originals') !!}" title="{{{ $item->getArticleTitle()  }}}" alt="{{{ $item->getArticleTitle() }}}" class="img-fluid card-img-top"/></a>
        @if ($article->isForLogedInContent() )
            <div class="position-absolute top-0 left-0 z-index-10 p-3">
                <div class="pre-title bg-primary py-1 px-2 cl-light rounded-sm mb-3">@langGet('global.Pluz')</div>
            </div>
        @endif
        <div class="card-body">
            <h4 class="mt-2 mb-3"><a href="{!! $item->url() !!}" class="text-dark" title="{{{ $item->getArticleTitle() }}}">{{{ $item->getArticleTitle() }}}</a></h4>

            @if ( !$article->isForLogedInContent() )
                <p class="mb-3 mb-sm-4">{!! \Illuminate\Support\Str::limit($item->getArticleTextForDisplay()['text'], 120, '...') !!}</p>
            @else
                <p class="mb-3 mb-sm-4 js-show-if-login js-remove-if-not-login">{!! \Illuminate\Support\Str::limit($item->getArticleTextForDisplay()['text'], 120, '...') !!}</p>
                <p class="mb-3 mb-sm-4 js-show-if-not-login">You need to login!</p>
            @endif

            <a href="{!! $item->url() !!}" class="btn bg-light ml-auto mb-2 tt-n font-weight-600" title="{{{ $item->getArticleTitle() }}}">@langGet('articles.readMore')</a>
        </div>
    </div>
</div>