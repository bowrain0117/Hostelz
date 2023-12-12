<?php

namespace App\Helpers;

use App\Models\Booking;
use App\Models\IncomingLink;
use App\Models\Listing\Listing;
use App\Models\MailAttachment;
use App\Models\MailMessage;
use App\Models\Rating;
use App\Models\User;
use App\Services\ImportSystems\ImportSystems;
use Carbon\Carbon;
use Config;
use DB;
use Exception;
use Illuminate\Support\Str;
use Lib\Emailer;

/*
copy current mail to /var/spool/mail

Example structure:

msg with no attachments:

 [type] => 0 [encoding] => 0 [ifsubtype] => 1 [subtype] => PLAIN [ifdescription] => 0 [ifid] => 0 [lines] => 5 [bytes] => 22 [ifdisposition] => 0 [ifdparameters] => 0 [ifparameters] => 1
 [parameters] => Array ( [0] => stdClass Object ( [attribute] => CHARSET [value] => us-ascii ) )

text msg with text/html of message and hjhphjbavef.gif 14 Kb

 [type] => 1 [encoding] => 0 [ifsubtype] => 1 [subtype] => RELATED [ifdescription] => 0 [ifid] => 0 [bytes] => 20615 [ifdisposition] => 0 [ifdparameters] => 0 [ifparameters] => 1
 [parameters] =>
    Array ( [0] => stdClass Object ( [attribute] => TYPE [value] => multipart/alternative ) [1] => stdClass Object ( [attribute] => BOUNDARY [value] => ----=_NextPart_000_0005_01C6360C.D6B3C190 ) )
 [parts] => (
     [0] => stdClass Object (
         [type] => 1 [encoding] => 0 [ifsubtype] => 1 [subtype] => ALTERNATIVE [ifdescription] => 0 [ifid] => 0 [bytes] => 1610 [ifdisposition] => 0 [ifdparameters] => 0 [ifparameters] => 1
         [parameters] => Array ([0] => stdClass Object  ( [attribute] => BOUNDARY [value] => ----=_NextPart_001_0006_01C6360C.D6B3C190))
         [parts] => Array (
             [0] => stdClass Object (
                 [type] => 0  [encoding] => 4  [ifsubtype] => 1  [subtype] => PLAIN  [ifdescription] => 0  [ifid] => 0  [lines] => 8  [bytes] => 156  [ifdisposition] => 0  [ifdparameters] => 0  [ifparameters] => 1
                 [parameters] => Array ( [0] => stdClass Object ([attribute] => CHARSET [value] => Windows-1252) )
             )
             [1] => stdClass Object (
                 [type] => 0  [encoding] => 4  [ifsubtype] => 1  [subtype] => HTML  [ifdescription] => 0  [ifid] => 0  [lines] => 24  [bytes] => 1114  [ifdisposition] => 0  [ifdparameters] => 0  [ifparameters] => 1
                 [parameters] => Array ( [0] => stdClass Object ([attribute] => CHARSET [value] => Windows-1252) )
             )
         )

     )
     [1] => stdClass Object (
         [type] => 5  [encoding] => 3  [ifsubtype] => 1  [subtype] => GIF  [ifdescription] => 0  [ifid] => 1  [id] => <000401c63604$74ef5990$d328f1d5@KARINA>  [bytes] => 18628  [ifdisposition] => 0  [ifdparameters] => 0  [ifparameters] => 1
         [parameters] => Array ([0] => stdClass Object ([attribute] => NAME  [value] => hjhphjbavef.gif))
     )

 )
*/

class MailFetch
{
    private static $plaintextMessage;

    private static $htmlMessage;

    private static $attachments;

