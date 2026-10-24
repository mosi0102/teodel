<?php

return [
    'brand' => 'Teodel',
    'title_login' => 'Sign in',
    'title_register' => 'Sign up',
    'skip' => 'Skip to form',
    'home' => 'Home',

    'aside' => [
        'title' => 'Learn English with music',
        'lead' => 'Listen, sing and practise your favourite songs step by step; words and pronunciation stick without effort.',
        'points' => ['8 learning steps on every song', 'Free level test', '3 fully free songs to start'],
    ],

    'mode' => ['login' => 'Sign in', 'register' => 'Sign up'],
    'steps' => ['phone' => 'Phone', 'code' => 'Verify', 'profile' => 'Account'],

    'phone' => [
        'title_login' => 'Sign in to Teodel',
        'title_register' => 'Create your Teodel account',
        'lead' => 'Enter your mobile number and we’ll text you a verification code.',
        'label' => 'Mobile number',
        'ph' => '09123456789',
        'go' => 'Get code',
        'use_password' => 'Sign in with password',
    ],

    'code' => [
        'title' => 'Enter the verification code',
        'lead' => 'We sent a :n-digit code to :phone.',
        'label' => 'Verification code',
        'digit' => 'Digit :n',
        'edit' => 'Change number',
        'resend' => 'Resend code',
        'resend_in' => 'Resend in :t',
        'sent_again' => 'Code sent again.',
        'not_sent' => 'We couldn’t send the SMS. You can sign in with your password or try again in a moment.',
        'missing' => 'Didn’t get it?',
        'use_password' => 'Sign in with password',
        'go' => 'Verify & continue',
    ],

    'password' => [
        'title' => 'Sign in with password',
        'lead' => 'Enter the password for :phone.',
        'label' => 'Password',
        'show' => 'Show password',
        'hide' => 'Hide password',
        'remember' => 'Remember me',
        'go' => 'Sign in',
        'forgot' => 'Forgot your password?',
        'forgot_hint' => 'Sign in with an SMS code, then set a new password in your profile.',
        'use_code' => 'Sign in with SMS code',
        'edit' => 'Change number',
    ],

    'profile' => [
        'title' => 'Finish signing up',
        'lead' => ':phone is verified. Complete your account details.',
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'password' => 'Password',
        'password_hint' => 'At least 8 characters with letters and numbers. You’ll need it when the SMS doesn’t arrive.',
        'strength' => ['weak' => 'Weak', 'fair' => 'Fair', 'good' => 'Good', 'strong' => 'Strong'],
        'ref' => 'I have a referral code',
        'ref_label' => 'Referral code (optional)',
        'ref_hint' => 'If a friend invited you, you both get a reward.',
        'terms' => 'I have read and accept the :link',
        'terms_link' => 'terms & conditions',
        'go' => 'Create account',
    ],

    'notice' => [
        'exists' => 'This number is already registered; signing you in.',
        'new' => 'This number has no account yet; let’s finish signing you up.',
    ],
    'done' => ['title' => 'Welcome!', 'lead' => 'Taking you to your account…', 'demo' => 'Demo mode: no redirect.'],
    'demo' => 'Demo mode (no backend): numbers ending 0–4 = existing account, 5–9 = new account · code: 12345 · password: demo1234',
    'back' => 'Back',

    'v' => [
        'phone_required' => 'Enter your mobile number.',
        'phone_invalid' => 'Enter a valid mobile number, like 09123456789.',
        'code_incomplete' => 'Enter the full code.',
        'code_invalid' => 'The code is wrong or has expired.',
        'password_required' => 'Enter your password.',
        'password_wrong' => 'The password is wrong.',
        'password_short' => 'The password must be at least 8 characters.',
        'password_weak' => 'The password needs both letters and numbers.',
        'first_required' => 'Enter your first name.',
        'last_required' => 'Enter your last name.',
        'terms_required' => 'You need to accept the terms to continue.',
    ],
    'errors' => [
        'network' => 'Couldn’t reach the server. Try again.',
        'generic' => 'Something went wrong. Try again.',
        'too_many' => 'Too many attempts; try again in :t.',
    ],
];
