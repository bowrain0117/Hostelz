<?php

namespace App\Http\Controllers;

use App;
use App\Helpers\EventLog;
use App\Models\Booking;
use App\Models\IncomingLink;
use App\Models\Listing\Listing;
use App\Models\Listing\ListingDuplicate;
use App\Models\Macro;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Models\Rating;
use App\Models\User;
use App\Services\AjaxDataQueryHandler;
use Exception;
use Lib\FormHandler;
use Request;
use Response;
use URL;

class StaffMailController extends Controller
{
    public function searchAndDisplay($pathParameters = null)
    {
        // Handle Ajax Queries for <select> options (for ones that don't require us to get $mail first, those are handled later)

        $response = AjaxDataQueryHandler::handleUserSearch(
            Request::input('userID_selectorIdFind'),
            Request::input('userID_selectorSearch'),
            User::havePermission('staff')
        );
        if ($response !== null) {
            return $response;
        }

        $response = AjaxDataQueryHandler::handleIncomingLinkSearch(Request::input('incomingLinkID_selectorIdFind'), Request::input('incomingLinkID_selectorSearch'));
        if ($response !== null) {
            return $response;
        }

        // * Searching / Loading *

        $formHandler = new FormHandler(
            'MailMessage',
            MailMessage::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = (auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'updateForm', 'update', 'insertForm', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'updateForm', 'update', 'insertForm']); // (other actions like delete are handled by our own code)
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = Request::input('where.status') == 'new' ?
            ['transmitTime', 'sender', 'subject', 'comment'] :
            ['status', Request::input('where.reminderDate.min') ? 'reminderDate' : 'transmitTime', 'sender', 'recipient', 'subject', 'comment'];
        $formHandler->listSort['transmitTime'] = 'desc';
        $formHandler->go(null, $formHandler->allowedModes, 'searchAndList', Request::input('command') == 'sendNow' ? 'update' : null);

        if ($formHandler->mode == 'searchAndList') {
            switch (Request::input('command')) {
                case 'spamicityEvaluate':
                    $message = 'Spamicity re-evaluated.';
                    foreach ($formHandler->list as $listItem) {
                        $mailMessage = MailMessage::find($listItem->id); // have to fetch the full object
                        echo "<h1>$listItem->id</h1>" . $mailMessage->spamicityEvaluate();
                        $mailMessage->save();
                    }
                    exit();
            }
        }

        $mail = ($formHandler->mode == 'updateForm' ? $formHandler->model : null); // (is null if composing a new email)

        // Handle Ajax Queries for <select> options (for ones that require us to have the $mail object)

        if (Request::has('listingID_selectorIdFind') || Request::input('listingID_selectorSearch') !== null) {
            if ($mail && Request::input('listingID_selectorSearch') === '') {
                // Show listingIDs associated with this email.
                $associatedListingIDs = $mail->determineListingIDs();
                if (! $associatedListingIDs) {
                    $associatedListingIDs = [0];
                } // so it just gets no results
                $query = Listing::areNotListingCorrection()->whereIn('id', $associatedListingIDs);
            } else {
                $query = null;
            }
            $response = AjaxDataQueryHandler::handleListingSearch(
                Request::input('listingID_selectorIdFind'),
                Request::input('listingID_selectorSearch'),
                $query
            );
            if ($response !== null) {
                return $response;
            }
        }

        if ($mail) {
            if (Request::has('bookingID_selectorIdFind') || Request::input('bookingID_selectorSearch') !== null) {
                if (Request::input('bookingID_selectorSearch') === '') {
                    // Show listingIDs associated with this email.
                    $bookingIDs = $mail->determineBookingIDs();
                    if (! $bookingIDs) {
                        $bookingIDs = [0];
                    } // so it just gets no results
                    $query = Booking::whereIn('id', $bookingIDs);
                } else {
                    $query = null;
                }
                $response = AjaxDataQueryHandler::handleBookingSearch(
                    Request::input('bookingID_selectorIdFind'),
                    Request::input('bookingID_selectorSearch'),
                    $query
                );
                if ($response !== null) {
                    return $response;
                }
            }
        }

        if (! $formHandler->model) {
            // If search or list mode, or editing an outgoingQueue message, just return the standard form handler page.
            return $formHandler->display('staff/mail');
        }

        // Set up the composing (outgoing) message attributes

        $composingMessageAttributes = [
            'bodyText' => $mail && $mail->getSendersFirstName() ? 'Hi ' . $mail->getSendersFirstName() . '.  ' : 'Hi.  ',
            'recipient' => $mail ? ($mail->status == 'outgoing' ? $mail->recipient : $mail->sender) : '',
            'subject' => $mail ? $mail->getReplySubject() : '',
            'listingID' => $mail ? $mail->listingID : 0,
            'cc' => '', 'bcc' => '',
        ];

        $inputFields = ['recipient', 'cc', 'bcc', 'subject', 'bodyText', 'listingID'];
        foreach ($inputFields as $inputField) {
            if (Request::has("composingMessage.$inputField")) {
                $composingMessageAttributes[$inputField] = Request::input("composingMessage.$inputField");
            }
        }

        // Handle setting/submitting values of $mail

        if ($mail) {
            // Don't allow non-admin users to read admin sent/received emails.
            // Note:  We don't currently prevent admin emails from appearing in the email history, or from being *listed* in search results.
            if (! $mail->isViewableByUser(auth()->user())) {
                return accessDenied();
            }

            // Save special input values (we don't actually call $mail->save() here, but some commands may do that below when appropriate)
            // (note that we can't use Request::has() because that returns false if the value is ''.)
            if (Request::input('reminderDate') !== null) {
                $mail->reminderDate = (Request::input('reminderDate') == '' ? null : Request::input('reminderDate'));
            }
            if (Request::input('comment') !== null) {
                $mail->comment = Request::input('comment');
            }
        }

        // Handle commands

        $displayMessagePage = $displayErrorPage = $message = '';

        switch (Request::input('command')) {
            case 'send':
                $composingMessageAttributes['bodyText'] = trim($composingMessageAttributes['bodyText']) . "\n";

                if (trim($composingMessageAttributes['bodyText']) == '') {
                    $displayErrorPage = 'Message text is empty.';

                    break;
                }
                if ($composingMessageAttributes['recipient'] == '') {
                    $displayErrorPage = 'Message "To" field is empty.';

                    break;
                }
                if ($composingMessageAttributes['subject'] == '') {
                    $displayErrorPage = 'Message "Subject" field is empty.';

                    break;
                }

                if (Request::input('addSignature')) {
                    $composingMessageAttributes['bodyText'] .= auth()->user()->getEmailSignature();
                }

                if ($mail && Request::input('addQuotedMessage')) {
                    $quotedBodyText = $mail->quotedBodyText(false, 300);
                    if (strpos($mail->bodyText, 'http://' . config('custom.publicStaticDomain')) === 0 || strpos($mail->bodyText, 'https://' . config('custom.publicStaticDomain')) === 0) {
                        $quotedBodyText = strstr($quotedBodyText, "\n");
                    } // remove our url from the first line before quoting
                    $composingMessageAttributes['bodyText'] .= "\n\n" . $quotedBodyText . "\n";
                }

                $sentMessage = MailMessage::createOutgoing($composingMessageAttributes, auth()->user(), Request::input('delaySending') ? 20 : 0, Request::input('businessHours'));
                if (! $sentMessage) {
                    throw new Exception('Send error.');
                }

                if ($mail) {
                    if (Request::input('clearReminderDate')) {
                        $mail->reminderDate = null;
                    }
                    if (Request::input('archiveOriginal') && ($mail->status == 'new' || $mail->status == 'hold')) {
                        $mail->spamicityTrain(false);
                        $mail->status = 'archived';
                    }
                    $mail->save();
                    if (Request::input('deleteAttachments')) {
                        $mail->deleteAttachments();
                    } // this has to be *after* spamicityTrain()
                }

                IncomingLink::updateLastContactDatesByEmailAddress($sentMessage->getNonLocalEmailAddresses());

                $displayMessagePage = '<i class="fa fa-paper-plane"></i> Message queued for delivery.';
                $displayMessagePage .= " <a href='" . route('staff-mailMessages', ['pathParameters' => $sentMessage->id, '#attachments']) . "'>Edit Email Attachments</a>";

                break;

            case 'redirect':
                $redirectAddress = Request::input('redirAddress');
                if ($redirectAddress == '') {
                    $displayErrorPage = 'Missing redirect address.';

                    break;
                }

                if (stripos($redirectAddress, '@hostelz.com') === false) {
                    $originalMessage = "[redirected from Hostelz.com]\n\n" . trim(Request::input('originalMessage'));
                } else {
                    $originalMessage = Request::input('originalMessage');
                } // we use the textarea data so the staff can edit the message text or add a note

                $attributes = ['sender' => $mail->sender, 'senderAddress' => $mail->senderAddress, 'userID' => auth()->id(),
                    'recipient' => $redirectAddress, 'subject' => $mail->subject, 'bodyText' => $originalMessage, 'listingID' => $mail->listingID,
                ];
                $sentMessage = MailMessage::createOutgoing($attributes, null, Request::input('delaySending') ? 20 : 0);
                if (! $sentMessage) {
                    throw new Exception('Send error.');
                }

                if (Request::input('clearReminderDate')) {
                    $mail->reminderDate = null;
                }
                if (Request::input('archiveOriginal') && ($mail->status == 'new' || $mail->status == 'hold')) {
                    $mail->status = 'archived';
                    $mail->spamicityTrain(false);
                }
                $mail->save();
                if (Request::input('deleteAttachments')) {
                    $mail->deleteAttachments();
                } // this has to be *after* spamicityTrain()

                EventLog::log('staff', 'redirect', 'MailMessage', $sentMessage->id, $mail->subject, implode(', ', $sentMessage->recipientAddresses));
                $displayMessagePage = '<i class="fa fa-paper-plane"></i> Redirected.';

                break;

            case 'defer':
                $mail->userID = User::$ADMIN_USER_ID;
                $mail->spamicityTrain(false); // presumably isn't spam, and also to make sure it shows up in my inbox.
                $mail->reminderDate = null;
                $mail->status = 'new';
                $mail->bodyText = Request::input('originalMessage'); // save any changes they made to the message text, such as a note at the top
                $mail->save();
                $displayMessagePage = 'Deferred.';
                EventLog::log('staff', 'defer', 'MailMessage', $mail->id, $mail->subject, $mail->sender);

                break;

            case 'archive':
                if ($mail->status != 'new' && $mail->status != 'hold') {
                    $displayErrorPage = "Can't archive this message because it isn't a new message.";

                    break;
                }
                $mail->status = 'archived';
                // We only train as non-spam on archived emails if the user is admin.  This way, non-admin users
                // can safely "ignore" emails that are borderline-spam without mistraining the spam filter.
                if (auth()->user()->hasPermission('admin')) {
                    $mail->spamicityTrain(false);
                }
                $mail->save();
                if (Request::input('deleteAttachments')) {
                    $mail->deleteAttachments();
                } // this has to be *after* spamicityTrain()
                $displayMessagePage = 'Archived.';

                break;

            case 'unarchive':
                if ($mail->status != 'archived') {
                    $displayErrorPage = "Can't unarchive this message because it isn't archived.";

                    break;
                }
                $mail->status = 'new';
                $mail->save();
                $displayMessagePage = 'Unarchived.';

                break;

            case 'hold':
                $mail->spamicityTrain(false);
                $mail->status = 'hold';
                $mail->save();
                $displayMessagePage = 'Holding.';

                break;

            case 'update':
                // This just saves changes to things like the comments field or the reminderDate
                $mail->save();
                $displayMessagePage = 'Updated.';

                break;

            case 'sendNow':
                // (The Send Now button is on the page when viewing emails in the Outgoing Queue.)
                if ($formHandler->model->status != 'outgoingQueue') {
                    $displayErrorPage = "Message isn't in the outgoing queue.";

                    break;
                }
                $formHandler->model->sendNow();
                $displayMessagePage = 'Sent.';

                break;

            case 'delete':
                if (! auth()->user()->hasPermission('admin') && ! ($mail->status == 'outgoingQueue' && $mail->userID == auth()->id())) {
                    return accessDenied();
                }
                EventLog::log('staff', 'delete', 'MailMessage', $mail->id, $mail->subject, $mail->sender);
                $mail->delete(); // (also deletes attachments automatically)
                $displayMessagePage = 'Deleted.';

                break;

            case 'spamicityEvaluate':
                $message = 'Spamicity re-evaluate: ' . $mail->spamicityEvaluate();
                $mail->save();

                break;

            case 'markAsSpam':
                $displayMessagePage = 'Marked as spam.';
                $mail->spamicityTrain(true);
                $mail->reminderDate = null;
                $mail->save();
                $mail->deleteAttachments();
                EventLog::log('staff', 'mark spam', 'MailMessage', $mail->id, $mail->subject);

                break;

            case 'getDynamicData':
                if ($mail && $mail->listingID != Request::input('listingID')) {
                    $mail->listingID = Request::input('listingID');
                    $mail->save();
                }
                if (auth()->user()->hasPermission('admin') && $mail && Request::input('userID') && $mail->userID != Request::input('userID')) {
                    $mail->userID = Request::input('userID');
                    $mail->save();
                }

                return Response::json($this->getDynamicData($mail, Request::input('listingID'), Request::input('bookingID'), Request::input('incomingLinkID')));

            case 'emailHistory':
                $mailMessages = null;

                $nonLocalEmailAddresses = $mail->getNonLocalEmailAddresses();
                if ($nonLocalEmailAddresses) {
                    $mailMessages = MailMessage::forRecipientOrBySenderEmail($nonLocalEmailAddresses)->where('id', '!=', $mail->id)->
                        orderBy('transmitTime', 'desc')->limit(40)->get();
                } else {
                    // Special case - For emails sent by our own staff to other staff.
                    $otherLocalEmailAddresses = array_diff($mail->getLocalEmailAddresses(), auth()->user()->allLocalEmailAddresses());
                    if ($otherLocalEmailAddresses) {
                        // (Only shows emails from that staff to the same recipient userID)
                        $mailMessages = MailMessage::forRecipientOrBySenderEmail($otherLocalEmailAddresses)->where('id', '!=', $mail->id)
                            ->where('userID', $mail->userID)->orderBy('transmitTime', 'desc')->limit(40)->get();
                    }
                }

                return view('partials/_emailHistory', compact('mailMessages'))->with('originalMail', $mail);
        }

        // ('updateForm' is the mode when we display the email, 'insertForm' is the mode when composing a new email)
        if ($displayMessagePage != '' || $displayErrorPage != '' || $formHandler->mode == 'update' || ($mail && $mail->status == 'outgoingQueue')) {
            $editAttachments = $displayMessagePage === '' && $mail
                ? $this->editAttachments($mail->id, $view = 'staff/mail/mail-showAttachments', true)
                : '';

            return $formHandler->display('staff/mail', compact('displayMessagePage', 'displayErrorPage', 'editAttachments'));
        } else {
            return view('staff/mail-display', compact('formHandler', 'mail', 'message', 'composingMessageAttributes'))->
            with('replyAllAddresses', $mail ? $mail->replyAllAddresses() : null);
        }
    }