    public static function fetchNew()
    {
        $output = 'Fetch Incoming Mail: ';

        // Note: No timeout used because if it crashes while fetching mail, it's probably best if we keep it locked until we figure out the problem.
        $gotLock = acquireLock('fetchIncomingMail');
        if (! $gotLock) {
            $output .= "Already locked by another task.\n";
            logError('Mail fetch was already locked.');

            return $output;
        }

        ignore_user_abort(true);
        set_time_limit(30 * 60);
        DB::disableQueryLog(); // to save memory

        // Some code inspired by https://github.com/tedious/Fetch

        $userMailboxes = User::arrayMapOfIncomingEmailAddressesToUserIDs();

        $imap = imap_open('{localhost:110/pop3/notls}INBOX', config('custom.hostelzMailUser'), config('custom.hostelzMailPassword'));
        if (! $imap) {
            throw new Exception('Unable to connect to IMAP server.');
        }

        $numMsgs = $totalMsgs = imap_num_msg($imap);
        if ($numMsgs > 100) {
            $numMsgs = 100;
        }

        $output .= "(getting $numMsgs of $totalMsgs messages) ";

        $overviews = imap_fetch_overview($imap, "1:$numMsgs");

        foreach ($overviews as $overview) {
            usleep(10000); // make sure a mail flood doesn't overwhelm the server

            $output .= $overview->msgno . ' ';

            imap_delete($imap, $overview->msgno);

            $message = new MailMessage();

            $message->transmitTime = Carbon::now();
            $message->status = 'new';

            // * Get Headers *

            $message->headers = imap_fetchheader($imap, $overview->msgno);
            $headerLines = explode("\n", $message->headers);
            $headerInfo = imap_rfc822_parse_headers($message->headers);
            /* this does the same thing but some comments say it has issues...
            $headers = imap_headerinfo($imap, $overview->msgno); */

            // Find header info we need that isn't included in $headerInfo...

            foreach ($headerLines as $key => $line) {
                // leading spaces means it was a continuation of another line (which we currently ignore)
                if (substr($line, 0, 1) == "\t") {
                    continue;
                }

                $line = self::flatDecode($line);
                $parts = explode(':', $line, 2);
                if (count($parts) != 2) {
                    continue;
                }
                $name = $parts[0];
                $value = $parts[1];

                switch ($name) {
                    case 'Received':
                    case 'X-Mailer': // our contact form puts the IP in the X-Mailer line
                        preg_match("/(\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3})/", $value, $matches);
                        if (isset($matches[1])) {
                            $message->ipAddress = $matches[1];
                        } // note this saves the last one

                        break;
                }
            }

            // Subject

            $message->subject = (isset($overview->subject) ? self::flatDecode($overview->subject) : '');

            // sender

            /*
        	if (!property_exists($headerInfo, 'reply_toaddress')) {
        	    dd($headerInfo);
        	}
        	*/

            if (isset($headerInfo->reply_toaddress) && $headerInfo->reply_toaddress !== '') { // we use the replyto as the from address if given
                $message->sender = self::flatDecode($headerInfo->reply_toaddress);
            } elseif (isset($headerInfo->fromaddress) && $headerInfo->fromaddress !== '') {
                $message->sender = self::flatDecode($headerInfo->fromaddress);
            } else {
                $message->sender = self::flatDecode(isset($overview->from) ? $overview->from : '');
            } // this shouldn't happen except some spam missing a from address

            // senderAddress

            if (isset($headerInfo->reply_to)) { // we use the replyto address as the from address if given
                $message->senderAddress = self::processAddressObject($headerInfo->reply_to, true);
            }
            if ($message->senderAddress == '' && isset($headerInfo->from[0]->mailbox)) {
                $message->senderAddress = self::processAddressObject($headerInfo->from, true);
            }
            if ($message->senderAddress == '') {
                $message->senderAddress = isset($overview->from) ? Emailer::extractEmailAddress($overview->from) : '';
            } // this shouldn't happen

            // recipient / cc

            if (isset($headerInfo->toaddress)) {
                $message->recipient = self::flatDecode($headerInfo->toaddress);
            }
            if (isset($headerInfo->ccaddress)) {
                $message->cc = self::flatDecode($headerInfo->ccaddress);
            }

            // recipientAddresses[]

            $recipientAddresses = [];
            if (isset($headerInfo->to)) {
                $recipientAddresses = array_merge($recipientAddresses, self::processAddressObject($headerInfo->to));
            }
            // We also add cc addresses to the $recipientAddresses list
            if (isset($headerInfo->cc)) {
                $recipientAddresses = array_merge($recipientAddresses, self::processAddressObject($headerInfo->cc));
            }
            $message->recipientAddresses = array_unique($recipientAddresses);

            // Message Data / Attachments

            $structure = imap_fetchstructure($imap, $overview->msgno);

            self::$plaintextMessage = self::$htmlMessage = '';
            self::$attachments = [];

            // (Sets self::$plaintextMessage, self::$htmlMessage, and self::$attachments[].)
            self::fetchMessageParts($imap, $structure, $overview->msgno);

            if (self::$plaintextMessage != '') {
                $message->bodyText = self::$plaintextMessage;
            } else {
                $message->bodyText = html_entity_decode(strip_tags(
                    str_replace(['<p>', '<P>', '<br>', '<BR>'], ["\n\n", "\n\n", "\n", "\n"], self::$htmlMessage)
                ), ENT_QUOTES, 'UTF-8');
            }

            // Handle Special Email Types

            if (($outputTemp = self::handleImportSystemEmail($message)) !== '') {
                $output .= $outputTemp;

                continue;
            }

            // Spam Checking / Bounce Checking

            $message->senderTrust = self::getSenderTrust($message);
            $bounced = self::handleBounceEmails($message);
            $message->listingID = $message->determineListingIDs(true);

            // for null@hostelz.com, we just handle the bounces, but don't keep the actual emails
            if ($recipientAddresses == ['null@hostelz.com']) {
                $output .= '[null] ';

                continue;
            }

            if ($bounced) {
                if (in_array(Config::get('custom.listingSupportEmail'), $message->recipientAddresses)) {
                    /* archive bounces sent to the listingSupportEmail because they're generally from us sending listing mgmt emails
                    and we don't want to flood listingSupportEmail with daily bounce emails (but we do need to get the bounce emails
                    so we can remove those addresses from the hostels database) */
                    $message->status = 'archived';
                }
            } else {
                /*
            	if (getSpamReportEmail($imap,$overview)) continue;
                */

                // We automatically archive (ignore) messages with subjects that start with...
                $ignoreSubjectStartsWith = [
                    'Receipt for Your Payment to',
                    'Receipt for your Mass Payment',
                    'Payment Accepted!',
                    'Your unclaimed payment to ',
                    'PayPal Electronic Funds Transfer',
                    'Receipt for your mass payment',
                    'New Hostel Booking Sign-up',
                    'Warning: could not send message for past 4 hours',
                ];

                foreach ($ignoreSubjectStartsWith as $ignoreString) {
                    if (Str::startsWith($message->subject, $ignoreString)) {
                        $message->status = 'archived';
                    }
                }

                /* also ignore strstr($message->sender,'.hbad.hostelbookers.com') ? */

                IncomingLink::setResponseReceivedByEmailAddress($message->getNonLocalEmailAddresses());
            }

            $matchingMailboxFound = false;
            foreach ($userMailboxes as $emailAddress => $userID) {
                if ($emailAddress == 'default') {
                    if ($matchingMailboxFound) {
                        continue;
                    } // already found a box for this message
                } else {
                    if (! in_arrayi($emailAddress, $message->recipientAddresses)) {
                        continue;
                    } // not a match
                }

                $output .= self::saveMesageForUser($message, $userID);

                $matchingMailboxFound = true;
            }
        }

        imap_expunge($imap);

        // What these functions do is return all errors and alerts that have occured and then flushes them.
        // If you do not call these functions they are issued as notices when imap_close() is called, or the page dies
        // (we were getting segfaults)
        imap_errors();
        imap_alerts();

        imap_close($imap);

        releaseLock('fetchIncomingMail');

        return $output . "\n";
    }

