<?php

/* Error pages: resources/views/errors/*.blade.php — text per code: ['t' => title, 'd' => description] */
return [
    'home' => 'Back to home',
    'back' => 'Previous page',
    'reload' => 'Try again',
    'support' => 'Report the problem',
    'code' => 'Error code',
    'other_lang' => 'فارسی',

    '404' => ['t' => 'We can’t find that page', 'd' => 'The address you opened doesn’t exist or has moved. Check it once more, or continue from the home page.'],
    '500' => ['t' => 'Something went wrong on our side', 'd' => 'The server couldn’t complete your request. We can see the problem; try again in a moment, and tell support if it keeps happening.'],
    '403' => ['t' => 'You don’t have access', 'd' => 'This page isn’t available for your account. If you think this is a mistake, tell support.'],
    '419' => ['t' => 'This page has expired', 'd' => 'Some time has passed and the page is no longer valid. Reload it and do what you were doing from the start.'],
    '429' => ['t' => 'Too many requests', 'd' => 'You sent a lot of requests in a short time. Wait a little and try again.'],
    '503' => ['t' => 'We’re updating', 'd' => 'Teodel is unavailable for a few minutes while we make it better. Please come back shortly.'],
];
