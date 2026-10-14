<?php

namespace App\Support;

/**
 * لغت‌ها و عبارت‌های دشوارِ یک موزیک (مرحله‌ی ۳) و علامت‌گذاری آن‌ها داخل لیریک.
 *
 * 🔌 بک‌اند در $song['words'] لیستی از این شکل می‌دهد (پشتیبان سایت برای هر موزیک انتخاب می‌کند):
 *   [
 *     'phrase'    => 'step by step',          // خودِ لغت/عبارت، همان‌طور که در لیریک آمده (حروف بزرگ/کوچک مهم نیست)
 *     'type'      => 'idiom',                 // نوع: noun / verb / phrasal verb / idiom ...
 *     'level'     => 'A2',
 *     'ipa'       => ['uk' => '/…/', 'us' => '/…/'],     // اختیاری
 *     'audio'     => ['uk' => 'https://…mp3', 'us' => '…'], // اختیاری؛ اگر نباشد، تلفظ با صدای مرورگر پخش می‌شود
 *     'meaning'   => 'توضیح کامل (انگلیسی)',
 *     'synonyms'  => ['gradually', 'bit by bit'],
 *     'antonyms'  => ['suddenly'],
 *     'translate' => 'ترجمه‌ی فارسی',
 *   ]
 * و در $song['saved_words'] لیست کلیدهای بوکمارک‌شده‌ی کاربر (حروف کوچک) را، مثلاً ['linger'].
 * اگر چیزی نرسد، چند لغت نمونه‌ی همین موزیک تست نشان داده می‌شود (فقط برای تست فرانت).
 */
class Words
{
    private const DEMO = [
        ['phrase' => 'step by step', 'type' => 'idiom', 'level' => 'A2',
            'ipa' => ['uk' => '/ˌstep baɪ ˈstep/', 'us' => '/ˌstep baɪ ˈstep/'],
            'meaning' => 'Slowly and gradually, completing one stage at a time rather than everything at once.',
            'synonyms' => ['gradually', 'bit by bit', 'little by little'], 'antonyms' => ['suddenly', 'all at once'],
            'translate' => 'قدم‌به‌قدم؛ کم‌کم و به‌تدریج'],
        ['phrase' => 'pace', 'type' => 'noun', 'level' => 'B1',
            'ipa' => ['uk' => '/peɪs/', 'us' => '/peɪs/'],
            'meaning' => 'The speed at which someone or something moves, or at which something happens.',
            'synonyms' => ['speed', 'tempo', 'rhythm'], 'antonyms' => [],
            'translate' => 'سرعت؛ آهنگ حرکت'],
        ['phrase' => 'outgrew', 'type' => 'verb (past of outgrow)', 'level' => 'B2',
            'ipa' => ['uk' => '/ˌaʊtˈɡruː/', 'us' => '/ˌaʊtˈɡruː/'],
            'meaning' => 'Became too old, too big or too developed for something, so it no longer fits or suits you.',
            'synonyms' => ['grew out of', 'left behind'], 'antonyms' => ['grew into'],
            'translate' => 'از چیزی بزرگ‌تر شد؛ دیگر به دردش نخورد'],
        ['phrase' => 'grand design', 'type' => 'noun phrase', 'level' => 'B2',
            'ipa' => ['uk' => '/ˌɡrænd dɪˈzaɪn/', 'us' => '/ˌɡrænd dɪˈzaɪn/'],
            'meaning' => 'A large, carefully planned scheme or purpose that is believed to guide everything that happens.',
            'synonyms' => ['master plan', 'big picture'], 'antonyms' => ['accident', 'chaos'],
            'translate' => 'طرح بزرگ؛ نقشه‌ی کلان'],
        ['phrase' => 'sparks', 'type' => 'noun', 'level' => 'A2',
            'ipa' => ['uk' => '/spɑːks/', 'us' => '/spɑːrks/'],
            'meaning' => 'Tiny bright points of light, or the small beginning of an idea or a feeling.',
            'synonyms' => ['flashes', 'glimmers'], 'antonyms' => [],
            'translate' => 'جرقه‌ها'],
        ['phrase' => 'quiet grace', 'type' => 'noun phrase', 'level' => 'B2',
            'ipa' => ['uk' => '/ˌkwaɪət ˈɡreɪs/', 'us' => '/ˌkwaɪət ˈɡreɪs/'],
            'meaning' => 'A calm, gentle and elegant way of being or behaving, without showing off.',
            'synonyms' => ['poise', 'elegance', 'calm dignity'], 'antonyms' => ['clumsiness'],
            'translate' => 'ظرافت و وقار آرام'],
        ['phrase' => 'unfold', 'type' => 'verb', 'level' => 'B2',
            'ipa' => ['uk' => '/ʌnˈfəʊld/', 'us' => '/ʌnˈfoʊld/'],
            'meaning' => 'To develop or become clear gradually over time; also, to open something that was folded.',
            'synonyms' => ['develop', 'emerge', 'unravel'], 'antonyms' => ['fold', 'conceal'],
            'translate' => 'به‌تدریج رخ دادن یا آشکار شدن؛ باز شدن'],
        ['phrase' => 'linger', 'type' => 'verb', 'level' => 'B2',
            'ipa' => ['uk' => '/ˈlɪŋɡə(r)/', 'us' => '/ˈlɪŋɡɚ/'],
            'meaning' => 'To stay somewhere, or keep doing something, for longer than necessary, often because you enjoy it.',
            'synonyms' => ['stay', 'remain', 'dwell'], 'antonyms' => ['leave', 'hurry'],
            'translate' => 'معطل ماندن؛ ماندن و دل نکندن'],
        ['phrase' => 'fading', 'type' => 'adjective / verb', 'level' => 'B1',
            'ipa' => ['uk' => '/ˈfeɪdɪŋ/', 'us' => '/ˈfeɪdɪŋ/'],
            'meaning' => 'Slowly becoming weaker, paler or less clear until it almost disappears.',
            'synonyms' => ['dimming', 'vanishing', 'weakening'], 'antonyms' => ['brightening', 'growing'],
            'translate' => 'رو به محو شدن؛ کم‌رنگ‌شونده'],
        ['phrase' => 'growing old', 'type' => 'phrase', 'level' => 'A2',
            'ipa' => ['uk' => '/ˌɡrəʊɪŋ ˈəʊld/', 'us' => '/ˌɡroʊɪŋ ˈoʊld/'],
            'meaning' => 'Becoming older as the years pass.',
            'synonyms' => ['ageing', 'getting older'], 'antonyms' => ['staying young'],
            'translate' => 'پیر شدن؛ سالخورده شدن'],
    ];