    private static function saveMesageForUser(MailMessage $message, $userID)
    {
        $newMessage = $message->replicate();
        $newMessage->userID = $userID;
        $newMessage->save(); // so we know the id so we can save attachments

        foreach (self::$attachments as $attachment) {
            if ($attachment['data'] == '') {
                continue;
            }
            (new MailAttachment([
                'mailID' => $newMessage->id,
                'filename' => $attachment['filename'],
                'mimeType' => $attachment['mimeType'],
            ]))->saveContentsFromString($attachment['data']);
        }

        $newMessage->spamicityEvaluate(); // have to do this *after* we save attachments
        $newMessage->save();

        return '[Saving ' . $newMessage->id . ' for user ' . $userID . '] ';
    }

    /* This sets $plaintextMessage, $htmlMessage, and $attachments[]. */

    private static function fetchMessageParts($imap, $structure, $msgNumber, $partNumber = null, $verbose = false): void
    {
        // Get $type and $mimeType

        $typeMap = ['TEXT', 'MULTIPART', 'MESSAGE', 'APPLICATION', 'AUDIO', 'IMAGE', 'VIDEO', 'OTHER'];
        $type = $typeMap[(int) $structure->type] ?? null;
        if ($type == '') {
            $type = 'UNKNOWN';
        }
        $mimeType = $type;

        if (isset($structure->subtype)) {
            $mimeType .= '/' . $structure->subtype;
        }
        if ($mimeType == 'TEXT' || $mimeType == '') {
            $mimeType = 'TEXT/PLAIN';
        } // default

        // Handle Multipart Messages

        if ($type == 'MULTIPART' && isset($structure->parts)) {
            // multipart: iterate through each part
            foreach ($structure->parts as $partIndex => $part) {
                self::fetchMessageParts($imap, $part, $msgNumber, ($partNumber ? $partNumber . '.' : '') . ($partIndex + 1), $verbose);
            }

            return;
        }

        // Create $parameters[] from the $structure's parameters for easier access

        $parameters = [];
        if (isset($structure->parameters)) {
            foreach ($structure->parameters as $parameter) {
                $parameters[strtolower($parameter->attribute)] = $parameter->value;
            }
        }

        if (isset($structure->dparameters)) {
            foreach ($structure->dparameters as $parameter) {
                $parameters[strtolower($parameter->attribute)] = $parameter->value;
            }
        }

        // Get and Decode the Data

        $data = isset($partNumber) ?
            imap_fetchbody($imap, $msgNumber, $partNumber) :
            imap_body($imap, $msgNumber);

        if ($data == '') {
            return;
        }

        $encodingTypeMap = ['7BIT', '8BIT', 'BINARY', 'BASE64', 'QUOTED-PRINTABLE', 'OTHER', 'OTHER' /* don't know what this one is, but we got one */];
        if (! array_key_exists((int) $structure->encoding, $encodingTypeMap)) {
            logWarning("Unknown encoding '" . (int) $structure->encoding . "'.");

            return;
        }
        switch ($encodingTypeMap[(int) $structure->encoding]) {
            case 'BASE64':
                $data = imap_base64($data);

                break;
            case 'QUOTED-PRINTABLE':
                $data = imap_qprint($data);

                break;
        }

        if (self::$htmlMessage == '' && $mimeType == 'TEXT/HTML') {
            self::$htmlMessage = convertStringToValidUTF8($data, $parameters['charset'] ?? null);
        }

        if (self::$plaintextMessage == '' && $mimeType == 'TEXT/PLAIN') {
            self::$plaintextMessage = mb_trim(convertStringToValidUTF8($data, $parameters['charset'] ?? null), $verbose);
        } else {
            // Save Attachment (we don't yet have our internal mailID, so we have to set those later)
            self::$attachments[] = [
                'filename' => isset($parameters['filename']) ? $parameters['filename'] :
                    (isset($parameters['name']) ? $parameters['name'] : ''),
                'mimeType' => $mimeType,
                'data' => $data,
            ];
        }
    }

