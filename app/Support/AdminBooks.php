<?php

namespace App\Support;

/**
 * پنل مدیریت — کتاب‌ها. داده‌ی نمونه از app/Support/Books.php ساخته می‌شود.
 *
 * 🔌 قرارداد کتاب در ویرایشگر:
 *   id, status (draft|published), title, author, cover, level (a|b|c), cefr (A1…C2), type (graded|novel|short_stories|non_fiction),
 *   genre, year, publisher, pages, isbn, is_free, recommended, updated_at
 *   summary_fa, summary_en, author_bio_fa, author_bio_en, author_born, author_country, author_works (رشته با ویرگول)
 *   chapters[] { title, text (پاراگراف‌ها با یک خط خالی جدا می‌شوند), summary_fa, summary_en, audio, audio_source (upload|tts), minutes (خودکار) }
 *   file_url (PDF/EPUB برای دانلود), audio (صوت کل کتاب، اختیاری)
 *   words[] { phrase, type, level, meaning, translate }   لغات کلیدی
 *
 * سمت کاربر (Books.php) متن هر فصل به paragraphs[] تبدیل می‌شود: preg_split('/\n\s*\n/', text).
 * AI kinds: book_info | chapter_summary | author_bio | words_suggest | tts
 */
class AdminBooks
{
    public const TABS = ['info', 'summary', 'chapters', 'files', 'words'];

    public const NEED = [
        'info' => ['title', 'author', 'cefr', 'type'],
        'summary' => ['summary_fa', 'author_bio_fa'],
        'chapters' => ['chapters[]:text'],
        'files' => [],
        'words' => ['words[]:phrase'],
    ];

    public static function all(): array
    {
        return array_map(function (array $b) {
            $row = [
                'id' => $b['id'],
                'status' => $b['id'] === 3006 ? 'draft' : 'published',
                'title' => $b['title'],
                'author' => $b['author'],
                'cover' => $b['cover'] ?? '',
                'level' => $b['level'],
                'cefr' => $b['cefr'],
                'type' => $b['type'],
                'genre' => $b['genre'],
                'year' => $b['year'],
                'publisher' => $b['publisher'],
                'pages' => $b['pages'],
                'isbn' => $b['isbn'] ?? '',
                'is_free' => $b['is_free'],
                'recommended' => $b['recommended'],
                'summary_fa' => $b['summary_fa'],
                'summary_en' => $b['summary_en'],
                'author_bio_fa' => $b['author_bio_fa'],
                'author_bio_en' => $b['author_bio_en'],
                'author_born' => $b['author_born'] ?? '',
                'author_country' => $b['author_country'] ?? '',
                'author_works' => implode(', ', $b['author_works'] ?? []),
                'file_url' => $b['file_url'] ?? '',
                'audio' => $b['audio'] ?? '',
                'chapters' => array_map(fn ($c) => [
                    'title' => $c['title'], 'text' => implode("\n\n", $c['paragraphs']),
                    'summary_fa' => $c['summary_fa'] ?? '', 'summary_en' => $c['summary_en'] ?? '',
                    'audio' => $c['audio'] ?? '', 'audio_source' => 'upload', 'minutes' => $c['minutes'],
                ], $b['chapters']),
                'words' => array_map(fn ($w) => ['phrase' => $w['phrase'], 'type' => $w['type'], 'level' => $w['level'], 'meaning' => $w['meaning'], 'translate' => $w['translate']], $b['words']),
                'minutes' => $b['minutes'],
                'updated_at' => $b['updated_at'],
            ];
            $row['done_map'] = AdminContent::doneMap($row, self::NEED);
            $row['steps_done'] = count(array_filter($row['done_map']));

            return $row;
        }, Books::all());
    }

    public static function query(array $p): array
    {
        return AdminContent::query(self::all(), $p, fn ($r) => $r['title'].' '.$r['author'].' '.$r['genre']);
    }

    public static function find(int $id): ?array
    {
        return collect(self::all())->firstWhere('id', $id);
    }

    public static function blank(): array
    {
        return ['id' => null, 'status' => 'draft', 'title' => '', 'author' => '', 'cover' => '', 'level' => 'a', 'cefr' => 'A2', 'type' => 'graded',
            'genre' => '', 'year' => '', 'publisher' => '', 'pages' => '', 'isbn' => '', 'is_free' => false, 'recommended' => false,
            'summary_fa' => '', 'summary_en' => '', 'author_bio_fa' => '', 'author_bio_en' => '', 'author_born' => '', 'author_country' => '', 'author_works' => '',
            'file_url' => '', 'audio' => '', 'chapters' => [], 'words' => []];
    }
}
