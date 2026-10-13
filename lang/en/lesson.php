<?php

return [
    'title' => 'Music lesson',
    'subtitle' => 'In :n steps we will learn this song together. Stay with us ...',
    'stepper' => 'Lesson steps',
    'progress' => 'Progress',
    'prev' => 'Previous step',
    'next' => 'Next step',
    'finish' => 'Finish lesson',
    'placeholder' => 'Step :n content will be built here',

    'ordinal' => [
        1 => 'Step 1',
        2 => 'Step 2',
        3 => 'Step 3',
        4 => 'Step 4',
        5 => 'Step 5',
        6 => 'Step 6',
        7 => 'Step 7',
        8 => 'Step 8',
    ],

    // label = short name under the circle | desc = text under the step title (temporary except "lyrics")
    'steps' => [
        'play' => ['label' => 'play', 'desc' => 'In this step, listen to the song carefully and with full focus.'],
        'lyrics' => ['label' => 'Lyrics', 'desc' => 'In this step you can see the lyrics while listening to the song.'],
        'words' => ['label' => 'words', 'desc' => 'In this step you learn the important words and phrases of the song.'],
        'listening' => ['label' => 'listening', 'desc' => 'In this step, listen carefully and practice your listening skills.'],
        'shadowing' => ['label' => 'shadowing', 'desc' => 'In this step, repeat along with the singer.'],
        'karaoke' => ['label' => 'Karaoke', 'desc' => 'In this step, sing along with the lyrics, karaoke style.'],
        'repeat' => ['label' => 'repeat', 'desc' => 'In this step, listen to the song again and see how much more you understand now.'],
        'details' => ['label' => 'details', 'desc' => 'In this step you review the details and overall result of the lesson.'],
    ],

    'locked' => [
        'title' => 'This step is for subscribers',
        'text' => 'On the free plan only the first :free of :total steps of this song are open. Get a subscription to continue.',
        'back' => 'Back to the free steps',
    ],

    'player' => [
        'label' => 'Music player',
        'play' => 'Play',
        'pause' => 'Pause',
        'seek' => 'Song timeline',
        'back' => 'Back 10 seconds',
        'speed' => 'Playback speed',
        'slower' => 'Slower',
        'faster' => 'Faster',
        'error' => 'The audio could not be loaded. Check your connection and try again.',
        'vocals' => 'Where the singer sings',
    ],

    // Step 1 (play) and step 7 (repeat): "How much did you understand?" from 1 to 10
    'rate' => [
        'low' => '1 (lowest)',
        'high' => '10 (highest)',
        'min' => 'Lowest',
        'max' => 'Highest',
        'legend' => 'Score from 1 to 10',
        'locked' => 'First listen to the whole song',
        'ready' => 'Great! Now give your score.',
        'submit' => 'Submit score',
        'saved' => 'Your score is saved',
        'bands' => [
            1 => 'I understood almost nothing',
            2 => 'I understood a little',
            3 => 'I understood some of it',
            4 => 'I understood it well',
            5 => 'I understood almost everything',
        ],
        'play' => [
            'title' => 'How much did you understand?',
            'prompt' => 'Dear learner, after listening to the song, rate how much you understood from :low to :high:',
            'note' => 'Do not worry, this score is only for you. We will ask again in step 7 so you can see your progress.',
        ],
        'repeat' => [
            'title' => 'How much do you understand now?',
            'prompt' => 'Congratulations! You finished the lessons, and now it is time to see how far you have come. Listen to the whole song one more time, then rate how much you understood from :low to :high:',
        ],
        'compare' => [
            'first' => 'Step 1 score',
            'now' => 'Score now',
            'up' => 'Well done! You improved by :n points 🎉',
            'same' => 'You understand about the same as before. A little more practice will help.',
            'down' => 'That is okay. Review the harder parts again and you will improve.',
            'none' => 'Your step 1 score was not saved, so there is nothing to compare.',
        ],
    ],
];