    private static function flatDecode($string)
    {
        /* Was using imap_utf8() for a while, but it doesn't convert UTF-8 subjects like
        '=?UTF-8?B?TmV3IFJlc2VydmF0aW9uIGZvciBZb2hvIEludGVybmF0aW9uYWwgWW91dGggSG9zdGVsIA==?='
    	return imap_utf8($string);
        */

        $result = '';
        $array = imap_mime_header_decode($string);
        if (! $array) {
            return $string;
        } // Decode error, just return the original
        foreach ($array as $key => $part) {
            $result .= $part->text;
        }

        return $result;
    }

    private static function handleBounceEmails(MailMessage $message)
    {
        // global self::$htmlMessage, $message->bodyText, $bounceEmailAddress, $attachments_var;

        $TEST_MODE = false; // makes it not edit DB records

        $comment = $bounceEmailAddress = $invalidEmail = '';

        $bounceFrom = ['`.*MAILER-DAEMON@.*`i', '`.*postmaster@.*`i'];
        $isBounceFrom = false;
        foreach ($bounceFrom as $from) {
            if (preg_match($from, $message->sender) > 0) {
                $isBounceFrom = true;
            }
        }
        if (! $isBounceFrom) {
            return false;
        }

        $invalidEmail = '';

        if (self::$attachments) {
            foreach (self::$attachments as $attachment) {
                if ($attachment['mimeType'] != 'MESSAGE/DELIVERY-STATUS' || ! preg_match('`Action\: failed`i', $attachment['data'])) {
                    continue;
                }
                if (preg_match('`Final-Recipient\: rfc822;(.*)`i', $attachment['data'], $matches)) {
                    $invalidEmail = trim($matches[1]); // RFC822 bounce
                    /*
    	            // ('totalcommercial.com' shows up as part of the email address if it was sent without a valid email address domain.)
    				if (strpos($invalidEmail, 'totalcommercial.com') !== false) {
    				    $invalidEmail = '';
    				    continue;
    				}
    				*/
                    break;
                }
            }
        }

        if ($invalidEmail == '') {
            $bounceFormats = [
                ['message' => '`.*Failed Recipient\: (\S+)\s.*`s'],
                ['message' => '`.*Delivery to the following recipients failed.\s*(\S+)(\s|$).*`s'],
                ['message' => '`.*This is a permanent error. The following address\(es\) failed\:.+\(ultimately generated from (.*?)\).*`sU'],
                ['message' => '`.*This is a permanent error. The following address\(es\) failed\:.+\(generated from (.*?)\).*`sU'],
                ['message' => '`.*This is a permanent error. The following address\(es\) failed\:\s+(\S+)\s.*`s'],
                ['message' => '`.*Unable to deliver message to the following address\(es\).\s+<(\S+)>.*`sU'],
                ['message' => '`.*This is a permanent error. I.ve given up. Sorry it didn.t work out.\s+(.*)\:.*`sU'],
                ['message' => '`.*This Message was undeliverable due to the following reason\:\s+The following destination addresses were unknown \(.*\)\:\s+<(\S+)>.*`sU'],
                ['message' => '`.*The following addresses had permanent fatal errors \-+\s+(.*?)\s.*`s'],
                ['message' => '`.*The following addresses had permanent fatal errors.*\(expanded from\: (.*?)\).*`sU'],
                ['message' => '`.*The following recipients haven\'t received this message\:\s+(\S+)\s.*`s'],
                ['message' => '`.*The following recipient\(s\) could not be reached\:\s+Recipient\: \[SMTP\:(.*)\].*`sU'], // not sure this one is working
                ['message' => '`.*RCPT TO\:\<(.*?)\> Mailbox disk quota exceeded.*`sU'],

                /*	Only need to enable these for mail senders that don't send a proper delivery-status attachment

    			[ 'message'=>'`.*The following addresses had permanent fatal errors \-\-\-\-\-\s+(.*?)\s.*`s' ],
    			[ 'message'=>'`.*The following addresses had delivery errors\s*\-+\s+(.*?) \[\s.*`s' ],
    			[ 'message'=>'`.*The following addresses had permanent delivery errors.*\(expanded from\: (.*?)\).*`s' ],
    			[ 'message'=>'`.*The following addresses had permanent delivery errors \-\-\-\-\-\s+(.*?)\s.*`s' ],
    			[ 'message'=>'`.*The following address\(es\) failed\:\s*(\S*)\s.*`s' ],
    			[ 'message'=>'`.*<(.*?)>\:\s+The e\-mail message could not be delivered because .*`s' ],
    			[ 'message'=>'`.*<(.*?)>\: Recipient address rejected.*`' ], // not "s" multi-line
    			[ 'message'=>'`.*your message could not be\s*be delivered to one or more recipients.*?<postmaster>.*?<(.*?)>.*`s' ],
    			[ 'message'=>'`.*\(reason\: 550 unknown user (.*?)\)`' ], // not "s" multi-line
    			[ 'message'=>'`.*This user doesn.t have a yahoo.com account \((.*?)\)`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: unknown user\: ".*".*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: QUOTA EXCEEDED<br>.*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: mail for \S+ loops back<br>.*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: host \S+ said\:\s+550 .*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: host \S+ said\:\s+554 .*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: host \S+ said\:\s+501.*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: host \S+ said\:\s+452.*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)>\: Host or domain name not found.*`' ], // not "s" multi-line
    			[ 'message'=>'`.*<(.*?)> \.\.\. unknown user`' ], // not "s" multi-line
    			[ 'message'=>'`.*Failed addresses follow\: \-+.\s+<(.*?)>.*`s' ],
                */
            ];

            foreach ($bounceFormats as $format) {
                if (preg_match($format['message'], $message->bodyText, $matches) == 0) {
                    continue;
                }
                if (strpos($message->bodyText, '451 Temporary local problem')) {
                    continue;
                }
                $invalidEmail = trim($matches[1]);

                break;
            }
        }

        $invalidEmail = mb_strtolower(trim(trim($invalidEmail), '<>'));
        if ($invalidEmail == '') {
            $message->comment = "[blank invalidEmail string!]\n";
            logWarning("Blank invalidEmail string for email with subject '" . $message->subject . "'.");

            return false;
        }

        if (strpos($invalidEmail, "\n") !== false) {
            $message->comment = "[New line in bounce email address.]\n";
            logWarning("New line in bounce email address for email with subject '" . $message->subject . "'.");

            return false;
        }

        /*
            // remove @totalcommercial.com which is appended automatically to outgoing mails w/out domain
        	if (strpos($invalidEmail,'@totalcommercial.com')!==false) {
        		preg_match('`\(expanded from\: (.*)\)`',$message->bodyText,$matches);
        		$invalidEmail = $matches[1];
        	}
        */

        $message->sender = $message->senderAddress = $invalidEmail;
        $message->comment = "[bounce detected for '$invalidEmail']\n";

        self::setInvalidEmails(
            $message,
            User::class,
            $invalidEmail,
            ['username'],
            false
        );
        self::setInvalidEmails(
            $message,
            Listing::class,
            $invalidEmail,
            ['managerEmail', 'supportEmail', 'bookingsEmail', 'importedEmail'],
            true
        );
        self::setInvalidEmails(
            $message,
            IncomingLink::class,
            $invalidEmail,
            ['contactEmails'],
            true
        );
        self::setInvalidEmails(
            $message,
            Booking::class,
            $invalidEmail,
            ['email'],
            false
        );

        return true;
    }

