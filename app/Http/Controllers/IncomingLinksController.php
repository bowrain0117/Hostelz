<?php

namespace App\Http\Controllers;

use App\Helpers\EventLog;
use App\Models\Ad;
use App\Models\IncomingLink;
use App\Models\MailMessage;
use App\Models\User;
use App\Services\AjaxDataQueryHandler;
use App\Services\Payments;
use Exception;
use Illuminate\Support\Str;
use Lib\FileListHandler;
use Lib\FileUploadHandler;
use Lib\FormHandler;
use Lib\WebsiteTools;
use Request;
use Response;
use Route;

class IncomingLinksController extends Controller
{
    private $generatedMessage;

    public function instructions()
    {
        return view('staff/incomingLink-instructions');
    }

    public function createNew($pathParameters = null)
    {
        $mode = $message = $incomingLink = '';
        $checkForExistingSameDomain = (string) Request::input('checkForExistingSameDomain');
        $url = (string) Request::input('url');

        if ($url != '') {
            $result = IncomingLink::addNewLink(['url' => $url,
                'userID' => auth()->id(), 'contactStatus' => 'todo', 'source' => 'manually entered',
            ], $linkObject, (bool) Request::input('ignoreExistingDomain'));

            switch ($result) {
                case 'created':
                    $incomingLink = $linkObject;
                    $mode = 'done';

                    break;

                case 'exists':
                    $message = "A link to this URL or domain already exists ('<a href=\"" . routeURL('staff-incomingLinks', $linkObject->id) . "\">$linkObject->url</a>').";

                    break;

                default:
                    $message = $result; // an error message
            }
        }

        return view('staff/edit-incomingLinks-new', compact('message', 'mode', 'url', 'checkForExistingSameDomain', 'incomingLink'));
    }

