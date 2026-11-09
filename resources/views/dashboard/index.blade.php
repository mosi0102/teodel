@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/dashboard.js')
@endpush

@section('title', __('dashboard.title'))

{{--
    داشبورد: نمای کلی یادگیری — ادامه‌ی آخرین موزیک، هدف روزانه، آمار هفته، سطح و مهارت‌ها، مرور لغت، پیشنهادها، اشتراک و پشتیبانی.
    همه‌ی چهار بخش: ادامه‌ی آموزش‌های نیمه‌کاره (موزیک/پادکست/شدوئینگ/کتاب)، آمار کل، مسیرهای یادگیری، پیشنهادهای ترکیبی.
    رفتار (سلام بر اساس ساعت، انیمیشن حلقه‌ها و نمودار، تغییر هدف): resources/js/modules/dashboard.js
    قرارداد داده: app/Support/Dashboard.php و README.
--}}
@php
    $d = $d ?? \App\Support\Dashboard::demo();
    $u = $d['user'];
    $ic = fn (string $p, string $c = 'size-5') => '<svg viewBox="0 0 24 24" class="'.$c.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$p.'</svg>';
    $chev = '<svg viewBox="0 0 24 24" class="size-5 rtl:-scale-x-100" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m9 6 6 6-6 6"/></svg>';
    $levels = ['A1' => 20, 'A2' => 40, 'B1' => 60, 'B2' => 80, 'C1' => 100];
    $hue = fn ($id) => ($id * 47) % 360;
    $cover = fn ($s) => ! empty($s['cover'])
        ? 'background-image:url('.e($s['cover']).');background-size:cover;background-position:center'
        : 'background: radial-gradient(120% 120% at 15% 0%, hsl('.(($hue($s['id']) + 40) % 360).' 85% 66%), transparent 60%), linear-gradient(135deg, hsl('.$hue($s['id']).' 65% 46%), hsl('.(($hue($s['id']) + 50) % 360).' 60% 28%))';
    $kinds = \App\Support\Dashboard::KINDS;
    $kindLabel = fn ($k) => __('ui.nav.'.($kinds[$k]['nav'] ?? $k));
    $resume = array_values($d['resume'] ?? []);
    $doneTotal = array_sum($d['totals']['done'] ?? []);
    $goal = $d['goal'];
    $goalPct = $goal['minutes'] > 0 ? min(100, (int) round($goal['today'] / $goal['minutes'] * 100)) : 0;
    $weekTotal = array_sum(array_column($d['week'], 'minutes'));
    $weekMax = max(1, max(array_column($d['week'], 'minutes')));
    $delta = $weekTotal - ($d['week_prev_total'] ?? $weekTotal);
