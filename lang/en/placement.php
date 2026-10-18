<?php

return [
    'title' => 'Level test',
    'subtitle' => 'Find your English level in three short tests.',

    'levels' => [
        'a1' => 'Beginner',
        'a2' => 'Elementary',
        'b1' => 'Intermediate',
        'b2' => 'Upper-intermediate',
        'c1' => 'Advanced',
    ],

    'intro' => [
        'heading' => 'How the level test works',
        'text' => 'Three short tests measure your vocabulary, grammar and listening. Each one gives you a score and a level from A1 to C1, and your overall level is the average of all of them.',
        'steps' => [
            ['title' => 'Three separate tests', 'text' => 'Vocabulary, Grammar and Listening. Take them in any order, whenever you like.'],
            ['title' => 'Four options, one answer', 'text' => 'Every question has four options. Questions go from easy to hard, so don’t worry if the last ones feel difficult.'],
            ['title' => 'Answers at the end', 'text' => 'You can move back and forth and change answers. The correct answers are shown only after you finish.'],
            ['title' => 'Your level, updated', 'text' => 'After each test your score is saved and your overall level is updated. You can retake any test later.'],
        ],
        'tips_title' => 'Before you start',
        'tips' => [
            'Find a quiet place and about 10 minutes for each test.',
            'Don’t use a dictionary or translator; the result is only useful if it’s honest.',
            'If you don’t know an answer, make your best guess or skip it and come back later.',
            'For the listening test, use headphones if you can.',
        ],
        'scale' => 'Level scale',
        'start' => 'Choose a test',
        'your_level' => 'Your level',
    ],

    'choose' => [
        'heading' => 'Which test do you want to take?',
        'text' => 'Choose one of the three tests. Your latest score for each one is shown on its card.',
        'back' => 'About the test',
        'overall' => 'Your overall level',
        'overall_text' => 'Average of :n of 3 tests',
        'overall_none' => 'Take your first test to see your level.',
    ],

    'sections' => [
        'vocab' => ['title' => 'Vocabulary', 'text' => 'Choose the right word to complete each sentence.'],
        'grammar' => ['title' => 'Grammar', 'text' => 'Choose the correct form to complete each sentence.'],
        'listening' => ['title' => 'Listening', 'text' => 'Listen to a short audio and answer a question about it.'],
    ],

    'card' => [
        'questions' => ':n questions',
        'minutes' => '~:n min',
        'not_taken' => 'Not taken yet',
        'last_score' => 'Last score',
        'start' => 'Start test',
        'retake' => 'Retake',
        'resume' => 'Resume',
        'resume_text' => ':a of :n answered',
    ],

    'exam' => [
        'exit' => 'Exit',
        'question_of' => 'Question :n of :total',
        'answered' => 'Answered',
        'remaining' => 'Remaining',
        'prev' => 'Previous',
        'next' => 'Next',
        'finish' => 'Finish test',
        'questions' => 'Questions',
        'legend_current' => 'Current',
        'legend_done' => 'Answered',
        'legend_todo' => 'Not answered',
        'option' => 'Option :n',
        'loading' => 'Preparing your test…',
        'failed' => 'The test could not be loaded. Please try again.',
        'saved' => 'Progress is saved automatically.',
    ],

    'audio' => [
        'label' => 'Audio player',
        'play' => 'Play',
        'pause' => 'Pause',
        'replay' => 'Play again',
        'volume' => 'Volume',
        'mute' => 'Mute',
        'speed' => 'Speed',
        'seek' => 'Position',
        'unavailable' => 'Audio is not available on this device.',
        'hint' => 'Press play and listen, then answer.',
    ],

    'confirm_finish' => [
        'title' => 'Finish the test?',
        'text' => 'You have :n unanswered question(s). Unanswered questions count as wrong.',
        'ok' => 'Finish anyway',
        'back' => 'Keep answering',
        'all_text' => 'You answered all questions. Ready to see your result?',
        'all_ok' => 'See my result',
    ],
    'confirm_exit' => [
        'title' => 'Leave the test?',
        'text' => 'Your answers are saved. You can come back and continue where you stopped.',
        'ok' => 'Leave',
        'back' => 'Stay',
    ],

    'result' => [
        'title' => ':section result',
        'score' => 'Score',
        'correct_of' => ':c of :t correct',
        'level' => 'Level',
        'by_level' => 'Your answers by level',
        'level_note' => 'Your level is estimated from how well you answered at each level, not just the total.',
        'overall_updated' => 'Your overall level is now',
        'overall_partial' => 'Based on :n of 3 tests. Take the rest for a more accurate result.',
        'review' => 'Review answers',
        'hide_review' => 'Hide review',
        'retake' => 'Retake this test',
        'to_tests' => 'Back to tests',
        'next_test' => 'Take another test',
        'review_title' => 'Answers',
        'all' => 'All',
        'wrong' => 'Wrong',
        'right' => 'Correct',
        'skipped' => 'Not answered',
        'your_answer' => 'Your answer',
        'correct_answer' => 'Correct answer',
        'transcript' => 'Audio script',
        'no_wrong' => 'No wrong answers here. Great job!',
        'msg' => [
            'a1' => 'You’re at the start of the journey, and that’s a great place to begin.',
            'a2' => 'You know the basics. Keep building everyday words and sentences.',
            'b1' => 'You can handle everyday English. Time to widen your range.',
            'b2' => 'You’re a confident user. Now work on the fine details.',
            'c1' => 'Excellent. You work comfortably with complex English.',
        ],
    ],

    'toast' => [
        'saved' => 'Your score was saved.',
        'failed' => 'Could not save your score. Please try again.',
    ],
];
