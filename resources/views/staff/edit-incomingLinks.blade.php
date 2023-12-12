{{--

    This view is currently only used in updateForm mode.

--}}

<?php
    $incomingLink = $formHandler->model;
?>

@extends('staff/edit-layout', [ 'showTitles' => false, 'showModelIcon' => false ])

@section('header')
    <style>
        #mailPanels .panel-heading {
            padding: 6px 8px;
        }
        
        #mailPanels .panel-title {
            font-size: 14px;
        }
        
        #mailPanels .panel-body {
            font-size: 13px;
        }
    </style>
@stop


@section('aboveForm')
    
    <h2><a href="{{{ $incomingLink->url }}}" rel="noreferrer" target="_blank">{{{ $incomingLink->url }}}</a></h2>
    <hr>
    
    @if ($incomingLink->contactStatus == 'todo' && !$incomingLink->competitorLinkSpiderResults())
        <br><div class="alert alert-info">Note: Couldn't find links to hostel websites on the current page.</div>
    @endif
    
    @if ($incomingLink->spiderResults && isset($incomingLink->spiderResults['Hostelz']))
        <br><div class="alert alert-danger">Hostelz.com link found on the website!</div>
    @endif
    
    @if ($relatedIncomingLinks && !$relatedIncomingLinks->isEmpty()) 
        <div class="list-group">
            <a href="#" class="list-group-item active">Related Links</a>
            @foreach ($relatedIncomingLinks as $relatedLink)
                <a href="@routeURL('staff-incomingLinks', $relatedLink->id)" class="list-group-item">{{{ $relatedLink->url }}} ({!! $relatedLink->contactStatus !!})</a>
            @endforeach
        </div>
    @endif
        
@stop


@section('nextToForm')

    <div class="list-group">
        <a href="#" class="list-group-item active">Related</a>
        @if (auth()->user()->hasPermission('admin'))
            <a href="@routeURL('staff-incomingLinksEdit', $incomingLink->id)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.IncomingLink') !!} Admin Edit</a>
        @endif
        <a href="{!! Lib\FormHandler::searchAndListURL('staff-eventLogs', [ 'subjectType' => $formHandler->modelName, 'subjectID' => $incomingLink->id ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.EventLog') !!} History</a>
        @if ($incomingLink->contactEmails)
            @foreach ($incomingLink->contactEmails as $email)
                <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'senderOrRecipientEmail' => $email, 'spamFilter' => false ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.MailMessage') !!} {{{ count($incomingLink->contactEmails) == 1 ? '' : "\"$email\"" }}} Emails</a>
            @endforeach
        @endif
        
        @if ($incomingLink->placeType != '')
            <a href="{!! $incomingLink->placeURL() !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! $incomingLink->placeDisplayName() !!}</a>
        @endif
        
        @if (auth()->user()->hasPermission('staffMarketingLevel2'))
            <a href="@routeURL('staff-incomingLinkAds', $formHandler->model->id)" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.Ad') !!} Ads</a>
        @endif
        
        @if (auth()->user()->hasPermission('staffEditUsers'))
            @if ($incomingLink->userID)
                <a href="{!! routeURL('staff-users', [ $incomingLink->userID ]) !!}" class="list-group-item"><span class="pull-right">&raquo;</span>{!! langGet('Staff.icons.User') !!} {{{ $incomingLink->user->username }}}</a>
            @else
                <a href="#" class="list-group-item disabled">(No user ID)</a>
            @endif
        @elseif ($incomingLink->userID && $incomingLink->userID != auth()->id())
            <a href="#" class="list-group-item disabled">{!! langGet('Staff.icons.User') !!} This link currently belongs to {{{ $incomingLink->user->username }}}</a>
        @endif
        
    </div>
    
    @if (auth()->user()->hasPermission('staffMarketingLevel2'))
        <div class="list-group">
            <a href="#" class="list-group-item">Domain Authority: <strong>{!! $incomingLink->domainAuthority ? $incomingLink->domainAuthority.'/100' : 'unknown' !!}</strong></a>
            <a href="#" class="list-group-item">Page Authority: <strong>{!! $incomingLink->pageAuthority ? $incomingLink->pageAuthority.'/100' : 'unknown' !!}</strong></a>
            <a href="#" class="list-group-item">Traffic Rank: <strong>{!! $incomingLink->trafficRank ? number_format($incomingLink->trafficRank) : 'unknown' !!}</strong></a>
        </div>
    @endif
    
    @if ($incomingLink->contactStatus == 'todo')
        <p><small class="text-muted">Your Hostelz.com email address is "{!! auth()->user()->localEmailAddress !!}".</small></p>
    @endif
        