    private static function processAddressObject($addresses, $onlyGetOne = false)
    {
        $outputAddresses = [];
        if (is_array($addresses)) {
            foreach ($addresses as $address) {
                if (empty($address) || ! isset($address->mailbox)) {
                    continue;
                }
                $email = $address->mailbox . '@' . (isset($address->host) ? $address->host : 'UNKNOWN');
                if ($onlyGetOne) {
                    return mb_strtolower($email);
                }
                $outputAddresses[] = $email;
            }
        }

        return array_unique(array_map('mb_strtolower', $outputAddresses));
    }

    public static function setInvalidEmails(MailMessage $message, $modelClass, $invalidEmail, $emailFields, $removeInvalidEmail): void
    {
        foreach ($emailFields as $emailField) {
            $objects = $modelClass::whereRaw("FIND_IN_SET(?, $emailField)", [$invalidEmail])->get();

            foreach ($objects as $object) {
                // ** Add the address to the record's invalidEmails **
                $invalids = $object->invalidEmails;
                if (! in_array($invalidEmail, $invalids)) {
                    $invalids[] = $invalidEmail;
                    $object->invalidEmails = $invalids;
                    $message->comment .= "($invalidEmail set to invalidEmails for $modelClass {$object->id})\n";
                }

                if ($removeInvalidEmail) {
                    // ** Remove the address from the record's email address field **
                    $filtered = array_filter((array) $object->$emailField, function ($v) use ($invalidEmail) {
                        return strcasecmp($v, $invalidEmail) == 0 ? false : true;
                    });

                    $object->$emailField = is_array($object->$emailField) ? $filtered : implode(',', $filtered);

                    $message->comment .= "($invalidEmail removed from $emailField for $modelClass {$object->id})\n";
                }

                $object->save();
            }
        }
    }

