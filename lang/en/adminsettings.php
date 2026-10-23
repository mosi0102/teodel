<?php

return [
    'title' => 'Settings',
    'sub' => 'Site-wide rules and switches; after changing anything, press “Save changes” at the bottom',

    'nav' => ['general' => 'General', 'access' => 'Free access', 'level_test' => 'Level test', 'ai' => 'AI', 'payments' => 'Payment gateway', 'notify' => 'Notifications', 'referral' => 'Referrals'],
    'sec' => [
        'general' => ['t' => 'General', 'd' => 'Site name, default language and maintenance mode'],
        'access' => ['t' => 'Free access', 'd' => 'How much content is open to learners without a subscription'],
        'level_test' => ['t' => 'Level test', 'd' => 'Number of questions and test limits'],
        'ai' => ['t' => 'AI', 'd' => 'The AI buttons in the content-creation forms'],
        'payments' => ['t' => 'Payment gateway', 'd' => 'Gateway for subscription checkout; plan prices live under “Subscriptions & payments”'],
        'notify' => ['t' => 'Admin notifications', 'd' => 'Which events are emailed to the team'],
        'referral' => ['t' => 'Referrals', 'd' => 'Rewards for the referral code'],
    ],

    'f' => [
        'general' => ['site_name_fa' => 'Site name (Persian)', 'site_name_en' => 'Site name (English)', 'support_email' => 'Support email', 'default_locale' => 'Default language', 'maintenance' => 'Maintenance mode', 'maintenance_msg' => 'Maintenance page message'],
        'access' => ['free_songs' => 'Fully free songs', 'free_steps' => 'Free steps on other songs'],
        'level_test' => ['questions_per_section' => 'Questions per section', 'time_limit_min' => 'Time limit (minutes)', 'retake_days' => 'Days before a retake'],
        'ai' => ['enabled' => 'AI buttons enabled', 'model' => 'Model', 'batch_max' => 'Max items in bulk creation', 'key' => 'API key'],
        'payments' => ['gateway' => 'Gateway', 'sandbox' => 'Sandbox mode', 'merchant' => 'Merchant ID'],
        'notify' => ['email' => 'Notification email', 'new_ticket' => 'New ticket', 'new_report' => 'New bug report', 'failed_payment' => 'Failed payment'],
        'referral' => ['inviter_points' => 'Referrer points', 'invitee_days' => 'Gift days for the invited friend'],
    ],
    'h' => [
        'general' => [
            'site_name_fa' => 'Shown in page titles and emails.', 'site_name_en' => 'The English version of the site.', 'support_email' => 'Shown to learners and used as the sender of emails.',
            'default_locale' => 'For a visitor who has not chosen a language yet.',
            'maintenance' => 'The site closes to learners; only admins can sign in.', 'maintenance_msg' => 'Optional, e.g. “Back at 6 pm”.',
        ],
        'access' => ['free_songs' => 'All 8 steps of these songs are open to everyone (0 to 20).', 'free_steps' => 'On every other song, only this many first steps are open without a subscription (1 to 8; 8 = all).'],
        'level_test' => ['questions_per_section' => 'A multiple of 5; the test draws equally from the 5 levels (15 = 3 per level).', 'time_limit_min' => '0 means no limit.', 'retake_days' => '0 means any time.'],
        'ai' => ['enabled' => 'When off, the AI button is hidden in every panel form and content is entered by hand only.', 'model' => 'Model name at the AI service.', 'batch_max' => 'Cap for “Bulk create” of questions and words (5 to 30).', 'key' => 'Write-only; never shown again after saving.'],
        'payments' => ['gateway' => 'The gateway learners are sent to when buying a subscription.', 'sandbox' => 'Payments are not real; for testing only.', 'merchant' => 'Write-only; never shown again after saving.'],
        'notify' => ['email' => 'The notifications below go to this address.', 'new_ticket' => 'When a learner opens a ticket.', 'new_report' => 'When a learner sends a bug report.', 'failed_payment' => 'When a payment fails or stays pending.'],
        'referral' => ['inviter_points' => 'Given to the referrer when the invited friend signs up (0 to 1000).', 'invitee_days' => 'Free subscription for the invited friend (0 to 30 days).'],
    ],
    'locale' => ['fa' => 'فارسی', 'en' => 'English'],
    'gateway' => ['zarinpal' => 'ZarinPal', 'idpay' => 'IDPay', 'zibal' => 'Zibal', 'nextpay' => 'NextPay'],
    'secret' => ['set' => 'Set', 'unset' => 'Not set', 'ph_set' => '•••••••• (type a new value to change)', 'ph_new' => 'Enter the value'],
    'cover' => [
        'ok' => 'The question bank is enough for this number (lowest coverage: :m published questions per level).',
        'short' => 'For this number every level needs at least :n published questions, but in “:section” one level has only :m; tests will repeat.',
        'link' => 'Question bank',
    ],
    'maint' => ['title' => 'Turn maintenance mode on?', 'lead' => 'After saving, the site closes for all learners and only admins can sign in.', 'go' => 'Yes, turn it on'],

    'bar' => ['unsaved' => 'Unsaved changes', 'discard' => 'Discard', 'save' => 'Save changes', 'saving' => 'Saving…', 'saved' => 'Settings saved', 'fix' => 'Fix :n item(s)'],
    'v' => [
        'required' => 'This field is required.',
        'email' => 'Enter a valid email.',
        'range' => 'Enter a number between :a and :b.',
        'step' => 'Must be a multiple of 5.',
        'max' => 'At most :n characters.',
    ],
    'errors' => ['save' => 'Could not save. Try again.'],
];
