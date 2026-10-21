@extends('layouts.admin')

@section('title', __('admin.users.title'))

{{--
    لیست کاربران: جست‌وجو، فیلتر وضعیت اشتراک، مرتب‌سازی، جدول (موبایل: کارت).
    فیلترها GET هستند و بدون جاوااسکریپت هم کار می‌کنند؛ داده: app/Support/AdminUsers.php
--}}
@php
    use App\Support\AdminNav;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $n = fn ($v) => $digits(number_format((int) $v, 0, '.', $fa ? '٬' : ','));
    $plan = $p['plan'] ?? 'all';
    $sort = $p['sort'] ?? 'newest';
    $q = $p['q'] ?? '';
    $qs = fn (array $o) => '?'.http_build_query(array_filter(array_merge(['q' => $q, 'plan' => $plan === 'all' ? null : $plan, 'sort' => $sort === 'newest' ? null : $sort], $o), fn ($v) => $v !== null && $v !== ''));
    $tone = ['paid' => 'ok', 'expired' => 'warn', 'free' => ''];
@endphp

@section('content')
<div data-ad-users>
    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('admin.users.title') }}</h1>
            <p class="ad-sub">{{ __('admin.users.sub') }}</p>
        </div>
    </div>

    <form method="GET" class="ad-tools" data-ad-filter role="search">
        <div class="relative min-w-0 flex-1 sm:max-w-sm sm:flex-none">
            <input type="search" name="q" value="{{ $q }}" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('admin.users.search_ph') }}" aria-label="{{ __('admin.search') }}" autocomplete="off" data-ad-q>
        </div>
        <label class="sr-only" for="ad-sort">{{ __('admin.users.sort.label') }}</label>
        <select id="ad-sort" name="sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['newest', 'score', 'seen'] as $o)
                <option value="{{ $o }}" @selected($sort === $o)>{{ __('admin.users.sort.'.$o) }}</option>
            @endforeach
        </select>
        @if ($plan !== 'all') <input type="hidden" name="plan" value="{{ $plan }}"> @endif
        <button type="submit" class="btn-outline !min-h-10 !text-xs sm:hidden">{{ __('admin.common.filter') }}</button>
    </form>

    <nav class="mb-4 flex gap-2 overflow-x-auto pb-1" aria-label="{{ __('admin.common.filter') }}">
        @foreach (['all', 'free', 'paid', 'expired', 'blocked'] as $f)
            <a href="{{ $qs(['plan' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($plan === $f) aria-current="true" @endif>
                {{ __('admin.users.filters.'.$f) }} <b class="ms-1 tabular-nums">{{ $n($list['counts'][$f]) }}</b>
            </a>
        @endforeach
    </nav>

    <section class="ad-card !p-3 md:!p-5">
        @if (! $list['rows'])
            <p class="ad-empty">{{ __('admin.users.empty') }}</p>
        @else
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ __('admin.users.col.user') }}</th><th>{{ __('admin.users.col.level') }}</th><th>{{ __('admin.users.col.score') }}</th>
                        <th>{{ __('admin.users.col.plan') }}</th><th class="ad-col-reg">{{ __('admin.users.col.registered') }}</th><th>{{ __('admin.users.col.seen') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($list['rows'] as $u)
                            @php $st = \App\Support\AdminUsers::planState($u); @endphp
                            <tr>
                                <td>
                                    <a href="{{ url('/admin/users/'.$u['id']) }}" class="ad-user">
                                        <i class="ad-av" style="--h: {{ $u['color'] }}" aria-hidden="true">{{ mb_substr($u['name'], 0, 1) }}</i>
                                        <span class="min-w-0">
                                            <b>{{ $u['name'] }}</b>
                                            <small dir="ltr">{{ $u['contact'] }}</small>
                                        </span>
                                        @if ($u['status'] === 'blocked') <span class="ad-badge" data-tone="bad">{{ __('admin.users.blocked') }}</span> @endif
                                    </a>
                                </td>
                                <td data-label="{{ __('admin.users.col.level') }}">
                                    @if ($u['level']) <span class="ad-badge" data-tone="info">{{ $u['level'] }}</span>
                                    @else <span class="text-xs text-slate-400">{{ __('admin.users.no_test') }}</span> @endif
                                </td>
                                <td data-label="{{ __('admin.users.col.score') }}" class="ad-num">{{ $n($u['score']) }}</td>
                                <td class="ad-col-plan" data-label="{{ __('admin.users.col.plan') }}">
                                    @if ($u['plan_active'])
                                        <span class="ad-badge" data-tone="ok">{{ __('admin.dash.mix.'.$u['plan_active']['plan']) }}</span>
                                        <small class="ms-1 text-slate-400">{{ __('admin.users.days_left', ['n' => $digits($u['plan_active']['days_left'])]) }}</small>
                                    @elseif ($u['plan_prev'])
                                        <span class="ad-badge" data-tone="warn">{{ __('admin.users.filters.expired') }}</span>
                                    @else
                                        <span class="ad-badge">{{ __('admin.users.no_plan') }}</span>
                                    @endif
                                </td>
                                <td class="ad-col-reg" data-label="{{ __('admin.users.col.registered') }}"><time data-ad-date="{{ $u['registered_at'] }}" data-ad-short></time></td>
                                <td data-label="{{ __('admin.users.col.seen') }}">
                                    @if ($u['last_seen']) <time data-ad-ago="{{ $u['last_seen'] }}"></time> @else <span class="text-slate-400">{{ __('admin.users.never') }}</span> @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ad-pager">
                <span>{{ __('admin.users.result', ['n' => $n($list['total'])]) }} · {{ __('admin.common.page_of', ['a' => $digits($list['page']), 'b' => $digits($list['pages'])]) }}</span>
                <div>
                    @if ($list['page'] > 1) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] - 1]) }}" rel="prev">{{ __('admin.common.prev') }}</a> @endif
                    @if ($list['page'] < $list['pages']) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] + 1]) }}" rel="next">{{ __('admin.common.next') }}</a> @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
