<?php 
    Lib\HttpAsset::requireAsset('autocomplete');
    Lib\HttpAsset::requireAsset('staff.css');
?>

@extends('layouts/admin', [ 'customHeader' => '', 'customFooter' => '' ])

@section('title', 'Mail - '.($mail ? $mail->subject : 'New'))

@section('header')
    <style>
        .originalMessage {
            background-color:#202035;
        }
        .translatedMessage {
            background-color:#202055;
        }
        
        .originalOutgoingMessage {
            background-color:#fff;
        }
        .translatedOutgoingMessage {
            background-color:#eef;
        }
        
        #translateOutgoingMessageDropdown {
            @if ($mail)
                display: none;
            @endif
        }
        
        #redirectEmails a {
            text-decoration: underline; 
            font-size: 12px
        }
        
        .inputTable {
            width: 100%;
        }
        
        .inputTable td {
            padding: 2px 0;
        }
        
        .select2-drop {
            font-size: 13px;
        }
        
        #macros {
            font-size: 12px;
            height: 100%;
        }
        #macros b {
            font-size: 12px;
            display: block;
            margin: 5px 0 3px 0;
        }
        #macros a {
            display: inline-block;
            width: 160px;
            margin: 0 0 2px 4px;
            text-decoration: underline;
            font-family: sans-serif;
        }
        
        .outgoingMessageCheckboxes label {
            font-size: 12px;
            display: inline; 
            padding: 4px 14px 0 0;
            font-weight: 100;
        }
        
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

