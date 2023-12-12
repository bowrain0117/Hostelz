<?php

namespace App\Models;

use App\Helpers\EventLog;
use App\Models\Listing\Listing;
use Carbon\Carbon;
use Exception;
use Illuminate\Support\Str;
use Lib\BaseModel;
use Lib\BayesianFilter;
use Lib\Emailer;
use Lib\WebsiteTools;

class MailMessage extends BaseModel
{
    protected $table = 'mail';

    public static $staticTable = 'mail';

    protected $guarded = [];

    public $timestamps = false; // we'll manage our own timestamps

    protected $casts = [
        'transmitTime' => 'datetime',
    ];

    public static $statusOptions = ['new', 'archived', 'hold', 'outgoingQueue', 'outgoing'];

    public static $spamSampleLimit = 1500; // look at first x number of characters only

    // weight is relative importance to other sourceTypes
    public static $spamSourceTypeWeights = [
        // split by any non-word initial characters, whitespace (and any surrounding non-word characters), any trailing non-word characters.
        // see http://www.php.net/manual/en/regexp.reference.unicode.php
        'subject' => ['weight' => 9, 'reasonableNumOfHits' => 7, 'tokenRegex' => '/(^\W+|\W*\s\W*|\p{Cc}|\p{Cf}|\p{Cn}|\p{Co}|\p{Z}|;|!|\W+$)/u'],
        'sender' => ['weight' => 6, 'reasonableNumOfHits' => 3, 'tokenRegex' => ''],
        'recipient' => ['weight' => 2, 'reasonableNumOfHits' => 3, 'tokenRegex' => ''],
        'headers' => ['weight' => 3, 'reasonableNumOfHits' => 1,
            // (not sure why we aren't using headers... maybe too many unique tokens are created from the header IP addresses and things?) |Date\:.*\v|Message\-ID\:.*\v|\;.*\v|boundary\=\".*\v
            'tokenRegex' => '/(\p{C}Date\:\P{C}+\p{C}|\p{C}From\:\P{C}+\p{C}|\p{C}Subject\:\P{C}+\p{C}|\p{C}Message-ID\:\P{C}+\p{C}|\p{C}for \P{C}+\p{C}|ESMTP id \P{C}+\p{C}| |\p{C})/u', ],
        'bodyText' => ['weight' => 12, 'reasonableNumOfHits' => 20, 'tokenRegex' => '/(^\W+|\W*\s\W*|\p{Cc}|\p{Cf}|\p{Cn}|\p{Co}|\p{Z}|;|!|\W+$|<|>)/u'],
        'attachmentFilenames' => ['weight' => 6, 'reasonableNumOfHits' => 2, 'tokenRegex' => '/\s*,\s*/u'],
        'attachmentMimeType' => ['weight' => 15, 'reasonableNumOfHits' => 5, 'tokenRegex' => '/\s*,\s*/u'],
    ];

    protected static function staticDataTypes()
    {
        static $dataTypes = [];

        if (! $dataTypes) {
            $dataTypes = [
                'recipientAddresses' => new \Lib\dataTypes\StringSetDataType(['tableName' => self::$staticTable, 'fieldName' => 'recipientAddresses']),
            ];
        }

        return $dataTypes;
    }

    public function delete(): void
    {
        $this->deleteAttachments();
        parent::delete();
    }

