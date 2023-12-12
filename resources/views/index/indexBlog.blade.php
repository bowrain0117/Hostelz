@if(isset($blogs))
    <section class="py-5 py-lg-6 bg-gray-100">
        <div class="container">
            <div class="text-center">
                <p class="subtitle mb-2">{!! langGet('index.blogSubTitle') !!}</p>
                <h3 class="title-2 cl-dark text-left text-lg-center mb-5">{!! langGet('index.blogTitle') !!}</h3>
            </div>

            <div class="mb-5 text-center">
                <p>@langGet('index.blogDesc', ['BlogLink' => routeURL('articles')])</p>
            </div>

            <div class="row">

                @foreach ($blogs as $article)

                    <script type="application/ld+json">
                        {
                          "@context": "https://schema.org",
                          "@type": "Blog",
                          "mainEntityOfPage": {
                            "@type": "WebPage",
                            "@id": "https://www.hostelz.com/articles"
                          },
                          "headline": "{{ clearTextForSchema($article->getArticleTitle()) }}",
                          "description": "{{ clearTextForSchema($article->getSnippet(300)) }}",
                          "image": {
                            "@type": "ImageObject",
                            "url": "{!! $article->thumbnailUrl('originals') !!}",
                            "width": 1000,
                            "height": 459
                          },
                          "author": {
                            "@type": "Organization",
                            "name": "{{ clearTextForSchema($article->authorName) }}"
                          },
                          "publisher": {
                            "@type": "Organization",
                            "name": "Hostelz",
                            "logo": {
                              "@type": "ImageObject",
                              "url": "https://www.hostelz.com/images/logo-hostelz.png",
                              "width": 180,
                              "height": 42
                            }
                          }
                        }
                    </script>

                    <div class="col-lg-4 col-sm-6 mb-4 mb-lg-0 hover-animate">
                        <div class="card shadow border-0 h-100">
                            <a href="{!! $article->url() !!}" title="{{{ $article->getArticleTitle() }}}">
                                <img
                                    data-src="{!! $article->thumbnailUrl('originals') !!}"
                                    src=""
                                    title="{{{ $article->getArticleTitle() }}}"
                                    alt="{{{ $article->getArticleTitle() }}}"
                                    class="img-fluid card-img-top lazyload"
                                />
                            </a>
                            <div class="card-body">
                                <h4 class="mt-2 mb-3"><a href="{!! $article->url() !!}" class="text-dark" title="{{{ $article->getArticleTitle() }}}">{{{ $article->getArticleTitle() }}}</a></h4>
                                <p class="mb-3 mb-sm-4">{!! $article->getSnippet(125) !!}</p>
                                <a href="{!! $article->url() !!}" class="btn bg-light ml-auto mb-2 tt-n font-weight-600" title="{{{ $article->getArticleTitle() }}}">@langGet('articles.readMore')</a>
                            </div>
                        </div>
                    </div>

                @endforeach

            </div>

        </div>
    </section>
@endif
