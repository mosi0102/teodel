<?php

namespace App\Support;

/**
 * متن‌های مرحله‌ی ۸ (details): «تفسیر موزیک» و «توضیحات» (آرایه‌های ادبی و نکته‌های زبان انگلیسی).
 *
 * 🔌 بک‌اند (به زبان صفحه) در $song می‌دهد:
 *   'interpretation' => "پاراگراف اول\n\nپاراگراف دوم"           // رشته (پاراگراف‌ها با خط خالی) یا آرایه‌ای از پاراگراف‌ها
 *   'notes' => [                                                  // توضیحات؛ هر بخش یک عنوان و چند مورد
 *       ['title' => 'آرایه‌های ادبی', 'type' => 'literary', 'items' => [
 *           ['term' => 'Metaphor — استعاره', 'example' => 'Little sparks in a grand design', 'text' => 'توضیح…'],
 *       ]],
 *       ['title' => 'نکته‌های زبان', 'type' => 'language', 'items' => [...]],
 *   ]
 *   (notes می‌تواند فقط یک رشته هم باشد: پاراگراف‌های ساده)
 * همه‌ی متن‌ها escape می‌شوند (HTML اجرا نمی‌شود). مقدارهای مشخصات (لهجه، سال، ژانر، …) از خودِ $song می‌آیند.
 * اگر چیزی نرسد، متن نمونه (هم‌خوان با لیریک نمونه) نشان داده می‌شود.
 */
class Details
{
    /** @return array{interpretation: string[], sections: array<int, array{title: ?string, type: string, items: array, paragraphs: string[]}>} */
    public static function forSong(array $song): array
    {
        $demo = self::demo();

        return [
            'interpretation' => self::paragraphs($song['interpretation'] ?? $demo['interpretation']),
            'sections' => self::sections($song['notes'] ?? $demo['notes']),
        ];
    }

    private static function paragraphs(mixed $v): array
    {
        $list = is_array($v) ? $v : preg_split('/\R{2,}/u', (string) $v);

        return array_values(array_filter(array_map(fn ($p) => trim((string) $p), $list), fn ($p) => $p !== ''));
    }

    private static function sections(mixed $notes): array
    {
        if (is_string($notes)) {
            $p = self::paragraphs($notes);

            return $p ? [['title' => null, 'type' => 'language', 'items' => [], 'paragraphs' => $p]] : [];
        }

        $out = [];
        foreach ((array) $notes as $s) {
            $items = [];
            foreach ((array) ($s['items'] ?? []) as $it) {
                $item = [
                    'term' => trim((string) ($it['term'] ?? '')),
                    'example' => trim((string) ($it['example'] ?? '')),
                    'text' => trim((string) ($it['text'] ?? '')),
                ];
                if ($item['term'] !== '' || $item['example'] !== '' || $item['text'] !== '') {
                    $items[] = $item;
                }
            }
            $paragraphs = self::paragraphs($s['text'] ?? []);
            if ($items || $paragraphs) {
                $out[] = [
                    'title' => ($t = trim((string) ($s['title'] ?? ''))) !== '' ? $t : null,
                    'type' => ($s['type'] ?? '') === 'literary' ? 'literary' : 'language',
                    'items' => $items,
                    'paragraphs' => $paragraphs,
                ];
            }
        }

        return $out;
    }