    public static function fieldInfo($purpose = null)
    {
        switch ($purpose) {
            case null:
            case 'adminEdit':
            case 'staffEdit':
                $fieldInfos = [
                    'id' => ['isPrimaryKey' => true, 'editType' => 'display'],
                    'status' => ['type' => 'select', 'options' => self::$statusOptions, 'optionsDisplay' => 'translate'],
                    // Special field, used only for searching
                    'senderOrRecipientEmail' => ['type' => 'ignore', 'searchType' => 'text',
                        'searchQuery' => function ($formHandler, $query, $value) {
                            if ($value == '') {
                                return $query;
                            }

                            return $query->forRecipientOrBySenderEmail($value);
                        },
                    ],
                    'sender' => ['type' => 'textarea', 'rows' => 2],
                    'senderAddress' => ['maxLength' => 255],
                    'recipient' => [],
                    'recipientAddresses' => ['dataTypeObject' => self::staticDataTypes()['recipientAddresses'], 'editType' => 'multi', 'maxLength' => 255, 'validation' => 'emailList'],
                    'cc' => [],
                    'bcc' => ['maxLength' => 255],
                    'ipAddress' => ['maxLength' => 50],
                    'transmitTime' => ['searchType' => 'datePicker', 'dataType' => 'Lib\dataTypes\DateTimeDataType'],
                    'reminderDate' => ['type' => 'datePicker', 'dataType' => 'Lib\dataTypes\NullableDateDataType', 'dataAccessMethod' => 'dataType'],
                    'subject' => ['maxLength' => 255],
                    'headers' => ['type' => 'textarea'],
                    'bodyText' => ['type' => 'textarea'],
                    'comment' => ['type' => 'textarea'],
                    'userID' => [
                        'type' => $purpose == 'staffEdit' ? 'ignore' : '',
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->user ? $model->user->username : $model->userID;
                        }, ],
                    'listingID' => [
                        'dataType' => 'Lib\dataTypes\NumericDataType',
                        'getValue' => function ($formHandler, $model) {
                            return $formHandler->isListMode() && $model->listing ? $model->listing->fullDisplayName() : $model->listingID;
                        }, ],
                    'senderTrust' => ['displayType' => 'ignore', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'spamicity' => ['displayType' => 'ignore', 'dataType' => 'Lib\dataTypes\NumericDataType', 'sanitize' => 'int'],
                    'spamFilter' => ['type' => 'ignore', 'searchType' => 'checkbox', 'value' => true, 'fieldLabelText' => ' ',
                        'searchQuery' => function ($formHandler, $query, $value) use ($purpose) {
                            if ($value) {
                                return $query->where('comment', 'NOT LIKE', '%(bounce detected%removed from%')-> // so we don't filter anything with a comment, including bounce emails.
                                where(function ($query) use ($purpose): void {
                                    $query->where(function ($query) use ($purpose): void {
                                        $query->where('senderTrust', '>', 0)->where('spamicity', '<=', $purpose == 'adminEdit' ? 56 : 52);
                                    })->orWhere(function ($query) use ($purpose): void {
                                        $query->where('senderTrust', '<=', 0)->where('spamicity', '<=', $purpose == 'adminEdit' ? 55 : 51);
                                    });
                                });
                            }
                        },
                    ],
                ];

                break;

            default:
                throw new Exception("Unknown purpose '$purpose'.");
        }

        return $fieldInfos;
    }

    /* Static */

    public static function createOutgoing($attributes, $sendingUser = null, $transmitDelayMinutes = 10, $businessHours = false, $logEvent = true)
    {
        // Transmit Time

        if (! isset($attributes['transmitTime'])) {
            $time = Carbon::now();
            if ($transmitDelayMinutes) {
                $time->addMinutes($transmitDelayMinutes);
            }

            if ($businessHours && (/* $time->isWeekend() || */ $time->hour < 7 || $time->hour >= 20)) {
                $time->hour = 8;
                if (! $time->isFuture()) {
                    $time->addDays(1);
                } // unless it's before 8 AM, have to do it tomorrow.
                /* (decided to let it send businessHour emails on weekend days)
    			if (!$time->isWeekday()) {
    			    $time->modify('next weekday'); // go to the next weekday (also clears the hour/minute... PHP bug?)
    			    $time->hour = 8; // set to 8 AM again because 'next weekday' also clears the hour/minute. (PHP bug?)
    			}
    			*/
            }
            $attributes['transmitTime'] = $time;
        }

        // Create the message

        // (Any of these attributes can be overrided by values passed to this function in $attributes.)
        $attributes = array_merge([
            'status' => 'outgoingQueue',
        ], $attributes);

        // Sending User
        if (! $sendingUser) {
            $sendingUser = auth()->user();
        }
        $emailInfo = $sendingUser->getOutgoingEmailInfo();
        $attributes = array_merge([
            'sender' => trim($emailInfo['name'] . ' <' . $emailInfo['email'] . '>'),
            'senderAddress' => $sendingUser->getEmailAddress(),
            'userID' => $sendingUser->id,
        ], $attributes);

        $new = new static($attributes);

        if (! $new->recipientAddresses) {
            $new->setRecipientAddresses();
        }
        if ($new->senderAddress == '') {
            $new->setSenderAddress();
        }
        if (! $new->listingID) {
            $new->listingID = $new->determineListingIDs(true);
        }
        if ($new->cc === null) {
            $new->cc = '';
        }
        if ($new->bcc === null) {
            $new->bcc = '';
        }

        $new->save();

        if ($logEvent) {
            EventLog::log('staff', 'sent', 'MailMessage', $new->id, $new->subject, implode(', ', $new->recipientAddresses));
        }

        return $new;
    }

