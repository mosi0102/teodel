<?php

namespace App\Support;

/**
 * پنل مدیریت — Shadowing (موضوع با نسخه‌های A1…C2). داده‌ی نمونه از app/Support/ShadowingTopics.php ساخته می‌شود.
 *
 * 🔌 قرارداد موضوع (topic):
 *   id, status (draft|published), title, category, cover, is_free, desc_fa, desc_en, repeats (تکرار پیش‌فرض هر جمله), updated_at
 *   versions { a1: {...}, a2: {...}, … c2: {...} } — هر نسخه:
 *     enabled (bool)              این سطح ساخته شود؟ (نسخه‌ی خاموش در سایت نمایش داده نمی‌شود)
 *     audio                       فایل صوتی کل متن (آپلود ادمین یا ساخته‌ی AI)
 *     audio_source                upload | tts
 *     tts { voice, accent, speed }   تنظیمات ساخت صدا با AI (برای «ساخت دوباره»)
 *     sentences[] { text, prosody, audio? }      متن نسخه، جمله‌به‌جمله (prosody: ^ _ - *کلمه* |)
 *     words[]     { phrase, type, level, meaning, translate, where (شماره‌ی جمله‌ای که لغت در آن آمده، از ۱) }
 *     questions[] { q, answer (پاسخ نمونه), keys (کلیدواژه‌ها با ویرگول), criteria (معیار بررسی AI) }
 *
 * AI kinds: shadowing_text | sentences_split | prosody | words_suggest | questions_suggest | tts
 */
class AdminShadowing
{
    public const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];

    public const TABS = ['info', 'a1', 'a2', 'b1', 'b2', 'c1', 'c2'];

    /** قانون‌ها؛ @any = حداقل یک نسخه‌ی روشن، @v:xx = نسخه‌ی روشن باید صوت، جمله و سؤال داشته باشد (خاموش = بدون نیاز) */
    public static function need(): array
    {
        $n = ['info' => ['title', 'category', '@any']];
        foreach (self::LEVELS as $l) {
            $n[$l] = ['@v:'.$l];
        }

        return $n;
    }

    public static function special(): array
    {
        $s = ['@any' => fn ($m) => collect($m['versions'] ?? [])->contains(fn ($v) => ! empty($v['enabled']))];
        foreach (self::LEVELS as $l) {
            $s['@v:'.$l] = function ($m) use ($l) {
                $v = $m['versions'][$l] ?? null;

                return ! $v || empty($v['enabled']) || (AdminContent::filled($v['audio'] ?? '') && ! empty($v['sentences']) && ! empty($v['questions']));
            };
        }

        return $s;
    }

    public static function all(): array
    {
        return array_map(function (array $t) {
            $versions = [];
            foreach (self::LEVELS as $l) {
                $v = collect($t['versions'])->firstWhere('level', $l);
                $versions[$l] = $v ? [
                    'enabled' => true,
                    'audio' => $t['id'] % 2 ? '/songs/'.rawurlencode('Benny Rivers – Step by Step in Time.mp3') : '',
                    'audio_source' => $t['id'] % 2 ? 'upload' : 'tts',
                    'tts' => ['voice' => 'female', 'accent' => 'us', 'speed' => '1'],
                    'sentences' => array_map(fn ($s) => ['text' => $s['text'], 'prosody' => '', 'audio' => ''], $v['sentences']),
                    'words' => array_map(fn ($w) => ['phrase' => $w['phrase'], 'type' => $w['type'], 'level' => $w['level'], 'meaning' => $w['meaning'], 'translate' => $w['translate'], 'where' => self::where($w['phrase'], $v['sentences'])], $v['words']),
                    'questions' => array_map(fn ($q) => ['q' => $q['q'], 'answer' => $q['answer'], 'keys' => implode(', ', $q['keys']), 'criteria' => ''], $v['questions']),
                ] : self::blankVersion(false);
            }
            $row = [
                'id' => $t['id'],
                'status' => $t['id'] % 4 === 0 ? 'draft' : 'published',
                'title' => $t['title'],
                'category' => $t['category'],
                'cover' => '',
                'is_free' => $t['is_free'],
                'desc_fa' => '',
                'desc_en' => '',
                'repeats' => 3,
                'level' => 'all',
                'versions' => $versions,
                'updated_at' => $t['updated_at'],
            ];
            $row['done_map'] = AdminContent::doneMap($row, self::need(), self::special());
            $row['steps_done'] = count(array_filter($row['done_map']));
            $row['levels'] = array_keys(array_filter($versions, fn ($v) => $v['enabled']));

            return $row;
        }, ShadowingTopics::topics());
    }

    public static function query(array $p): array
    {
        $rows = self::all();
        $lvl = $p['level'] ?? 'all';
        if (in_array($lvl, self::LEVELS, true)) {
            $rows = array_values(array_filter($rows, fn ($r) => in_array($lvl, $r['levels'], true)));
        }
        unset($p['level']);

        return AdminContent::query($rows, $p, fn ($r) => $r['title'].' '.$r['category']);
    }

    public static function find(int $id): ?array
    {
        return collect(self::all())->firstWhere('id', $id);
    }

    public static function blank(): array
    {
        $v = [];
        foreach (self::LEVELS as $l) {
            $v[$l] = self::blankVersion(false);
        }

        return ['id' => null, 'status' => 'draft', 'title' => '', 'category' => '', 'cover' => '', 'is_free' => false,
            'desc_fa' => '', 'desc_en' => '', 'repeats' => 3, 'versions' => $v];
    }

    private static function blankVersion(bool $on): array
    {
        return ['enabled' => $on, 'audio' => '', 'audio_source' => 'upload', 'tts' => ['voice' => 'female', 'accent' => 'us', 'speed' => '1'],
            'sentences' => [], 'words' => [], 'questions' => []];
    }

    private static function where(string $phrase, array $sentences): int
    {
        foreach ($sentences as $i => $s) {
            if (stripos($s['text'], $phrase) !== false) {
                return $i + 1;
            }
        }

        return 1;
    }
}