    /** متن نمونه برای تست فرانت (درباره‌ی لیریک نمونه‌ی Step by Step in Time) */
    private static function demo(): array
    {
        if (app()->getLocale() === 'en') {
            return [
                'interpretation' => "The song is a gentle meditation on getting older. The singer no longer runs the way he used to, yet he does not mourn it: slowing down lets him notice the light, the road and the people around him.\n\n"
                    . "Over the verses the mood moves from small losses (plans that did not come true) to quiet acceptance, and the chorus, “step by step in time”, turns that acceptance into a promise: nothing needs to be rushed, because every step still counts.",
                'notes' => [
                    ['title' => 'Literary devices', 'type' => 'literary', 'items' => [
                        ['term' => 'Personification', 'example' => 'Morning light finds lines on my face', 'text' => 'Light is given a human action (“finds”). It makes the passing of time feel personal and gentle.'],
                        ['term' => 'Metaphor', 'example' => 'Little sparks in a grand design', 'text' => 'People are described as tiny sparks inside a bigger plan, without using “like” or “as”.'],
                        ['term' => 'Imagery', 'example' => 'Windows down with a slower song', 'text' => 'A short picture that lets you see, hear and feel the calm of the moment.'],
                        ['term' => 'Repetition', 'example' => 'step by step in time', 'text' => 'The chorus repeats the same idea, so the main message stays in your head.'],
                    ]],
                    ['title' => 'English language notes', 'type' => 'language', 'items' => [
                        ['term' => 'Idiom: step by step', 'example' => 'We’re just walking this road, step by step in time', 'text' => 'It means “slowly, one stage at a time”. Use it for any process you do gradually.'],
                        ['term' => 'Present perfect', 'example' => 'But I’ve learned to move at a kind of pace', 'text' => 'have/has + past participle: a past experience that still matters now.'],
                        ['term' => 'Comparative adjectives', 'example' => 'Every year brings a clearer sight', 'text' => 'One-syllable adjectives take -er: clear → clearer, soft → softer.'],
                        ['term' => 'Used to + verb', 'example' => 'Used to think the best was gone too fast', 'text' => 'A habit or belief in the past that is no longer true.'],
                    ]],
                ],
            ];
        }

        return [
            'interpretation' => "این آهنگ مرور آرام و مهربانانه‌ی پیر شدن است. خواننده دیگر مثل گذشته نمی‌دود، اما برای آن غصه نمی‌خورد؛ آرام‌تر شدن به او فرصت می‌دهد نور، جاده و آدم‌های اطرافش را بهتر ببیند.\n\n"
                . "در بندهای آهنگ، حال‌وهوا از دلخوری‌های کوچک (نقشه‌هایی که درست پیش نرفتند) به پذیرشی آرام می‌رسد و ترجیع‌بند «step by step in time» این پذیرش را به یک قول تبدیل می‌کند: لازم نیست عجله کنیم، چون هر قدم ارزش خودش را دارد.",
            'notes' => [
                ['title' => 'آرایه‌های ادبی', 'type' => 'literary', 'items' => [
                    ['term' => 'جان‌بخشی (Personification)', 'example' => 'Morning light finds lines on my face', 'text' => 'به نور، کاری انسانی (پیدا کردن) داده شده است. این کار گذر زمان را شخصی و ملایم نشان می‌دهد.'],
                    ['term' => 'استعاره (Metaphor)', 'example' => 'Little sparks in a grand design', 'text' => 'آدم‌ها بدون «مثل» یا «مانند»، جرقه‌های کوچکی در یک طرح بزرگ‌تر توصیف شده‌اند.'],
                    ['term' => 'تصویرسازی (Imagery)', 'example' => 'Windows down with a slower song', 'text' => 'یک تصویر کوتاه که آرامش آن لحظه را می‌شود دید، شنید و حس کرد.'],
                    ['term' => 'تکرار (Repetition)', 'example' => 'step by step in time', 'text' => 'ترجیع‌بند یک پیام را تکرار می‌کند تا ایده‌ی اصلی در ذهن بماند.'],
                ]],
                ['title' => 'نکته‌های زبان انگلیسی', 'type' => 'language', 'items' => [
                    ['term' => 'اصطلاح: step by step', 'example' => 'We’re just walking this road, step by step in time', 'text' => 'یعنی «آهسته و مرحله‌به‌مرحله». برای هر کاری که کم‌کم انجام می‌شود به کار می‌رود.'],
                    ['term' => 'حال کامل (Present perfect)', 'example' => 'But I’ve learned to move at a kind of pace', 'text' => 'have/has + قسمت سوم فعل: تجربه‌ی گذشته‌ای که هنوز برای الان مهم است.'],
                    ['term' => 'صفت تفضیلی (Comparative)', 'example' => 'Every year brings a clearer sight', 'text' => 'صفت‌های یک‌سیلابی -er می‌گیرند: clear ← clearer، soft ← softer.'],
                    ['term' => 'Used to + فعل', 'example' => 'Used to think the best was gone too fast', 'text' => 'عادت یا باوری در گذشته که دیگر درست نیست.'],
                ]],
            ],
        ];
    }
}