    public static function sendQueuedMessages()
    {
        set_time_limit(10 * 60); // Note: This also resets the timeout timer.

        $output = 'Send Queued Messages: ';
        $messages = self::where('status', 'outgoingQueue')->where('transmitTime', '<=', Carbon::now())->limit(50)->get();
        foreach ($messages as $message) {
            $output .= $message->id . ' ';
            $message->sendNow();
        }

        return $output . "\n";
    }

    public static function maintenanceTasks($timePeriod)
    {
        $output = '';

        switch ($timePeriod) {
            case 'tenMinute':
                $output .= self::sendQueuedMessages();

                break;

            case 'daily':
                set_time_limit(30 * 60); // Note: This also resets the timeout timer.

                $output .= 'Delete old spam emails: ';
                $ids = self::where('status', 'new')->where('spamicity', '>=', 60)->where('senderTrust', '<=', 0)->where('transmitTime', '<', Carbon::now()->subDays(200))->limit(5000)->pluck('id');
                foreach ($ids as $id) {
                    $message = self::find($id);
                    if (! $message) {
                        continue;
                    }
                    $output .= "[$message->id $message->transmitTime] ";
                    $message->delete();
                }
                // Delete the really spammy ones sooner (there were too many spam emails still in the system)
                $ids = self::where('status', 'new')->where('spamicity', '>=', 70)->where('senderTrust', '<=', 0)->where('transmitTime', '<', Carbon::now()->subDays(30))->limit(5000)->pluck('id');
                foreach ($ids as $id) {
                    $message = self::find($id);
                    if (! $message) {
                        continue;
                    }
                    $output .= "[$message->id $message->transmitTime] ";
                    $message->delete();
                }
                $output .= "\n";

                break;

            case 'weekly':
                // (this only does anything if a staff user's account was disabled in the past week)
                $output .= 'Move new emails in inactive mailboxes to the default user ID: ';
                $activeMailboxUserIDs = User::arrayMapOfIncomingEmailAddressesToUserIDs();
                $defaultUserID = $activeMailboxUserIDs['default'];
                $messages = self::where('status', 'new')->whereNotIn('userID', $activeMailboxUserIDs)->get();
                foreach ($messages as $message) {
                    $output .= "$message->id (was user $message->userID) ";
                    $message->userID = $defaultUserID;
                    $message->save();
                }
                $output .= "\n";

                set_time_limit(2 * 60 * 60); // Note: This also resets the timeout timer.

                $output .= 'Re-evaluate borderline spam emails: ';
                $ids = self::where('status', 'new')->whereBetween('spamicity', [40, 60])->limit(5000)->pluck('id');
                foreach ($ids as $id) {
                    $message = self::find($id);
                    if (! $message) {
                        continue;
                    }
                    $message->spamicityEvaluate();
                    $message->save();
                    $output .= "$id ($message->spamicity%) ";
                }
                $output .= "\n";

                break;

            default:
                throw new Exception("Unknown time period '$timePeriod'.");
        }

        return $output;
    }

    public static function quoteText($text, $limitLineCount = false, $wordWrapSize = 75)
    {
        $text = explode("\n", wordwrap($text, $wordWrapSize));
        array_walk($text, function (&$line, $key): void {
            $line = '> ' . $line;
        });
        if ($limitLineCount) {
            $text = array_slice($text, 0, $limitLineCount);
        }

        return implode("\n", $text);
    }

    /* Accessors & Mutators */

    /* Scopes */

    // $emails can be one email or an array to match any

