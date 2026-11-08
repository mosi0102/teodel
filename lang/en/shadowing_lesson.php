<?php

/*
 * Overrides for the lesson page texts (lang/en/lesson.php), Shadowing only — ShadowingController applies them onto the lesson.* keys.
 * Any key not listed here falls back to lesson.php.
 */
return [
    'title' => 'Shadowing lesson',
    'subtitle' => 'In :n steps you will listen to the sentences of this text, repeat them in your own voice and answer questions.',

    'steps' => [
        'listen' => ['label' => 'listen', 'desc' => 'In this step, listen to the first sentence 3 times, carefully, and notice its rhythm and tone.'],
        'record' => ['label' => 'record', 'desc' => 'In this step, record the same sentence in your own voice; AI checks your similarity and pronunciation.'],
        'practice' => ['label' => 'practice', 'desc' => 'In this step you do the same as steps 1 and 2 (listen 3 times, then record) for the rest of the sentences.'],
        'text' => ['label' => 'text', 'desc' => 'In this step you see the whole text. Check the hard words, see their meanings and save the ones you want.'],
        'questions' => ['label' => 'questions', 'desc' => 'In this step you answer a few questions at the level of the text with your voice, and AI checks your answer.'],
    ],

    'locked' => [
        'text' => 'With a free account only the first :free of :total steps of this topic are open. Subscribe to continue.',
    ],

    'lyrics' => ['label' => 'Full text'],
    'words' => ['in_song' => 'In this text'],
];
