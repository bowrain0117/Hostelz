<?php

Lib\HttpAsset::requireAsset('indexMain.js');
?>
@extends('layouts/default', ['showHeaderSearch' => false ])

@section('title', 'Reviewer - Hostelz.com')

@section('header')

    <style>
        .reviewStatus div {
            padding: 4px 8px;
            font-weight: 600;
            text-align: center;
        }

        .wordCount {
            font-size: 13px;
            color: #222;
            font-weight: 700;
        }
    </style>

    @parent
@stop

@section('content')

    @include('user.navbarDashboard')

    <div class="pt-3 pb-5 container">
        <div class="breadcrumbs">
            <ol class="breadcrumb black" typeof="BreadcrumbList">
                {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
                {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
                @if ($formHandler->mode == 'list')
                    {!! breadcrumb('My Reviews') !!}
                @else
                    {!! breadcrumb('My Reviews', routeURL('reviewer:reviews')) !!}
                    {!! breadcrumb('Review') !!}
                @endif
            </ol>
        </div>
        <div class="pull-right" style="font-size: 60px">{!! langGet('Staff.icons.Review') !!}</div>

        @if ($formHandler->list)

            <h1 class="hero-heading h2">My Reviews</h1>

            <br>
            <div class="alert alert-warning"><b>Note: We currently no longer pay for reviews for hostels that are not
                    located in North America, Australia/Oceania, or Europe. We hope to eventually accept reviews from
                    all countries again. But until further notice, after November 1st we will only pay for reviews for
                    hostels in those areas. If there are hostels in other countries that you just want to review anyway
                    even if it isn't for pay, we will absolutely still gladly accept and publish reviews for hostels in
                    other countries, but those would be non-paid reviews.</b></div>

            <h4>Be sure to read the <a
                        href="{!! routeURL('reviewer:instructions') !!}"><strong>instructions</strong></a> before you
                begin.</h4>
            <br>

            @if ($message != '')
                <div class="well">{!! $message !!}</div>
            @endif

            @if ($formHandler->list->isEmpty())

                <p>You don't yet have any hostels in your hostels list. If you want to hold a hostel for reviewing
                    later, or if
                    you want to submit a review now, use the "Find Hostels to Review" link below...</p>

            @else

                <table class="table table-hover table-striped">

                    @foreach ($formHandler->list as $rowKey => $review)
                        <tr>
                            <td><a href="/{!! Request::path() !!}/{!! $review->id !!}">{{{ $review->listing->name }}}
                                    ({{{ $review->listing->city }}}, {{{ $review->listing->country }}})</a></td>
                            @if ($review->newStaffComment)
                                <td class="reviewStatus">
                                    <div class="bg-warning text-warning">NEW STAFF COMMENT!</div>
                                </td>
                            @else
                                <td></td>
                            @endif

                            <td class="reviewStatus">
                                @include('user/reviewer/_reviewStatus')

                            </td>

                        </tr>
                    @endforeach

                </table>
            @endif

            {!! $formHandler->list->links() !!}

            <h3 class="text-center background-primary-lt" style="padding: 5px; text-decoration: underline"><a
                        href="{!! routeURL('reviewer:findListingsToReview') !!}">Find Hostels to Review</a></h3>

            <p>When you add hostels to this list, we will hold the hostel so that no one else may review it until your
                hold expires. If you want to renew a hostel to continue holding it for a period of time starting from
                today's date, you can use the "renew" link. To submit or edit a review, click the hostel's name.</p>
            <p>We typically approve reviews about once a month, so it may take up to a month for your new reviews to be
                approved. You can view your payment balance and payment information on the <a
                        href="{!! routeURL('user:yourPay') !!}"><strong>Your Pay</strong></a> page.</p>

        @elseif ($formHandler->mode == 'updateForm' || $formHandler->mode == 'display')

                <?php
                $review = $formHandler->model; /* for convenience */ ?>

            <h1 class="hero-heading h2">"{{{ $review->listing->name }}}" Review</h1>

            <hr>
            <h3 class="reviewStatus">@include('user/reviewer/_reviewStatus', [ 'review' => $review ])</h3>
            <hr>
            <h4>Be sure to read the <a
                        href="{!! routeURL('reviewer:instructions') !!}"><strong>instructions</strong></a> before you
                begin.</h4>

            <h3>Tips</h3>
            <ul>
                <li>To see a review look at <a
                            href="{!! \App\Models\Listing\Listing::areLive()->findOrFail(215471)->getURL() !!}">this
                        one</a> for example.
                </li>
                <li>Reviews should be written in <strong>present tense</strong> (instead of saying "the hostel
                    <i>was</i> marvelous", say "the hostel <i>is</i> marvelous"). You also should <strong>avoid using
                        first person ("we," "I," "us," etc.)</strong> since it should be mostly about the hostel in
                    general, not just your personal experience. But if there is something you want to mention about your
                    particular experience you should use "we" instead of "I" since it sounds more professional and
                    you're representing Hostelz.com, not just yourself.
                </li>
                <li>All reviews should at least mention the location, condition of the building and how clean it is, and
                    what the atmosphere is like (fun, quiet, social, dreary, happy, colorful, cold & dull, etc), and
                    details about the bathrooms, dorm rooms, and common areas (kitchen, sitting areas, etc.). We don't
                    usually say much about the staff because they can change from month to month, but you might want to
                    mention the staff if something stands out or if the hostel is run by the actual owners.
                </li>
                <li>Minimum length: <strong>{!! \App\Models\Review::$minimumWordsAccepted !!} words</strong>. May be up
                    to 600 words.
                </li>
                <li>Please use proper punctuation and capitalization.</li>
            </ul>

            <h3>Format reviews using these subtitled sections:</h3>

            <p>This text is a recommended layout for your review. Replace the text in brackets with your review text and
                you can also change the titles of the review sections if needed.</p>

            @foreach (\App\Models\Review::$templateFormat as $title => $text)
                @if ($title != 'intro')
                    <p><strong>{!! $title !!}</strong></p>
                @endif
                <p><em>{!! $text !!}</em></p>
            @endforeach

            <br>

            <div class="staffForm">
                <div class="row">
                    <div class="col-md-10">

                        @include('user/reviewer/_reviewWarnings')

                        <form method="post" class="formHandlerForm form-horizontal">

                            <input type="hidden" name="_token" value="{!! csrf_token() !!}">

                            @include('Lib/formHandler/form', [ 'horizontalForm' => true ])

                            @if ($formHandler->mode == 'updateForm')
                                <p class="text-center">

                                    @if ($review->status == 'newReview')
                                        <button class="btn btn-info submit" name="updateAndSetStatus" value="reviewHold"
                                                type="submit">Change back to Draft
                                        </button>
                                        <button class="btn btn-primary submit" name="updateAndSetStatus"
                                                value="submitted" type="submit">Re-Submit Completed Review
                                        </button>
                                    @else
                                        <button class="btn btn-info submit" name="updateAndSetStatus" value="reviewHold"
                                                type="submit">Save Draft
                                        </button>
                                        <button class="btn btn-primary submit" name="updateAndSetStatus"
                                                value="submitted" type="submit">Submit Completed Review
                                        </button>
                                    @endif

                                    <br>
                                    <button class="btn btn-xs btn-danger submit" name="mode" value="delete"
                                            type="submit" onClick="javascript:return confirm('Delete.  Are you sure?')">
                                        Delete
                                    </button>
                                </p>
                            @endif

                        </form>

                    </div>

                    <div class="col-md-2">

                        <div class="list-group">
                            <a href="#" class="list-group-item active">Links</a>

                            @if ($review->listing->isLive())
                                <a href="{!! $review->listing->getURL() !!}" class="list-group-item"><span
                                            class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Listing') !!} View
                                    Listing</a>
                            @else
                                <a href="#" class="list-group-item disabled">(The listing for this review isn't yet
                                    live.)</a>
                            @endif
                            @if ($formHandler->mode == 'updateForm')
                                <a href="{!! routeURL('reviewer:reviewPics', [ $review->id ]) !!}"
                                   class="list-group-item"><span
                                            class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Pic') !!} Upload
                                    Photos</a>
                            @endif
                        </div>

                        @if ($review->listing->cityInfo)
                            <div class="well">
                                We also appreciate any photos you want to submit of the hostel's city!
                                <b>To upload city photos of {!! $review->listing->city !!}, click <a
                                            href="{!! routeURL('submitCityPics', $review->listing->cityInfo->id) !!}">here</a></b>.
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        @else
            <div class="clearfix"></div>

            @if ($formHandler->mode == 'update')
                <div class="row">
                    <div class="col-md-6">
                        @include('user/reviewer/_reviewWarnings', [ 'review' => $formHandler->model ])
                    </div>
                </div>
            @endif

            @include('Lib/formHandler/doEverything', [ 'itemName' => 'Review', 'horizontalForm' => true, 'showTitles' => false, 'returnToURL' => routeURL('reviewer:reviews') ])

        @endif

    </div>

@stop

@section('pageBottom')

    @if ($formHandler->mode == 'updateForm')

        <script>
            {{-- Word Counts --}}
            showWordCount('data[review]');
        </script>

    @endif

    @parent

@endsection