    private function getDynamicData($mail, $listingID, $bookingID, $incomingLinkID)
    {
        $incomingLinks = $ratings = $booking = null;

        $nonLocalEmailAddresses = $mail ? $mail->getNonLocalEmailAddresses() : null;
        $nonLocalDomains = $mail ? $mail->getNonLocalDomains() : null;

        // * Booking Info *

        if ($bookingID) {
            $booking = Booking::find($bookingID);
            if (! $listingID) {
                $listingID = $booking->listingID;
            }
        }

        // * User Info *

        $user = $mail ? $mail->findAssociatedUser() : null;

        // * Listing Info *

        $listing = $listingDuplicates = null;
        if ($listingID && auth()->user()->hasPermission('staffEditHostels')) {
            $listing = Listing::find($listingID);
            if ($listing) {
                $listingDuplicates = ListingDuplicate::forListingID($listingID)->where('status', '!=', 'nonduplicates')->orderBy('score', 'desc')->limit(5)->get();

                $return['listing'] = [
                    'customerSupportEmail' => $listing->getBestEmail('customerSupport'),
                ];
            }
        }

        // * Incoming Links *

        $incomingLinks = null;
        if ($incomingLinkID) {
            $incomingLinks = IncomingLink::where('id', $incomingLinkID)->get();
        } elseif ($mail) {
            if ($nonLocalDomains && auth()->user()->hasPermission('staffMarketing')) {
                $incomingLinks = IncomingLink::where('contactStatus', '!=', 'ignored')
                    ->where(function ($query) use ($nonLocalEmailAddresses, $nonLocalDomains): void {
                        $query->byEmail($nonLocalEmailAddresses)
                            ->orWhereIn('domain', $nonLocalDomains)->orWhereIn('contactFormURL', $nonLocalEmailAddresses);
                    })->get();
                if ($incomingLinks->isEmpty()) {
                    $incomingLinks = null;
                }
            }
        }

        // * Macros *

        // Note: Strings added here should also be documented in views/staff/edit-macros.blade.php.

        $macroReplacementStrings = [];

        if ($mail) {
            $macroReplacementStrings = array_merge($macroReplacementStrings, [
                '[sender]' => $mail->sender,
                '[recipients]' => $mail->recipient,
                '[subject]' => $mail->subject,
                '[messageText]' => $mail->bodyText,
                '[quotedMessage]' => $mail->quotedBodyText(true, 300),
            ]);
        }

        if ($listing) {
            $activeBookingSystemNames = $listing->activeImporteds->map(function ($imported) {
                return $imported->getImportSystem()->shortName();
            })->toBase()->unique()->toArray();

            $macroReplacementStrings = array_merge($macroReplacementStrings, [
                '[listingBookingSystemNames]' => itemList($activeBookingSystemNames),
                '[listingBookingSystemNamesSystems]' => itemList($activeBookingSystemNames, 'booking system'),
                '[listingEditURL]' => User::mgmtSignupURL($listing->id, 'en'), // TO DO: Ideally this could also pass the language parameter if a translation language for the email is known.
            ]);
        }

        if ($booking) {
            $macroReplacementStrings = array_merge($macroReplacementStrings, [
                '[bookingSystem]' => $booking->getImportSystem()->shortName(),
            ]);
        }

        if ($user) {
            $macroReplacementStrings = array_merge($macroReplacementStrings, [
                // to do, if any.
            ]);
        }

        // Variables added here should also be documented in views/staff/edit-macros.blade.php.
        $variableValues = [
            'isNewMessage' => $mail ? 'false' : 'true',
            'isAlreadyMgmt' => $user && $listing && in_array($listing->id, $user->mgmtListings) ? 'true' : 'false',
            'isInActiveBookingSystems' => $listing && ! $listing->activeImporteds->isEmpty() ? 'true' : 'false',
        ];

        $return['macros'] = (string) view('staff/mail-display-macros')->with('macros', Macro::getMacrosTextArray('mail', auth()->user(), $variableValues, $macroReplacementStrings));

        if ($mail) {
            // * To/From Same Address Warnings *

            // (The only reason we do this in getDynamicData() is to keep the initial message loading fast.)

            if ($nonLocalEmailAddresses) {
                $mostRecentFromSame = MailMessage::forRecipientOrBySenderEmail($nonLocalEmailAddresses)->where('id', '!=', $mail->id)->
                    orderBy('transmitTime', 'desc')->first();
                $return['moreRecentFromSame'] = ($mostRecentFromSame && $mostRecentFromSame->transmitTime > $mail->transmitTime);

                $return['otherNewFromSame'] = MailMessage::forRecipientOrBySenderEmail($nonLocalEmailAddresses)->where('id', '!=', $mail->id)->
                    where('userID', $mail->userID)->where('status', 'new')->count();

                // * Ratings *

                $ratings = Rating::whereIn('email', $nonLocalEmailAddresses)->orderBy('commentDate', 'asc')->limit(5)->get();
                if ($ratings->isEmpty()) {
                    $ratings = null;
                }
            }
        }

        // * dynamicHTML *

        $return['dynamicHTML'] = (string) view('staff/mail-display-dynamicHTML', compact('mail', 'listing', 'listingDuplicates', 'user', 'ratings', 'incomingLinks', 'booking'));

        return $return;
    }

