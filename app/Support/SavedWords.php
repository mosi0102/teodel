<?php

namespace App\Support;

/**
 * لغت‌ها و عبارت‌های ذخیره‌شده‌ی کاربر (صفحه‌ی «لغات و عبارت‌های هایلایت‌شده»).
 *
 * 🔌 بک‌اند به‌جای demo() لیست واقعیِ کاربر را بدهد؛ هر مورد (همه‌ی کلیدها اختیاری‌اند جز id و phrase):
 *   [
 *     'id'         => 15,                       // شناسه‌ی رکورد (برای حذف/مرور)
 *     'phrase'     => 'bathroom',
 *     'type'       => 'noun',                   // noun / verb / idiom / phrasal verb ...
 *     'level'      => 'a1',                     // a1 | a2 | b1 | b2 | c1
 *     'ipa'        => ['uk' => '/…/', 'us' => '/…/'],
 *     'audio'      => ['uk' => 'https://…mp3', 'us' => '…'],   // اگر نباشد، با صدای مرورگر خوانده می‌شود
 *     'meaning'    => 'تعریف انگلیسی',
 *     'example'    => 'جمله‌ی نمونه (انگلیسی)',
 *     'example_tr' => 'ترجمه‌ی جمله',
 *     'translate'  => 'ترجمه‌ی لغت',
 *     'synonyms'   => ['…'], 'antonyms' => ['…'],
 *     'image'      => 'https://…webp',           // تصویر لغت (اختیاری)
 *     'song'       => ['id' => 1, 'title' => 'Step by Step in Time'],   // موزیکی که از آن ذخیره شده
 *     'saved_at'   => 1760000000,               // زمان ذخیره (timestamp)
 *     'status'     => 'new',                    // new | learning | learned (نتیجه‌ی مرور)
 *   ]
 */
class SavedWords
{
    /** @return array<int, array<string, mixed>> */
    public static function normalize(array $rows): array
    {
        $out = [];
        foreach ($rows as $r) {
            $phrase = trim((string) ($r['phrase'] ?? ''));
            if ($phrase === '' || ! isset($r['id'])) {
                continue;
            }
            $level = strtolower((string) ($r['level'] ?? ''));
            $out[] = [
                'id' => $r['id'],
                'key' => mb_strtolower($phrase),
                'phrase' => $phrase,
                'type' => (string) ($r['type'] ?? ''),
                'level' => in_array($level, ['a1', 'a2', 'b1', 'b2', 'c1'], true) ? $level : '',
                'ipa' => ['uk' => $r['ipa']['uk'] ?? '', 'us' => $r['ipa']['us'] ?? ''],
                'audio' => ['uk' => $r['audio']['uk'] ?? '', 'us' => $r['audio']['us'] ?? ''],
                'meaning' => (string) ($r['meaning'] ?? ''),
                'example' => (string) ($r['example'] ?? ''),
                'example_tr' => (string) ($r['example_tr'] ?? ''),
                'translate' => (string) ($r['translate'] ?? ''),
                'synonyms' => array_values((array) ($r['synonyms'] ?? [])),
                'antonyms' => array_values((array) ($r['antonyms'] ?? [])),
                'image' => $r['image'] ?? null,
                'song' => isset($r['song']['id']) ? ['id' => (int) $r['song']['id'], 'title' => (string) ($r['song']['title'] ?? '')] : null,
                'saved_at' => (int) ($r['saved_at'] ?? 0),
                'status' => in_array($r['status'] ?? '', ['learning', 'learned'], true) ? $r['status'] : 'new',
            ];
        }

        return $out;
    }

