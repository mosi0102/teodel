<?php

namespace App\Http\Controllers;

use App\Support\ShadowingTopics;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Lang;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: داده‌ها نمونه (mock) هستند (app/Support/ShadowingTopics.php).
 * Shadowing = موضوع‌هایی (مثل معرفی خود، شغل) که هر کدام نسخه‌ی A1…C2 با متن منسجم دارند.
 * آموزش هر نسخه ۵ مرحله دارد و از همان view آموزش (music.lesson) با $kind='shadowing' استفاده می‌کند:
 *   ۱ listen (هر جمله ۳ بار) | ۲ record (ضبط صدا + بررسی AI) | ۳ practice (تکرار ۱ و ۲ برای بقیه‌ی جمله‌ها)
 *   ۴ text (متن کامل + لغت‌ها) | ۵ questions (پاسخ صوتی به سوال‌ها، بررسی AI)
 */
class ShadowingController extends Controller
{
    private const PER_PAGE = 9;

    public const STEPS = ShadowingTopics::STEPS;

    public function index(Request $request)
    {
        $levels = ShadowingTopics::LEVELS;
        $level = in_array($request->query('level'), $levels, true) ? $request->query('level') : 'all';
        $q = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('filter'), ['all', 'new', 'in_progress', 'done', 'free'], true) ? $request->query('filter') : 'all';
        $sort = in_array($request->query('sort'), ['default', 'newest', 'title'], true) ? $request->query('sort') : 'default';

        $list = collect(ShadowingTopics::topics());

        if ($level !== 'all') {
            $list = $list->filter(fn ($t) => in_array($level, $t['levels'], true));
        }
        if ($q !== '') {
            $list = $list->filter(fn ($t) => mb_stripos($t['title'] . ' ' . $t['category'] . ' ' . __('shadowing.topic.' . $t['key'] . '.title'), $q) !== false);
        }
        $list = match ($filter) {
            'new', 'in_progress', 'done' => $list->where('status', $filter),
            'free' => $list->where('is_free', true),
            default => $list,
        };
        $list = match ($sort) {
            'newest' => $list->sortByDesc('updated_at'),
            'title' => $list->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE),
            default => $list->sortBy('id'),
        };

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $list->values()->forPage($page, self::PER_PAGE)->values(),
            $list->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('shadowing.index', [
            'topics' => $paginator,
            'level' => $level,
            'userLevel' => 'a2', // TODO(backend): سطح کاربر (برای پیشنهاد نسخه‌ی مناسب)
            'filters' => ['q' => $q, 'filter' => $filter, 'sort' => $sort],
            'subscribed' => $this->subscribed(),
            'stats' => ['completed' => 3, 'in_progress' => 3, 'points' => 180, 'highlights' => 31],
            'user' => $this->user(),
        ]);
    }

    /** /shadowing/{id} → مرحله‌ای که کاربر باید ادامه بدهد */
    public function show(string $id)
    {
        $v = $this->findOrFail((int) $id);
        $next = min($v['completed_steps'] + 1, count(self::STEPS));
        if ($this->isLocked($v, $next)) {
            $next = $v['free_steps'];
        }

        return redirect()->route('shadowing.step', ['id' => $v['id'], 'step' => max($next, 1)]);
    }

    /** /shadowing/{id}/step/{step} */
    public function step(string $id, string $step)
    {
        $this->overrideLessonTexts();

        $v = $this->findOrFail((int) $id);
        $total = count(self::STEPS);
        $n = (int) $step;
        abort_if($n < 1 || $n > $total, 404);
        $completed = (int) $v['completed_steps'];

        $steps = collect(self::STEPS)->map(function (string $key, int $i) use ($v, $completed) {
            $num = $i + 1;
            $locked = $this->isLocked($v, $num);

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
                'href' => route('shadowing.step', ['id' => $v['id'], 'step' => $num]),
            ];
        });

        $finishUrl = route('shadowing.index', ['level' => $v['level']]);
        $topic = collect(ShadowingTopics::topics())->firstWhere('id', $v['topic_id']);
        $others = collect($topic['versions'])->where('id', '!=', $v['id'])->values();

        return view('music.lesson', [
            'kind' => 'shadowing',
            'topic' => $topic,
            'otherVersions' => $others,
            'urls' => ['check' => null, 'answer' => null],
            'song' => $v,
            'steps' => $steps,
            'step' => $n,
            'stepKey' => self::STEPS[$n - 1],
            'total' => $total,
            'completed' => $completed,
            'fraction' => $completed < $total ? (float) $v['current_step_fraction'] : 0.0,
            'prevUrl' => $n > 1 ? route('shadowing.step', ['id' => $v['id'], 'step' => $n - 1]) : null,
            'nextUrl' => $n < $total ? route('shadowing.step', ['id' => $v['id'], 'step' => $n + 1]) : $finishUrl,
            'finishUrl' => $finishUrl,
            'subscribed' => $this->subscribed(),
            'user' => $this->user(),
        ]);
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

    private function isLocked(array $v, int $n): bool
    {
        return ! $this->subscribed() && ! $v['is_free'] && $n > $v['free_steps'];
    }

    private function findOrFail(int $id): array
    {
        $v = $this->mock()->firstWhere('id', $id);
        abort_if($v === null, 404);

        return $v;
    }

    private function mock(): Collection
    {
        return collect(ShadowingTopics::all());
    }

    /** متن‌های صفحه‌ی آموزش را برای shadowing عوض می‌کند (lang/{locale}/shadowing_lesson.php؛ کلیدها همان کلیدهای lesson.* هستند) */
    private function overrideLessonTexts(): void
    {
        __('lesson.title'); // ابتدا گروه lesson بارگذاری شود
        $lines = trans('shadowing_lesson');
        if (is_array($lines)) {
            Lang::addLines(\Illuminate\Support\Arr::dot($lines, 'lesson.'), app()->getLocale());
        }
    }
}
