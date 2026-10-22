<?php

namespace App\Support;

/**
 * بانک سؤال آزمون تعیین سطح در پنل مدیریت (/admin/placement، /admin/placement/create، /admin/placement/{id}/edit).
 * سمت کاربر همین سؤال‌ها را در /level-test می‌خواند (قرارداد: app/Support/PlacementTest.php).
 *
 * 🔌 بک‌اند — مدل یک سؤال (همان کلیدهای PlacementTest + چند کلید مدیریتی):
 *   id, status ('draft'|'published')، section (vocab|grammar|listening)، level (a1|a2|b1|b2|c1)
 *   title    عنوان نوع آزمون (Vocabulary / Grammar / Listening) — پیش‌فرض از بخش
 *   fa       دستور فارسی بالای سؤال      q  متن سؤال انگلیسی (جای خالی: ____)
 *   options  دقیقاً ۴ گزینه              answer  اندیس گزینه‌ی درست (۰ تا ۳)
 *   audio, transcript  (فقط listening)   explain  توضیح کوتاه پاسخ (اختیاری)
 *   stats    { asked, correct_pct }  فقط خواندنی؛ برای سنجش سختی (اگر سؤالی ۹۰٪+ درست یا ۱۰٪− درست جواب داده شود به سطحش شک کنید)
 *   updated_at  ثانیه‌ی یونیکس
 *
 * لیست: کنترلر کل بانک را به‌صورت $rows می‌دهد؛ مرورگر فیلتر/جست‌وجو/صفحه‌بندی/پوشش را از همان حساب می‌کند.
 * ذخیره (data-save-url): POST (ساخت) / PUT (ویرایش) با JSON کامل مدل + CSRF ← 200 { id, status, url? } یا 422 { message, errors:{ 'q':['…'] } }
 * آپلود صوت (data-upload-url): POST multipart { file, kind:'audio' } ← { url, duration? }
 * حذف (data-delete-url): DELETE.
 * ساخت گروهی با AI (data-bulk-url): POST { items:[ مدل سؤال… ] } ← { saved: n }   (همه به‌صورت پیش‌نویس)
 * AI: POST data-ai-url  { kind:'question'|'question_batch', seeds, fields, note, current, locale }  ← { fields, missing }  (قرارداد: resources/js/admin/ai.js)
 *   question_batch: seeds { section, level ('all' = توزیع مساوی روی ۵ سطح), count, topic } ← { fields:{ items:[ مدل سؤال… ] }, missing:[] }
 * یا به‌جای آدرس‌ها: رویدادهای لغوشدنی admin:question-save / -upload / -delete / -bulk  یا  Teodel.admin.question.api.{save,upload,remove,bulk}
 *
 * ⚠️ قانون «انتشار» (validate) را سمت سرور هم اعمال کنید؛ سؤال منتشرشده‌ی ناقص آزمون کاربر را خراب می‌کند.
 * ⚠️ انتخاب سؤال برای آزمون به‌صورت متوازن از هر سطح است: هر بخش باید در هر سطح دست‌کم MIN_PER_LEVEL سؤال «منتشرشده» داشته باشد
 *    (پیشنهاد: GOOD_PER_LEVEL تا آزمون‌ها تکراری نشوند).
 */
class AdminPlacement
{
    public const SECTIONS = ['vocab', 'grammar', 'listening'];

    public const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1'];

    public const MIN_PER_LEVEL = 3;

    public const GOOD_PER_LEVEL = 8;

    public const TITLES = ['vocab' => 'Vocabulary', 'grammar' => 'Grammar', 'listening' => 'Listening'];

    public const FA = [
        'vocab' => 'کلمه‌ی مناسب را برای جای خالی انتخاب کنید.',
        'grammar' => 'گزینه‌ی درست را برای تکمیل جمله انتخاب کنید.',
        'listening' => 'به صدا گوش دهید و به سؤال پاسخ دهید.',
    ];

    /** سؤال خالی برای «سؤال جدید» */
    public static function blank(string $section = 'vocab', string $level = 'a1'): array
    {
        $section = in_array($section, self::SECTIONS, true) ? $section : 'vocab';

        return [
            'id' => null, 'status' => 'draft', 'section' => $section, 'level' => in_array($level, self::LEVELS, true) ? $level : 'a1',
            'title' => self::TITLES[$section], 'fa' => self::FA[$section], 'q' => '', 'options' => ['', '', '', ''], 'answer' => 0,
            'audio' => '', 'transcript' => '', 'explain' => '', 'stats' => ['asked' => 0, 'correct_pct' => null], 'updated_at' => null,
        ];
    }

