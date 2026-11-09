<?php

namespace App\Http\Controllers;

use App\Support\Books;
use App\Support\Progress;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Lang;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: داده‌ها نمونه (mock) هستند (app/Support/Books.php — قرارداد 🔌 بالای همان فایل).
 * کتاب همان view مشترک آموزش (music.lesson) را با ۴ مرحله دارد: intro | read | review | author.
 * دسترسی: ۳ کتاب کاملاً رایگان؛ بقیه برای کاربر رایگان فقط فصل اول (Books::FREE_CHAPTERS). مرحله‌ها قفل نمی‌شوند،
 * فصل‌های قفل اصلاً متنشان به view نمی‌رسد. 🔌 بک‌اند همین کنترل را برای فایل دانلودی و خلاصه‌ی AI هم انجام دهد.
 */
class BookController extends Controller
{
    private const PER_PAGE = 9;

    public function index(Request $request)
    {
        $level = in_array($request->query('level'), ['a', 'b', 'c'], true) ? $request->query('level') : 'a';
        $q = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('filter'), ['all', 'recommended', 'new', 'in_progress', 'done', 'free'], true) ? $request->query('filter') : 'all';
        $type = in_array($request->query('type'), ['all', 'graded', 'novel', 'short_stories', 'non_fiction'], true) ? $request->query('type') : 'all';
        $sort = in_array($request->query('sort'), ['default', 'newest', 'pages', 'shortest', 'title'], true) ? $request->query('sort') : 'default';

        $list = collect(Books::all())
            ->map(fn ($b) => $b + ['status' => Progress::status($b['progress'])])
            ->where('level', $level);

        if ($q !== '') {
            $list = $list->filter(fn ($b) => mb_stripos($b['title'].' '.$b['author'].' '.$b['genre'], $q) !== false);
        }
        if ($type !== 'all') {
            $list = $list->where('type', $type);
        }

        $list = match ($filter) {
            'new', 'in_progress', 'done' => $list->where('status', $filter),
            'free' => $list->where('is_free', true),
            'recommended' => $list->where('recommended', true),
            default => $list,
        };

