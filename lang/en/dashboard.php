<?php

return [
    'title' => 'Dashboard',
    'greet' => ['morning' => 'Good morning', 'noon' => 'Good afternoon', 'evening' => 'Good evening', 'night' => 'Good night'],
    'hello' => ':greet, :name',
    'streak_line' => 'You’ve studied :n days in a row. Keep going!',
    'streak_zero' => 'Do your first lesson today to start your streak.',

    'resume' => [
        'title' => 'Continue learning',
        'step_of' => 'Step :n of :total',
        'progress' => ':p% of this song done',
        'cta' => 'Continue',
        'empty_title' => 'Start your first song',
        'empty_text' => 'Pick a song for your level and learn English in 8 steps.',
        'empty_cta' => 'Browse songs',
    ],

    'stats' => [
        'streak' => 'day streak',
        'streak_best' => 'Best: :n days',
        'songs' => 'songs completed',
        'words' => 'words saved',
        'minutes' => 'minutes this week',
    ],

    'goal' => [
        'title' => 'Today’s goal',
        'of' => ':a of :b min',
        'done' => 'You reached today’s goal. Well done!',
        'left' => ':n min left to your goal',
        'set' => 'Daily goal',
        'minutes' => ':n min',
        'week' => 'Last 7 days',
        'day_done' => 'You studied',
        'day_missed' => 'No study',
    ],

    'week' => [
        'title' => 'Weekly activity',
        'total' => ':n min',
        'up' => ':n min more than last week',
        'down' => ':n min less than last week',
        'same' => 'Same as last week',
        'today' => 'Today',
        'bar' => ':day: :n min',
        'chart' => 'Chart of learning minutes over the last 7 days',
    ],

    'level' => [
        'title' => 'Your level',
        'overall' => 'Overall level',
        'skills' => ['listening' => 'Listening', 'reading' => 'Reading', 'writing' => 'Writing', 'speaking' => 'Speaking'],
        'none' => 'Not measured yet',
        'tests' => ':n of :total tests taken',
        'cta' => 'Level test',
        'cta_more' => 'Take more tests',
    ],

    'review' => [
        'title' => 'Word review',
        'due' => ':n words are ready to review',
        'none' => 'No words to review',
        'text' => 'A short review every day keeps words in your memory for good.',
        'cta' => 'Start review',
        'cta_none' => 'See my words',
    ],

    'recommended' => [
        'title' => 'Recommended for your level',
        'all' => 'All songs',
        'free' => 'Free',
        'locked' => 'Premium',
        'level' => 'Level :l',
        'min' => ':n min',
    ],

    'plan' => [
        'free_title' => 'Free plan',
        'free_used' => 'You’ve opened :a of your :b free songs',
        'free_text' => 'A subscription unlocks all 8 steps of every song.',
        'buy' => 'Get a subscription',
        'paid_title' => 'Subscription active',
        'paid_days' => ':n days left on your subscription',
        'renew' => 'Renew subscription',
    ],

    'support' => [
        'title' => 'Support',
        'open' => ':n open tickets',
        'unread' => ':n new replies',
        'none' => 'No open tickets',
        'cta' => 'View tickets',
        'new' => 'New ticket',
    ],
];
