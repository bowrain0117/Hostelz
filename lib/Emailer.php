<?php

namespace Lib;

use App\Models\User;
use Mail;

class Emailer
{
    private $pretending;

    private $alwaysTo; // send all emails to this email address (for testing)

    public function __construct($pretending = false, $alwaysTo = false)
    {
        $this->pretending = $pretending;
        $this->alwaysTo = $alwaysTo;
    }

    /*
    Parameters:

        $to/$from/$cc/$bcc ->
            Can be any of: User ID, User object, email address, email address line ("Joe <foo@example.com>, foo2@example2.com"), or [ 'name' => ..., 'email' => ... ]
        $from - See above, or false to send as default app email address. (TO DO)
        $subject
        $view - If string, assumed to be the view name of an HTML view (automatically stripped to make text version), or [ html, text ] array of two separate views, or [ 'text' => textView ] for text-only.
        $data - Data passed to the view(s).
        $logCategory -
        $useReplyToInsteadOfFrom - Use the default app email address as the From address, and use the supplied $from as the Reply To instead.
            (Can be needed to avoid problems with some spam filters that don't allow third-party email senders, etc.)
        $callback - Callback function to add more stuff to the message.
    */

    public static function send($to, $subject, $view, $data = [], $from = false, $logCategory = 'system', $logUserID = 0, $cc = null, $bcc = null, $useReplyToInsteadOfFrom = false, $callback = false)
    {
        //  todo: temp
        $_this = new self;
        if ($_this->alwaysTo !== false) {
            $to = $_this->alwaysTo;
        } // used for testing
        $toAddresses = $_this->recipientParameterToNameAndEmailArray($to);
        if (!$toAddresses) {
            logWarning("Invalid to address '$to' for Emailer::send().");

            return false;
        }

        if (!is_array($view)) {
            $plainText = trim(html_entity_decode(strip_tags(
                preg_replace('/[\n\r] +/m', "\n", view($view, $data)->with('plainText', true)->render()) // (trims whitespace from each line)
            )));
            $view = ['html' => $view]; // the $view string is the name of the HTML version.
        } else {
            $plainText = false;
        }

        if ((is_string($to) && $_this->mailParameterHasIllegalNewlines($to)) ||
            (is_string($from) && $_this->mailParameterHasIllegalNewlines($from)) ||
            (is_string($cc) && $_this->mailParameterHasIllegalNewlines($cc)) ||
            (is_string($bcc) && $_this->mailParameterHasIllegalNewlines($bcc)) ||
            $_this->mailParameterHasIllegalNewlines($subject)
        ) {
            return false;
        }

        $toEmails = [];
        foreach ($toAddresses as $address) {
            if (!filter_var($address['email'], FILTER_VALIDATE_EMAIL)) {
                logError("Invalid email address '$address[email]'.");
                continue;
            }
            $toEmails[] = $address['email'];
        }
        if (!$toEmails) {
            return false;
        } // probably means the email address was invalid

        // if (! $_this->pretending) {
        //     Mail::send($view, $data,
        //         function ($message) use ($toAddresses, $subject, $from, $cc, $bcc, $useReplyToInsteadOfFrom, $callback, $plainText, $_this) {
        //             foreach ($toAddresses as $address) {
        //                 if (! filter_var($address['email'], FILTER_VALIDATE_EMAIL)) {
        //                     continue;
        //                 }
        //                 $message->to($address['email'], $address['name']);
        //             }

        //             if ($from !== false) {
        //                 $addresses = $_this->recipientParameterToNameAndEmailArray($from);
        //                 foreach ($addresses as $address) {
        //                     if ($useReplyToInsteadOfFrom) {
        //                         $message->replyTo($address['email'], $address['name']);
        //                     } else {
        //                         $message->from($address['email'], $address['name']);
        //                     }
        //                 }
        //             }

        //             if ($cc !== '') {
        //                 $addresses = $_this->recipientParameterToNameAndEmailArray($cc);
        //                 foreach ($addresses as $address) {
        //                     $message->cc($address['email'], $address['name']);
        //                 }
        //             }

        //             if ($bcc !== '') {
        //                 $addresses = $_this->recipientParameterToNameAndEmailArray($bcc);
        //                 foreach ($addresses as $address) {
        //                     $message->bcc($address['email'], $address['name']);
        //                 }
        //             }

        //             $message->subject($subject);
        //             if ($plainText !== false) {
        //                 $message->text($plainText);
        //             }

        //             if ($callback instanceof \Closure) {
        //                 return call_user_func($callback, $message);
        //             }
        //         }
        //     );
        // }

        if (!empty($logCategory)) {
            // Note:  The subjectID and the userID are both $logUserID (not sure if that's the best way to do it or not).
            EventLog::log(
                $logCategory,
                'Emailer:send',
                $logUserID ? 'User' : '',
                $logUserID,
                implode(', ', $toEmails),
                !empty($view['html']) ? $view['html'] : $subject,
                $logUserID
            );
        }

        return true;
    }

