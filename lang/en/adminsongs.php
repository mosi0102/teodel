<?php

return [
    'title' => 'Music lessons',
    'sub' => 'Create and edit songs and all 8 learning steps',
    'new' => 'New song',
    'new_song' => 'New song',
    'back' => 'All songs',
    'search_ph' => 'Search song or artist…',
    'empty' => 'No songs found',
    'result' => ':n songs',
    'steps_of' => ':n of 8 steps done',
    'unsaved' => 'Unsaved',
    'free' => 'Free',
    'paid' => 'Paid',
    'preview' => 'Preview',
    'save' => 'Save',
    'save_draft' => 'Save draft',
    'publish' => 'Publish',
    'time' => 'Time',
    'now' => 'Current player time',
    'tabs_label' => 'Song sections',
    'q_default' => [
        'play' => 'After listening to the song, rate from 1 (least) to 10 (most) how much you understood:',
        'repeat' => 'Listen to the whole song once more, then rate from 1 (least) to 10 (most) how much you understand now:',
    ],

    'sort' => ['label' => 'Sort', 'newest' => 'Last edited', 'title' => 'Song name', 'progress' => 'Most complete'],
    'status' => ['all' => 'All', 'published' => 'Published', 'draft' => 'Draft'],
    'level' => ['a' => 'A · Beginner', 'b' => 'B · Intermediate', 'c' => 'C · Advanced'],
    'accent' => ['us' => 'American', 'uk' => 'British', 'au' => 'Australian', 'ie' => 'Irish'],
    'col' => ['song' => 'Song', 'level' => 'Level', 'access' => 'Access', 'status' => 'Status', 'steps' => 'Steps', 'updated' => 'Last edited'],
    'tabs' => [
        'info' => 'Info', 'play' => 'Play', 'lyrics' => 'Lyrics', 'words' => 'Words', 'listening' => 'Listening',
        'shadowing' => 'Shadowing', 'karaoke' => 'Karaoke', 'repeat' => 'Repeat', 'details' => 'Details',
    ],

    'info' => ['title' => 'Song info'],
    'f' => [
        'title' => 'Song title', 'artist' => 'Artist', 'level' => 'Level', 'level_hint' => 'Shown under tab A, B or C in the site list.',
        'accent' => 'Accent', 'year' => 'Release year', 'genre' => 'Genre', 'wpm' => 'Speed (words per minute)', 'topic' => 'Topic', 'album' => 'Album',
        'points' => 'Points', 'desc_fa' => 'Persian description', 'desc_en' => 'English description',
        'free' => 'Free song', 'free_hint' => 'Free users see all 8 steps of this song (others only the first two).',
    ],
    'files' => [
        'title' => 'Files', 'cover' => 'Cover image', 'cover_hint' => 'JPG, PNG or WebP; square, up to 3 MB.',
        'audio' => 'Song file', 'audio_hint' => 'MP3 or M4A; used by play, lyrics, listening, shadowing and repeat.',
    ],

    'play' => [
        'title' => 'Step 1 — Play', 'lead' => 'The user listens once, then rates from 1 to 10 how much they understood.',
        'vocals' => 'Where the singer sings', 'vocals_hint' => 'Built from the lyric timings and shown on the user’s player bar.',
        'q_fa' => 'Question text (Persian)', 'q_en' => 'Question text (English)', 'q_hint' => 'Leave empty to use the site’s default text.',
        'prev' => 'Preview', 'needs' => 'Requirements', 'need_audio' => 'Song file uploaded', 'need_lyrics' => 'Fully timed lyrics',
    ],
    'repeat' => [
        'title' => 'Step 7 — Repeat', 'lead' => 'Same as step 1 with a different question; the first score is compared with the score after the lessons.',
        'compare' => 'The result is shown next to the step-1 score.',
    ],

    'lyrics' => [
        'title' => 'Step 2 — Lyrics', 'lead' => 'Every line has a time; the line being sung is highlighted. Persian translation is optional.',
        'paste' => 'Paste LRC', 'copy' => 'Copy LRC', 'tap' => 'Tap to time', 'ai' => 'Translate with AI',
        'tap_help' => 'Play the song and press the button (or Space) as each line starts. Lines get times in order; press Undo if you slip.',
        'mark' => 'Mark time', 'undo' => 'Undo',
        'text' => 'English text', 'tr' => 'Persian translation', 'add' => 'Add line', 'empty' => 'No lyrics yet. Paste LRC or add lines by hand.',
        'paste_t' => 'Paste lyrics', 'paste_lead' => 'Paste LRC (with times) or plain lines. This replaces the current lyrics.',
        'paste_go' => 'Apply', 'keep_tr' => 'Keep translations of matching lines',
    ],

    'words' => [
        'title' => 'Step 3 — Words', 'lead' => 'Hard words and phrases highlighted in the lyrics; users tap them to see the explanation.',
        'ai' => 'Suggest words with AI', 'add' => 'Add word', 'empty' => 'No words yet.', 'fill' => 'Complete with AI',
        'phrase' => 'Word / phrase', 'type' => 'Type', 'level' => 'Level', 'translate' => 'Persian translation', 'meaning' => 'English meaning',
        'syn' => 'Synonyms', 'ant' => 'Antonyms', 'audio_uk' => 'UK pronunciation file', 'audio_us' => 'US pronunciation file',
        'audio_hint' => 'Without a file, the user’s browser voice reads it.',
    ],

    'listening' => [
        'easy_t' => 'Step 4 — Easy listening', 'easy_lead' => 'A part of the song plays and exactly one word of the text is blank.',
        'hard_t' => 'Step 4 — Hard listening', 'hard_lead' => 'A phrase or several words in a row are blank and the user types them.',
        'ai' => 'Suggest with AI', 'add' => 'Add exercise', 'empty' => 'No exercises yet.',
    ],
    'clip' => [
        'pick' => 'Pick from lyric lines', 'start' => 'Start', 'end' => 'End', 'text' => 'Text of the part', 'answer' => 'Answer',
        'play' => 'Play part', 'preview' => 'Blank preview',
    ],

    'shadowing' => [
        'title' => 'Step 5 — Shadowing', 'lead' => 'The user hears each part and then repeats it.',
        'ai' => 'Suggest with AI', 'repeats' => 'Target repeats', 'add' => 'Add part', 'empty' => 'No parts yet.',
        'from' => 'From line', 'to' => 'To line', 'text' => 'Text of the part (one line per row)',
    ],

    'karaoke' => [
        'title' => 'Step 6 — Karaoke', 'lead' => 'Two files of the same length are needed: with vocals and without vocals. Lyrics are the same as step 2.',
        'audio_vocal' => 'Song with vocals', 'audio_vocal_hint' => 'Users can lower or mute the singer.',
        'audio_backing' => 'Song without vocals', 'audio_backing_hint' => 'Backing track only; exactly the same length as the file above.',
        'mismatch' => 'The two files differ in length; the user’s voice and the track will drift apart.',
        'need_vocal' => 'File with vocals', 'need_backing' => 'File without vocals', 'need_lyrics' => 'Fully timed lyrics',
        'note' => 'You can make the vocal-free file with a stem-separation tool.',
    ],

    'details' => [
        'ai' => 'Create with AI', 'interp' => 'Step 8 — Song interpretation', 'interp_hint' => 'Separate paragraphs with a blank line.',
        'notes' => 'Notes', 'notes_hint' => 'Literary devices and English language tips; each section has a title and several items.',
        'add_section' => 'Add section', 'add_item' => 'Add item', 'empty' => 'No sections yet.',
        's_title' => 'Section title', 's_type' => 'Type', 'literary' => 'Literary devices', 'language' => 'Language tips',
        'term' => 'Term', 'example' => 'Example from the song', 'text' => 'Explanation',
    ],

    'pub' => ['title' => 'Publish song', 'go' => 'Publish', 'fix' => 'Needs fixing', 'required' => 'Required', 'optional' => 'Will be empty',
        'blocked' => 'Complete the required items to publish.', 'soft' => ':n step(s) will stay empty; users won’t see them. Publish anyway?', 'ready' => 'Everything is ready.'],
    'del' => ['title' => 'Delete song?', 'lead' => 'The song and all its steps will be deleted and users’ progress on it is removed. This cannot be undone.', 'go' => 'Delete'],

    'js' => [
        'new_song' => 'New song', 'ai_made' => 'AI-made — review', 'item_n' => 'Item :n',
        'steps_of' => ':n of 8 steps done',
        'status' => ['draft' => 'Draft', 'published' => 'Published'],
        'tabs' => ['info' => 'Info', 'play' => 'Play', 'lyrics' => 'Lyrics', 'words' => 'Words', 'listening' => 'Listening', 'shadowing' => 'Shadowing', 'karaoke' => 'Karaoke', 'repeat' => 'Repeat', 'details' => 'Details'],
        'player' => ['none' => 'Upload the song file in the “Info” tab first.'],
        'words' => ['in_lyrics' => 'Found in lyrics', 'not_in_lyrics' => 'Not found in lyrics'],
        'clip' => ['answer' => 'Answer', 'answer_easy' => 'Answer (one word — pick one)', 'answer_hard' => 'Answer (exactly as in the text)'],
        'play' => ['vocals_n' => ':n sung part(s)', 'vocals_none' => 'No timed lyrics yet; the parts are built from them.'],
        'lyrics' => [
            'parse_empty' => 'No lines found', 'parsed' => ':n lines applied', 'nothing' => 'No timed lyrics', 'copied' => 'LRC copied',
            'tap_pos' => 'Line :a of :b', 'tap_done' => 'All lines are timed',
        ],
        'v' => [
            'title' => 'Enter the song title', 'artist' => 'Enter the artist', 'year' => 'Use a four-digit year (e.g. 2019)', 'number' => 'Numbers only',
            'repeats' => 'Repeats must be between 1 and 10', 'no_time' => 'This line has no time', 'no_text' => 'Has a time but no text', 'order' => 'Time is earlier than the previous line',
            'phrase' => 'Enter the word or phrase', 'translate' => 'No Persian translation', 'text' => 'The text is empty', 'range' => 'End must be after start',
            'answer' => 'Set the answer', 'answer_not_in_text' => 'The answer is not in the text', 'easy_one_word' => 'In easy mode the answer must be a single word',
        ],
        'errors' => [
            'network' => 'Could not connect', 'upload' => 'Upload failed', 'type' => 'Wrong file type', 'size' => 'File is larger than :n MB',
            'no_backend' => 'No save URL configured', 'save' => 'Could not save',
        ],
        'save' => ['fix' => 'Fix :n item(s)', 'saving' => 'Saving…', 'saved' => 'Saved', 'published' => 'Song published'],
        'pub' => ['fix' => 'Needs fixing', 'required' => 'Required', 'optional' => 'Will be empty',
            'blocked' => 'Complete the required items to publish.', 'soft' => ':n step(s) will stay empty and users won’t see them. Publish anyway?', 'ready' => 'Everything is ready.'],
        'ai' => [
            'applied' => ':n item(s) made by AI — please review', 'nothing' => 'Nothing was added', 'need_lyrics' => 'Add the lyrics first', 'need_phrase' => 'Write the word first',
            'missing_audio' => 'AI could not make the pronunciation file; add it by hand', 'error' => 'AI did not respond',
        ],
    ],
];
