<?php

return [
    'title' => 'Tickets',
    'subtitle' => 'Get in touch with support. Your previous tickets and their status are here.',
    'new' => 'New ticket',

    'status' => [
        'open' => 'Waiting for support',
        'answered' => 'Answered',
        'closed' => 'Closed',
    ],
    'status_hint' => [
        'open' => 'Support will reply soon.',
        'answered' => 'Support replied; it’s your turn.',
        'closed' => 'This ticket is closed.',
    ],
    'filters' => ['all' => 'All', 'open' => 'Waiting', 'answered' => 'Answered', 'closed' => 'Closed'],
    'stats' => ['open' => 'Waiting for support', 'answered' => 'Waiting for you', 'closed' => 'Closed'],

    'departments' => [
        'technical' => 'Technical problem',
        'payment' => 'Payment & subscription',
        'content' => 'Learning content',
        'account' => 'Account',
        'suggestion' => 'Suggestion & feedback',
    ],

    'list' => [
        'search' => 'Search by subject or ticket code',
        'search_label' => 'Search',
        'clear' => 'Clear search',
        'results' => ':n tickets',
        'empty_title' => 'No tickets yet',
        'empty_text' => 'If you have a problem or a question, send a new ticket to support.',
        'empty_filter' => 'No tickets match this filter.',
        'unread' => 'New reply',
        'you' => 'You',
        'support' => 'Support',
    ],

    'pane' => [
        'title' => 'Select a ticket',
        'text' => 'The conversation opens here. You can reply, attach images or close the ticket.',
    ],

    'detail' => [
        'back' => 'All tickets',
        'created' => 'Opened :date',
        'close' => 'Close ticket',
        'reopen' => 'Reopen',
        'closed_note' => 'This ticket is closed. If you still have the problem, reopen it.',
        'you' => 'You',
        'support' => 'Support',
        'attachment' => 'Attached image',
        'reply' => 'Your reply',
        'reply_ph' => 'Write your reply…',
        'send' => 'Send',
        'sending' => 'Sending…',
        'attach' => 'Attach image',
        'hint' => 'Ctrl + Enter to send',
        'loading' => 'Loading conversation…',
        'load_failed' => 'Could not load the conversation.',
        'retry' => 'Try again',
        'messages' => 'Ticket messages',
    ],

    'form' => [
        'heading' => 'New ticket',
        'text' => 'Describe the problem fully so support can answer faster.',
        'back' => 'Back',
        'dept' => 'Department',
        'subject' => 'Subject',
        'subject_ph' => 'e.g. The repeat step audio does not play',
        'message' => 'Description',
        'message_ph' => 'What happened? Which page or step were you on? If you saw an error, write its text.',
        'attach' => 'Attach images (optional)',
        'attach_hint' => 'Up to :n images, :mb MB each',
        'add_file' => 'Add image',
        'remove_file' => 'Remove :name',
        'submit' => 'Send ticket',
        'submitting' => 'Sending…',
        'cancel' => 'Cancel',
        'counter' => ':n of :max',
        'tips_title' => 'For a faster answer',
        'tips' => [
            'Write only one topic per ticket.',
            'For technical problems, tell us your phone and browser.',
            'For payment problems, include the tracking code.',
        ],
    ],

    'errors' => [
        'dept' => 'Choose a department.',
        'subject_min' => 'The subject needs at least 3 characters.',
        'subject_max' => 'The subject can be at most 120 characters.',
        'message_min' => 'The description needs at least 10 characters.',
        'message_max' => 'The description can be at most 3000 characters.',
        'reply_empty' => 'Write your message.',
        'file_type' => 'Only images (JPG, PNG, WebP) can be attached.',
        'file_size' => 'Each image can be at most :mb MB.',
        'file_count' => 'You can attach at most :n images.',
        'network' => 'Could not reach the server. Please try again.',
    ],

    'confirm_close' => [
        'title' => 'Close this ticket?',
        'text' => 'After closing you can’t reply, but you can reopen it any time.',
        'ok' => 'Close ticket',
        'back' => 'Cancel',
    ],

    'toast' => [
        'created' => 'Your ticket was sent; support will reply soon.',
        'replied' => 'Your reply was sent.',
        'closed' => 'Ticket closed.',
        'reopened' => 'Ticket reopened.',
        'failed' => 'Something went wrong. Please try again.',
    ],

    'time' => [
        'now' => 'Just now',
        'minutes' => ':n min ago',
        'hours' => ':n h ago',
        'yesterday' => 'Yesterday',
        'days' => ':n days ago',
    ],
];
