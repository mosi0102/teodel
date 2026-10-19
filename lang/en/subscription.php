<?php

return [
    'title' => 'Get a subscription',
    'subtitle' => 'Pick a plan and unlock every step of every song.',
    'tagline_en' => 'Learn from what you love.',
    'tagline_fa' => 'Learn English from what you already enjoy',
    'currency' => 'Toman',
    'per_month' => 'About :price Toman / month',
    'off' => ':n% off',
    'pick' => 'Recommended',
    'current' => 'Your current plan',

    'prefix' => 'Plan',
    'days_left' => ':n days left',
    'plans' => [
        'free' => ['name' => 'Free plan', 'term' => 'Free', 'cta' => 'Start free'],
        '1m' => ['name' => '1-month plan', 'term' => '1 month', 'cta' => 'Get the 1-month plan'],
        '6m' => ['name' => '6-month plan', 'term' => '6 months', 'cta' => 'Get the 6-month plan'],
        '12m' => ['name' => '12-month plan', 'term' => '12 months', 'cta' => 'Get the 12-month plan'],
    ],
    'renew' => 'Renew plan',

    'features' => [
        'free_exercises' => 'Limited access to every song’s exercises',
        'free_songs' => 'Full access to :n songs',
        'level_test' => 'Access to the level test',
        'free_reports' => 'Limited access to reports',
        'full_exercises' => 'Full access to song exercises',
        'full_reports' => 'Full access to reports',
        'advanced_level' => 'Advanced level assessment',
        'planning' => 'Study planning & coaching',
        'ai' => 'Unlimited access to Teodel AI',
    ],

    'guarantee' => '<b>Money-back</b> guarantee if you’re <b>not satisfied</b>',
    'guarantee_note' => 'If you’re not happy with your subscription, we’ll refund you.',

    'trust' => [
        ['t' => 'Secure payment', 'd' => 'You pay on your bank’s gateway. Your card details never reach us.'],
        ['t' => 'Instant activation', 'd' => 'Your plan is active the moment the payment succeeds.'],
        ['t' => 'Support', 'd' => 'Any payment trouble, open a ticket and we’ll sort it out.'],
    ],

    'faq' => [
        'title' => 'Frequently asked questions',
        'items' => [
            ['q' => 'When does my plan become active?', 'a' => 'The moment the payment is confirmed. You’ll see the result right after the gateway, and every step of every song opens up.'],
            ['q' => 'The payment failed but money was taken. What now?', 'a' => 'The amount is usually returned within 72 hours. If it isn’t, open a ticket under “Payment & subscription”.'],
            ['q' => 'What happens when my plan ends?', 'a' => 'You go back to the free plan: 3 full songs and the first two steps of the rest. Your progress and words are never deleted.'],
            ['q' => 'Can I renew early?', 'a' => 'Yes. Whenever you buy a plan, it is added to the time you already have.'],
        ],
    ],

    'confirm' => [
        'title' => 'Confirm your purchase',
        'lead' => 'One last look at your order.',
        'plan' => 'Plan',
        'period' => 'Valid',
        'from_to' => ':from – :to',
        'price' => 'Price',
        'discount' => 'Discount',
        'total' => 'Total to pay',
        'secure' => 'Tapping “Pay” takes you to your bank’s secure gateway. After paying you’ll come back here.',
        'pay' => 'Pay :price Toman',
        'busy' => 'Taking you to the gateway…',
        'cancel' => 'Cancel',
    ],

    'errors' => [
        'network' => 'Couldn’t connect. Check your internet and try again.',
        'failed' => 'Couldn’t start the payment. Please try again.',
    ],

    'result' => [
        'title' => 'Payment result',
        'success' => [
            'title' => 'Your plan is active!',
            'text' => 'Payment went through. Every step of every song is open for you now.',
        ],
        'failed' => [
            'title' => 'Payment failed',
            'text' => 'The payment didn’t complete and your plan wasn’t activated.',
            'money' => 'If any money was taken, it is usually returned within 72 hours.',
            'reason' => 'Bank message',
        ],
        'cancelled' => [
            'title' => 'Payment cancelled',
            'text' => 'You left the payment gateway. Nothing was charged.',
        ],
        'receipt' => 'Receipt',
        'plan' => 'Plan',
        'amount' => 'Amount',
        'ref' => 'Tracking code',
        'bank_ref' => 'Bank reference',
        'paid_at' => 'Paid at',
        'ends_at' => 'Valid until',
        'copy' => 'Copy',
        'copied' => 'Tracking code copied',
        'start' => 'Start learning',
        'dashboard' => 'Go to dashboard',
        'retry' => 'Try again',
        'support' => 'Contact support',
        'back' => 'Back to plans',
    ],
];