    // currently scale of -100 (spammy) to 100 (not spam)

    public static function getSenderTrust(MailMessage $message)
    {
        // if ($listingID) return 21; because the hostelid may just come from someone with the same domain it was causing too many spam mails to be marked as this. (we no longer pass hostelID to this function either now that it isn't used here)
        if (strpos($message->headers, 'X-Mailer: PHP (ip: ') !== false) {
            return 10;
        }

        $rejectAttachmentExtensions = ['.pif', '.com', '.exe', '.bat', '.scr', '.temp', '.zip'];

        foreach (self::$attachments as $attachment) {
            foreach ($rejectAttachmentExtensions as $rejectExtension) {
                if (Str::endsWith($attachment['filename'], $rejectExtension)) {
                    return -20;
                } // suspicious attachment filename extension
            }
        }

        $addresses = $message->recipientAddresses;
        $addresses[] = $message->senderAddress;

        foreach ($addresses as $address) {
            if ($address == '' || strpos($address, '@hostelz.com') !== false) {
                continue;
            } // ignore hostelz addresses

            // Look for any past emails to/from them that seem to be ok...
            $hasExistingOkEmails = MailMessage::forRecipientOrBySenderEmail($address)->
            where('status', '!=', 'new')->where('spamicity', '<', 100)->value('id');

            if ($hasExistingOkEmails) {
                // See if we have ever marked one of their emails as spam...
                if (MailMessage::forRecipientOrBySenderEmail($address)->
                where('status', '!=', 'new')->where('spamicity', '>=', 100)->value('id')) {
                    return 0;
                } // Some of their emails are ok, some are spam... so 0 for unknown.
                else {
                    return 20;
                } // known email address
            }

            if (User::where('username', $address)->value('id')) {
                return 10;
            } // known web user
            if (Rating::where('email', $address)->value('id')) {
                return 5;
            } // known comment poster
        }

        if ($message->senderAddress == '') {
            return -10;
        } // no from email address
        if (! $message->recipientAddresses) {
            return -5;
        } // no to email address
        if ($message->senderAddress == $message->recipientAddresses) {
            return -15;
        } // typical case for spam

        return 0; // unknown
    }