    public function scopeForRecipientEmail($query, $emails)
    {
        return self::staticDataTypes()['recipientAddresses']->searchQuery($query, (array) $emails, 'matchAny');
    }

    public function scopeForRecipientEmailSubstring($query, $email)
    {
        return self::staticDataTypes()['recipientAddresses']->searchQuery($query, $email, 'substring');
    }

    // $emails can be one email or an array to match any

    public function scopeForRecipientOrBySenderEmail($query, $emails)
    {
        $emails = (array) $emails;
        if (! $emails) {
            return $query;
        }

        return $query->where(function ($query) use ($emails): void {
            $this->scopeForRecipientEmail($query, $emails);
            $query->orWhereIn('senderAddress', $emails);
        });
    }

    /* Misc */

    public function setRecipientAddresses(): void
    {
        $this->recipientAddresses = (new \Lib\Emailer())->extractEmailAddresses([$this->recipient, $this->cc, $this->bcc]);
    }

    public function setSenderAddress(): void
    {
        $this->senderAddress = (new \Lib\Emailer())->extractEmailAddress($this->sender);
    }

    public function getSendersName()
    {
        $parsed = (new \Lib\Emailer())->parseAddressLine($this->sender);
        if (! $parsed || $parsed[0]['name'] == '') {
            return '';
        }

        return $parsed[0]['name'];
    }

    public function getSendersFirstName()
    {
        $name = $this->getSendersName();
        if ($name == '') {
            return '';
        }
        $parts = explode(' ', $name);

        return $parts[0];
    }

    public function deleteAttachments(): void
    {
        // Have to do each one individually so the attachment's delete handler gets called to delete the actual files.
        foreach ($this->attachments as $attachment) {
            $attachment->queuedDelete();
        }
    }

    private function compileDataForSpamicity()
    {
        $data = [];

        foreach (self::$spamSourceTypeWeights as $fieldName => $sourceParams) {
            switch ($fieldName) {
                case 'attachmentFilenames':
                    $data[$fieldName] = trim(implode(', ', array_diff($this->attachments->pluck('filename')->all(), ['untitled'])), ', ');

                    break;
                case 'attachmentMimeType':
                    $data[$fieldName] = trim(implode(', ', $this->attachments->pluck('mimeType')->all()), ', ');

                    break;
                default:
                    $data[$fieldName] = is_array($this->$fieldName) ? trim(implode(', ', $this->$fieldName), ', ') : $this->$fieldName;
            }

            $data[$fieldName] = substr($data[$fieldName], 0, self::$spamSampleLimit); // self::$spamSampleLimit size
        }

        return $data;
    }

    public function spamicityEvaluate()
    {
        $bayFilter = new BayesianFilter();
        $bayFilter->tokenMinimumSignificance = 0.12;

        $output = '';
        $results = $bayFilter->evaluateMultipleFields($this->compileDataForSpamicity(), self::$spamSourceTypeWeights, 'mail-', $output);

        if (! $results) {
            logWarning("No results for mail {$this->id}."); // this might happen if none of the tokens were recognized?

            return;
        }

        $spamicity = round($results['spam'] * 100);

        $this->spamicity = $spamicity;

        return $output . "<br><b>spamicity for entire mail: $spamicity%</b><br><br>";
    }

    public function spamicityTrain($isSpam): void
    {
        $bayFilter = new \Lib\BayesianFilter();
        $bayFilter->trainMultipleFields($this->compileDataForSpamicity(), self::$spamSourceTypeWeights, 'mail-', $isSpam);

        $this->spamicity = ($isSpam ? 101 : -1);
        $this->senderTrust = ($isSpam ? 0 : 100);
    }

    public function getAllEmailAddresses()
    {
        $result = $this->recipientAddresses;
        $result[] = $this->senderAddress;

        return array_unique(array_filter($result));
    }

    public function getLocalEmailAddresses()
    {
        return array_filter($this->getAllEmailAddresses(), function ($v) {
            return stripos($v, '@hostelz.com');
        });
    }

    public function getNonLocalEmailAddresses()
    {
        return array_filter($this->getAllEmailAddresses(), function ($v) {
            return ! stripos($v, '@hostelz.com');
        });
    }