    public function viewAttachment($attachmentID, $filename = '')
    {
        $attachment = MailAttachment::find($attachmentID);
        if (! $attachment || $attachment->filename != $filename) {
            App::abort(404);
        }

        if (! $attachment->mailMessage) {
            return 'Associated mail message is missing.';
        }

        if (! $attachment->mailMessage->isViewableByUser(auth()->user())) {
            return accessDenied();
        }

        switch ($attachment->mimeType) {
            case 'MESSAGE/DELIVERY-STATUS':
            case 'MESSAGE/RFC822':
                $mimeType = 'text/plain';

                break;

            default:
                $mimeType = $attachment->mimeType;
        }

        return $response = Response::make($attachment->getContents(), 200, [
            'Content-type' => $mimeType,
            /* (this forces the user to save the file, which we probably don't want to do... 'Content-Disposition' => 'attachment; filename="'.$filename.'"' */
        ]);
    }

    public function editAttachments($mailID, $view = 'staff/mail', $showThumbs = false)
    {
        $mail = MailMessage::findOrfail($mailID);
        if (! $mail->isViewableByUser(auth()->user())) {
            return accessDenied();
        }

        $mail->load('attachments'); // eager loading
        $existing = $mail->attachments;
        $fileList = new \Lib\FileListHandler($existing, ['filename', 'size'], ['filename'], true, $showThumbs);
        $fileList->viewLinkClosure = function ($row) {
            return routeURL('staff-mailAttachment', [$row->id, $row->filename]);
        };
        $response = $fileList->go();
        if ($response !== null) {
            return $response;
        }

        // FileUpload
        $fileUpload = new \Lib\FileUploadHandler();
        $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($mail): void {
            $mail->addAttachmentByFilename($originalName, $filePath);
        });
        if ($response !== null) {
            return $response;
        }