        $list = match ($sort) {
            'newest' => $list->sortByDesc('updated_at'),
            'pages' => $list->sortByDesc('pages'),
            'shortest' => $list->sortBy('minutes'),
            'title' => $list->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE),
            default => $list->sortByDesc('recommended'),
        };

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $list->values()->forPage($page, self::PER_PAGE)->values(),
            $list->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('books.index', [
            'songs' => $paginator,
            'level' => $level,
            'filters' => ['q' => $q, 'filter' => $filter, 'type' => $type, 'sort' => $sort],
            'subscribed' => $this->subscribed(),
            'stats' => ['completed' => 1, 'in_progress' => 1, 'pages' => 86, 'sentences' => 12],
            'user' => $this->user(),
        ]);
    }

    /** /books/{id} → مرحله‌ای که کاربر باید ادامه بدهد */
    public function show(string $id)
    {
        $book = $this->findOrFail((int) $id);
        $next = min($book['completed_steps'] + 1, count(Books::STEPS));

        return redirect()->route('books.step', ['id' => $book['id'], 'step' => max($next, 1)]);
    }

    /** /books/{id}/step/{step} */
    public function step(string $id, string $step)
    {
        $this->overrideLessonTexts();

        $book = $this->findOrFail((int) $id);
        $total = count(Books::STEPS);
        $n = (int) $step;
        abort_if($n < 1 || $n > $total, 404);
        $completed = (int) $book['completed_steps'];
        $subscribed = $this->subscribed();

        // متن فصل‌های قفل حذف می‌شود (محتوای پولی به HTML نمی‌رسد)
        $book['chapters'] = array_map(function ($c) use ($book, $subscribed) {
            $c['locked'] = Books::chapterLocked($book, $c['n'], $subscribed);
            if ($c['locked']) {
                $c['paragraphs'] = [];
                $c['summary_fa'] = $c['summary_en'] = null;
                $c['audio'] = null;
            }

            return $c;
        }, $book['chapters']);

        $steps = collect(Books::STEPS)->map(fn (string $key, int $i) => [
            'n' => $i + 1,
            'key' => $key,
            'locked' => false,
            'state' => match (true) {
                $i + 1 <= $completed => 'done',
                $i + 1 === $completed + 1 => 'current',
                default => 'upcoming',
            },
            'href' => route('books.step', ['id' => $book['id'], 'step' => $i + 1]),
        ]);

        $finishUrl = route('books.index', ['level' => $book['level']]);

        return view('music.lesson', [
            'kind' => 'book',
            'song' => $book,
            'steps' => $steps,
            'step' => $n,
            'stepKey' => Books::STEPS[$n - 1],
            'total' => $total,
            'completed' => $completed,
            'fraction' => $completed < $total ? (float) $book['current_step_fraction'] : 0.0,
            'prevUrl' => $n > 1 ? route('books.step', ['id' => $book['id'], 'step' => $n - 1]) : null,
            'nextUrl' => $n < $total ? route('books.step', ['id' => $book['id'], 'step' => $n + 1]) : $finishUrl,
            'finishUrl' => $finishUrl,
            'subscribed' => $subscribed,
            'user' => $this->user(),
            // 🔌 آدرس‌های اختیاری بک‌اند (null = حالت دمو در مرورگر؛ قرارداد در resources/js/modules/book-reader.js)
            'urls' => [
                'progress' => null,     // POST { chapter, offset, percent }
                'settings' => null,     // POST { theme, font, size }
                'sentences' => null,    // POST/DELETE { chapter, text, ref }
                'summary' => null,      // POST { chapter } ← { text } (خلاصه‌ی AI)
                'lookup' => null,       // GET ?word= ← اطلاعات لغت
                'download' => route('books.download', ['id' => $book['id']]),
            ],
        ]);
    }

    /**
     * 🔌 فایل دانلودی کتاب. بک‌اند: فایل واقعی (file_url) را با کنترل دسترسی و Content-Disposition: attachment بدهد.
     * دمو: اگر فایل در public نبود، نسخه‌ی قابل‌چاپِ فصل‌های باز را نشان می‌دهد (کاربر «ذخیره به‌عنوان PDF» می‌زند).
     */
    public function download(string $id)
    {
        $book = $this->findOrFail((int) $id);
        $file = $book['file_url'] ? public_path(ltrim(parse_url($book['file_url'], PHP_URL_PATH), '/')) : null;
        if ($file && is_file($file) && ($book['is_free'] || $this->subscribed())) {
            return response()->download($file);
        }

        $subscribed = $this->subscribed();
        $chapters = array_values(array_filter($book['chapters'], fn ($c) => ! Books::chapterLocked($book, $c['n'], $subscribed)));

        return view('books.print', ['book' => $book, 'chapters' => $chapters, 'partial' => count($chapters) < count($book['chapters'])]);
    }

    /* ---------------- داده‌ی نمونه (بک‌اند‌کار جایگزین کند) ---------------- */

    /** TODO(backend): اشتراک فعال؟ */
    private function subscribed(): bool
    {
        return false;
    }

    /** TODO(backend): کاربر واقعی */
    private function user(): array
    {
        return ['name' => 'مهدی پوربشیری', 'days_left' => 0, 'points' => 560, 'streak' => 5, 'level' => 'A2'];
    }

    private function findOrFail(int $id): array
    {
        $book = Books::find($id);
        abort_if($book === null, 404);

        return $book;
    }

    /** متن‌های صفحه‌ی آموزش را برای کتاب عوض می‌کند (lang/{locale}/book_lesson.php روی کلیدهای lesson.*) */
    private function overrideLessonTexts(): void
    {
        __('lesson.title');
        $lines = trans('book_lesson');
        if (is_array($lines)) {
            Lang::addLines(Arr::dot($lines, 'lesson.'), app()->getLocale());
        }
    }
}