    public function getNonLocalDomains()
    {
        $domains = [];
        $emailAddresses = $this->getNonLocalEmailAddresses();

        foreach ($emailAddresses as $emailAddress) {
            // The "email address" might be a URL (for incomingLink form post messages)
            if (filter_var($emailAddress, FILTER_VALIDATE_URL)) {
                $domain = WebsiteTools::getRootDomainName($emailAddress);
            } elseif (filter_var($emailAddress, FILTER_VALIDATE_EMAIL)) {
                $temp = explode('@', $emailAddress);
                $domain = array_pop($temp);
            } else {
                // isn't a URL or email address.
                continue;
            }

            if ($domain != '' && ! in_array($domain, $domains)) {
                $domains[] = $domain;
            }
        }

        return $domains;
    }

    // Finds the first user associated with email addresses in this email
    // (other than the user that the message belongs to)

    public function findAssociatedUser()
    {
        $emails = $this->getNonLocalEmailAddresses();
        if ($emails) {
            $user = User::whereIn('username', $emails)->first();
            if ($user) {
                return $user;
            }
        }

        // Special case - For emails sent by our own staff to other staff.
        $emails = $this->getLocalEmailAddresses();
        if ($emails) {
            $user = User::byLocalEmails($emails)->where('id', '!=', $this->userID)->first();
            if ($user) {
                return $user;
            }
        }

        return null;
    }

    public function replyAllAddresses($exceptLocalEmailAddresses = true, $exceptUser = null)
    {
        $addresses = $this->recipientAddresses;
        if ($exceptUser) {
            $addresses = array_diff($addresses, $exceptUser->allLocalEmailAddresses());
        }
        if ($exceptLocalEmailAddresses) {
            $addresses = array_filter($addresses, function ($address) {
                return stripos($address, '@hostelz.com') === false;
            });
        }

        return $addresses;
    }

    public function determineBookingIDs($onlyGetOne = false)
    {
        $result = [];

        // TO DO: Extract the booking ID from quoted confirmation email.

        // Try matching user's bookings

        $user = $this->findAssociatedUser();

        if ($user && $user->bookings->isNotEmpty()) {
            $result = array_merge($result, $user->bookings->pluck('id')->all());
            if ($result && $onlyGetOne) {
                return reset($result);
            }
        }

        // Try finding bookings by the email address

        $emailAddresses = $this->getNonLocalEmailAddresses();

        if ($emailAddresses) {
            $bookingIDs = Booking::whereIn('email', $emailAddresses)->pluck('id')->all();
            if ($bookingIDs) {
                $result = array_merge($result, $bookingIDs);
                if ($result && $onlyGetOne) {
                    return reset($result);
                }
            }
        }

        // Add recent bookings for the selected listingID

        if ($this->listingID && ! $onlyGetOne) {
            $bookingIDs = Booking::where('listingID', $this->listingID)->orderBy('id', 'desc')->limit(10)->pluck('id')->all();
            if ($bookingIDs) {
                $result = array_merge($result, $bookingIDs);
            }
        }

        return array_unique($result);
    }