    // $parameter an be one of or an array of any of these:
    //      User ID, User object, email address, email address line ("Joe <foo@example.com>, foo2@example2.com"), or [ 'name' => ..., 'email' => ... ]
    //      Which gets converted to a [ 'name' => ..., 'email' => ... ] array.

    public function recipientParameterToNameAndEmailArray($value)
    {
        if (!is_array($value) || array_key_exists('name', $value)) {
            $value = [$value];
        }

        $return = [];
        foreach ($value as $v) {
            if (is_a($v, \App\Models\User::class)) {
                // User object
                $return[] = $v->getOutgoingEmailInfo();
            } elseif (is_array($v) && array_key_exists('name', $v) && array_key_exists('email', $v)) {
                // name/email array
                $return[] = $v;
            } elseif ((int) $v && (int) $v === $v) {
                // Is just a number, so use it as a User ID
                $user = User::findOrFail($v);
                $return[] = $user->getOutgoingEmailInfo();
            } elseif (filter_var($v, FILTER_VALIDATE_EMAIL)) {
                // Is just an email address
                $return[] = ['name' => '', 'email' => $v];
            } else {
                $return = array_merge($return, self::parseAddressLine($v));
            }
        }

        return $return;
    }

    public function removeDuplicateEmailsFromNameEmailArray($array)
    {
        $return = [];
        foreach ($array as $k => $v) {
            $existingMatch = keysOfArrayWithMatchingElements($return, 'email', $v['email']);
            if (!$existingMatch) {
                $return[] = $v;
            }
        }

        return $return;
    }

    // Simple method to extract just one email address from a string

    public function extractEmailAddress($s)
    {
        preg_match("/([a-zA-Z0-9_\-\.\=\+]+@[a-zA-Z0-9_\-\.]+\.[a-zA-Z]{2,5})/", $s, $matches);

        return $matches ? $matches[1] : '';
    }

    // $emailsArray may be an array or string with multiple email addresses on the line

    public function extractEmailAddresses($emailsArray)
    {
        $return = [];
        foreach ((array) $emailsArray as $addressLine) {
            $parsed = self::parseAddressLine($addressLine);
            foreach ($parsed as $parsedAddress) {
                $return[] = $parsedAddress['email'];
            }
        }

        // We convert them all the lowercase for easier comparisons, etc.
        // Email addresses are pretty much universally case insensitive.
        return array_unique(array_map('mb_strtolower', $return));
    }

    // Extracts the email addresses and *names* (ex. "Joe <foo@example.com>") -> [ [ 'name' => 'Joe', 'email' => 'foo@example.com' ] ]

    public static function parseAddressLine($addressLine)
    {
        $mailParser = new Mail_RFC822;
        $parsedAddresses = $mailParser->parseAddressList($addressLine, null, false);
        if (!$parsedAddresses) {
            return [];
        }

        $return = [];
        foreach ($parsedAddresses as $parse) {
            if ($parse->mailbox === '' || $parse->host === '') {
                continue;
            }
            $return[] = [
                'name' => trim($parse->personal, ' "'),
                'email' => mb_strtolower($parse->mailbox . '@' . $parse->host),
            ];
        }

        return $return;
    }

    // Check for injection exploits
    // Newlines are sometimes inserted into mail to/from/subject strings to improperly add falsified mail headers.
    // (sendMail() would strip them out, anyway, but even better is if we just exit and don't send it at all.)

    public function mailParameterHasIllegalNewlines($s)
    {
        if (!str_contains($s, "\n") && !str_contains($s, "\r")) {
            return false;
        }
        logWarning("Mail string '$s' contains an illegal newline.");

        return true;
    }
}