@section('content')

    <div class="breadcrumbs" style="width: auto">
        <ol class="breadcrumb" typeof="BreadcrumbList">
            {!! breadcrumb(langGet('global.Home'), routeURL('home')) !!}
            {!! breadcrumb(langGet('User.menu.UserMenu'), routeURL('user:menu')) !!}
            {!! breadcrumb('Staff', routeURL('staff-menu')) !!}
            {!! breadcrumb('Mail', routeURL(Route::currentRouteName())) !!}
        </ol>
    </div>
    
    <div class="container-fluid">
    
        <form method="post" role="form" class="form-horizontal" name="mailForm">
        <input type="hidden" name="_token" value="{!! csrf_token() !!}">
        
        {{-- Special Messages --}}
        
        @if (@$message != '')
            <br><div class="well">{!! $message !!}</div>
        @endif
        
            
        <div class="row" style="font-size: 13px">
            
            @if ($mail)
        
                {{-- Display Email --}}
                
                <div class="col-md-6">
    
                    {{-- To/From/Subject --}}
    
                    <div class="background-primary-lt" style="color: black; padding: 4px 8px; border-radius: 4px 4px 0 0;">
                        <div class="row">
                            <div class="col-md-6">
                                    From: 
                                    <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'senderOrRecipientEmail' => $mail->senderAddress, 'spamFilter' => false ]) !!}" style="color:#009">{{{ $mail->sender }}}</a>
                                    @if (auth()->user()->hasPermission('admin'))
                                        <a href="https://www.facebook.com/search/results.php?q={!! urlencode($mail->senderAddress) !!}&type=users">[f]</a>
                                    @endif
                            </div>
                            <div class="col-md-6">
                                    To:
                                    <?php $count = substr_count($mail->recipient, ','); ?>
                                    @foreach (explode(',', $mail->recipient) as $addressPart)
                                        <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'senderOrRecipientEmail' =>Emailer::extractEmailAddress($addressPart), 'spamFilter' => false ]) !!}" 
                                            style="color:#009">{{{ trim($addressPart) }}}</a>{!! $count-- ? ',' : '' !!}
                                    @endforeach
                                    
                                    @if ($mail->cc != '') 
                                        <div>
                                            CC: 
                                            <?php $count = substr_count($mail->cc, ','); ?>
                                            @foreach (explode(',', $mail->cc) as $addressPart)
                                                <a href="{!! Lib\FormHandler::searchAndListURL('staff-mailMessages', [ 'senderOrRecipientEmail' =>Emailer::extractEmailAddress($addressPart), 'spamFilter' => false ]) !!}" 
                                                    style="color:#009">{{{ trim($addressPart) }}}</a>{!! $count-- ? ',' : '' !!}
                                            @endforeach
                                        </div>
                                    @endif
                            </div>
                        </div>
                    </div>
                    
                    <div class="background-primary-xlt" style="color: black; padding: 4px 8px;"><strong>{{{ $mail->subject }}}</strong></div>
                    
                    {{-- Message Text Box --}}

                    <textarea class="form-control originalMessage" rows="17" style="font-size:13px;color:#fdfdff" name="originalMessage">{{{ $mail->bodyText }}}</textarea>
                    
                </div>
                
                <div class="col-md-3">
                    <p>
                        {!! $mail->transmitTime !!} ({!! $mail->formatForDisplay('status') !!})
                        
                    	@if (auth()->user()->hasPermission('admin'))
                            (sndrTrust:{!! $mail->senderTrust !!},spamicity:{!! $mail->spamicity !!}%)
                            <button name="command" value="spamicityEvaluate" class="btn btn-xs btn-default">Spamicity Re-eval</button>
                    	@endif
                    	
                    	@if ($mail->spamicity >= 42)
                    	    <div class="text-danger">May be spam ({!! $mail->spamicity !!}% spam rating).</div>
                    	@endif
                	</p>
                	
                	@if ($mail->status == 'new')
                        <p id="moreRecentFromSame" style="display:none;padding:2px;background-color:#FFFFAA" class="text-danger"><strong>Note: The are more recent emails to/from this sender.</strong></p>
                    @endif
                    <p id="otherNewFromSame" style="display:none" class="text-danger"><strong>Note: There are other (older) emails from this sender in your inbox.</strong></p>
                	
                    {{-- Attachments --}}
                    
                    @if ($mail->attachments && !$mail->attachments->isEmpty())
                    	<p>
                    	    <div class="bold">Attachments:</div>
                    	    @foreach ($mail->attachments as $attachment)
                    		    <div>
                    		        <a href="{!! routeURL('staff-mailAttachment', [ $attachment->id, $attachment->filename ]) !!}">{{{ $attachment->filename != '' ? '"'.$attachment->filename.'"' : '[no name]' }}}</a>&nbsp; {{{ $attachment->mimeType }}}
                    		    </div>
                    		@endforeach 
                    	</p>
                    @endif
                    
                    {{-- Translate Button --}}
                    
                    <div class="btn-toolbar">
                        <div class="btn-group" id="translationDropdown">
                            <button type="button" class="btn btn-default" id="translateButton">Translate (auto detect)</button>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                <li><a href="">Auto Detect</a></li>
                                <li class="divider"></li>
                                @foreach (\App\Models\Languages::allNamesKeyedByCode() as $langCode => $langName)
                                    @if ($langCode != 'en')
                                        <li><a href="{!! $langCode !!}">{!! $langName !!}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                    
                    <br>
                    
                    {{-- Notes --}}

                	<p>
                	    Notes: 
                	    <textarea class="form-control" style="background:#FDF5E1;font-size:10pt" name="comment" cols=40 rows=3>{{{ $mail->comment }}}</textarea>
                	</p>
                	
                	<br>
                	
                    {{-- Redirect --}}


                    <div class="input-group input-group-sm">
                        <input type="text" name="redirAddress" class="form-control">
                        <span class="input-group-btn">
                            <button class="btn btn-primary" type="submit" name="command" value="redirect">Redirect</button>
                        </span>
                    </div>

                    <div id="redirectEmails">
                        @foreach (\App\Services\ImportSystems\ImportSystems::allActive() as $systemInfo)
                            @if ($systemInfo->customerSupportEmail != '')
                            	<a href="{{{ $systemInfo->customerSupportEmail }}}">{!! $systemInfo->shortName() !!}</a>&nbsp;
                            @endif
                        @endforeach
                        
                        <a id="listingCustomerSupportEmail"></a>
                    </div>
                    
                    <br>
                    
                    {{-- Reminder Date --}}
                    
                    <table>
                        <tr>
                            <td style="white-space: nowrap;">Reminder Date:&nbsp;</td>
                            <td><input id="reminderDate" name="reminderDate" value="{!! $mail->reminderDate ? carbonFromDateString($mail->reminderDate)->format('Y-m-d') : '' !!}" class="form-control input-xs" style="width: 10em"></td>
                            <td>&nbsp;<a href="#" id="reminderDateClearLink">clear</a></td>
                        </tr>
                    </table>
                    
                    <br>

                    {{-- Buttons --}}

                    <p>
                        @if (auth()->id() != 1)
                            <button name="command" value="defer" class="btn btn-sm btn-default">Defer</button>
                        @endif
                        @if ($mail->status == 'new' || $mail->status == 'hold')
                            <button name="command" value="archive" class="btn btn-sm btn-default">Ignore (archive)</button>
                        @elseif ($mail->status == 'archived')
                            <button name="command" value="unarchive" class="btn btn-sm btn-default">Move to Inbox</button>
                        @endif
                        
                        @if ($mail->status != 'outgoing')
                            <button name="command" value="markAsSpam" type="submit" class="btn btn-sm btn-warning" 
                                onClick="javascript:return confirm('Mark this message as spam?')">Is Spam</button>
                        @endif
                        @if (auth()->user()->hasPermission('admin'))
                            <button name="command" value="delete" type="submit" class="btn btn-sm btn-danger" onClick="javascript:return confirm('Delete?')">Delete</button>
                        @endif
                        @if ($mail->status == 'new' || $mail->status == 'archived')
                            <button name="command" value="hold" type="submit" class="btn btn-sm btn-default">Hold</button>
                        @endif
                        <button name="command" value="update" type="submit" class="btn btn-sm btn-default">Update</button>
                    </p>

                </div>
            
            @else 
            
                {{-- Composing New Email --}}
            
                <div class="col-md-6">
                
                    <h2><i class="fa fa-pencil-square-o"></i>&nbsp; Compose New Email</h2>

                    <br>
                    
                    <div id="redirectEmails">
                        <strong>Email Addresses:</strong> &nbsp;
                        @foreach (\App\Services\ImportSystems\ImportSystems::allActive() as $systemInfo)
                            @if ($systemInfo->customerSupportEmail != '')
                            	<a href="{{{ $systemInfo->customerSupportEmail }}}">{!! $systemInfo->shortName() !!}</a>&nbsp;
                            @endif
                        @endforeach
                        <a id="listingCustomerSupportEmail"></a>
                    </div>
                </div>
            
            @endif
            
            <div @if ($mail) class="col-md-3" @else class="col-md-5" @endif >
                
                @if ($mail)
                    {{-- Select Listing/Booking --}}
                        
                    <table class="inputTable">
                        @if (auth()->user()->hasPermission('admin'))
                            <tr>
                                <td>User:&nbsp;</td>
                                <td style="width: 100%"><input id="userSelector" type="hidden" style="width: 100%" value="{!! $mail->userID !!}"></td>
                            </tr>
                        @endif
                        @if (auth()->user()->hasPermission('staffEditHostels'))
                            <tr>
                                <td>Listing:&nbsp;</td>
                                <td style="width: 100%"><input id="listingSelector" type="hidden" style="width: 100%" value="{!! $mail->listingID !!}"></td>
                            </tr>
                        @endif
                        @if (auth()->user()->hasPermission('staffBookings'))
                            <tr>
                                <td>Booking:&nbsp;</td>
                                <td style="width: 100%"><input id="bookingSelector" type="hidden" style="width: 100%"></td>
                            </tr>
                        @endif
                        @if (auth()->user()->hasPermission('staffMarketing'))
                            <tr>
                                <td>Link:&nbsp;</td>
                                <td style="width: 100%"><input id="incomingLinkSelector" type="hidden" style="width: 100%"></td>
                            </tr>
                        @endif
                    </table>
                    
                    <hr>
    
                    {{-- dynamicHTML --}}
    
                    <div id="dynamicHTML"></div>
                @endif
                    
            </div>
               
        </div>
            
        <br>
        
        {{-- Response --}}
        
        <div class="row" style="font-size: 13px">
        
            <div class="col-md-6">
            
                {{-- To/From/Subject --}}
            
                <div class="background-primary-md" style="color: black; padding: 3px 4px; border-radius: 4px 4px 0 0">
                    <div class="row">
                        <div class="col-md-5">
                            <div class="input-group input-group-xs">
                                <div class="input-group-addon">To</div>
                                <input type="text" class="form-control autoCompleteEmail" name="composingMessage[recipient]" value="{{{ $composingMessageAttributes['recipient'] }}}">
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="input-group input-group-xs">
                                <div class="input-group-addon">CC</div>
                                <input type="text" class="form-control autoCompleteEmail" name="composingMessage[cc]" value="{{{ $composingMessageAttributes['cc'] }}}">
                                @if ($replyAllAddresses)
                                    <span class="input-group-btn">
                                        <button class="btn" type="button" id="autoFillCC">Auto-Fill</button>
                                    </span>
                                @endif
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="input-group input-group-xs">
                                <div class="input-group-addon">BCC</div>
                                <input type="text" class="form-control autoCompleteEmail" name="composingMessage[bcc]" value="{{{ $composingMessageAttributes['bcc'] }}}">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="background-primary-lt" style="color: black; padding: 3px 4px;">
                    <div class="input-group input-group-xs">
                        <div class="input-group-addon">Subject</div>
                        <input type="text" class="form-control" name="composingMessage[subject]" value="{{{ $composingMessageAttributes['subject'] }}}">
                    </div>
                </div>
                
                {{-- Outgoing Message Text Box --}}
                
                <textarea class="form-control originalOutgoingMessage" rows="12" name="composingMessage[bodyText]" wrap="soft" style="font-size: 13px">{{{ $composingMessageAttributes['bodyText'] }}}</textarea>
                
                {{-- Outgoing Message Checkboxes --}}
                
                <div class="outgoingMessageCheckboxes text-muted">
                    <label><input type="checkbox" name="delaySending" CHECKED> 20 min. Delay</label>
                    {{-- <label><input type="checkbox" name="businessHours" @if (auth()->user()->hasPermission('admin') == 1) CHECKED @endif> Business Hours</label> --}}
                	<label><input type="checkbox" name="addSignature" CHECKED> Add Signature</label>
                	@if ($mail)
                		<label><input type="checkbox" name="addQuotedMessage" CHECKED> Quote Original</label>
                		@if ($mail->status == 'new' || $mail->status == 'hold') <label><input type="checkbox" name="archiveOriginal" CHECKED> Archive Message</label> @endif
                		<label><input type="checkbox" name="deleteAttachments" CHECKED> Delete Attachments</label>
                		@if ($mail->reminderDate != null)
                    		<label><input type="checkbox" name="clearReminderDate" CHECKED> Clear Reminder Date</label>
                    	@endif
                	@endif 
	            </div>
                
            </div>
            
            {{-- Outgoing Message Controls --}}
            
            <div class="col-md-6">
            
                {{-- Macros --}}
                    
                <div id="macros">{{-- dynamically loaded --}}</div>
                    
                <br>
                
                <div class="btn-toolbar">
                
                    {{-- Translate Outgoing Message Button --}}
                    
                    <div id="translateOutgoingMessageDropdown">
                        <div class="btn-group">
                            <button type="button" class="btn btn-default" id="translateOutgoingMessageButton">Translate (select)</button>
                            <button type="button" class="btn btn-default dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                <span class="caret"></span>
                                <span class="sr-only">Toggle Dropdown</span>
                            </button>
                            <ul class="dropdown-menu" role="menu">
                                @foreach (\App\Models\Languages::allNamesKeyedByCode() as $langCode => $langName)
                                    @if ($langCode != 'en')
                                        <li><a href="{!! $langCode !!}">{!! $langName !!}</a></li>
                                    @endif
                                @endforeach
                            </ul>
                        </div>
                    </div>
                
                    {{-- Buttons --}}
                    
                    <button name="command" value="send" class="btn {!! $replyAllAddresses ? 'btn-default' : 'btn-primary' !!}"><span class="glyphicon glyphicon-send" aria-hidden="true"></span>&nbsp; Send</button>
                    @if ($replyAllAddresses)
                        <button name="command" value="send" class="btn btn-primary" id="replyAll" {{-- see javascript --}}><span class="glyphicon glyphicon-send" aria-hidden="true"></span>&nbsp; Send Reply to All</button>
                    @endif
                
                </div>
                
            </div>
            
        </div>
    
        </form>
        
        @if ($mail) 
        
            <br>
            
            <div class="panel-group" id="mailPanels">
            
                {{-- Headers --}}
    
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <div class="panel-title"><a data-toggle="collapse" data-parent="#mailPanels" href="#headersPanel">Headers</a></div>
                    </div>
                    <div id="headersPanel" class="panel-collapse collapse">
                        <div class="panel-body">{!! nl2br(trim(htmlspecialchars($mail->headers))) !!}</div>
                    </div>
                </div>
                
                {{-- Email History --}}
    
                <div class="panel panel-info">
                    <div class="panel-heading">
                        <div class="panel-title"><a data-toggle="collapse" data-parent="#mailPanels" href="#emailHistoryPanel">Email History</a></div>
                    </div>
                    <div id="emailHistoryPanel" class="panel-collapse collapse">
                        <div class="panel-body"></div>
                    </div>
                </div>
                
            </div>
            
        @endif
        
        
    </div>