    public function incomingLinksEdit($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(
            Request::input('userID_selectorIdFind'),
            Request::input('userID_selectorSearch'),
            User::havePermission('staffMarketing')
        );
        if ($response !== null) {
            return $response;
        }

        $placeSearchResult = IncomingLink::handlePlaceSearchAjaxCommand();
        if ($placeSearchResult !== null) {
            return $placeSearchResult;
        }

        $message = '';

        $formHandler = new FormHandler(
            'IncomingLink',
            IncomingLink::fieldInfo(auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit'),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'editableList', 'multiUpdate', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['contactStatus', 'contactStatusSpecific', 'url', 'pageTitle', 'checkStatus', 'priorityLevel', 'name', 'notes'];
        $formHandler->listSort['priorityLevel'] = (auth()->user()->hasPermission('staffMarketingLevel2') ? 'desc' : 'asc');

        $formHandler->go();

        if (Request::has('objectCommand') && $formHandler->model) {
            // objectCommands are commands performed on the object after it has been loaded

            switch (Request::input('objectCommand')) {
                case 'updateLinkInformation':
                    $formHandler->model->updateLinkInformation(true)->save();
                    IncomingLink::updateAuthorityStats(collect([$formHandler->model]));
                    IncomingLink::updateTrafficRanks(collect([$formHandler->model]));
                    $message = '<pre>Link information updated.</pre><br>';
                    $formHandler->model = $formHandler->model->fresh(); // reload it in case anything changed.

                    break;
            }
        }

        return $formHandler->display('staff/edit-incomingLinks-edit', compact('message'));
    }

    public function incomingLinks($pathParameters = null)
    {
        $response = AjaxDataQueryHandler::handleUserSearch(
            Request::input('userID_selectorIdFind'),
            Request::input('userID_selectorSearch'),
            User::havePermission('staffMarketing')
        );
        if ($response !== null) {
            return $response;
        }

        $placeSearchResult = IncomingLink::handlePlaceSearchAjaxCommand();
        if ($placeSearchResult !== null) {
            return $placeSearchResult;
        }

        if (Request::has('specialCommand')) {
            switch (Request::input('specialCommand')) {
                case 'assignTodoLinksToMarketingUsers':
                    return IncomingLink::assignTodoLinksToMarketingUsers();
            }
        }

        $formHandler = new FormHandler(
            'IncomingLink',
            IncomingLink::fieldInfo(intval($pathParameters) ? 'contact' : (auth()->user()->hasPermission('admin') ? 'adminEdit' : 'staffEdit')),
            $pathParameters,
            'App\Models'
        );
        $formHandler->allowedModes = auth()->user()->hasPermission('admin') ?
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update', 'insertForm', 'insert', 'delete', 'multiDelete'] :
            ['searchForm', 'list', 'searchAndList', 'display', 'updateForm', 'update'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['contactStatus', 'url', 'notes', 'lastContact'];
        if (Request::input('where.reminderDate.min')) {
            $formHandler->listSelectFields[] = 'reminderDate';
        }
        $formHandler->listSort['lastContact'] = 'desc';
        $formHandler->listSort['priorityLevel'] = (auth()->user()->hasPermission('staffMarketingLevel2') ? 'desc' : 'asc');
        $formHandler->go();

        $message = '';
        $relatedIncomingLinks = null;
        $contactTopicsForCategory = null;

        $incomingLink = $formHandler->model;
        if ($incomingLink) {
            // Hide fields that are empty or not used
            if ($incomingLink->anchorText == '') {
                $formHandler->fieldInfo['anchorText']['updateType'] = 'ignore';
            }
            if ($incomingLink->linksTo == '') {
                $formHandler->fieldInfo['linksTo']['updateType'] = 'ignore';
            }
            if (! $incomingLink->invalidEmails) {
                $formHandler->fieldInfo['invalidEmails']['updateType'] = 'ignore';
            }
            if (! $incomingLink->lastContact) {
                $formHandler->fieldInfo['lastContact']['updateType'] = 'ignore';
            }

            switch ($incomingLink->contactStatus) {
                case 'initialContact':
                    $ignoreFields = ['contactTopics', 'emailSubject', 'contactMessage'];
                    foreach ($ignoreFields as $field) {
                        $formHandler->fieldInfo[$field]['updateType'] = 'ignore';
                    }

                    $displayOnly = ['otherWebsitesLinked'];
                    foreach ($displayOnly as $field) {
                        $formHandler->fieldInfo[$field]['editType'] = 'display';
                    }

                    if ($incomingLink->followUpStatus == 'done') {
                        $formHandler->fieldInfo['followUpStatus'] =
                        ['type' => 'display', 'options' => IncomingLink::$followUpStatusOptions, 'optionsDisplay' => 'translate'];
                    }

                    break;
            }

            // Set contactTopicsForCategory
            $contactTopicsForCategory = IncomingLink::$contactTopicsForCategory;
            if ($incomingLink->otherAffiliateSitesLinked()) {
                foreach ($contactTopicsForCategory as $category => $topics) {
                    if (! in_array('affiliate', $topics)) {
                        $contactTopicsForCategory[$category][] = 'affiliate';
                    }
                }
            }

            // Find related incomingLinks
            if ($incomingLink->contactEmails) {
                $query = IncomingLink::byEmail($incomingLink->contactEmails);
            } else {
                $query = IncomingLink::query();
            }
            $query->orWhere('domain', $incomingLink->domain);
            if ($incomingLink->contactFormURL != '') {
                $query->orWhere('contactFormURL', $incomingLink->contactFormURL);
            }
            $relatedIncomingLinks = $query->get()
                ->except($incomingLink->id); // (we remove the link itself here instead of in the query because the SQL gets complicated with the "OR" statements.)

            // Commands that need the IncomingLink
            switch (Request::input('objectCommand')) {
                case 'searchForContactEmails':
                    $matchingEmails = WebsiteTools::searchForContactEmails($incomingLink->domain, Request::input('name'), 10);
                    if (! $matchingEmails) {
                        return 'No matches found.';
                    }
                    $output = '';
                    foreach ($matchingEmails as $email) {
                        $output .= "<p>$email</p>";
                    }

                    return $output;

                case 'emailHistory':
                    $mailMessages = null;
                    $searchFor = Request::input('data.contactEmails');
                    if (! $searchFor) {
                        $searchFor = [];
                    }
                    if (Request::has('data.contactFormURL')) {
                        $searchFor[] = Request::input('data.contactFormURL');
                    }
                    if ($incomingLink->invalidEmails) {
                        $searchFor = array_merge($searchFor, $incomingLink->invalidEmails);
                    }
                    $searchFor = array_filter($searchFor);
                    if ($searchFor) {
                        $mailMessages = MailMessage::forRecipientOrBySenderEmail($searchFor)->orderBy('transmitTime', 'desc')->limit(40)->get();
                    }
                    if (! $mailMessages || $mailMessages->isEmpty()) {
                        return '';
                    } else {
                        return view('partials/_emailHistory', compact('mailMessages'));
                    }

                    // no break
                case 'generateMessageText':
                    return $this->generateMessageText($incomingLink);

                case 'sendPayment':
                    $amount = Request::input('amount');
                    $description = 'Incoming Link';
                    $email = trim(Request::input('email'));

                    if (! auth()->user()->hasPermission('admin')) {
                        return accessDenied();
                    }
                    if (! $amount) {
                        $message = 'Missing payment amount.';

                        break;
                    }
                    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        $message = 'Invalid payment email.';

                        break;
                    }

                    $result = Payments::pay($email, $amount, $description, 'incomingLink-' . $incomingLink->id, Request::input('paypalPassword'));

                    if ($result) {
                        EventLog::log('staff', 'payment sent', 'IncomingLink', $incomingLink->id, $amount, $email);
                        $message = 'Payment sent.';
                    } else {
                        $message = 'Payment error.';
                    }

                    $message .= ' (current balance $' . Payments::paymentSystemBalance(Request::input('paypalPassword')) . ')';

                    break;
            }
        }

        if ($formHandler->mode == 'update') {
            // Automatically assign userID to be the editing user if there isn't a userID assigned
            if (! $incomingLink->userID && $incomingLink->contactStatus != 'todo') {
                $incomingLink->userID = auth()->id();
                $incomingLink->save();
            }

            $emailSubject = $formHandler->inputData['emailSubject'] ?? null;
            $contactMessage = $formHandler->inputData['contactMessage'] ?? null;

            if ($contactMessage != '') {
                $contactMessage = trim($contactMessage) . "\n";
                if ($incomingLink->contactStatus == 'discussing' && $incomingLink->contactStatusSpecific == '') {
                    $incomingLink->contactStatusSpecific = 'contacting';
                }

                if ($incomingLink->contactEmails) {
                    if ($emailSubject == '') {
                        return 'Email subject missing.';
                    }

                    $contactMessage .= auth()->user()->getEmailSignature();

                    // Send Emails (separate email to each email address)
                    foreach ($incomingLink->contactEmails as $email) {
                        $mail = MailMessage::createOutgoing([
                            'bodyText' => $contactMessage, 'subject' => $emailSubject, 'recipient' => $email,
                        ], null, 2 * 24 * 60 /* long delay so i can review some of the messages first */, true);
                    }
                    $message = 'Email sent.';
                    if ($incomingLink->contactFormURL != '') {
                        $message .= ' (Contact Form URL ignored).';
                    }
                } else {
                    // Contact Form
                    $mail = MailMessage::createOutgoing(['status' => 'outgoing', 'bodyText' => $contactMessage, 'subject' => $emailSubject,
                        'recipient' => $incomingLink->contactFormURL, 'recipientAddresses' => $incomingLink->contactFormURL,
                    ]);

                    $message = "Message saved. Remember to <b>submit the message with the contact form</b> on their website if you didn't already!";
                }

                $incomingLink->updateLastContactDate();
            }
        }

        return $formHandler->display($formHandler->mode == 'updateForm' || $formHandler->mode == 'insertForm' ?
            'staff/edit-incomingLinks' : 'staff/edit-incomingLinks-edit', compact('message', 'relatedIncomingLinks', 'contactTopicsForCategory'));
    }

    public function followUp($pathParameters = null)
    {
        $incomingLinks = IncomingLink::initialFollowUpDue()->where('userID', auth()->id())->get();

        $selectedLinkIDs = Request::input('selectedLinks');
        $linksDone = [];

        if ($selectedLinkIDs) {
            set_time_limit(1 * 60 * 60);

            foreach ($selectedLinkIDs as $selectedLinkID) {
                $link = $incomingLinks->where('id', $selectedLinkID)->first();
                if (! $link) {
                    continue;
                } // probably means they already edited the link and it no longer needs to be contacted

                // Send message(s)

                $query = MailMessage::forRecipientEmail($link->contactEmails)->where('status', 'outgoing');
                if (! auth()->user()->hasPermission('admin')) {
                    $query->where('userID', auth()->id());
                } // probably only makes sense to follow-up the user's own emails
                $sentMails = $query->orderBy('transmitTime', 'desc')->limit(40)->get();
                $recipientAddresses = [];

                foreach ($sentMails as $sentMail) {
                    if (array_intersect($sentMail->recipientAddresses, $recipientAddresses)) {
                        continue;
                    } // could happen if we sent them multiple emails
                    $recipientAddresses = array_merge($recipientAddresses, $sentMail->recipientAddresses);
                    $messageText = "Hi.  I just wanted to follow-up with you to see if you received my email.  Did you have a chance to look at Hostelz.com yet?\n";
                    $messageText .= auth()->user()->getEmailSignature();
                    $quotedBodyText = $sentMail->quotedBodyText(false, 300);
                    $messageText .= "\n\n" . $quotedBodyText . "\n";
                    $sentMessage = MailMessage::createOutgoing([
                        'bodyText' => $messageText,
                        'recipient' => $sentMail->recipient,
                        'subject' => $sentMail->getReplySubject(),
                    ], auth()->user(), 20, true, false);
                    if (! $sentMessage) {
                        throw new Exception('Send error.');
                    }
                }

                if (! $recipientAddresses) {
                    logWarning("No messages found to send follow-up to for link $selectedLinkID. Maybe the original emails were sent by a different user?");
                    // (We still go ahead and change the followupStatus anyway)
                }

                // Update the link

                $link->followUpStatus = 'done';
                $link->save();

                $linksDone[] = $link;
            }
            $mode = 'done';
        } else {
            $mode = 'list';
        }

        return view('staff/edit-incomingLinks-followUp', compact('incomingLinks', 'linksDone', 'mode'));
    }

    private function generateMessageText($incomingLink)
    {
        $lastEmailQuoted = '';
        if ($incomingLink->contactStatus != 'todo' && $incomingLink->contactEmails) {
            $lastEmail = MailMessage::forRecipientOrBySenderEmail($incomingLink->contactEmails)->orderBy('transmitTime', 'desc')->first();
            if ($lastEmail) {
                $lastEmailQuoted = $lastEmail->quotedBodyText(false, 300);
            }
        }

        // Update the chosen associated model in case it has changed (we don't save it, just updates our copy of the model)
        $incomingLink->category = Request::input('category');
        $incomingLink->setTypeAndIdFromEncodedPlaceString(Request::input('placeEncodedString'));
        $incomingLink->contactTopics = (array) Request::input('contactTopics');

        // Other websites linked

        $incomingLink->otherWebsitesLinked = Request::input('otherWebsitesLinked');
        $otherAffiliateSitesLinked = $incomingLink->otherAffiliateSitesLinked();
        $otherBookingSystemsLinked = [];
        if ($incomingLink->otherWebsitesLinked) {
            foreach ($incomingLink->otherWebsitesLinked as $site) {
                if (strpos($site, 'Affiliate') === false) {
                    $otherBookingSystemsLinked[] = $site;
                }
            }
        }

        // Name / first name
        $incomingLink->name = Request::input('contactName');
        $firstName = '';
        if ($incomingLink->name != '') {
            $parts = explode(' ', $incomingLink->name);
            $firstName = $parts[0];
        }

        if ($incomingLink->contactTopics == ['crossPromotion']) {
            $subject = 'cross promotion ideas';
        } else {
            $subject = 'hostel information';
        }

        $this->generatedMessage = '';

        $this->addParagraph('Hi' . ($firstName != '' ? " $firstName" : '') . '.');

        // Page Info

        switch ($incomingLink->category) {
            case 'blog':
                if ($otherBookingSystemsLinked) {
                    $this->addSentence('I noticed that in your ' . ($incomingLink->pageTitle != '' ? "$incomingLink->pageTitle " : '') . ' post you recommend ' . itemList($otherBookingSystemsLinked) . '.');
                } else {
                    $this->addSentence('I noticed your ' . ($incomingLink->pageTitle != '' ? "$incomingLink->pageTitle " : '') . 'blog post.');
                }

                break;

            case 'press':
                $this->addSentence("I just read your article $incomingLink->pageTitle.");

                break;

            case 'tour':
                if ($incomingLink->placeType != '') {
                    $this->addSentence('I noticed you do tours of ' . $incomingLink->placeDisplayName() . '.');
                } else {
                    $this->addSentence("I noticed your tour company's website.");
                }

                break;

            case 'accommodation':
                if ($otherBookingSystemsLinked) {
                    $this->addSentence('I noticed that on your ' . ($incomingLink->pageTitle != '' ? "$incomingLink->pageTitle " : '') . "page at $incomingLink->url you link to " . itemList($otherBookingSystemsLinked) . '.');
                } else {
                    $this->addSentence('I noticed your ' . ($incomingLink->pageTitle != '' ? "$incomingLink->pageTitle " : '') . "website at $incomingLink->url.");
                }

                break;

            default:
                if ($otherBookingSystemsLinked && in_array('featuresList', $incomingLink->contactTopics) && ! in_array('mediaAvailability', $incomingLink->contactTopics)) {
                    $this->addSentence('I noticed that on your ' . ($incomingLink->pageTitle != '' ? "$incomingLink->pageTitle " : '') . "page at $incomingLink->url you link to " . itemList($otherBookingSystemsLinked) . '.');
                } // I wanted to reach out and recommend adding a link to Hostelz.com.
                else {
                    $this->addSentence('I noticed your ' . ($incomingLink->pageTitle != '' ? "$incomingLink->pageTitle " : '') . "page at $incomingLink->url.");
                }

                break;
        }

        // Our Accommodation

        if ($incomingLink->placeType == 'Listing') {
            $this->addParagraph('Hostelz.com is the only worldwide hostels guide that lets you include your direct contact information in your listing, including your phone number and website. In return, the only thing we ask is that you put a link back to Hostelz.com somewhere on your website. You can link to any page on Hostelz.com, including the homepage or your own listing page.  This is your ' . $incomingLink->placeDisplayName() . ' page:');
            $this->addParagraph(' ' . $incomingLink->placeURL('publicSite', null, true));
        }

        // Our Features

        if (in_array($incomingLink->category, ['edu', 'org', 'gov'])) {
            if ($incomingLink->otherWebsitesLinked) {
                switch ($incomingLink->category) {
                    case 'edu':
                        $featuresText = "a free information resource that would be more fitting to be recommended by a school's website.  It ";

                        break;
                    case 'org':
                        $featuresText = "a free information resource that would be more fitting to be recommended by your organization's website.  It ";

                        break;
                    case 'gov':
                        $featuresText = 'a free information resource that would be more fitting to be recommended by a government website.  It ';

                        break;
                }
            } else {
                $featuresText = 'a free information resource that ';
            }

            $featuresText .= 'lists all hostels worldwide for free, providing direct contact information for all hostels.  The goal of Hostelz.com is to provide all the hostel information anyone could ever want, all in one place.';
        } else {
            $featuresText = "the only website that freely lists information on all hostels worldwide.  The site shows a price comparison of all the major booking websites for each hostel (including Hostelworld, HostelBookers, Booking.com, and others), so you can see where to get the lowest price.  But we also list the many other hostels that don't use the booking websites, and we provide direct contact info for all hostels for free.  The goal of Hostelz.com is to provide all the hostel information anyone could ever want, all in one place.";
        }

        if ($otherBookingSystemsLinked) {
            if (in_array($incomingLink->category, ['edu', 'org', 'gov'])) {
                // (We only use this text for edu/gov type sites so the booking sites don't find out what we're saying and get mad at us.)
                $featuresText =
                    ($otherBookingSystemsLinked == ['Hostels.com'] ?
                        "Hostels.com was bought by Hostelworld, and they removed the contact information for hostels and now it's just a portal for Hostelworld's booking system.  " :
                        'Online booking websites such as ' . (count($otherBookingSystemsLinked) == 1 ? itemList($otherBookingSystemsLinked) : 'those') . ' only list the limited number of hostels that are in their own booking systems.  ') . // , and many hostels no longer work with them since they started raising the fees they charge hostel owners.
                    "\n\nHostelz.com is completely different.  It's " . $featuresText;
            } else {
                $featuresText = "Online booking websites only list the hostels that signed up to use that particular website's booking service.  Hostelz.com is completely different.  It's " . $featuresText;
            }
        } else {
            $featuresText = 'Hostelz.com is ' . $featuresText;
        }

        if (in_array('featuresList', $incomingLink->contactTopics)) {
            if (! $otherBookingSystemsLinked) {
                $this->addSentence("In case you aren't familiar with Hostelz.com, I wanted to tell you about it.");
            }
            $this->addParagraph($featuresText);
        }

        // Cross-promotion

        if (in_array('crossPromotion', $incomingLink->contactTopics)) {
            if ($incomingLink->placeType != '') {
                $ourPagesText = 'our ' . $incomingLink->placeDisplayName() . ' page';
            } else {
                $ourPagesText = 'our city pages';
            }

            $this->addParagraph('I was wondering if you would be interested in doing any kind of cross-promotion with Hostelz.com?  For example, we can run a free ad for your ' .
                ($incomingLink->category == 'tour' ? 'tours' : 'website') .
                " on $ourPagesText, if you're willing to put a link to " .
                ($incomingLink->placeType != '' ? 'our ' . $incomingLink->placeDisplayName() . ' page' : 'Hostelz.com') .
                ' on your website.  Let me know if you would be interested in trying that, or there may be other creative ways we can find to work together as well. ');
        }

        // Provide article

        if (in_array('provideArticle', $incomingLink->contactTopics)) {
            $this->addParagraph('If we provide you with a useful travel article, would you be interested in publishing it on your ' . (in_array($incomingLink->category, ['blog']) ? 'blog' : 'website') . '? ');
        }

        // Affiliate

        if (in_array('affiliate', $incomingLink->contactTopics)) {
            if ($otherAffiliateSitesLinked) {
                $this->addParagraph('I noticed that you have ' . (count($otherAffiliateSitesLinked) == 1 ? 'an affiliate link to' : 'affiliate links to') . ' ' .
                    itemList($otherAffiliateSitesLinked) . '.');
                if ($otherAffiliateSitesLinked == ['HostelWorld']) {
                    $this->addSentence("Hostelz.com now offers an affiliate program, and we pay at least double what you're earning from HostelWorld's affiliate program.");
                } elseif ($otherAffiliateSitesLinked == ['HostelBookers']) {
                    $this->addSentence('HostelBookers terminated their affiliate program, so that link is no longer paying you for bookings.  But Hostelz.com now offers an affiliate program that offers an opportunity to earn a higher percent and offers a better source of hostel information for your users.');
                } else {
                    $this->addSentence('Hostelz.com now offers an affiliate program that offers an opportunity to earn a higher percent and offers a better source of hostel information for your users.');
                }

                if (! in_array('featuresList', $incomingLink->contactTopics)) {
                    $this->addSentence('Hostelz.com has a larger hostels database, so users are more likely to find available beds at the hostels they want, which results in more successful bookings and a higher level of return for your affiliate link.  You can sign-up here for more information:  ' . routeURL('affiliateSignup', [], 'publicSite') . "\n\nOnline for over 10 years, Hostelz.com is well-respected and is the longest running and largest database of hostel information and reviews online.  Our unique booking system allows users to compare rates and availability from all of the hostel booking systems at once.  So your visitors will appreciate that you're providing a valuable website recommendation, while also earning an income for referring them to the site.");
                } else {
                    $this->addSentence('You can sign-up here for more information:  ' . routeURL('affiliateSignup', [], 'publicSite'));
                }
            } else {
                if (in_array('crossPromotion', $incomingLink->contactTopics)) {
                    $affiliateText = 'You may also be interested in our affiliate program.  ';
                } elseif (count($incomingLink->contactTopics) > 1) {
                    $affiliateText = 'By the way, you may be interested in our affiliate program.  ';
                } else {
                    $affiliateText = 'We recently launched the Hostelz.com affiliate program, and I would like to invite you to join.  ';
                }
                $affiliateText .= "By simply linking to Hostelz.com, you'll earn a commission each time a user makes a booking after clicking the link from your website.  You can sign-up here for more information:  " . routeURL('affiliateSignup', [], 'publicSite');
                if (! in_array('featuresList', $incomingLink->contactTopics)) {
                    $affiliateText .= "\n\nOnline for over 10 years, Hostelz.com is by far the largest database of hostel information online, and is trusted as the best source for hostel reviews and ratings.  Our unique booking system allows users to compare rates and availability from all of the hostel booking systems at once.  So your visitors will appreciate that you're providing a valuable website recommendation, while also earning an income for referring them to the site.";
                }
                $this->addParagraph($affiliateText);
            }
        }

        // Specific Place/Listing

        if ($incomingLink->placeType != '' && $incomingLink->placeType != 'Listing' && $incomingLink->contactTopics != ['crossPromotion']) {
            $this->addParagraph('This is our ' . $incomingLink->placeDisplayName() . ' page:');
            $this->addParagraph('  ' . $incomingLink->placeURL('publicSite', null, true));
        }

        // Reimburse

        if (in_array('reimburse', $incomingLink->contactTopics)) {
            $this->addParagraph("We can offer you up to \$80 (USD) in reimbursement towards a hostel stay during your trip in exchange for a post on your blog with a link to Hostelz.com (ideally we prefer if you can include a link directly to one of our city pages).  If you make the hostel bookings through Hostelz.com, just send me the booking confirmation number, or if you already booked it somewhere else, just email me a copy of the confirmation email.  The payment will be sent with PayPal to your email address.\n\nIf you would be interested in that, let me know.");
        }

        // Pay

        if (in_array('pay', $incomingLink->contactTopics)) {
            $this->addParagraph("[pay text not yet entered]\n\nIf you would be interested in that, let me know.");
        }

        // Ending

        if (in_array('mediaAvailability', $incomingLink->contactTopics)) {
            $endingText = "Take a look at the site and let me know what you think. And we're available if you need quotes, statistics, or other information related to hostels or budget travel. Thanks.";
        } elseif (in_array($incomingLink->category, ['edu', 'org', 'gov'])) {
            $endingText = 'Take a look at the site and see if it may be a more useful resource for your visitors.';
        } elseif (in_array('crossPromotion', $incomingLink->contactTopics)) {
            $endingText = 'Let me know what you think.';
        } elseif ($incomingLink->placeType == 'Listing') {
            $endingText = 'Let me know if you can add that link-back.  Thanks.';
        } else {
            $endingText = 'Take a look at the site and let me know what you think.  Thanks.';
        }

        // Add a short feature tidbit if not giving them the longer one
        if (! array_intersect(['featuresList', 'affiliate'], $incomingLink->contactTopics) && $incomingLink->category != 'accommodation') {
            $this->addParagraph("In case you aren't familiar with the site, Hostelz.com is the world's largest database of hostel information combining data from all of the hostel booking sites, but also adding contact info, our own reviews/photos, and tons of other information that isn't available anywhere else to provide the best hostel information resource available.");
        }

        $this->addParagraph($endingText);

        return Response::json(['subject' => $subject, 'messageText' => $this->generatedMessage]);
    }

    private function addParagraph($text): void
    {
        $this->generatedMessage .= ($this->generatedMessage != '' ? "\n\n" : '') . $text;
    }

    private function addSentence($text): void
    {
        $this->generatedMessage .= ($this->generatedMessage != '' ? '  ' : '') . $text;
    }

    public function importFromFile()
    {
        if (Request::input('file') == '') {
            echo '<p>Instructions: SemRush, etc. notes in SimpleNote</p>';
            echo '<form>file: <select name="file">';
            foreach (glob(config('custom.userRoot') . '/data/incomingLinks/*/*') as $filename) {
                echo "<option value=\"$filename\" " . (Request::input('file') == $filename ? 'SELECTED' : '') . ">$filename</option>";
            }
            echo '</select>
                <div>source: <input name="source"> semrush, seomoz, majestic</div>
                <div><input type=checkbox name=isLinkingToUs value=1> Is links to us (links to Hostelz.com)</div>
                <button type="submit">Submit</button>
                </form>';

            return '';
        }

        $isLinkingToUs = (Request::input('isLinkingToUs') ? true : false);

        set_time_limit(60 * 60);
        $fp = fopen(Request::input('file'), 'r');
        if (! $fp) {
            throw new Exception("Couldn't open $filePath.");
        }

        $lineNumber = 0;
        $lastDomain = '';
        $lastURL = '';
        while (($line = fgets($fp)) !== false) {
            $lineNumber++;
            $attributes = null;

            switch ($source = Request::input('source')) {
                case 'semrush':
                    if ($lineNumber == 1) {
                        continue 2;
                    }
                    $parts = str_getcsv($line);
                    // Page score,Page trust score,Source title,Source url,Target url,Anchor,External links,Internal links,Nofollow,Frame,Form,Image,Sitewide,First seen,Last seen

                    if (count($parts) != 15) {
                        throw new Exception('Unexpected number of columns (' . count($parts) . ") for '$line'.");
                    }
                    $attributes = ['pageTitle' => $parts[2], 'url' => $parts[3], 'linksTo' => $parts[4], 'anchorText' => $parts[5],
                        /* 'pageAuthority' => $parts[4], (their score isn't really the same thing) */
                        'followable' => ($parts[8] == 'true' ? 'n' : 'y'), 'createDate' => $parts[13],
                        'source' => 'semrush',
                    ];
                    // dd($attributes);
                    break;

                case 'seomoz':
                    if ($lineNumber < 7) {
                        echo "<p>Skipping '$line'.</p>";

                        continue 2;
                    }
                    $parts = str_getcsv($line, ',');
                    // URL,Title,Anchor Text,Spam Score,Page Authority,Domain Authority,Number of Domains Linking to this Page,Number of Domains Linking to Domain,Origin,Target URL,Link Equity,No Link Equity,Only rel=nofollow,Only follow,301
                    if (count($parts) != 15) {
                        throw new Exception('Unexpected number of columns (' . count($parts) . ") for '$line'.");
                    }
                    $attributes = ['pageTitle' => $parts[1], 'url' => $parts[0], 'linksTo' => $parts[9], 'anchorText' => $parts[2],
                        'pageAuthority' => $parts[4], 'domainAuthority' => $parts[5], 'followable' => ($parts[12] == 'Yes' ? 'n' : 'y'),
                        'source' => 'seomoz',
                    ];

                    break;

                case 'majestic':
                    if ($lineNumber == 1) {
                        $header = explode(',', $line);

                        continue 2;
                    }
                    $parts = str_getcsv($line, ',');
                    if (count($parts) != 20) {
                        // (Some of Majestic's rows are invalid CSV, so we just skip them)
                        echo '<p>Unexpected number of columns (' . count($parts) . ") for '$line'.<p>";

                        continue 2;
                    }
                    $parts = array_combine($header, $parts);

                    // See http://developer-support.majestic.com/api/commands/download-backlinks.shtml
                    // Target URL,Source URL,Anchor Text,Source Crawl Date,Source First Found Date, 5 FlagNoFollow,FlagImageLink,FlagRedirect,FlagFrame,FlagOldCrawl,FlagAltText,FlagMention,
                    // SourceCitationFlow,SourceTrustFlow,TargetCitationFlow,TargetTrustFlow,SourceTopicalTrustFlow_Topic_0,SourceTopicalTrustFlow_Value_0,
                    // RefDomainTopicalTrustFlow_Topic_0,RefDomainTopicalTrustFlow_Value_0
                    /* see download instructions in simplenote */

                    if ($parts['FlagRedirect'] != '' || $parts['FlagOldCrawl'] != '') {
                        continue 2;
                    } // ignore this one
                    $attributes = ['pageTitle' => '', 'url' => $parts['Source URL'], 'linksTo' => $parts['Target URL'], 'anchorText' => $parts['Anchor Text'],
                        /* 'pageAuthority' => $parts[],*/ 'followable' => ($parts['FlagNoFollow'] == '' ? 'y' : 'n'), 'createDate' => $parts['Source First Found Date'],
                        'source' => 'majestic',
                    ];

                    if (! filter_var($attributes['url'], FILTER_VALIDATE_URL) || Str::contains($attributes['url'], '@')) {
                        // Some of their URLs were technically invalid (contained spaces). Not worth keeping those links anyway, just skip them.
                        // Also skips weird URLs with "@" in them that they were giving us.
                        echo "<p>Invalid URL '" . htmlspecialchars($attributes['url']) . "'.</p>";

                        continue 2;
                    }

                    break;

                default:
                    throw new Exception("Unknown source '$source'.");
            }

            // Seomoz has many of the same URL in a row (multiple links I guess?)
            if ($attributes['url'] == $lastURL) {
                echo "<p>Skipping '" . htmlspecialchars($attributes['url']) . "' because is same URL again.</p>";

                continue;
            }
            $lastURL = $attributes['url'];

            // We skip multiple URLs that are of the same domain as the last URL.
            // We can techniqually allow multiple URLs from the same domain for some domains,
            // But Majestic's huge number of similar URLs was making the script run out of memory.
            // (Could check IncomingLink::$allowMultipleURLsForDomains if we wanted to.)
            $domain = WebsiteTools::getRootDomainName($attributes['url']);
            if ($domain == $lastDomain) {
                echo "<p>Skipping '" . htmlspecialchars($attributes['url']) . "' because is same domain again.</p>";

                continue;
            }
            $lastDomain = $domain;

            $output = $this->insertIncomingLink($attributes, $isLinkingToUs);
            echo '<p><div><a href="' . htmlspecialchars($attributes['url']) . '">' . htmlspecialchars($attributes['url']) . '</a></div>' . htmlspecialchars($output) . '</p>';
            // if ($lineNumber > 4000) exit();
        }

        return 'done';
    }

    private function insertIncomingLink($attributes, $isLinkingToUs)
    {
        if (isset($attributes['contactStatus']) && $attributes['contactStatus'] === '') {
            $attributes['contactStatus'] = ($isLinkingToUs ? 'ignored' : 'todo');
        }
        if (isset($attributes['contactStatusSpecific']) && $attributes['contactStatusSpecific'] === '') {
            $attributes['contactStatusSpecific'] = ($isLinkingToUs ? 'already' : '');
        }
        if ($attributes['source'] == '') {
            throw new Exception('Missing source.');
        }
        if ($attributes['url'] == '') {
            throw new Exception('Missing url.');
        }

        if (isset($attributes['linksTo']) && $attributes['linksTo'] !== '') {
            $linksToDomain = WebsiteTools::getRootDomainName($attributes['linksTo']);
            if ($isLinkingToUs && $linksToDomain != 'hostelz.com') {
                throw new Exception("'$attributes[linksTo]' isn't a link to us.");
            } elseif (! $isLinkingToUs && $linksToDomain == 'hostelz.com') {
                throw new Exception("'$attributes[linksTo]' is a link to us?");
            }
        }

        $result = IncomingLink::addNewLink($attributes, $linkObject, false);

        switch ($result) {
            case 'created':
                return '[created] ' . json_encode($attributes);

            case 'exists':
                return "[exists as ($linkObject->contactStatus/$linkObject->contactStatusSpecific) '$linkObject->url'] " . json_encode($attributes);

            default:
                logError($result); // an error message
        }
    }

    public function ads($incomingLinkID, $pathParameters = null)
    {
        $placeSearchResult = Ad::handlePlaceSearchAjaxCommand();
        if ($placeSearchResult !== null) {
            return $placeSearchResult;
        }

        $incomingLink = IncomingLink::findOrFail($incomingLinkID);

        $formHandler = new FormHandler('Ad', Ad::fieldInfo('incomingLinkAd'), $pathParameters, 'App\Models');
        $formHandler->allowedModes =
            ['list', 'insertForm', 'insert', 'updateForm', 'update', 'delete'];
        $formHandler->listPaginateItems = 100;
        $formHandler->logChangesAsCategory = 'staff';
        $formHandler->listSelectFields = ['status', 'linkURL'];
        $formHandler->listSort['id'] = 'asc';
        $formHandler->query = Ad::where('incomingLinkID', $incomingLinkID);

        $formHandler->go(null, null, 'list');

        if ($formHandler->mode == 'insertForm') {
            // Set some default values
            $ad = $formHandler->model;
            $ad->linkURL = $incomingLink->url;
            $ad->adText = $incomingLink->pageTitle;
            $this->setAdPlaceBasedOnIncomingLink($incomingLink, $ad);
        } elseif ($formHandler->mode == 'insert') {
            // Set values of new ad
            $ad = $formHandler->model;
            $ad->incomingLinkID = $incomingLinkID;
            $ad->userID = auth()->id();
            $ad->save();
        }

        $message = '';

        if ($formHandler->mode == 'updateForm') {
            $ad = $formHandler->model;

            $adsForTheSamePlace = Ad::where('id', '!=', $ad->id)->samePlaceAs($ad)->get();

            if (Request::has('objectCommand')) {
                switch (Request::input('objectCommand')) {
                    case 'duplicate':
                        $duplicate = $ad->duplicate();
                        $message = 'Duplicate created. <a href="' . routeURL(Route::currentRouteName(), [$incomingLinkID, $duplicate->id]) . '">View New Ad</a>';
                }
            }

            // FileList
            $pics = $ad->pics;
            $fileList = new FileListHandler($pics, null, null, true);
            $fileList->picListSizeTypeNames = ['originals'];
            if (auth()->user()->hasPermission('staffPicEdit')) {
                $fileList->viewLinkClosure = function ($row) {
                    return routeURL('staff-pics', [$row->id, 'pics']);
                };
            }
            $response = $fileList->go();
            if ($response !== null) {
                return $response;
            }

            // FileUpload
            $fileUpload = new FileUploadHandler(['jpg', 'jpeg', 'gif', 'png'], false, false, count($pics));
            $response = $fileUpload->handleUpload(function ($originalName, $filePath) use ($ad): void {
                $ad->addPic($filePath);
            });
            if ($response !== null) {
                return $response;
            }
        } else {
            $fileUpload = $fileList = $adsForTheSamePlace = null;
        }

        return $formHandler->display('staff/edit-incomingLink-ads', compact('incomingLink', 'fileList', 'fileUpload', 'message', 'adsForTheSamePlace'));
    }

    private function setAdPlaceBasedOnIncomingLink($incomingLink, $ad)
    {
        if ($incomingLink->placeType == '') {
            return;
        }

        if ($incomingLink->placeType == 'Listing') {
            $listing = $incomingLink->getPlaceObjectOrFail();
            $cityInfo = $listing->cityInfo;
            if (! $cityInfo) {
                return "The link is associated with a listing that doesn't have a city. Don't know what city to associate this ad with.";
            }
            $ad->placeType = 'CityInfo';
            $ad->placeID = $cityInfo->id;
        } else {
            $ad->placeType = $incomingLink->placeType;
            $ad->placeID = $incomingLink->placeID;
            $ad->placeString = $incomingLink->placeString;
        }
    }
}
