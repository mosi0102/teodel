<?php

/*
 * Replacements for the lesson page texts (lang/en/lesson.php), podcast only. PodcastController overlays them on lesson.* keys.
 * Same key structure as lesson.php; any key missing here falls back to lesson.php.
 */
return [
    'title' => 'Podcast lesson',
    'subtitle' => 'In :n steps we will work through this podcast together. Stay with us ...',

    'steps' => [
        'play' => ['label' => 'play', 'desc' => 'In this step just listen to the podcast, with no text and no help. Slow it down, pause or repeat a part whenever you need.'],
        'lyrics' => ['label' => 'Transcript', 'desc' => 'In this step you see the transcript while you listen. The sentence being played is highlighted, and clicking any sentence plays the podcast from there.'],
        'words' => ['label' => 'words', 'desc' => 'In this step see the meaning of the difficult words and phrases, and pick any other hard word yourself. At the end you can download everything as a PDF.'],
        'listening' => ['label' => 'listening', 'desc' => 'In this step a few sentences chosen by the system are played. Listen to each one and type it in full; the system checks what you wrote.'],
        'shadowing' => ['label' => 'shadowing', 'desc' => 'In this step you practise the same sentences with “shadowing”: listen, then repeat right away. We recommend at least 3 repeats.'],
        'repeat' => ['label' => 'repeat', 'desc' => 'In this step, listen to the podcast again and see how much more you understand now.'],
        'details' => ['label' => 'details', 'desc' => 'Here you see the podcast information: host, topic, level, speaking speed and more.'],
    ],

    'locked' => [
        'text' => 'With a free account only the first :free of :total steps of this podcast are open. Subscribe to continue.',
    ],

    'player' => [
        'label' => 'Podcast player',
        'seek' => 'Podcast timeline',
        'vocals' => 'Where the speaker talks',
    ],

    'rate' => [
        'locked' => 'Listen to the podcast to the end first',
        'play' => [
            'prompt' => 'Dear learner, after listening to the podcast, rate how much you understood from :low to :high:',
            'note' => 'Do not worry, this score is only for you. We will ask again in step 6 so you can see your progress.',
        ],
        'repeat' => [
            'prompt' => 'Congratulations! You finished the lessons, and now it is time to see how far you have come. Listen to the whole podcast one more time, then rate how much you understood from :low to :high:',
        ],
    ],

    'lyrics' => [
        'label' => 'Podcast transcript',
        'empty' => 'The transcript of this podcast is not ready yet.',
    ],

    'words' => [
        'in_song' => 'In the podcast',
    ],

    'listening' => [
        'empty' => 'No listening exercise has been added for this podcast yet.',
    ],

    'shadowing' => [
        'empty' => 'No part to repeat has been added for this podcast yet.',
    ],
];