    /**
     * قانون اعتبار یک سؤال. خروجی: [ ['level'=>'error'|'warn', 'code'=>'q', 'field'=>'q'] … ]
     * (همان قانون resources/js/admin/bank.js → validate)
     */
    public static function validate(array $q): array
    {
        $out = [];
        $add = fn (string $level, string $code, string $field) => $out[] = compact('level', 'code', 'field');
        $section = $q['section'] ?? '';
        if (! in_array($section, self::SECTIONS, true)) {
            $add('error', 'section', 'section');
        }
        if (! in_array($q['level'] ?? '', self::LEVELS, true)) {
            $add('error', 'level', 'level');
        }
        $text = trim((string) ($q['q'] ?? ''));
        if ($text === '') {
            $add('error', 'q_required', 'q');
        } elseif (in_array($section, ['vocab', 'grammar'], true) && ! str_contains($text, '____')) {
            $add('warn', 'blank_missing', 'q');
        }
        $opts = array_map(fn ($o) => trim((string) $o), array_values((array) ($q['options'] ?? [])));
        if (count($opts) !== 4 || in_array('', $opts, true)) {
            $add('error', 'options_required', 'options');
        } elseif (count(array_unique(array_map('mb_strtolower', $opts))) !== 4) {
            $add('error', 'options_distinct', 'options');
        }
        $ans = $q['answer'] ?? null;
        if (! is_numeric($ans) || (int) $ans < 0 || (int) $ans > 3) {
            $add('error', 'answer', 'answer');
        }
        if ($section === 'listening') {
            if (trim((string) ($q['transcript'] ?? '')) === '') {
                $add('error', 'transcript_required', 'transcript');
            }
            if (trim((string) ($q['audio'] ?? '')) === '') {
                $add('warn', 'audio_missing', 'audio');
            }
        }

        return $out;
    }

    public static function isReady(array $q): bool
    {
        foreach (self::validate($q) as $i) {
            if ($i['level'] === 'error') {
                return false;
            }
        }

        return true;
    }

    /** بانک نمونه (۶۰ سؤال از PlacementTest::demoBank + وضعیت/آمار ساختگی) — فقط برای دیدن فرانت */
    public static function all(): array
    {
        static $cache = null;
        if ($cache !== null) {
            return $cache;
        }
        $now = time();
        $out = [];
        foreach (PlacementTest::demoBank() as $i => $b) {
            $id = $i + 1;
            // چند پیش‌نویس و یک کمبود عمدی (c1 شنیداری) تا هشدار پوشش دیده شود
            $draft = $id % 11 === 0 || ($b['section'] === 'listening' && $b['level'] === 'c1' && $id % 2 === 0);
            $asked = $draft ? 0 : 40 + ($id * 37) % 420;
            $base = ['a1' => 88, 'a2' => 74, 'b1' => 58, 'b2' => 44, 'c1' => 31][$b['level']];
            $out[] = [
                'id' => $id, 'status' => $draft ? 'draft' : 'published', 'section' => $b['section'], 'level' => $b['level'],
                'title' => $b['title'], 'fa' => $b['fa'], 'q' => $b['q'], 'options' => $b['options'], 'answer' => $b['answer'],
                'audio' => '', 'transcript' => $b['transcript'] ?? '',
                'explain' => $id % 3 === 0 ? 'گزینه‌ی درست با ساختار و معنی جمله می‌خواند.' : '',
                'stats' => ['asked' => $asked, 'correct_pct' => $asked ? max(5, min(97, $base + (($id * 13) % 17) - 8)) : null],
                'updated_at' => $now - ($id * 5400),
            ];
        }

        return $cache = $out;
    }

    /** همه‌ی سؤال‌ها برای لیست (فیلتر/جست‌وجو/صفحه‌بندی را مرورگر انجام می‌دهد؛ برای چند هزار سؤال کافی است — بعداً می‌شود سمت سرور کرد) */
    public static function demo(): array
    {
        return self::all();
    }

    public static function find(int $id): ?array
    {
        foreach (self::all() as $q) {
            if ($q['id'] === $id) {
                return $q;
            }
        }

        return null;
    }

    /** ماتریس پوشش: [section][level] = تعداد سؤال «منتشرشده» */
    public static function coverage(?array $all = null): array
    {
        $cov = [];
        foreach (self::SECTIONS as $s) {
            foreach (self::LEVELS as $l) {
                $cov[$s][$l] = 0;
            }
        }
        foreach ($all ?? self::all() as $q) {
            if ($q['status'] === 'published') {
                $cov[$q['section']][$q['level']]++;
            }
        }

        return $cov;
    }

    /** وضعیت هر خانه‌ی پوشش: bad (< MIN) | warn (< GOOD) | ok */
    public static function covTone(int $n): string
    {
        return $n < self::MIN_PER_LEVEL ? 'bad' : ($n < self::GOOD_PER_LEVEL ? 'warn' : 'ok');
    }
}