@endphp
@section('content')
<div class="page-wrap" data-db
    data-greet='@json(__('dashboard.greet'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-t='@json(__('dashboard'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-week='@json($d['week'], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG)'
    data-goal-options="10,20,30,45"
    @isset($goalUrl) data-goal-url="{{ $goalUrl }}" @endisset>

    @if (app()->getLocale() === 'fa' && ! empty($d['tip_english']['show']))
        {{-- پیشنهاد انگلیسی‌کردن سایت (بعد از اولین آهنگ تمام‌شده): رفتار در modules/dashboard.js --}}
        <aside class="db-tip" data-db-tip data-tip-key="english" hidden role="note"
            @if (! empty($d['tip_english']['dismiss_url'])) data-dismiss-url="{{ $d['tip_english']['dismiss_url'] }}" @endif>
            <span class="db-tip__ic" aria-hidden="true">{!! $ic('<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c2.6 2.6 3.9 5.6 3.9 9S14.600 18.400 12 21M12 3C9.400 5.600 8.100 8.600 8.100 12S9.400 18.400 12 21"/>') !!}</span>
            <div class="db-tip__b">
                <b>{{ __('dashboard.tip.title') }}</b>
                <p>{{ __('dashboard.tip.text') }}</p>
                <div class="db-tip__acts">
                    <a href="{{ $d['tip_english']['switch_url'] ?? url('/lang/en') }}" class="btn-primary !min-h-10 !text-xs" data-tip-switch>{{ __('dashboard.tip.switch') }}</a>
                    <button type="button" class="btn-ghost !min-h-10 !text-xs" data-tip-never>{{ __('dashboard.tip.never') }}</button>
                </div>
            </div>
            <button type="button" class="db-tip__x" data-tip-close aria-label="{{ __('dashboard.tip.close') }}">{!! $ic('<path d="M6 6l12 12M18 6 6 18"/>', 'size-4') !!}</button>
        </aside>
    @endif

    <div class="db-grid">

        {{-- ───────── سلام + ادامه‌ی یادگیری ───────── --}}
        <section class="db-hero" aria-labelledby="db-hello">
            <div class="min-w-0">
                <h1 id="db-hello" class="text-xl font-extrabold leading-9 text-white md:text-2xl" data-db-hello data-name="{{ $u['name'] }}">
                    {{ __('dashboard.hello', ['greet' => __('dashboard.greet.morning'), 'name' => $u['name']]) }}
                </h1>
                <p class="mt-1 text-sm leading-7 text-white/80">
                    {{ ($d['streak']['current'] ?? 0) > 0 ? __('dashboard.streak_line', ['n' => num($d['streak']['current'])]) : __('dashboard.streak_zero') }}
                </p>
            </div>

            @if ($resume)
                @php $r = $resume[0]; @endphp
                <a href="{{ $r['url'] }}" class="db-resume group">
                    <span class="db-resume__cover" style="{!! $cover($r) !!}" aria-hidden="true">
                        <span class="db-kind" data-kind="{{ $r['kind'] }}"><x-icon :name="$kinds[$r['kind']]['icon']" class="size-3.5" /></span>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-[0.7rem] font-extrabold text-brand-600">{{ __('dashboard.resume.title') }} · {{ $kindLabel($r['kind']) }}</span>
                        <span class="mt-0.5 block truncate text-base font-extrabold text-brand-900" dir="ltr"><bdi>{{ $r['title'] }}</bdi></span>
                        <span class="block truncate text-xs text-slate-500" dir="ltr"><bdi>{{ $r['subtitle'] }}</bdi></span>
                        <span class="mt-2 flex items-center gap-2">
                            <span class="db-bar min-w-0 flex-1"><i data-db-bar="{{ $r['percent'] }}"></i></span>
                            <span class="shrink-0 text-xs font-extrabold text-brand-800">{{ num($r['percent']) }}٪</span>
                        </span>
                        <span class="mt-1.5 block truncate text-xs font-medium text-slate-500">
                            {{ __('dashboard.resume.step_of', ['n' => num($r['step']), 'total' => num($r['steps'])]) }} · <bdi>{{ \App\Support\Dashboard::stepLabel($r['kind'], $r['step_key']) }}</bdi>
                        </span>
                    </span>
                    <span class="db-resume__go" aria-hidden="true">{!! $chev !!}</span>
                    <span class="sr-only">{{ __('dashboard.resume.cta') }}</span>
                </a>
                @if (count($resume) > 1)
                    <div class="db-more" aria-label="{{ __('dashboard.resume.more') }}">
                        @foreach (array_slice($resume, 1, 3) as $r)
                            <a href="{{ $r['url'] }}" class="db-more__a">
                                <span class="db-kind" data-kind="{{ $r['kind'] }}"><x-icon :name="$kinds[$r['kind']]['icon']" class="size-3.5" /></span>
                                <span class="min-w-0 flex-1">
                                    <bdi class="block truncate text-xs font-extrabold text-white" dir="ltr">{{ $r['title'] }}</bdi>
                                    <span class="db-bar db-bar--light mt-1"><i data-db-bar="{{ $r['percent'] }}"></i></span>
                                </span>
                                <span class="shrink-0 text-[0.7rem] font-extrabold text-white/80">{{ num($r['percent']) }}٪</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            @else
                <div class="db-resume">
                    <span class="grid size-14 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700">{!! $ic('<path d="M9 18V5l12-2v13"/><circle cx="6" cy="18" r="3"/><circle cx="18" cy="16" r="3"/>', 'size-7') !!}</span>
                    <span class="min-w-0 flex-1">
                        <span class="block text-base font-extrabold text-brand-900">{{ __('dashboard.resume.empty_title') }}</span>
                        <span class="mt-1 block text-xs leading-6 text-slate-500">{{ __('dashboard.resume.empty_text') }}</span>
                    </span>
                    <a href="#db-paths-t" class="btn-primary shrink-0">{{ __('dashboard.resume.empty_cta') }}</a>
                </div>
            @endif
        </section>

        {{-- ───────── هدف امروز ───────── --}}
        <section class="db-card db-goal" aria-labelledby="db-goal-t" data-today="{{ $goal['today'] }}" data-goal="{{ $goal['minutes'] }}">
            <div class="flex items-center justify-between gap-3">
                <h2 id="db-goal-t" class="db-title">{{ __('dashboard.goal.title') }}</h2>
                <span class="text-xs font-bold text-slate-400" data-db-goal-of>{{ __('dashboard.goal.of', ['a' => num($goal['today']), 'b' => num($goal['minutes'])]) }}</span>
            </div>
            <div class="mt-3 flex items-center gap-4">
                <div class="db-ring" data-db-ring="{{ $goalPct }}" style="--p:0" role="img" aria-label="{{ __('dashboard.goal.of', ['a' => num($goal['today']), 'b' => num($goal['minutes'])]) }}">
                    <svg viewBox="0 0 120 120" aria-hidden="true"><circle cx="60" cy="60" r="50" class="db-ring__bg"/><circle cx="60" cy="60" r="50" class="db-ring__fg" pathLength="100"/></svg>
                    <b data-db-goal-pct>{{ num($goalPct) }}٪</b>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-bold leading-7 text-slate-700" data-db-goal-msg>
                        {{ $goal['today'] >= $goal['minutes'] ? __('dashboard.goal.done') : __('dashboard.goal.left', ['n' => num($goal['minutes'] - $goal['today'])]) }}
                    </p>
                    <p class="mt-2.5 text-[0.7rem] font-extrabold text-slate-400">{{ __('dashboard.goal.set') }}</p>
                    <div class="db-goalopts" role="radiogroup" aria-label="{{ __('dashboard.goal.set') }}" data-db-goalopts>
                        @foreach ([10, 20, 30, 45] as $mins)
                            <button type="button" role="radio" data-m="{{ $mins }}" aria-checked="{{ $mins === $goal['minutes'] ? 'true' : 'false' }}">{{ num($mins) }}</button>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="mt-4 border-t border-slate-100 pt-3">
                <p class="text-[0.7rem] font-extrabold text-slate-400">{{ __('dashboard.goal.week') }}</p>
                <ul class="db-days" data-db-days>
                    @foreach ($d['streak']['week'] as $i => $on)
                        <li class="{{ $on ? 'is-on' : '' }}" data-ts="{{ $d['week'][$i]['at'] ?? '' }}" title="{{ $on ? __('dashboard.goal.day_done') : __('dashboard.goal.day_missed') }}">
                            <span class="db-days__dot">@if ($on){!! $ic('<path d="m5 12.5 4.5 4.5L19 7.5"/>', 'size-3.5') !!}@endif</span>
                            <span class="db-days__l" data-db-day></span>
                            <span class="sr-only">{{ $on ? __('dashboard.goal.day_done') : __('dashboard.goal.day_missed') }}</span>
                        </li>
                    @endforeach
                </ul>
            </div>
        </section>

        {{-- ───────── آمار ───────── --}}
        <section class="db-stats" aria-label="{{ __('dashboard.title') }}">
            <div class="db-stat" data-tone="amber">
                <span class="db-stat__ic">{!! $ic('<path d="M12 3c1 3 4 4.5 4 8a4 4 0 0 1-8 0c0-1.6.7-2.6 1.5-3.5C10 9 12 7 12 3z"/><path d="M8.5 17.5A5 5 0 0 0 12 21a5 5 0 0 0 3.5-3.5"/>') !!}</span>
                <b data-db-num="{{ $d['streak']['current'] }}">{{ num($d['streak']['current']) }}</b>
                <span>{{ __('dashboard.stats.streak') }}</span>
                <small>{{ __('dashboard.stats.streak_best', ['n' => num($d['streak']['best'])]) }}</small>
            </div>
            <div class="db-stat" data-tone="green">
                <span class="db-stat__ic">{!! $ic('<circle cx="12" cy="12" r="9"/><path d="m8 12.5 3 3 5-6"/>') !!}</span>
                <b data-db-num="{{ $doneTotal }}">{{ num($doneTotal) }}</b>
                <span>{{ __('dashboard.stats.done') }}</span>
                <small>{{ __('dashboard.stats.done_split', ['m' => num($d['totals']['done']['music'] ?? 0), 'p' => num($d['totals']['done']['podcast'] ?? 0), 's' => num($d['totals']['done']['shadowing'] ?? 0), 'b' => num($d['totals']['done']['books'] ?? 0)]) }}</small>
            </div>
            <a href="{{ url('/words') }}" class="db-stat" data-tone="brand">
                <span class="db-stat__ic">{!! $ic('<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H20v15H6.5A2.5 2.5 0 0 0 4 20.5z"/><path d="M4 20.5A2.5 2.5 0 0 0 6.5 23H20v-5"/>') !!}</span>
                <b data-db-num="{{ $d['totals']['words_saved'] }}">{{ num($d['totals']['words_saved']) }}</b>
                <span>{{ __('dashboard.stats.words') }}</span>
                @if (! empty($d['totals']['sentences_saved']))<small>{{ __('dashboard.stats.sentences', ['n' => num($d['totals']['sentences_saved'])]) }}</small>@endif
            </a>
            <div class="db-stat" data-tone="sky">
                <span class="db-stat__ic">{!! $ic('<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/>') !!}</span>
                <b data-db-num="{{ $weekTotal }}">{{ num($weekTotal) }}</b>
                <span>{{ __('dashboard.stats.minutes') }}</span>
            </div>
        </section>

        {{-- ───────── نمودار هفته ───────── --}}
        <section class="db-card db-week" aria-labelledby="db-week-t">
            <div class="flex flex-wrap items-end justify-between gap-x-4 gap-y-1">
                <h2 id="db-week-t" class="db-title">{{ __('dashboard.week.title') }}</h2>
                <p class="text-xs font-bold {{ $delta > 0 ? 'text-emerald-600' : ($delta < 0 ? 'text-rose-500' : 'text-slate-400') }}">
                    {{ $delta > 0 ? __('dashboard.week.up', ['n' => num($delta)]) : ($delta < 0 ? __('dashboard.week.down', ['n' => num(abs($delta))]) : __('dashboard.week.same')) }}
                </p>
            </div>
            <ol class="db-chart" data-db-chart role="img" aria-label="{{ __('dashboard.week.chart') }}">
                @foreach ($d['week'] as $i => $w)
                    <li class="{{ $i === count($d['week']) - 1 ? 'is-today' : '' }}" data-ts="{{ $w['at'] }}" data-min="{{ $w['minutes'] }}">
                        <span class="db-chart__v">{{ num($w['minutes']) }}</span>
                        <span class="db-chart__track"><i data-db-h="{{ (int) round($w['minutes'] / $weekMax * 100) }}"></i></span>
                        <span class="db-chart__l" data-db-day></span>
                    </li>
                @endforeach
            </ol>
        </section>

        {{-- ───────── سطح و مهارت‌ها ───────── --}}
        <section class="db-card db-level" aria-labelledby="db-level-t">
            <div class="flex items-center justify-between gap-3">
                <h2 id="db-level-t" class="db-title">{{ __('dashboard.level.title') }}</h2>
                <span class="text-xs font-bold text-slate-400">{{ __('dashboard.level.tests', ['n' => num($d['level_test']['taken']), 'total' => num($d['level_test']['total'])]) }}</span>
            </div>
            <div class="mt-3 flex items-center gap-4">
                <div class="db-lv" aria-label="{{ __('dashboard.level.overall') }}">
                    <b dir="ltr" data-user-level>{{ $u['level'] }}</b>
                    <span>{{ __('dashboard.level.overall') }}</span>
                </div>
                <ul class="grid min-w-0 flex-1 gap-2.5">
                    @foreach (['listening', 'reading', 'writing', 'speaking'] as $k)
                        @php $lv = $u['skills'][$k] ?? null; @endphp
                        <li class="db-skill">
                            <span class="truncate text-xs font-bold text-slate-600">{{ __('dashboard.level.skills.'.$k) }}</span>
                            <span class="db-bar"><i data-db-bar="{{ $lv && isset($levels[$lv]) ? $levels[$lv] : 0 }}"></i></span>
                            <b class="text-xs {{ $lv ? 'text-brand-800' : 'text-slate-300' }}" dir="ltr">{{ $lv ?: '—' }}</b>
                        </li>
                    @endforeach
                </ul>
            </div>
            <a href="{{ $d['level_test']['url'] }}" class="btn-outline mt-4 w-full">
                {!! $ic('<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>', 'size-4') !!}
                {{ $d['level_test']['taken'] >= $d['level_test']['total'] ? __('dashboard.level.cta') : __('dashboard.level.cta_more') }}
            </a>
        </section>

        {{-- ───────── ستون کناری: مرور لغت | اشتراک | پشتیبانی ───────── --}}
        <div class="db-side">
            <section class="db-card" aria-labelledby="db-rev-t">
                <div class="flex items-start gap-3">
                    <span class="grid size-11 shrink-0 place-items-center rounded-2xl bg-brand-50 text-brand-700">{!! $ic('<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5"/>') !!}</span>
                    <div class="min-w-0">
                        <h2 id="db-rev-t" class="db-title">{{ __('dashboard.review.title') }}</h2>
                        <p class="mt-0.5 text-sm font-extrabold text-brand-800">{{ $d['review']['due'] > 0 ? __('dashboard.review.due', ['n' => num($d['review']['due'])]) : __('dashboard.review.none') }}</p>
                    </div>
                </div>
                <p class="mt-2.5 text-xs leading-6 text-slate-500">{{ __('dashboard.review.text') }}</p>
                <a href="{{ $d['review']['url'] }}" class="{{ $d['review']['due'] > 0 ? 'btn-primary' : 'btn-outline' }} mt-3.5 w-full">{{ $d['review']['due'] > 0 ? __('dashboard.review.cta') : __('dashboard.review.cta_none') }}</a>
            </section>

            <section class="db-card" aria-labelledby="db-plan-t">
                @if (($d['plan']['type'] ?? 'free') === 'paid')
                    <h2 id="db-plan-t" class="db-title">{{ __('dashboard.plan.paid_title') }}</h2>
                    <p class="mt-1 text-sm font-bold text-emerald-700">{{ __('dashboard.plan.paid_days', ['n' => num($d['plan']['days_left'])]) }}</p>
                    <a href="{{ url('/subscription') }}" class="btn-outline mt-3.5 w-full">{{ __('dashboard.plan.renew') }}</a>
                @else
                    <h2 id="db-plan-t" class="db-title">{{ __('dashboard.plan.free_title') }}</h2>
                    <p class="mt-1 text-xs font-bold text-slate-500">{{ __('dashboard.plan.free_used') }}</p>
                    <ul class="db-free">
                        @foreach ($d['plan']['free'] ?? [] as $f)
                            <li>
                                <span class="db-kind" data-kind="{{ $f['kind'] }}"><x-icon :name="$kinds[$f['kind']]['icon']" class="size-3.5" /></span>
                                <span class="min-w-0 flex-1 truncate">{{ $kindLabel($f['kind']) }}</span>
                                <b class="tabular-nums">{{ num($f['used']) }}/{{ num($f['total']) }}</b>
                            </li>
                        @endforeach
                    </ul>
                    <p class="mt-2.5 text-xs leading-6 text-slate-500">{{ __('dashboard.plan.free_text') }}</p>
                    <a href="{{ url('/subscription') }}" class="btn-primary mt-3.5 w-full">{{ __('dashboard.plan.buy') }}</a>
                @endif
            </section>

            <section class="db-card" aria-labelledby="db-sup-t">
                <div class="flex items-center justify-between gap-3">
                    <h2 id="db-sup-t" class="db-title">{{ __('dashboard.support.title') }}</h2>
                    @if ($d['tickets']['unread'] > 0)
                        <span class="rounded-full bg-brand-700 px-2.5 py-0.5 text-[0.7rem] font-extrabold text-white">{{ __('dashboard.support.unread', ['n' => num($d['tickets']['unread'])]) }}</span>
                    @endif
                </div>
                <p class="mt-1 text-sm font-bold text-slate-700">{{ $d['tickets']['open'] > 0 ? __('dashboard.support.open', ['n' => num($d['tickets']['open'])]) : __('dashboard.support.none') }}</p>
                <div class="mt-3.5 grid grid-cols-2 gap-2">
                    <a href="{{ $d['tickets']['url'] }}" class="btn-outline">{{ __('dashboard.support.cta') }}</a>
                    <a href="{{ $d['tickets']['url'] }}?new=1" class="btn-ghost">{{ __('dashboard.support.new') }}</a>
                </div>
            </section>
        </div>

        {{-- ───────── پیشنهاد موزیک ───────── --}}
        <section class="db-rec" aria-labelledby="db-rec-t">
            {{-- ───────── مسیرهای یادگیری (موزیک، پادکست، شدوئینگ، کتاب) ───────── --}}
            @if (! empty($d['sections']))
                <h2 class="db-title mb-3 !text-base" id="db-paths-t">{{ __('dashboard.paths.title') }}</h2>
                <ul class="db-paths mb-6" aria-labelledby="db-paths-t">
                    @foreach ($d['sections'] as $sec)
                        @php $pct = $sec['total'] ? (int) round($sec['done'] / $sec['total'] * 100) : 0; @endphp
                        <li>
                            <a href="{{ $sec['url'] }}" class="db-path" data-path="{{ $sec['key'] }}">
                                <span class="db-path__ic"><x-icon :name="['music' => 'music', 'podcast' => 'mic', 'shadowing' => 'repeat', 'books' => 'book'][$sec['key']]" class="size-5" /></span>
                                <b>{{ __('ui.nav.' . $sec['key']) }}</b>
                                <small>{{ __('dashboard.paths.done', ['n' => num($sec['done']), 't' => num($sec['total'])]) }}@if ($sec['started']) · {{ __('dashboard.paths.started', ['n' => num($sec['started'])]) }}@endif</small>
                                <span class="db-path__bar" aria-hidden="true"><i style="width: {{ $pct }}%"></i></span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
            <h2 id="db-rec-t" class="db-title mb-3 !text-base">{{ __('dashboard.recommended.title') }}</h2>
            <ul class="db-rec__list">
                @foreach ($d['recommended'] as $s)
                    <li>
                        <a href="{{ $s['url'] }}" class="db-song">
                            <span class="db-song__cover" style="{!! $cover($s) !!}">
                                <span class="db-song__tag {{ $s['is_free'] ? 'is-free' : '' }}">{{ $s['is_free'] ? __('dashboard.recommended.free') : __('dashboard.recommended.locked') }}</span>
                                <span class="db-kind db-kind--tag" data-kind="{{ $s['kind'] }}"><x-icon :name="$kinds[$s['kind']]['icon']" class="size-3.5" />{{ $kindLabel($s['kind']) }}</span>
                            </span>
                            <span class="mt-2.5 block truncate text-sm font-extrabold text-brand-900" dir="ltr"><bdi>{{ $s['title'] }}</bdi></span>
                            <span class="block truncate text-xs text-slate-500" dir="ltr"><bdi>{{ $s['subtitle'] }}</bdi></span>
                            <span class="mt-1.5 flex items-center gap-2 text-[0.7rem] font-bold text-slate-400">
                                <span class="rounded-md bg-brand-50 px-1.5 py-0.5 text-brand-700" dir="ltr">{{ strtoupper($s['level']) }}</span>
                                <span>{{ __('dashboard.recommended.min', ['n' => num($s['minutes'])]) }}</span>
                            </span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </section>
    </div>
</div>
@endsection