    public function determineListingIDs($onlyGetOne = false)
    {
        $result = [];

        $emailAddresses = $this->getNonLocalEmailAddresses();
        $nonlocalDomains = $this->getNonLocalDomains();

        // Use the subject if it's a reply to a booking confirmation email

        if (preg_match('/Hostelz.com Booking Confirmation - (.*)$/', $this->subject, $matches)) { // TO DO: Make sure this matches the emails we send currently.
            $listingIDs = Listing::where('name', $matches[1])->areNotListingCorrection()->pluck('id')->all();
            if ($onlyGetOne) {
                return reset($listingIDs);
            }
            $result = array_merge($result, $listingIDs);
        }

        // Try matching user's mgmtListings

        $user = $this->findAssociatedUser();
        if ($user && $user->mgmtListings) {
            $result = array_merge($result, $user->mgmtListings);
            if ($result && $onlyGetOne) {
                return reset($result);
            }
        }

        // Try email addresses in listings

        if ($emailAddresses) {
            $listingIDs = Listing::anyMatchingEmail($emailAddresses)->areNotListingCorrection()->pluck('id')->all();
            if ($listingIDs) {
                if ($onlyGetOne) {
                    return reset($listingIDs);
                }
                $result = array_merge($result, $listingIDs);
            }
        }

        // Listings with websites of matching domain

        if ($nonlocalDomains) {
            $listingIDs = Listing::whereIn('websiteDomain', $nonlocalDomains)->areNotListingCorrection()->pluck('id')->all();
            if ($listingIDs) {
                if ($onlyGetOne) {
                    return reset($listingIDs);
                }
                $result = array_merge($result, $listingIDs);
            }
        }

        // Look for URL in the message

        if (strpos($this->bodyText, 'http://www.hostelz.com/') === 0) {
            if (preg_match("/^http\:\/\/www.hostelz.com\/(l\/..\/)?(hostel|hotel)\/\+?(\d+)\-/s", $this->bodyText, $matches)) {
                $listingID = isset($matches[3]) ? (int) $matches[3] : 0;
                if ($listingID) {
                    if ($onlyGetOne) {
                        return $listingID;
                    }
                    $result[] = $listingID;
                }
            }
        }

        // Other emails from same user -> get listing ID

        if ($emailAddresses) {
            $listingIDs = self::forRecipientOrBySenderEmail($emailAddresses)->where('listingID', '!=', 0)->pluck('listingID')->all();
            if ($listingIDs) {
                if ($onlyGetOne) {
                    return reset($listingIDs);
                }
                $result = array_merge($result, $listingIDs);
            }
        }

        return $onlyGetOne ? reset($result) : array_unique($result);
    }

    public function bodyTextWithoutQuotedText()
    {
        $text = implode("\n", array_filter(explode("\n", $this->bodyText), function ($line) {
            $line = trim($line);

            return Str::startsWith($line, '>') || Str::startsWith($line, '|') || $line == '--' ||
            (Str::startsWith($line, 'On ') && Str::endsWith($line, ' wrote:'))
                ? false : true;
        }));

        return preg_replace('/(\s*\n){3,}/', "\n\n", $text); // removes extra blank lines
    }

    public function quotedBodyText($withoutQuotedText, $limitLineCount = false)
    {
        $text = trim($withoutQuotedText ? $this->bodyTextWithoutQuotedText() : $this->bodyText);
        if ($text == '') {
            return '';
        }

        return self::quoteText($text, $limitLineCount);
    }

    public function getReplySubject()
    {
        return trim(stripos($this->subject, 're:') !== 0 ? 'Re: ' . $this->subject : $this->subject);
    }

    public function addAttachmentByFilename($filename, $filePath): void
    {
        (new MailAttachment(['mailID' => $this->id, 'filename' => $filename]))->saveContentsFromFile($filePath);

        $this->load('attachments'); // update our relationship
    }

    public function isViewableByUser($user)
    {
        // Don't allow non-admins to view admins' mail.
        if ($this->userID && $this->userID != $user->id && ! $user->hasPermission('admin')) {
            if ($this->user->hasPermission('admin')) {
                return false;
            }
        }

        return true;
    }

    public function sendNow(): void
    {
        if ($this->status != 'outgoingQueue') {
            throw new Exception('This function is intended for outgoingQueue messages only.');
        }

        Emailer::send(
            $this->recipient,
            $this->subject,
            ['text' => 'email-plain'],
            ['messageText' => $this->bodyText],
            $this->sender,
            null,
            null,
            $this->cc,
            $this->bcc,
            false,
            function ($message): void {
                foreach ($this->attachments as $attachment) {
                    $message->attachData($attachment->getContents(), $attachment->filename, ['mime' => $attachment->mimeType]);
                }
            }
        );

        $this->status = 'outgoing';
        $this->transmitTime = Carbon::now(); // update the transmitTime to the *actual* transmitTime
        $this->save();
    }

    /* Relationships */

    public function attachments()
    {
        return $this->hasMany(\App\Models\MailAttachment::class, 'mailID');
    }

    public function user()
    {
        return $this->belongsTo(\App\Models\User::class, 'userID');
    }

    public function listing()
    {
        return $this->belongsTo(\App\Models\Listing\Listing::class, 'listingID');
    }
}