    /** لیست لغت‌های پشتیبان برای این موزیک، با کلید یکتا (حروف کوچک) */
    public static function forSong(array $song): array
    {
        $out = [];
        foreach ($song['words'] ?? self::DEMO as $w) {
            $phrase = trim((string) ($w['phrase'] ?? ''));
            if ($phrase === '') {
                continue;
            }
            $out[] = [
                'key' => mb_strtolower($phrase),
                'phrase' => $phrase,
                'type' => (string) ($w['type'] ?? ''),
                'level' => (string) ($w['level'] ?? ''),
                'ipa' => ['uk' => $w['ipa']['uk'] ?? '', 'us' => $w['ipa']['us'] ?? ''],
                'audio' => ['uk' => $w['audio']['uk'] ?? '', 'us' => $w['audio']['us'] ?? ''],
                'meaning' => (string) ($w['meaning'] ?? ''),
                'synonyms' => array_values($w['synonyms'] ?? []),
                'antonyms' => array_values($w['antonyms'] ?? []),
                'translate' => (string) ($w['translate'] ?? ''),
            ];
        }

        return $out;
    }

    /**
     * هر خط لیریک را به تکه‌ها می‌شکند:
     *   ['k' => 'support', 'text' => 'step by step', 'key' => 'step by step']  ← لغت پشتیبان (هایلایت)
     *   ['k' => 'word',    'text' => 'road']                                   ← کلمه‌ی عادی (کاربر می‌تواند انتخاب کند)
     *   ['k' => 'text',    'text' => ', ']                                     ← فاصله و علامت
     */
    public static function annotate(string $line, array $words): array
    {
        $tokens = [];
        $rest = function (string $s) use (&$tokens) {
            foreach (preg_split("/([\p{L}\p{N}]+(?:['’\-][\p{L}\p{N}]+)*)/u", $s, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY) as $i => $part) {
                $isWord = preg_match("/^[\p{L}\p{N}]/u", $part) === 1;
                $tokens[] = ['k' => $isWord ? 'word' : 'text', 'text' => $part];
            }
        };

        if (! $words) {
            $rest($line);

            return $tokens;
        }

        $phrases = array_map(fn ($w) => $w['phrase'], $words);
        usort($phrases, fn ($a, $b) => mb_strlen($b) <=> mb_strlen($a)); // اول بلندترها
        $alt = implode('|', array_map(fn ($p) => preg_replace('/\s+/u', '\s+', preg_quote($p, '/')), $phrases));
        $re = "/(?<![\p{L}\p{N}'’])(?:{$alt})(?![\p{L}\p{N}'’])/iu";

        $pos = 0;
        if (preg_match_all($re, $line, $m, PREG_OFFSET_CAPTURE)) {
            foreach ($m[0] as [$text, $off]) {
                if ($off > $pos) {
                    $rest(substr($line, $pos, $off - $pos));
                }
                $tokens[] = ['k' => 'support', 'text' => $text, 'key' => mb_strtolower(preg_replace('/\s+/u', ' ', $text))];
                $pos = $off + strlen($text);
            }
        }
        if ($pos < strlen($line)) {
            $rest(substr($line, $pos));
        }

        return $tokens;
    }
}
