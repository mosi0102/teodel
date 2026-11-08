<?php

namespace App\Http\Controllers;

use App\Support\Podcast;
use App\Support\Progress;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: داده‌ها نمونه (mock) هستند (app/Support/Podcast.php).
 * پادکست همان ساختار آموزش موزیک را دارد، با ۷ مرحله (بدون karaoke):
 *   ۱ play (گوش‌دادن + «چقدر متوجه شدی؟») | ۲ lyrics (متن همزمان) | ۳ words (لغات + PDF) | ۴ listening (جمله‌ها را سیستم انتخاب می‌کند)
 *   ۵ shadowing | ۶ repeat (دوباره «چقدر متوجه شدی؟») | ۷ details (اطلاعات پادکست)
 * صفحه‌ی آموزش همان view موزیک (music.lesson) است؛ فقط متن‌ها با lang/{fa,en}/podcast_lesson.php عوض می‌شوند.
 */
class PodcastController extends Controller
{
    private const PER_PAGE = 9;

    public const STEPS = Podcast::STEPS;

    public function index(Request $request)
    {
        $level = in_array($request->query('level'), ['a', 'b', 'c'], true) ? $request->query('level') : 'a';
        $q = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('filter'), ['all', 'recommended', 'new', 'in_progress', 'done', 'free'], true) ? $request->query('filter') : 'all';
        $sort = in_array($request->query('sort'), ['default', 'newest', 'episodes', 'duration', 'title'], true) ? $request->query('sort') : 'default';

        $list = collect(Podcast::series())->map(fn ($s) => $s + ['status' => $this->seriesStatus($s)])->where('level', $level);

        if ($q !== '') {
            $list = $list->filter(fn ($s) => mb_stripos($s['title'] . ' ' . $s['host'] . ' ' . $s['category'], $q) !== false);
        }

        $list = match ($filter) {
            'new', 'in_progress', 'done' => $list->where('status', $filter),
            'free' => $list->where('is_free', true),
            'recommended' => $list->where('recommended', true),
            default => $list,
        };

        $list = match ($sort) {
            'newest' => $list->sortByDesc('updated_at'),
            'episodes' => $list->sortByDesc('episodes_count'),
            'duration' => $list->sortBy('avg_seconds'),
            'title' => $list->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE),
            default => $list->sortByDesc('recommended'), // پیش‌فرض: پیشنهادی‌های ادمین اول
        };

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $list->values()->forPage($page, self::PER_PAGE)->values(),
            $list->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('podcast.index', [
            'songs' => $paginator,
            'level' => $level,
            'filters' => ['q' => $q, 'filter' => $filter, 'sort' => $sort],
            'subscribed' => $this->subscribed(),
            'stats' => ['completed' => 6, 'in_progress' => 3, 'points' => 240, 'highlights' => 31],
            'user' => $this->user(),
        ]);
    }

    /** /podcast/series/{id} — اطلاعات پادکست و لیست اپیزودها */
    public function series(string $id)
    {
        $series = collect(Podcast::series())->firstWhere('id', (int) $id);
        abort_if($series === null, 404);

        return view('podcast.series', ['series' => $series, 'subscribed' => $this->subscribed(), 'user' => $this->user()]);
    }

    private function seriesStatus(array $s): string
    {
        if ($s['progress'] >= 100) {
            return 'done';
        }

        return collect($s['episodes'])->contains(fn ($e) => $e['completed_steps'] > 0) ? 'in_progress' : 'new';
    }

    /** /podcast/{id} → مرحله‌ای که کاربر باید ادامه بدهد */
    public function show(string $id)
    {
        $ep = $this->findOrFail((int) $id);
        $next = min($ep['completed_steps'] + 1, count(self::STEPS));
        if ($this->isLocked($ep, $next)) {
            $next = $ep['free_steps'];
        }

        return redirect()->route('podcast.step', ['id' => $ep['id'], 'step' => max($next, 1)]);
    }

    /** /podcast/{id}/step/{step} */
    public function step(string $id, string $step)
    {
        $this->overrideLessonTexts();

        $ep = $this->findOrFail((int) $id);
        $total = count(self::STEPS);
        $n = (int) $step;
        abort_if($n < 1 || $n > $total, 404);
        $completed = (int) $ep['completed_steps'];

        $steps = collect(self::STEPS)->map(function (string $key, int $i) use ($ep, $completed) {
            $num = $i + 1;
            $locked = $this->isLocked($ep, $num);

            return [
                'n' => $num,
                'key' => $key,
                'locked' => $locked,
                'state' => match (true) {
                    $locked => 'locked',
                    $num <= $completed => 'done',
                    $num === $completed + 1 => 'current',
                    default => 'upcoming',
                },
                'href' => route('podcast.step', ['id' => $ep['id'], 'step' => $num]),
            ];
        });

        $finishUrl = route('podcast.series', ['id' => $ep['series_id']]);
        $series = collect(Podcast::series())->firstWhere('id', $ep['series_id']);
        $nextEpisode = collect($series['episodes'])->firstWhere('number', $ep['number'] + 1);

        return view('music.lesson', [
            'kind' => 'podcast',
            'series' => $series,
            'nextEpisode' => $nextEpisode,
            'urls' => ['main_idea' => null, 'words_pdf' => null],
            'song' => $ep,
            'steps' => $steps,
            'step' => $n,
            'stepKey' => self::STEPS[$n - 1],
            'total' => $total,
            'completed' => $completed,
            'fraction' => $completed < $total ? (float) $ep['current_step_fraction'] : 0.0,
            'prevUrl' => $n > 1 ? route('podcast.step', ['id' => $ep['id'], 'step' => $n - 1]) : null,
            'nextUrl' => $n < $total ? route('podcast.step', ['id' => $ep['id'], 'step' => $n + 1]) : $finishUrl,
            'finishUrl' => $finishUrl,
            'subscribed' => $this->subscribed(),
            'user' => $this->user(),
        ]);
    }

    /**
     * 🔌 PDF لغت‌های دشوار (مرحله‌ی ۳). بک‌اند فایل PDF واقعی را بسازد و همین آدرس را با Content-Disposition: attachment بدهد.
     * در دمو: صفحه‌ی قابل‌چاپ (کاربر «ذخیره به‌عنوان PDF» می‌زند).
     */
    public function wordsPdf(string $id)
    {
        $ep = $this->findOrFail((int) $id);
        abort_if($this->isLocked($ep, 3), 403);

        return view('podcast.words-print', ['song' => $ep]);
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

    private function isLocked(array $ep, int $n): bool
    {
        return ! $this->subscribed() && ! $ep['is_free'] && $n > $ep['free_steps'];
    }

    private function findOrFail(int $id): array
    {
        $ep = $this->mock()->firstWhere('id', $id);
        abort_if($ep === null, 404);

        return $ep;
    }

    private function mock(): Collection
    {
        return collect(Podcast::all());
    }

    /** متن‌های صفحه‌ی آموزش را برای پادکست عوض می‌کند (lang/{locale}/podcast_lesson.php؛ کلیدها همان کلیدهای lesson.* هستند) */
    private function overrideLessonTexts(): void
    {
        __('lesson.title'); // ابتدا گروه lesson بارگذاری شود تا جایگزینی فایل اصلی را کامل نکند
        $lines = trans('podcast_lesson');
        if (is_array($lines)) {
            Lang::addLines(\Illuminate\Support\Arr::dot($lines, 'lesson.'), app()->getLocale());
        }
    }
}
