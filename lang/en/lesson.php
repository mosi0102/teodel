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
        'play' => ['label' => 'play', 'desc' => 'In this step, listen to the song from start to finish and get a feel for it.'],
        'lyrics' => ['label' => 'Lyrics', 'desc' => 'In this step you can see the lyrics while listening to the song.'],
        'words' => ['label' => 'words', 'desc' => 'In this step you learn the important words and phrases of the song.'],
        'listening' => ['label' => 'listening', 'desc' => 'In this step, listen carefully and practice your listening skills.'],
        'shadowing' => ['label' => 'shadowing', 'desc' => 'In this step, repeat along with the singer.'],
        'karaoke' => ['label' => 'Karaoke', 'desc' => 'In this step, sing along with the lyrics, karaoke style.'],
        'repeat' => ['label' => 'repeat', 'desc' => 'In this step, repeat parts of the song until they stick.'],
        'details' => ['label' => 'details', 'desc' => 'In this step you review the details and overall result of the lesson.'],
    ],

    'locked' => [
        'title' => 'This step is for subscribers',
        'text' => 'On the free plan only the first :free of :total steps of this song are open. Get a subscription to continue.',
        'back' => 'Back to the free steps',
    ],
];