    private static function handleImportSystemEmail($message)
    {
        $output = '';
        foreach (ImportSystems::all('specialCallbacks', 'emailFilter') as $systemName => $system) {
            if (call_user_func([$system->getSystemService(), 'emailFilter'], $message)) {
                $output .= "[$systemName] ";

                break;
            }
        }

        return $output;

        /*
        if (strpos($message->subject, 'New Reservation for ')===0 && (strpos($message->sender, 'reservations@hostelsclub.com')!==false || strpos($message->sender, 'reservations@hotelsarea.com')!==false)) {
            return true; // the booking was already recorded when it was made, so we just ignore the emails

    		$system='Hostelsclub';
    		// (0 mailID makes it not save attachments, just gets messageText)
    		parseMessage(0,$imap,$overview->msgno); // sets $messageText and $messageHTML globals
    		// if (!strstr($messageText,'Affiliate Commission: ')) return true;
    		preg_match('`You have just received a new reservation for (.*)\nReservation No. (.*)\n.*Name: (.*)\n.*Email: (.*)\n.*10% Deposit: (.*) EUR`sU',$messageText,$matches);
    		$hostelName = trim($matches[1]);
    		$bookingID = trim($matches[2]);
    		$name = trim($matches[3]);
    		$email = trim($matches[4]);
    		$hostelCity = '';
    		$hostelCountry = '';
    		$commission = trim($matches[5])*0.5*exchangeRate('EUR','USD');
    		$intCode = 0;
    	}
    	*/
        /*
    	if (!$bookingID || !$commission || ($hostelName=='' && $intCode==0)) return false; // maybe a problem parsing the email

    	if ($intCode)
    		$hostelID = dbGetOne("SELECT hostelID FROM imported WHERE system='$system' AND intCode=$intCode AND status='active'");
    	else
    		$hostelID = dbGetOne("SELECT hostelID FROM imported WHERE system='$system' AND status='active' AND name=".dbQuote($hostelName).($hostelCity?(" AND city=".dbQuote($hostelCity)):''));
    	if (!$hostelID) $hostelID = 0;

        $userID = dbGetOne("SELECT id FROM users WHERE username=".dbQuote($email)." AND status='ok'");
        if (!$userID) $userID = 0;
        */
        /* Note we no longer have a name field in bookings (now firstName/lastName)
    	dbQuery("INSERT INTO bookings (userID,bookingTime,system,name,email,listingID,bookingID,commission,messageText)
    		VALUES ($userID, '".date('Y-m-d H:i:s',strtotime($overview->date))."','$system',".dbQuote($name).",".dbQuote($email).",$hostelID,".
    			dbQuote($bookingID).",$commission,".dbQuote(trim($messageText)).')');
    	echo "($system $commission) ";
    	return true;
        */
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'tenMinute':
                $output .= self::fetchNew();

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    /* not needed any more?

    function getSpamReportEmail(&$imap,&$overview)
    {
    	global $messageHTML, $messageText, $attachments_var;

    	if (strpos($overview->from, 'staff@hotmail.com')===false || strpos($overview->subject, 'complaint about message from')===false) return false;

    	$attachments_var = [ ];
    	parseMessage(0,$imap,$overview->msgno, 2000); // sets $messageText and $messageHTML globals

    	$fromEmail = '';
    	if (count($attachments_var)>0) {
    		foreach ($attachments_var as $attach) {
    			if ($attach['mimeType'] == 'MESSAGE/RFC822')
    				if (preg_match('`From: (.*)`i',$attach['data'], $matches)) {
    					$fromEmail = trim($matches[1]);
    					break;
    			}
    		}
    	}
    	if ($fromEmail == '') return false;

    	sendMail('admin@hostelz.com', $fromEmail, 'orrd101@gmail.com', 'Hotmail Spam Report', "This is a spam complaint from Hotmail.  This user's email must be removed from any mailing lists so they aren't contacted again.\n\n$attach[data]", false, false, false);
    	return true;
    }
    */
}
