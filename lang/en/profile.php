<?php

return [
    'title' => 'Profile',
    'subtitle' => 'Complete and edit your account information here.',

    'banner' => [
        'title' => 'Dear Teodel user',
        'text' => 'Share your referral code with your friends — you both get a discount on a subscription.',
        'ticket' => 'Referral code',
    ],

    'complete' => 'Your profile is :n% complete',
    'complete_done' => 'Your profile is complete',

    'fields' => [
        'first_name' => 'First name',
        'last_name' => 'Last name',
        'mobile' => 'Mobile number',
        'email' => 'Email',
        'gender' => 'Gender',
        'birthday' => 'Date of birth',
        'password' => 'Password',
        'password_new' => 'New password',
        'password_confirm' => 'Confirm password',
    ],
    'placeholders' => [
        'first_name' => 'Enter your first name',
        'last_name' => 'Enter your last name',
        'mobile' => 'Enter your mobile number: 09123456789',
        'email' => 'Enter your email: name@example.com',
        'gender' => 'Select an option',
        'password' => 'Choose a password',
        'password_new' => 'Type a new password to change it',
        'password_confirm' => 'Enter your password again',
        'day' => 'Day',
        'month' => 'Month',
        'year' => 'Year',
    ],
    'gender' => [
        'male' => 'Male',
        'female' => 'Female',
        'other' => 'Prefer not to say',
    ],
    'months' => ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'],

    'hint' => [
        'password_keep' => 'Leave empty to keep your current password.',
    ],
    'errors' => [
        'required' => 'This field is required.',
        'mobile' => 'The mobile number must start with 09 and have 11 digits.',
        'email' => 'This email address is not valid.',
        'birthday' => 'This date of birth is not valid.',
        'password' => 'The password must be at least 8 characters.',
        'confirm' => 'The password confirmation does not match.',
        'failed' => 'Could not save. Please try again.',
    ],

    'submit' => 'Save changes',
    'saved' => 'Your information was saved',
    'unsaved' => 'You have unsaved changes.',
    'show_password' => 'Show password',
    'hide_password' => 'Hide password',

    'side' => [
        'days_left' => 'subscription days left',
        'buy' => 'Buy a subscription',
        'code' => 'Your referral code',
        'copy' => 'Copy code',
        'copied' => 'Copied',
        'copy_failed' => 'Could not copy; select the code manually',
        'change_avatar' => 'Change avatar',
    ],
    'skills' => [
        'listening' => 'Listening',
        'reading' => 'Reading',
        'writing' => 'Writing',
        'speaking' => 'Speaking',
    ],

    'avatar' => [
        'title' => 'Choose an avatar',
        'text' => 'Dear user, you can pick one of the avatars below.',
        'more_before' => 'If you need more avatars, visit the',
        'more_link' => 'Achievements',
        'more_after' => 'section.',
        'confirm' => 'Confirm and close',
        'option' => 'Avatar :n',
        'locked' => 'Locked — unlock it in Achievements',
    ],
];