@stop

@section('pageBottom')

    <script>
    
        {{-- Email History --}}
        
        $('#emailHistoryPanel').on('show.bs.collapse', function () {
            $('#emailHistoryPanel div.panel-body').html("[Loading]").load("?command=emailHistory");
        });
        
        <?php Lib\HttpAsset::requireAsset('select2-bootstrap'); ?>
        
        {{-- ** Dynamic Data (changed depending on the listing selected) ** --}}
        
        function updateDynamicData(userID, listingID, bookingID, incomingLinkID) {
            if (typeof userID == 'undefined') userID = '';
            if (typeof listingID == 'undefined') listingID = '';
            if (typeof bookingID == 'undefined') bookingID = '';
            if (typeof incomingLinkID == 'undefined') incomingLinkID = '';
            
            $('#macros').html('[Loading]');
            $.get('?command=getDynamicData&userID='+userID+'&listingID='+listingID+'&bookingID='+bookingID+'&incomingLinkID='+incomingLinkID, function(data) {
                $('#macros').html(data.macros);
                
                @if ($mail)
                    $('#dynamicHTML').html(data.dynamicHTML);
                @endif
                
                if (typeof data.listing === "undefined")
                    $('#listingCustomerSupportEmail').hide();
                else
                    $('#listingCustomerSupportEmail').show().attr('href', data.listing.customerSupportEmail).html(data.listing.customerSupportEmail + " (listing's)");
                    
                $("div#macros a[data-macro-text!='']").click(function(event) {
                    event.preventDefault();
                    $("textarea[name='composingMessage\\[bodyText\\]']").val($("textarea[name='composingMessage\\[bodyText\\]']").val() + $(this).data('macroText'));
                    if ($(this).text() == 'forward' || $(this).text() == 'quote')
                        $('input[name="addQuotedMessage"]').prop('checked', false);
                });
                $('#redirectEmails a').click(function(event) {
                    event.preventDefault();
                    var address = $(this).attr('href');
                    @if ($mail)
                        $("input[name='redirAddress']").val(address);
                    @else
                        if ($("input[name='composingMessage\\[recipient\\]']").val() == '' || $("input[name='composingMessage\\[recipient\\]']").val() == address)
                            $("input[name='composingMessage\\[recipient\\]']").val(address);
                        else if ($("input[name='composingMessage\\[cc\\]']").val() == '' || $("input[name='composingMessage\\[cc\\]']").val() == address)
                            $("input[name='composingMessage\\[cc\\]']").val(address);
                        else
                            $("input[name='composingMessage\\[cc\\]']").val($("input[name='composingMessage\\[cc\\]']").val() + ', ' + address);
                    @endif
                });
                if (data.moreRecentFromSame) 
                    $('#moreRecentFromSame').show();
                else if (data.otherNewFromSame)
                    $('#otherNewFromSame').show();               
            });
        }
        
        updateDynamicData(0, {!! $mail ? $mail->listingID : 0 !!}, 0, 0); {{-- initial display --}}
        
        $("#userSelector, #listingSelector, #bookingSelector, #incomingLinkSelector").on("change", function(e) {
            /* doesn't work well $('#listingSelector').select2("data", { id: 0, text: "" }); */ // clear the listing selection
            updateDynamicData($('#userSelector').val(), $('#listingSelector').val(), $('#bookingSelector').val(), $('#incomingLinkSelector').val());
        });
        
        
        {{-- ** Reply All ** --}}

        @if ($replyAllAddresses) 
        
            $("button#autoFillCC").click(function(event) {
                event.preventDefault();
                $("input[name='composingMessage\\[cc\\]']").val("{!! addslashes(implode(', ', $replyAllAddresses)) !!}");
            });
                    
            $("button#replyAll").click(function(event) {
                $("input[name='composingMessage\\[cc\\]']").val("{!! addslashes(implode(', ', $replyAllAddresses)) !!}");
            });
            
        @endif
        
        
        {{-- ** Translation ** --}}
        
        var selectedTranslationLanguage = '';
        var untranslatedMessage = '', translatedMessage = '', currentTranslationLanguage = '';
        var untranslatedOutgoingMessage = '', translatedOutgoingMessage = '', currentOutgoingMessageTranslationLanguage = '';
        
        function selectTranslationLanguage(languageCode, languageName) {
            selectedTranslationLanguage = languageCode;
            $('#translateButton').text("Translate ("+languageName+")");
            $('#translateOutgoingMessageButton').text("Translate ("+languageName+")");
            $('#translateOutgoingMessageDropdown').show();
        }
    
        $('#translationDropdown a, #translateOutgoingMessageDropdown a').click(function(e) {
            selectTranslationLanguage($(this).attr('href'), $(this).text());
            e.preventDefault();
        });
        
        $('#translateButton').click(function(e) {
            if ($("textarea[name='originalMessage']").hasClass('originalMessage') || selectedTranslationLanguage != currentTranslationLanguage) {
                if (currentTranslationLanguage == '' || selectedTranslationLanguage != currentTranslationLanguage) {
                    untranslatedMessage = $("textarea[name='originalMessage']").val();
                    $.post("{!! routeURL('staff-translateText') !!}", 
                        { text: untranslatedMessage, languageFrom: selectedTranslationLanguage, languageTo: 'en', _token: '{!! csrf_token() !!}' {{-- for CSRF --}} }, 
                        function(data) {
                            translatedMessage = data['translation'];
                            $("textarea[name='originalMessage']").val(translatedMessage).removeClass("originalMessage").addClass("translatedMessage");
                            if (selectedTranslationLanguage == '') selectTranslationLanguage(data['detectedLanguageCode'], data['detectedLanguageName']);
                            currentTranslationLanguage = selectedTranslationLanguage;
                        }
                    );
                } else {
                    {{-- Use our existing translatedMessage --}}
                    $("textarea[name='originalMessage']").val(translatedMessage).removeClass("originalMessage").addClass("translatedMessage");
                }
            } else {
                $("textarea[name='originalMessage']").val(untranslatedMessage).removeClass("translatedMessage").addClass("originalMessage");
           }
        });
        
        $('#translateOutgoingMessageButton').click(function(e) {
            if (selectedTranslationLanguage != '' && ($("textarea[name='composingMessage\\[bodyText\\]']").hasClass('originalOutgoingMessage') 
                    || selectedTranslationLanguage != currentOutgoingMessageTranslationLanguage)) {
                if (currentOutgoingMessageTranslationLanguage == '' || selectedTranslationLanguage != currentOutgoingMessageTranslationLanguage || 
                        untranslatedOutgoingMessage != $("textarea[name='composingMessage\\[bodyText\\]']").val()) {
                    untranslatedOutgoingMessage = $("textarea[name='composingMessage\\[bodyText\\]']").val();
                    $.post("{!! routeURL('staff-translateText') !!}", 
                        { text: untranslatedOutgoingMessage, languageFrom: 'en', languageTo: selectedTranslationLanguage, _token: '{!! csrf_token() !!}' {{-- for CSRF --}} }, 
                        function(data) {
                            translatedOutgoingMessage = data['translation'] + "\n\n\n[Original English Translation]\n\n" + untranslatedOutgoingMessage;
                            $("textarea[name='composingMessage\\[bodyText\\]']").val(translatedOutgoingMessage).removeClass("originalOutgoingMessage").addClass("translatedOutgoingMessage");
                            currentOutgoingMessageTranslationLanguage = selectedTranslationLanguage;
                        }
                    );
                } else {
                    {{-- Use our existing translatedOutgoingMessage --}}
                    $("textarea[name='composingMessage\\[bodyText\\]']").val(translatedOutgoingMessage).removeClass("originalOutgoingMessage").addClass("translatedOutgoingMessage");
                }
            } else {
                $("textarea[name='composingMessage\\[bodyText\\]']").val(untranslatedOutgoingMessage).removeClass("translatedOutgoingMessage").addClass("originalOutgoingMessage");
           }
        });
        
        /* Reminder Date */
        
        <?php Lib\HttpAsset::requireAsset('jquery-ui'); ?>
    
        $('#reminderDate').datepicker({
            'showAnim': '', 
            'dateFormat': 'yy-mm-dd',
            'minDate' : 0, 
            'defaultDate' : 7
        });    
        
        $('#reminderDateClearLink').click(function (e) {
            e.preventDefault();
            $('#reminderDate').val('');
        });
        
        /* Auto complete */
        
        $(document).ready(function() 
        {
            {{-- Autocomplete Country --}}
            $('.autoCompleteEmail').devbridgeAutocomplete({
                serviceUrl: '{!! routeURL('staff.autocompleteEmail') !!}',
                paramName: 'search',
                minChars: 2,
                triggerSelectOnValidInput: false, // keeps it from auto-correcting capitalization
                deferRequestBy: 200 {{-- wait briefly to see if they hit another character before querying --}}
            });
            
            {{-- 
                (temp?) fix of issue where autocomplete causes the browser not to remember values the user entered when they go back to the form 
                See https://github.com/devbridge/jQuery-Autocomplete/issues/393.
            --}}
            $(window).bind('beforeunload', function() {
                $('.autoCompleteEmail').removeAttr( "autocomplete" );
            }); 
        });

    </script>

    {{-- Initialize select2 Selectors --}}
    
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'userID', 'placeholderText' => "Search by user ID, name, or username.", 'selectSelector' => '#userSelector', 'minCharacters' => 0 ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'incomingLinkID', 'placeholderText' => "Search by URL.", 'selectSelector' => '#incomingLinkSelector' ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'bookingID', 'placeholderText' => "Search by booking ID or last name.", 'selectSelector' => '#bookingSelector', 'minCharacters' => 0 ])
    @include('partials/_genericSelectorInitialize', [ 'fieldName' => 'listingID', 'placeholderText' => "Search by listing ID or name.", 'selectSelector' => '#listingSelector', 'minCharacters' => 0 ])

@endsection