        return view($view, compact('fileList', 'fileUpload', 'mail'));
    }

    public function autocompleteEmail()
    {
        $search = Request::input('search');

        // Note: order by transmitTime only kind of works because group by causes it to base the transmitTime on any random email from that sender.
        $matches = MailMessage::where('status', 'outgoing')->where('recipient', 'LIKE', '%' . $search . '%')
            ->orderBy('transmitTime', 'DESC')->groupBy('recipient')->limit(10)->pluck('recipient');

        return Response::json(['suggestions' => $matches]);
    }

    public function addListingContact()
    {
        $listing = Listing::find(Request::input('listingID'));
        if (! $listing) {
            return '';
        }

        if (! in_array(Request::input('email'), $listing->managerEmail)) {
            $temp = $listing->managerEmail;
            $temp[] = Request::input('email');
            $listing->managerEmail = $temp;
            $listing->save();
        }

        return 'ok';
    }

    public function addIncomingLinkContact()
    {
        $incomingLink = IncomingLink::find(Request::input('incomingLinkID'));
        if (! $incomingLink) {
            return '';
        }

        if (! in_array(Request::input('email'), $incomingLink->contactEmails)) {
            $temp = $incomingLink->contactEmails;
            $temp[] = Request::input('email');
            $incomingLink->contactEmails = $temp;
            $incomingLink->responseReceived(false);
            $incomingLink->save();
        }

        return 'ok';
    }
}