    /** نمونه برای تست فرانت (فقط تا وصل‌شدن بک‌اند) */
    public static function demo(): array
    {
        $now = time();
        $song = ['id' => 1, 'title' => 'Step by Step in Time'];
        $rows = [
            ['bathroom', 'noun', 'a1', '/ˈbɑːθruːm/', '/ˈbæθruːm/', 'A room containing a bath or shower, and usually a washbasin and a toilet.', 'We are redoing the whole place and I have just bought a new bathroom and kitchen.', 'همه‌ی خانه را نوسازی می‌کنیم و من تازه حمام و آشپزخانه‌ی جدیدی خریده‌ام.', 'حمام؛ سرویس بهداشتی', ['restroom', 'washroom'], []],
            ['blackboard', 'noun', 'a2', '/ˈblækbɔːd/', '/ˈblækbɔːrd/', 'A dark flat surface in a classroom that a teacher writes on with chalk.', 'The teacher wrote the new words on the blackboard.', 'معلم لغت‌های جدید را روی تخته‌سیاه نوشت.', 'تخته‌سیاه', ['chalkboard'], ['whiteboard']],
            ['cat', 'noun', 'a1', '/kæt/', '/kæt/', 'A small furry animal that people keep as a pet and that catches mice.', 'Our cat sleeps on the sofa all afternoon.', 'گربه‌ی ما تمام بعدازظهر روی مبل می‌خوابد.', 'گربه', ['kitten', 'feline'], []],
            ['dog', 'noun', 'a1', '/dɒɡ/', '/dɔːɡ/', 'A common animal kept as a pet or used for guarding and hunting; it barks.', 'She takes her dog for a walk every morning.', 'او هر صبح سگش را به پیاده‌روی می‌برد.', 'سگ', ['puppy', 'hound'], ['cat']],
            ['office', 'noun', 'a2', '/ˈɒfɪs/', '/ˈɔːfɪs/', 'A room or building where people work, usually at desks.', 'I usually get to the office at eight o’clock.', 'من معمولاً ساعت هشت به دفتر می‌رسم.', 'دفتر کار؛ اداره', ['workplace'], ['home']],
            ['train', 'noun', 'a1', '/treɪn/', '/treɪn/', 'A long vehicle that runs on rails and carries passengers or goods.', 'The train to London leaves from platform two.', 'قطار لندن از سکوی شماره‌ی دو حرکت می‌کند.', 'قطار', ['railway'], []],
            ['computer', 'noun', 'a1', '/kəmˈpjuːtə/', '/kəmˈpjuːtər/', 'An electronic machine that stores information and can be programmed to do many tasks.', 'He uses his computer to write and to listen to music.', 'او از رایانه‌اش برای نوشتن و گوش‌دادن به موزیک استفاده می‌کند.', 'رایانه؛ کامپیوتر', ['PC', 'laptop'], []],
            ['phone', 'noun', 'a1', '/fəʊn/', '/foʊn/', 'A device you use to speak to someone far away; also short for mobile phone.', 'Please turn off your phone during the lesson.', 'لطفاً در طول درس گوشی‌تان را خاموش کنید.', 'تلفن؛ گوشی', ['mobile', 'cell'], []],
            ['step by step', 'idiom', 'a2', '/ˌstep baɪ ˈstep/', '/ˌstep baɪ ˈstep/', 'Slowly and gradually, completing one stage at a time.', 'We’re just walking this road, step by step in time.', 'ما فقط قدم‌به‌قدم و همگام با زمان این راه را می‌رویم.', 'قدم‌به‌قدم؛ کم‌کم', ['gradually', 'bit by bit'], ['suddenly']],
            ['pace', 'noun', 'b1', '/peɪs/', '/peɪs/', 'The speed at which someone or something moves or at which something happens.', 'But I’ve learned to move at a kind of pace.', 'اما یاد گرفته‌ام با نوعی آهنگِ خودم حرکت کنم.', 'سرعت؛ آهنگ حرکت', ['speed', 'tempo'], []],
            ['linger', 'verb', 'b2', '/ˈlɪŋɡə/', '/ˈlɪŋɡər/', 'To stay somewhere or keep doing something longer than necessary, often because you enjoy it.', 'Now I linger just to rest a while.', 'حالا فقط برای کمی استراحت کردن درنگ می‌کنم.', 'درنگ کردن؛ ماندن', ['stay', 'dwell'], ['rush', 'hurry']],
            ['quiet grace', 'noun phrase', 'b2', '/ˌkwaɪət ˈɡreɪs/', '/ˌkwaɪət ˈɡreɪs/', 'A calm, gentle and elegant way of being, without showing off.', 'There’s a quiet grace in another sunrise.', 'در هر طلوعِ تازه، ظرافتی آرام هست.', 'ظرافت و وقار آرام', ['poise', 'elegance'], ['clumsiness']],
        ];

        $out = [];
        foreach ($rows as $i => [$phrase, $type, $level, $uk, $us, $meaning, $ex, $exTr, $tr, $syn, $ant]) {
            $out[] = [
                'id' => $i + 1, 'phrase' => $phrase, 'type' => $type, 'level' => $level,
                'ipa' => ['uk' => $uk, 'us' => $us], 'meaning' => $meaning, 'example' => $ex, 'example_tr' => $exTr,
                'translate' => $tr, 'synonyms' => $syn, 'antonyms' => $ant,
                'song' => $i >= 8 ? $song : null,
                'saved_at' => $now - ($i * 3600 * 7),
                'status' => $i === 2 ? 'learned' : ($i === 5 ? 'learning' : 'new'),
            ];
        }

        return self::normalize($out);
    }
}
