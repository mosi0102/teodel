@extends('layouts.admin')

@section('title', __('adminsongs.title'))

{{--
    لیست موزیک‌ها: جست‌وجو، فیلتر سطح/وضعیت، مرتب‌سازی، نوار پیشرفت ۸ مرحله، جدول (موبایل: کارت).
    فیلترها GET هستند؛ داده: app/Support/AdminSongs.php
--}}
@php
    use App\Support\AdminNav;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $q = $p['q'] ?? '';
    $level = $p['level'] ?? 'all';
    $status = $p['status'] ?? 'all';
    $sort = $p['sort'] ?? 'newest';
    $qs = fn (array $o) => '?'.http_build_query(array_filter(array_merge(['q' => $q, 'level' => $level === 'all' ? null : $level, 'status' => $status === 'all' ? null : $status, 'sort' => $sort === 'newest' ? null : $sort], $o), fn ($v) => $v !== null && $v !== ''));
    $hue = fn ($id) => ($id * 47) % 360;
    $mm = fn ($s) => $digits(sprintf('%d:%02d', intdiv((int) $s, 60), (int) $s % 60));
@endphp

@section('content')
<div data-ad-songs>
    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('adminsongs.title') }}</h1>
            <p class="ad-sub">{{ __('adminsongs.sub') }}</p>
        </div>
        <div class="ad-acts">
            <a href="{{ url('/admin/songs/create') }}" class="btn-primary !min-h-10 !text-xs">{!! AdminNav::icon('plus', 'size-4') !!}{{ __('adminsongs.new') }}</a>
        </div>
    </div>

    <form method="GET" class="ad-tools" data-ad-filter role="search">
        <div class="ad-tools__q">
            <input type="search" name="q" value="{{ $q }}" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('adminsongs.search_ph') }}" aria-label="{{ __('admin.search') }}" autocomplete="off" data-ad-q>
        </div>
        <label class="sr-only" for="ad-sort">{{ __('adminsongs.sort.label') }}</label>
        <select id="ad-sort" name="sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['newest', 'title', 'progress'] as $o)
                <option value="{{ $o }}" @selected($sort === $o)>{{ __('adminsongs.sort.'.$o) }}</option>
            @endforeach
        </select>
        @if ($level !== 'all') <input type="hidden" name="level" value="{{ $level }}"> @endif
        @if ($status !== 'all') <input type="hidden" name="status" value="{{ $status }}"> @endif
        <button type="submit" class="btn-outline !min-h-10 !text-xs sm:hidden">{{ __('admin.common.filter') }}</button>
    </form>

    <div class="ad-filters">
    <nav class="ad-chips" aria-label="{{ __('adminsongs.col.status') }}">
        @foreach (['all', 'published', 'draft'] as $f)
            <a href="{{ $qs(['status' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($status === $f) aria-current="true" @endif>
                {{ __('adminsongs.status.'.$f) }} <b class="ms-1 tabular-nums">{{ $digits($list['counts'][$f]) }}</b>
            </a>
        @endforeach
    </nav>
    <nav class="ad-chips" aria-label="{{ __('adminsongs.col.level') }}">
        @foreach (['all', 'a', 'b', 'c'] as $f)
            <a href="{{ $qs(['level' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($level === $f) aria-current="true" @endif>
                {{ $f === 'all' ? __('admin.common.all') : __('adminsongs.level.'.$f) }}
            </a>
        @endforeach
    </nav>
    </div>

    <section class="ad-card !p-3 md:!p-5">
        @if (! $list['rows'])
            <p class="ad-empty">{{ __('adminsongs.empty') }}</p>
        @else
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ __('adminsongs.col.song') }}</th><th>{{ __('adminsongs.col.level') }}</th><th>{{ __('adminsongs.col.access') }}</th>
                        <th>{{ __('adminsongs.col.status') }}</th><th>{{ __('adminsongs.col.steps') }}</th><th>{{ __('adminsongs.col.updated') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($list['rows'] as $s)
                            <tr>
                                <td>
                                    <a href="{{ url('/admin/songs/'.$s['id'].'/edit') }}" class="ad-user">
                                        <i class="ad-cover" style="--h: {{ $hue($s['id']) }}@if ($s['cover']);background-image:url('{{ $s['cover'] }}')@endif" aria-hidden="true">@unless ($s['cover']){!! AdminNav::icon('songs', 'size-5') !!}@endunless</i>
                                        <span class="min-w-0">
                                            <b dir="ltr" class="block truncate text-start">{{ $s['title'] }}</b>
                                            <small dir="ltr" class="block truncate text-start">{{ $s['artist'] }}@if ($s['duration']) · {{ $mm($s['duration']) }}@endif</small>
                                        </span>
                                    </a>
                                </td>
                                <td data-label="{{ __('adminsongs.col.level') }}"><span class="ad-badge" data-tone="info">{{ __('adminsongs.level.'.$s['level']) }}</span></td>
                                <td data-label="{{ __('adminsongs.col.access') }}"><span class="ad-badge" @if ($s['is_free']) data-tone="ok" @endif>{{ $s['is_free'] ? __('adminsongs.free') : __('adminsongs.paid') }}</span></td>
                                <td data-label="{{ __('adminsongs.col.status') }}"><span class="ad-badge" @if ($s['status'] === 'published') data-tone="ok" @endif>{{ __('admin.common.'.$s['status']) }}</span></td>
                                <td data-label="{{ __('adminsongs.col.steps') }}">
                                    <div class="ad-segs" role="img" aria-label="{{ __('adminsongs.steps_of', ['n' => $digits($s['steps_done'])]) }}">
                                        @foreach (collect($s['done_map'])->except('info') as $k => $ok)<i class="{{ $ok ? 'is-on' : '' }}" title="{{ __('adminsongs.tabs.'.$k) }}"></i>@endforeach
                                    </div>
                                    <small class="mt-1 block text-[0.7rem] font-bold text-slate-400">{{ __('adminsongs.steps_of', ['n' => $digits($s['steps_done'])]) }}</small>
                                </td>
                                <td data-label="{{ __('adminsongs.col.updated') }}">@if ($s['updated_at']) <time data-ad-ago="{{ $s['updated_at'] }}"></time> @else — @endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ad-pager">
                <span>{{ __('adminsongs.result', ['n' => $digits($list['total'])]) }} · {{ __('admin.common.page_of', ['a' => $digits($list['page']), 'b' => $digits($list['pages'])]) }}</span>
                <div>
                    @if ($list['page'] > 1) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] - 1]) }}" rel="prev">{{ __('admin.common.prev') }}</a> @endif
                    @if ($list['page'] < $list['pages']) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] + 1]) }}" rel="next">{{ __('admin.common.next') }}</a> @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
