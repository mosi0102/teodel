<?php

namespace App\Support;

/**
 * داده‌های تکمیلی هر لغت (مشترک بین دیکشنری، لغت‌های ذخیره‌شده و پنل مدیریت).
 * همه‌ی کلیدها اختیاری‌اند؛ فرانت هر بخش را «فقط اگر داده داشته باشد» نشان می‌دهد.
 *
 *   level        a1 | a2 | b1 | b2 | c1 | c2
 *   tags         زیرمجموعه‌ی ['gre', 'ielts', 'idiom', 'slang']  ← فیلتر «دسته» در دیکشنری
 *                (لغت‌هایی که type آن‌ها idiom است، در فیلتر «اصطلاحات» هم می‌آیند)
 *   connotation  positive | neutral | negative   ← بار معنایی/احساسی لغت
 *   senses       نقش‌های دستوری دیگر: [{ pos, meaning, example, example_tr, translate }]
 *   collocations ['make a decision', …]
 *   family       هم‌خانواده‌ها: [{ word, pos }]            مثلاً write ← writer, writing
 *   morphemes    ریشه‌شناسی/اجزای واژه: [{ part, meaning }]  مثلاً speechless = speech + less
 *   origin       توضیح آزاد ریشه‌ی لغت (اختیاری)
 *
 * (synonyms و antonyms از قبل در خود لغت هستند.)
 */
class WordExtras
{
    public const LEVELS = ['a1', 'a2', 'b1', 'b2', 'c1', 'c2'];

    public const TAGS = ['gre', 'ielts', 'idiom', 'slang'];

    public const CONNOTATIONS = ['positive', 'neutral', 'negative'];

    /** @return array<string, mixed> */
    public static function normalize(array $r): array
    {
        $level = strtolower((string) ($r['level'] ?? ''));
        $conn = strtolower((string) ($r['connotation'] ?? ''));

        $senses = [];
        foreach ((array) ($r['senses'] ?? []) as $s) {
            $pos = trim((string) ($s['pos'] ?? ''));
            $meaning = trim((string) ($s['meaning'] ?? ''));
            if ($pos === '' && $meaning === '') {
                continue;
            }
            $senses[] = [
                'pos' => $pos,
                'meaning' => $meaning,
                'example' => trim((string) ($s['example'] ?? '')),
                'example_tr' => trim((string) ($s['example_tr'] ?? '')),
                'translate' => trim((string) ($s['translate'] ?? '')),
            ];
        }

        $family = [];
        foreach ((array) ($r['family'] ?? []) as $f) {
            $word = is_array($f) ? trim((string) ($f['word'] ?? '')) : trim((string) $f);
            if ($word !== '') {
                $family[] = ['word' => $word, 'pos' => is_array($f) ? trim((string) ($f['pos'] ?? '')) : ''];
            }
        }

        $morphemes = [];
        foreach ((array) ($r['morphemes'] ?? []) as $m) {
            $part = trim((string) ($m['part'] ?? ''));
            if ($part !== '') {
                $morphemes[] = ['part' => $part, 'meaning' => trim((string) ($m['meaning'] ?? ''))];
            }
        }

        return [
            'level' => in_array($level, self::LEVELS, true) ? $level : '',
            'tags' => array_values(array_intersect(self::TAGS, array_map('strtolower', (array) ($r['tags'] ?? [])))),
            'connotation' => in_array($conn, self::CONNOTATIONS, true) ? $conn : '',
            'senses' => $senses,
            'collocations' => array_values(array_filter(array_map('trim', array_map('strval', (array) ($r['collocations'] ?? []))))),
            'family' => $family,
            'morphemes' => $morphemes,
            'origin' => trim((string) ($r['origin'] ?? '')),
        ];
    }
}