@stop


@section('belowForm')
    <br>
    
    {{-- Warn about previous emails to same contact (hidden by default, displayed by Javascript) --}}
    <div class="emailExistWarning alert alert-warning alert-dismissible" role="alert" style="display: none;">
        <button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>
        Note: Previous emails to/from the above contacts already exist!
    </div>
        
    <div class="panel-group" id="mailPanels">
                
        {{-- Spider Results --}}
    
        @if ($incomingLink->contactLinkSpiderResults())
            <h4 class="text-warning bold">Possible Contacts Found</h4>
            <table class="table table-condensed">
                <tbody>
                    @foreach ($incomingLink->contactLinkSpiderResults() as $linkType => $links)
                        @foreach ($links as $linksTo => $fromPage)
                            <tr>
                                <td>
                                    @if (stripos($linksTo, 'mailto:') === 0)
                                        <i class="fa fa-fw fa-envelope-o"></i>
                                        <?php
                                            $linksTo = str_replace('mailto:', '', str_replace('mailto://', '', $linksTo));
                                        ?>
                                    @elseif (stripos($linksTo, 'facebook.com'))
                                        <i class="fa fa-fw fa-facebook-square"></i>
                                    @elseif (stripos($linksTo, 'linkedin.com'))
                                        <i class="fa fa-fw fa-linkedin-square"></i>
                                    @elseif (stripos($linksTo, 'twitter.com'))
                                        <i class="fa fa-fw fa-twitter-square"></i>
                                    @endif
                                    <a href="{{{ $linksTo }}}">{{{ $linksTo }}}</a> 
                                    (on <a href="{{{ $fromPage }}}">{{{ $fromPage }}}</a>)
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif
            
        @if ($incomingLink->competitorLinkSpiderResults())
            <h4>Links Found</h4>
            <table class="table table-condensed">
                <thead>
                    <tr><th>Page</th><th></th><th>Links To</th></tr>
                </thead>
                <tbody>
                    @foreach ($incomingLink->competitorLinkSpiderResults() as $linkType => $links)
                        @foreach ($links as $linksTo => $fromPage)
                            <tr>
                                <td><a href="{{{ $fromPage }}}">{{{ $fromPage }}}</a></td>
                                <td><i class="fa fa-long-arrow-right"></i></td>
                                <td><a href="{{{ $linksTo }}}">{{{ $linksTo }}}</a></td>
                            </tr>
                        @endforeach
                    @endforeach
                </tbody>
            </table>
        @endif

        {{-- Email History --}}
    
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="panel-title"><a data-toggle="collapse" href="#emailHistoryPanel">Email/Message History</a></div>
            </div>
            <div id="emailHistoryPanel" class="panel-collapse in">
                <div class="panel-body"></div>
            </div>
        </div>
    
        {{-- Search For Contacts --}}
    
        <div class="panel panel-info">
            <div class="panel-heading">
                <div class="panel-title"><a data-toggle="collapse" href="#searchForContactEmailsPanel">Search for Contact Email Addresses</a></div>
            </div>
            <div id="searchForContactEmailsPanel" class="panel-collapse collapse">
                <div class="panel-body">
                    <p>(Only use this after you were unable to find another way to contact them.  This uses a paid search service and we have a limited number of search credits.)</p>
                    
                    <form class="form-inline">
                        <input type="text" class="form-control" placeholder="Name (optional)" name="name" value="{{{ $incomingLink->name }}}">
                        <button class="btn btn-primary" name="objectCommand" value="searchForContactEmails">Search for "{{{ $incomingLink->domain }}}" Email Addresses</button>
                    </form>
                    <br>
                    <div class="searchForContactEmailsResult"></div>
                </div>
            </div>
        </div>
        
        @if (auth()->user()->hasPermission('admin'))
            
            {{-- Send Payment --}}
        
            <div class="panel panel-info">
                <div class="panel-heading">
                    <div class="panel-title"><a data-toggle="collapse" href="#sendPaymentPanel">Send Payment</a></div>
                </div>
                <div id="sendPaymentPanel" class="panel-collapse collapse">
                    <div class="panel-body">
                        <form method="post" class="form-inline">
                            {!! csrf_field() !!}
                            <div class="input-group">
                                <span class="input-group-addon">$</span>
                                <input type="text" class="form-control" placeholder="Amount" name="amount">
                            </div>
                            <input type="text" class="form-control" placeholder="Email Address" name="email" value="{{{ $incomingLink->contactEmails ? $incomingLink->contactEmails[0] : '' }}}">
                            <input type="password" class="form-control" name="paypalPassword" size=20 placeholder="PayPal Password">
                            <button class="btn btn-primary" type="submit" name="objectCommand" value="sendPayment">Send Money</button>
                        </form>
                    </div>
                </div>
            </div>
            
        @endif
    
    </div>
    
