<?php

namespace App\Http\Controllers;

use App\Support\Progress;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

/**
 * ⚠️ فقط برای نمایش فرانت‌اند: داده‌ها نمونه (mock) هستند.
 * بک‌اند‌کار باید mock()، user() و مقدار $subscribed را با داده‌ی واقعی عوض کند
 * و فیلتر/مرتب‌سازی/صفحه‌بندی را روی دیتابیس انجام دهد. قرارداد داده در README.md است.
 */
class MusicController extends Controller
{
    private const PER_PAGE = 9;

    /** ترتیب ثابت ۸ مرحله‌ی آموزش (همین کلیدها در فایل lesson.php زبان‌ها و پوشه‌ی resources/views/music/steps هم هستند) */
    public const STEPS = ['play', 'lyrics', 'words', 'listening', 'shadowing', 'karaoke', 'repeat', 'details'];

    /* ------------------------------------------------------------------
     | لیست موزیک‌ها
     ------------------------------------------------------------------ */
    public function index(Request $request)
    {
        $level = in_array($request->query('level'), ['a', 'b', 'c'], true) ? $request->query('level') : 'a';
        $q = trim((string) $request->query('q', ''));
        $filter = in_array($request->query('filter'), ['all', 'new', 'in_progress', 'done', 'free'], true) ? $request->query('filter') : 'all';
        $sort = in_array($request->query('sort'), ['default', 'newest', 'points', 'duration', 'title'], true) ? $request->query('sort') : 'default';

        $songs = $this->mock()->where('level', $level);

        if ($q !== '') {
            $songs = $songs->filter(fn ($s) => mb_stripos($s['title'] . ' ' . $s['artist'], $q) !== false);
        }

        $songs = match ($filter) {
            'new', 'in_progress', 'done' => $songs->where('status', $filter),
            'free' => $songs->where('is_free', true),
            default => $songs,
        };

        $songs = match ($sort) {
            'newest' => $songs->sortByDesc('id'),
            'points' => $songs->sortByDesc('points'),
            'duration' => $songs->sortBy('duration'),
            'title' => $songs->sortBy('title', SORT_NATURAL | SORT_FLAG_CASE),
            default => $songs,
        };

        $page = LengthAwarePaginator::resolveCurrentPage();
        $paginator = new LengthAwarePaginator(
            $songs->forPage($page, self::PER_PAGE)->values(),
            $songs->count(),
            self::PER_PAGE,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('music.index', [
            'songs' => $paginator,
            'level' => $level,
            'filters' => ['q' => $q, 'filter' => $filter, 'sort' => $sort],
            'subscribed' => $this->subscribed(),
            'stats' => ['completed' => 15, 'in_progress' => 15, 'points' => 560, 'highlights' => 56],
            'user' => $this->user(),
        ]);
    }

    /* ------------------------------------------------------------------
     | صفحه‌ی آموزش
     ------------------------------------------------------------------ */

    /** /music/{id}  →  هدایت به مرحله‌ای که کاربر باید ادامه بدهد */
    public function show(string $id)
    {
        $song = $this->findOrFail((int) $id);

        $next = min($song['completed_steps'] + 1, count(self::STEPS));
        if ($this->isLocked($song, $next)) {
            $next = $song['free_steps'];
        }

        return redirect()->route('music.step', ['id' => $song['id'], 'step' => max($next, 1)]);
    }

    /** /music/{id}/step/{step} */
    public function step(string $id, string $step)
    {
        $song = $this->findOrFail((int) $id);
        $total = count(self::STEPS);
        $n = (int) $step;
        abort_if($n < 1 || $n > $total, 404);

        $completed = (int) $song['completed_steps'];

        // وضعیت هر مرحله برای نوار مراحل: done | current | upcoming | locked
        // current (نارنجی) = مرحله‌ی بعدی که باید انجام شود، نه لزوماً مرحله‌ای که الان باز است.
        // (بعد از لود، جاوااسکریپت صفحه همین وضعیت‌ها را زنده نگه می‌دارد.)
        $steps = collect(self::STEPS)->map(function (string $key, int $i) use ($song, $completed) {
            $num = $i + 1;
            $locked = $this->isLocked($song, $num);

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
                'href' => route('music.step', ['id' => $song['id'], 'step' => $num]),
            ];
        });

        $finishUrl = route('music.index', ['level' => $song['level']]);

        return view('music.lesson', [
            'song' => $song,
            'steps' => $steps,
            'step' => $n,
            'stepKey' => self::STEPS[$n - 1],
            'total' => $total,
            'completed' => $completed,
            // چه بخشی از «مرحله‌ی بعد از آخرین مرحله‌ی تمام‌شده» انجام شده (۰ تا ۱)
            'fraction' => $completed < $total ? (float) $song['current_step_fraction'] : 0.0,
            'prevUrl' => $n > 1 ? route('music.step', ['id' => $song['id'], 'step' => $n - 1]) : null,
            'nextUrl' => $n < $total ? route('music.step', ['id' => $song['id'], 'step' => $n + 1]) : $finishUrl,
            'finishUrl' => $finishUrl,
            'subscribed' => $this->subscribed(),
            'user' => $this->user(),
        ]);
    }

    /* ------------------------------------------------------------------
     | داده‌ی نمونه (بک‌اند‌کار جایگزین کند)
     ------------------------------------------------------------------ */

    /** TODO(backend): آیا کاربر اشتراک فعال دارد؟ */
    private function subscribed(): bool
    {
        return false;
    }

    /** TODO(backend): کاربر واقعی */
    private function user(): array
    {
        return [
            'name' => 'مهدی پوربشیری',
            'days_left' => 0,
            'points' => 560,
            'level' => 'A2',
            'skills' => ['listening' => 'A3', 'reading' => 'A1', 'writing' => 'A2', 'speaking' => 'A2'],
        ];
    }

