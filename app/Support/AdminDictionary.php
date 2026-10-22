<?php

namespace App\Support;

/**
 * مدیریت دیکشنری (/admin/dictionary، /admin/dictionary/create، /admin/dictionary/{id}/edit).
 * سمت کاربر: app/Support/Dictionary.php (همان کلیدهای لغت) — اینجا فقط مدیریتشان است.
 * هدف: بعداً دیکشنری با تصویرِ یک‌دست ساخته شود؛ پس پوشش تصویر/تلفظ/مثال در لیست پیگیری می‌شود.
 *
 * 🔌 بک‌اند — هر مدخل (لیست کامل را کنترلر به‌صورت $rows می‌دهد؛ مرورگر فیلتر/جست‌وجو/صفحه‌بندی را انجام می‌دهد؛
 *   برای ده‌ها هزار مدخل می‌توانید بعداً به صفحه‌بندی سمت سرور تبدیلش کنید):
 *   id, status ('published'|'draft')، phrase، type (noun|verb|adjective|adverb|pronoun|preposition|conjunction|interjection|phrasal verb|idiom|other)،
 *   ipa{uk,us}، audio{uk,us}، meaning (تعریف انگلیسی)، example، example_tr، translate (فارسی)،
 *   synonyms[]، antonyms[]، image (آدرس یا null)، updated_at
 *   — همان کلیدهای Dictionary::normalize + status و updated_at.
 *
 * ذخیره (صفحه‌ی ویرایش): data-save-url (POST ساخت، PUT ویرایش؛ JSON همین مدل + CSRF) ← 200 { id, status } | 422 { message, errors }
 * آپلود: data-upload-url (POST multipart: file, kind = image|audio_uk|audio_us) ← { url }
 * حذف: data-delete-url (DELETE). ساخت گروهی (دیالوگ لیست): data-bulk-url (POST { items:[مدخل…] }) ← { saved: n }
 * یا رویدادهای لغوشدنی admin:word-save / -upload / -delete / -bulk  یا  Teodel.admin.dictionary.api.{save,upload,remove,bulk}
 * مدخل تکراری (همان phrase + type) را سرور رد کند: 422 { errors:{ phrase:['…'] } }.
 *
 * 🔌 AI (Teodel.admin.ai.request؛ پاسخ: { fields, missing }):
 *   kind 'word'        seeds {phrase, type?}  fields: type, ipa_uk, ipa_us, meaning, example, example_tr, translate, synonyms, antonyms, image, audio_uk, audio_us
 *                      — تصویر و صوت را AI نمی‌سازد: در missing برگردانید تا مدیر دستی بگذارد.
 *   kind 'word_batch'  seeds {words:'هر خط یک لغت', type?}  ← { fields:{ items:[ {phrase,type,ipa{uk,us},meaning,example,example_tr,translate,synonyms[],antonyms[]} … ] }, missing:[] }
 */
class AdminDictionary
{
    public const TYPES = ['noun', 'verb', 'adjective', 'adverb', 'pronoun', 'preposition', 'conjunction', 'interjection', 'phrasal verb', 'idiom', 'other'];

    /** بخش‌های «کامل بودن» هر مدخل (به ترتیب نمایش) */
    public const PARTS = ['core', 'meaning', 'example', 'ipa', 'audio', 'image'];

    /** کلید یکتا برای تشخیص لغت تکراری (phrase + type؛ همان قانون dupKey در dictionary.js) */
    public static function key(string $phrase, string $type): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $phrase))).'|'.$type;
    }

    public static function blank(): array
    {
        return [
            'id' => null, 'status' => 'draft', 'phrase' => '', 'type' => '', 'ipa' => ['uk' => '', 'us' => ''], 'audio' => ['uk' => '', 'us' => ''],
            'meaning' => '', 'example' => '', 'example_tr' => '', 'translate' => '', 'synonyms' => [], 'antonyms' => [], 'image' => null, 'updated_at' => null,
        ];
    }

    /** مدخل‌های نمونه (از دیکشنری سمت کاربر) — فقط برای دیدن فرانت */
    public static function demo(): array
    {
        $now = time();
        $out = [];
        foreach (Dictionary::demo() as $i => $w) {
            $has = fn (int $mod, int $r = 0) => $i % $mod !== $r;
            $out[] = [
                'id' => $i + 1,
                'status' => ($i % 8 === 5) ? 'draft' : 'published',
                'phrase' => $w['phrase'], 'type' => $w['type'],
                'ipa' => $has(11, 3) ? $w['ipa'] : ['uk' => '', 'us' => ''],
                'audio' => $has(3, 1) ? ['uk' => 'https://example.com/a/'.$w['key'].'-uk.mp3', 'us' => 'https://example.com/a/'.$w['key'].'-us.mp3'] : ['uk' => '', 'us' => ''],
                'meaning' => $has(13, 6) ? $w['meaning'] : '',
                'example' => $w['example'], 'example_tr' => $has(7, 2) ? $w['example_tr'] : '',
                'translate' => $w['translate'], 'synonyms' => $w['synonyms'], 'antonyms' => $w['antonyms'],
                'image' => ($i % 4 === 0) ? 'https://example.com/img/'.$w['key'].'.jpg' : null,
                'updated_at' => $now - ($i * 7200 + 900),
            ];
        }

        return $out;
    }

    public static function find(int $id): ?array
    {
        foreach (self::demo() as $r) {
            if ($r['id'] === $id) {
                return $r;
            }
        }

        return null;
    }

    /** کدام بخش‌ها کامل‌اند؟ (همان قانون JS) @return array<string,bool> */
    public static function parts(array $w): array
    {
        $f = fn ($v) => trim((string) $v) !== '';

        return [
            'core' => $f($w['phrase'] ?? '') && $f($w['type'] ?? '') && $f($w['translate'] ?? ''),
            'meaning' => $f($w['meaning'] ?? ''),
            'example' => $f($w['example'] ?? '') && $f($w['example_tr'] ?? ''),
            'ipa' => $f($w['ipa']['uk'] ?? '') || $f($w['ipa']['us'] ?? ''),
            'audio' => $f($w['audio']['uk'] ?? '') || $f($w['audio']['us'] ?? ''),
            'image' => $f($w['image'] ?? ''),
        ];
    }
}
