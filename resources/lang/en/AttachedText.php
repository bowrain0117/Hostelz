<?php

return [
    'forms' => [
        'fieldLabel' => [
            'id' => 'ID',
            'subject_name' => 'Subject Name', // not an actual database field
            'subjectType' => 'Subject Type',
            'subjectID' => 'Subject ID',
            'subjectString' => 'Subject String',
            'type' => 'Type',
            'source' => 'Source',
            'userID' => 'User',
            'language' => 'Language',
            'status' => 'Status',
            'score' => 'Score',
            'lastUpdate' => 'Last Update',
            'plagiarismCheckDate' => 'Plagiarism Check Date',
            'plagiarismPercent' => 'Plagiarism Percent',
            'plagiarismInfo' => 'Plagiarism Info',
            'dataBeforeEditing' => 'Original Before Editing',
            'data' => 'Data/Text',
            'notes' => 'Staff Notes',
            'comments' => 'Comments',
            'newComment' => 'Reply',
        ],
        'placeholder' => [
            'newComment' => ['edit' => '[Your comment here.]'],
        ],
        'options' => [
            'status' => [
                'draft' => 'Draft',
                'submitted' => 'Submitted',
                'ok' => 'OK',
                'denied' => 'Denied',
                'returned' => 'Returned',
                'flagged' => 'Flagged',
            ],
        ],
    ],
];
