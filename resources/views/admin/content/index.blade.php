@extends('layouts.admin')

{{--
    لیست مشترک محتوا (پادکست، Shadowing، کتاب): جست‌وجو، فیلتر وضعیت/سطح، مرتب‌سازی، نوار کامل‌بودن تب‌ها، جدول (موبایل: کارت).
    ورودی‌ها از view هر بخش: $title, $sub, $base (/admin/x), $newLabel, $searchPh, $levels [key => label], $tabs [key => label],
      $row (fn(array) => ['title','sub','cover','icon','level','extra']), $list (AdminContent::query), $p
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
@endphp

@section('title', $title)

@section('content')
<div>
    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ $title }}</h1>
            <p class="ad-sub">{{ $sub }}</p>
        </div>
        <div class="ad-acts">
            <a href="{{ url($base.'/create') }}" class="btn-primary !min-h-10 !text-xs">{!! AdminNav::icon('plus', 'size-4') !!}{{ $newLabel }}</a>
        </div>
    </div>

    <form method="GET" class="ad-tools" data-ad-filter role="search">
        <div class="ad-tools__q">
            <input type="search" name="q" value="{{ $q }}" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ $searchPh }}" aria-label="{{ __('admin.search') }}" autocomplete="off" data-ad-q>
        </div>
        <label class="sr-only" for="ad-sort">{{ __('admincontent.sort.label') }}</label>
        <select id="ad-sort" name="sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['newest', 'title', 'progress'] as $o)
                <option value="{{ $o }}" @selected($sort === $o)>{{ __('admincontent.sort.'.$o) }}</option>
            @endforeach
        </select>
        @if ($level !== 'all') <input type="hidden" name="level" value="{{ $level }}"> @endif
        @if ($status !== 'all') <input type="hidden" name="status" value="{{ $status }}"> @endif
        <button type="submit" class="btn-outline !min-h-10 !text-xs sm:hidden">{{ __('admin.common.filter') }}</button>
    </form>

    <div class="ad-filters">
        <nav class="ad-chips" aria-label="{{ __('admincontent.col.status') }}">
            @foreach (['all', 'published', 'draft'] as $f)
                <a href="{{ $qs(['status' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($status === $f) aria-current="true" @endif>
                    {{ __('admincontent.status.'.$f) }} <b class="ms-1 tabular-nums">{{ $digits($list['counts'][$f]) }}</b>
                </a>
            @endforeach
        </nav>
        <nav class="ad-chips" aria-label="{{ __('admincontent.col.level') }}">
            @foreach (['all' => __('admin.common.all')] + $levels as $f => $l)
                <a href="{{ $qs(['level' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($level === $f) aria-current="true" @endif>{{ $l }}</a>
            @endforeach
        </nav>
    </div>

    <section class="ad-card !p-3 md:!p-5">
        @if (! $list['rows'])
            <p class="ad-empty">{{ __('admincontent.empty') }}</p>
        @else
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ $colItem }}</th><th>{{ __('admincontent.col.level') }}</th><th>{{ __('admincontent.col.access') }}</th>
                        <th>{{ __('admincontent.col.status') }}</th><th>{{ __('admincontent.col.steps') }}</th><th>{{ __('admincontent.col.updated') }}</th>
                    </tr></thead>
                    <tbody>
                        @foreach ($list['rows'] as $r)
                            @php $v = $row($r); @endphp
                            <tr>
                                <td>
                                    <a href="{{ url($base.'/'.$r['id'].'/edit') }}" class="ad-user">
                                        <i class="ad-cover" style="--h: {{ $hue($r['id']) }}@if ($v['cover']);background-image:url('{{ $v['cover'] }}')@endif" aria-hidden="true">@unless ($v['cover']){!! AdminNav::icon($v['icon'], 'size-5') !!}@endunless</i>
                                        <span class="min-w-0">
                                            <b dir="auto" class="block truncate text-start">{{ $v['title'] }}</b>
                                            <small dir="auto" class="block truncate text-start">{{ $v['sub'] }}</small>
                                        </span>
                                    </a>
                                </td>
                                <td data-label="{{ __('admincontent.col.level') }}">{!! $v['level'] !!}</td>
                                <td data-label="{{ __('admincontent.col.access') }}">
                                    <span class="ad-badge" @if ($r['is_free']) data-tone="ok" @endif>{{ $r['is_free'] ? __('admincontent.free') : __('admincontent.paid') }}</span>
                                    @if (! empty($r['recommended']))<span class="ad-badge" data-tone="warn">{{ __('admincontent.recommended') }}</span>@endif
                                </td>
                                <td data-label="{{ __('admincontent.col.status') }}"><span class="ad-badge" @if ($r['status'] === 'published') data-tone="ok" @endif>{{ __('admin.common.'.$r['status']) }}</span></td>
                                <td data-label="{{ __('admincontent.col.steps') }}">
                                    <div class="ad-segs" role="img" aria-label="{{ __('admincontent.steps_of', ['a' => $digits($r['steps_done']), 'b' => $digits(count($r['done_map']))]) }}">
                                        @foreach ($r['done_map'] as $k => $ok)<i class="{{ $ok ? 'is-on' : '' }}" title="{{ $tabs[$k] ?? $k }}"></i>@endforeach
                                    </div>
                                    <small class="mt-1 block text-[0.7rem] font-bold text-slate-400">{{ __('admincontent.steps_of', ['a' => $digits($r['steps_done']), 'b' => $digits(count($r['done_map']))]) }}@if ($v['extra']) · {{ $v['extra'] }}@endif</small>
                                </td>
                                <td data-label="{{ __('admincontent.col.updated') }}">@if ($r['updated_at']) <time data-ad-ago="{{ $r['updated_at'] }}"></time> @else — @endif</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ad-pager">
                <span>{{ __('admincontent.result', ['n' => $digits($list['total'])]) }} · {{ __('admin.common.page_of', ['a' => $digits($list['page']), 'b' => $digits($list['pages'])]) }}</span>
                <div>
                    @if ($list['page'] > 1) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] - 1]) }}" rel="prev">{{ __('admin.common.prev') }}</a> @endif
                    @if ($list['page'] < $list['pages']) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] + 1]) }}" rel="next">{{ __('admin.common.next') }}</a> @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
