<h2>@langGet('articles.categoryTitleNotLogin')</h2>
<p>@langGet('articles.categoryTextNotLogin1')</p>
<h3>@langGet('articles.categoryTextNotLogin2')</h3>
<p>@langGet('articles.categoryTextNotLogin3')</p>
 
<section class="py-4">
    <div class="dark-overlay">
        <img alt="" src="{!! routeURL('images', 'signup-hostels.jpg') !!}" class="bg-image">
        <div class="container col-12 col-md-8 position-relative overlay-content py-4">
            <div class="align-items-center justify-content-center py-2 text-center text-white">
                <p class="text-white h2">@langGet('Article.SignUpTitle')</p>
                <p>@langGet('Article.SignUpText')</p>
                <div class="mb-4">
                    <form method="post" action="{{ routeURL('userSignup') }}">
                        {!! csrf_field() !!}
                        <div class="row">
                            <div class="col-md-6 mx-auto">
                                <div class="form-group">
                                    <input type="email" class="form-control" id="email" name="email" placeholder="@langGet('loginAndSignup.BestEmailSignUp')" value="{{{ @$email }}}" required>
                                </div>
                                <button type="submit" name="submit" value=1 class="btn btn-block bg-primary text-white">@langGet('global.SignUp')</button>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="mb-4">
                    <span>@langGet('loginAndSignup.AlreadyAccount')</span>
                    <span><a class="ml-1 font-weight-bold text-white" href="{!! routeURL('login')!!}">@langGet('global.login')</a></span>
                </div>
            </div>
        </div>
    </div>
</section>