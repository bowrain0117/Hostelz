<?php

return [
    'forms' => [
        'fieldLabel' => [
            'id' => 'ID',
            'status' => 'Status',
            'senderOrRecipientEmail' => 'Sender or Recipient Email', // special, just used for searching
            'sender' => 'Sender',
            'senderAddress' => 'Sender Address',
            'ipAddress' => 'IP Address',
            'recipient' => 'Recipient',
            'cc' => 'CC',
            'bcc' => 'BCC',
            'recipientAddresses' => 'Recipient Addresses',
            'transmitTime' => 'Transmit Time',
            'reminderDate' => 'Reminder Date',
            'subject' => 'Subject',
            'headers' => 'Headers',
            'bodyText' => 'Body Text',
            'comment' => 'Notes',
            'userID' => 'User',
            'listingID' => 'Listing',
            'senderTrust' => 'Sender Trust',
            'spamicity' => 'Spamicity',
        ],
        'checkbox' => [
            'spamFilter' => 'Spam Filter',
        ],
        'options' => [
            'status' => [
                'new' => 'New',
                'archived' => 'Archived',
                'hold' => 'Hold',
                'outgoingQueue' => 'Outgoing Queue',
                'outgoing' => 'Outgoing',
            ],
        ],
    ],
];