    /** مرحله‌ی $n برای کاربر رایگان روی آهنگ‌های غیررایگان قفل است (بعد از free_steps مرحله) */
    private function isLocked(array $song, int $n): bool
    {
        return ! $this->subscribed() && ! $song['is_free'] && $n > $song['free_steps'];
    }

    private function findOrFail(int $id): array
    {
        $song = $this->mock()->firstWhere('id', $id);
        abort_if($song === null, 404);

        return $song;
    }

    /** ۳۶ آهنگ نمونه: ۱۲ تا برای هر سطح. سه آهنگ اول (id 1..3) کاملاً رایگان‌اند. */
    private function mock(): Collection
    {
        $catalog = [
            'a' => [
                ['Let Justice Run Down', 'Gregory Watson'], ['Say My Name', 'David Guetta'], ['Twilight', 'Henry Longfellow'],
                ['Perfect', 'Ed Sheeran'], ['Count on Me', 'Bruno Mars'], ['Happy', 'Pharrell Williams'],
                ['Shake It Off', 'Taylor Swift'], ['Counting Stars', 'OneRepublic'], ['Let Her Go', 'Passenger'],
                ['Dance Monkey', 'Tones and I'], ['Sunflower', 'Post Malone'], ['Believer', 'Imagine Dragons'],
            ],
            'b' => [
                ['Someone Like You', 'Adele'], ['Photograph', 'Ed Sheeran'], ['Stay With Me', 'Sam Smith'],
                ['Skyfall', 'Adele'], ['Shallow', 'Lady Gaga'], ['Viva La Vida', 'Coldplay'],
                ['Take Me to Church', 'Hozier'], ['Radioactive', 'Imagine Dragons'], ['Demons', 'Imagine Dragons'],
                ['Hello', 'Adele'], ['Fix You', 'Coldplay'], ['Riptide', 'Vance Joy'],
            ],
            'c' => [
                ['Bohemian Rhapsody', 'Queen'], ['Hotel California', 'Eagles'], ['Imagine', 'John Lennon'],
                ['Hallelujah', 'Jeff Buckley'], ['Stairway to Heaven', 'Led Zeppelin'], ['Fast Car', 'Tracy Chapman'],
                ['The Sound of Silence', 'Simon & Garfunkel'], ['Creep', 'Radiohead'], ['Yesterday', 'The Beatles'],
                ['Wish You Were Here', 'Pink Floyd'], ['Space Oddity', 'David Bowie'], ['Losing My Religion', 'R.E.M.'],
            ],
        ];

        $total = count(self::STEPS);
        $id = 0;
        $out = collect();

        foreach ($catalog as $level => $list) {
            foreach ($list as $i => [$title, $artist]) {
                $id++;

                // چند مرحله تمام شده؟ (آهنگ ۱: کامل | ۲: در حال انجام | بقیه به‌صورت نمونه)
                $completed = match (true) {
                    $id === 1 => $total,
                    $id === 2 => 1,
                    $i % 5 === 3 => 1 + ($id % 4),
                    $i % 7 === 5 => $total,
                    default => 0,
                };
                $fraction = ($completed > 0 && $completed < $total) ? 0.5 : 0.0;
                $percent = Progress::percent($completed, $fraction, $total);

                $out->push([
                    'id' => $id,
                    'title' => $title,
                    'artist' => $artist,
                    'cover' => '/covers/111.jpg', // آدرس کاور واقعی (ترجیحاً WebP و از دامنه‌ی خودتان)
                    'audio' => '/songs/Benny Rivers – Step by Step in Time.mp3', // آدرس فایل صوتی موزیک (خالی = پخش‌کننده در حالت دمو)
                    // بخش‌هایی که خواننده می‌خواند: [[شروع، پایان], ...] به ثانیه (داده‌ی نمونه؛ بک‌اند از زمان‌بندی لیریک بسازد)
                    'vocals' => collect(range(0, 5))->map(fn ($i) => [10 + $i * 32, 32 + $i * 32])->all(),
                    'level' => $level,
                    // فقط برای دیالوگ «اطلاعات موزیک» (داده‌ی نمونه؛ بک‌اند مقدار واقعی بدهد)
                    'accent' => ['us', 'uk', 'au', 'ie'][$id % 4],
                    'year' => 2005 + ($id * 7) % 20,
                    'genre' => ['Pop', 'Rock', 'Soul', 'Folk', 'Dance', 'R&B'][$id % 6],
                    'wpm' => 80 + ($id * 13) % 90,
                    'topic' => ['Love', 'Friendship', 'Freedom', 'Hope', 'Life', 'Dreams'][($id * 5) % 6],
                    'album' => 'Sample Album ' . $id,
                    'duration' => sprintf('%02d:%02d', 2 + ($id % 3), 10 + ($id * 7) % 50),
                    'points' => 50,
                    // امتیاز «چقدر متوجه شدی؟» (۱ تا ۱۰) در مرحله‌ی ۱ و ۷؛ null = هنوز ثبت نشده
                    'play_score' => $completed >= 1 ? 3 + ($id % 4) : null,
                    'repeat_score' => $completed >= 7 ? min(10, 6 + ($id % 4)) : null,
                    'completed_steps' => $completed,          // چند مرحله کاملاً تمام شده
                    'current_step_fraction' => $fraction,     // چقدر از مرحله‌ی بعدی انجام شده (۰ تا ۱)
                    'progress' => $percent,
                    'status' => Progress::status($percent),
                    'is_free' => $id <= 3,
                    'free_steps' => 2,
                    'total_steps' => $total,
                ]);
            }
        }

        return $out;
    }
}