@stop


@section('pageBottom')

    {{-- Generate Text button -- This gets moved up to the form. --}}
    <p id="generateMessageText"><button class="btn btn-sm btn-success" style="margin-top: 8px">Generate Message Text</button></p>

    <script>
        $(document).ready(function() {
            
            {{-- Ghost Contact Statuses that Don't Need to be Used for this Page --}}
            
            @if ($incomingLink->contactStatus == 'todo')
                $("input[name='data\\[contactStatus\\]'][value='discussing']").parent().css('opacity', '0.3');
                $("input[name='data\\[contactStatus\\]'][value='closed']").parent().css('opacity', '0.3');
            @elseif ($incomingLink->contactStatus != 'flagged')
                $("input[name='data\\[contactStatus\\]'][value='todo']").parent().css('opacity', '0.3');
                @if ($incomingLink->contactStatus != 'ignored')
                    $("input[name='data\\[contactStatus\\]'][value='ignored']").parent().css('opacity', '0.3');
                @endif
                @if ($incomingLink->contactStatus != 'initialContact')
                    $("input[name='data\\[contactStatus\\]'][value='initialContact']").parent().css('opacity', '0.3');
                @endif
            @endif
            
            {{-- Highlight Certain Fields (to make them stand out) --}}
            
            $("label[for='contactStatus']").parent().css('background-color', '#f0f0fa');
            
            {{-- Ghost previously used Contact Topics --}}
            
            $("input[name='data\\[contactTopics\\]\\[\\]']:checked")
                .attr('data-previously-selected', 'yes')
                .change(function (e) {
                    // If they click and re-check a previously selected contactTopic, then let it be active again to use for a new generated message
                    $(this).removeAttr('data-previously-selected');
                })
                .parent().css('opacity', '0.4');
            
            {{-- Change "update" button text to "submit" (makes more sense since the form may also do things like sending an email) --}}
            
            $("form.formHandlerForm button[value='update']").html('Submit');
            
            {{-- Search Website for Contacts --}}
            
            // Automatically use the name entered in the form
            $("input[name='data\\[name\\]']").blur(function (e) {
                $("#searchForContactEmailsPanel input[name='name']").val($(this).val());
            });
            
            $("#searchForContactEmailsPanel button").click(function (e) {
                e.preventDefault();
                $('.searchForContactEmailsResult').html("[Loading]");
                $.get("?objectCommand=searchForContactEmails&" + $.param($("#searchForContactEmailsPanel input[name='name']")), function(data) {
                    $('.searchForContactEmailsResult').html(data);
                });
            });
            
            {{-- Email History --}}
            
            updateEmailHistory();
            
            // (This moves up to the table element so that it will work when new fields are dynamically added
            $('input[name="data\\[contactEmails\\]\\[\\]"]').closest('table').focusout(function () { 
                updateEmailHistory(); 
            });
            
            $('input[name="data\\[contactFormURL\\]"]').blur(function() { 
                updateEmailHistory(); 
            });
            
            {{-- Generate Text --}}
            
            $('#generateMessageText').detach().insertAfter($("input[name='data\\[contactTopics\\]\\[\\]']").last().parent().parent());
            $('#generateMessageText button').click(function (event) {
                event.preventDefault();
                $.getJSON(window.location.pathname, { 
                        objectCommand: 'generateMessageText', 
                        category: $("input[name='data\\[category\\]']:checked").val(),
                        contactName: $("input[name='data\\[name\\]']").val(),
                        placeEncodedString: $("input[name='data\\[placeSelector\\]']").val(),
                        otherWebsitesLinked: $("input[name='data\\[otherWebsitesLinked\\]\\[\\]']:checked")
                            .map(function() { return this.value; }).get(),
                        contactTopics: $("input[name='data\\[contactTopics\\]\\[\\]']:checked:not([data-previously-selected])")
                            .map(function() { return this.value; }).get()
                    },
    				function (data) {
        		        $("textarea[name='data\\[contactMessage\\]']").val(data.messageText);
        		        $("input[name='data\\[emailSubject\\]']").val(data.subject);
        			}
        		);
            });
            
            {{-- Highlight Contact Topics --}}
            highlightContactTopicsForCategory();
            
            $("input[name='data\\[category\\]']").change(function (e) {
                highlightContactTopicsForCategory();
            });
        });
        
        function highlightContactTopicsForCategory() 
        {
            var category = $("input[name='data\\[category\\]']:checked").val();
            var topics = null;
            
            switch (category) {
                @foreach ($contactTopicsForCategory as $category => $topics)
                    case "{!! $category !!}":
                        topics = {!! json_encode($topics) !!};
                        break;
                @endforeach
            }
            
            // Reset all of them
            $("input[name='data\\[contactTopics\\]\\[\\]']:not([data-previously-selected])").parent().css('background-color', 'inherit');
            
            if (topics) {
                $.each(topics, function(index, value) {
                    $("input[name='data\\[contactTopics\\]\\[\\]'][value='"+value+"']:not([data-previously-selected])").parent().css('background-color', '#eaeaff');
                });
            }
        }
        
        function updateEmailHistory()
        {
            var emails = $('input[name="data\\[contactEmails\\]\\[\\]"]').serialize();
            var contactForm = $('input[name="data\\[contactFormURL\\]"]').serialize();
            var query = emails + (emails != '' && contactForm != '' ? '&' : '') + contactForm;
            $('#emailHistoryPanel div.panel-body').html("[Loading]");
            $.get("?objectCommand=emailHistory&" + query, function(data) {
                $('#emailHistoryPanel div.panel-body').html(data);
                @if ($incomingLink->contactStatus == 'todo')
                    if (data != '' && ($('input[name="data\\[contactStatus\\]"]:checked').val() == 'initialContact' || $('input[name="data\\[contactStatus\\]"]:checked').val() == 'todo'))
                        $('.emailExistWarning').show();
                    else
                        $('.emailExistWarning').hide();
                @endif
            });
        }
        
    </script>
    
    @include('partials/_placeFieldsSelectorEnable')

    @if (auth()->user()->hasPermission('admin'))
        @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by username or name.", 'minCharacters' => 0 ])
    @endif
    
    @parent

@endsection
