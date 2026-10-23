@extends('layouts.admin')

@section('title', __('adminreport.title'))

{{--
    گزارش خطا: فیلتر وضعیت/بخش/اولویت (GET؛ بدون JS هم کار می‌کند)، جدول با تصویر کوچک اسکرین‌شات (موبایل: کارت).
    داده: app/Support/AdminReports.php — جزئیات: resources/views/admin/reports/show.blade.php
--}}
@php
    use App\Support\AdminNav;
    use App\Support\AdminReports;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $n = fn ($v) => $digits(number_format((int) $v, 0, '.', $fa ? '٬' : ','));
    $ic = fn (string $k, string $c = 'size-4') => AdminNav::icon($k, $c);
    $status = $p['status'] ?? 'all';
    $area = $p['area'] ?? 'all';
    $prio = $p['priority'] ?? 'all';
    $sort = $p['sort'] ?? 'newest';
    $q = $p['q'] ?? '';
    $qs = fn (array $o) => '?'.http_build_query(array_filter(array_merge(['q' => $q, 'status' => $status === 'all' ? null : $status, 'area' => $area === 'all' ? null : $area, 'priority' => $prio === 'all' ? null : $prio, 'sort' => $sort === 'newest' ? null : $sort], $o), fn ($v) => $v !== null && $v !== ''));
    $tone = ['new' => 'warn', 'in_progress' => 'info', 'resolved' => 'ok', 'rejected' => ''];
    $ptone = ['low' => '', 'normal' => 'info', 'high' => 'bad'];
@endphp

@section('content')
<div>
    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('adminreport.title') }}</h1>
            <p class="ad-sub">{{ __('adminreport.sub') }}</p>
        </div>
    </div>

    <form method="GET" class="ad-tools" data-ad-filter role="search">
        <div class="ad-tools__q"><input type="search" name="q" value="{{ $q }}" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('adminreport.search_ph') }}" aria-label="{{ __('adminreport.search_ph') }}" autocomplete="off" data-ad-q></div>
        <label class="sr-only" for="rp-area">{{ __('adminreport.col.area') }}</label>
        <select id="rp-area" name="area" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (array_merge(['all'], AdminReports::AREAS) as $o)<option value="{{ $o }}" @selected($area === $o)>{{ __('adminreport.area.'.$o) }}</option>@endforeach
        </select>
        <label class="sr-only" for="rp-prio">{{ __('adminreport.col.priority') }}</label>
        <select id="rp-prio" name="priority" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (array_merge(['all'], AdminReports::PRIORITIES) as $o)<option value="{{ $o }}" @selected($prio === $o)>{{ __('adminreport.priority.'.$o) }}</option>@endforeach
        </select>
        <label class="sr-only" for="rp-sort">{{ __('adminreport.sort.label') }}</label>
        <select id="rp-sort" name="sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['newest', 'oldest', 'priority'] as $o)<option value="{{ $o }}" @selected($sort === $o)>{{ __('adminreport.sort.'.$o) }}</option>@endforeach
        </select>
        @if ($status !== 'all') <input type="hidden" name="status" value="{{ $status }}"> @endif
        <button type="submit" class="btn-outline !min-h-10 !text-xs sm:hidden">{{ __('admin.common.filter') }}</button>
    </form>

    <nav class="ad-filters" aria-label="{{ __('adminreport.col.status') }}">
        <div class="ad-chips">
            @foreach (array_merge(['all'], AdminReports::STATUSES) as $f)
                <a href="{{ $qs(['status' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($status === $f) aria-current="true" @endif>
                    {{ __('adminreport.status.'.$f) }} <b class="ms-1 tabular-nums">{{ $n($list['counts'][$f]) }}</b>
                </a>
            @endforeach
        </div>
    </nav>

    <section class="ad-card !p-3 md:!p-5">
        @if (! $list['rows'])
            <p class="ad-empty">{{ __('adminreport.empty') }}</p>
        @else
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ __('adminreport.col.report') }}</th><th>{{ __('adminreport.col.user') }}</th><th>{{ __('adminreport.col.area') }}</th>
                        <th>{{ __('adminreport.col.priority') }}</th><th>{{ __('adminreport.col.status') }}</th><th>{{ __('adminreport.col.at') }}</th>
                    </tr></thead>
                    <tbody data-rp-rows>
                        @foreach ($list['rows'] as $r)
                            <tr data-status="{{ $r['status'] }}">
                                <td>
                                    <a href="{{ url('/admin/reports/'.$r['id']) }}" class="rp-row">
                                        <span class="rp-thumb" @unless ($r['screenshot']) data-empty @endunless>
                                            @if ($r['screenshot'])<img src="{{ $r['screenshot'] }}" alt="" loading="lazy" decoding="async">@else{!! $ic('image', 'size-5') !!}@endif
                                        </span>
                                        <span class="min-w-0"><b class="rp-row__t" dir="auto">{{ $r['description'] }}</b><small dir="ltr">{{ $r['code'] }}@unless ($r['screenshot']) · {{ __('adminreport.no_shot') }}@endunless</small></span>
                                    </a>
                                </td>
                                <td data-label="{{ __('adminreport.col.user') }}">
                                    <span class="ad-user"><i class="ad-av !size-8 !text-xs" style="--h: {{ $r['user']['color'] }}" aria-hidden="true">{{ mb_substr($r['user']['name'], 0, 1) }}</i><span class="min-w-0"><b>{{ $r['user']['name'] }}</b></span></span>
                                </td>
                                <td data-label="{{ __('adminreport.col.area') }}"><span class="ad-badge">{{ __('adminreport.area.'.AdminReports::area($r['meta']['url'])) }}</span></td>
                                <td data-label="{{ __('adminreport.col.priority') }}"><span class="ad-badge" data-tone="{{ $ptone[$r['priority']] }}">{{ __('adminreport.priority.'.$r['priority']) }}</span></td>
                                <td data-label="{{ __('adminreport.col.status') }}"><span class="ad-badge" data-tone="{{ $tone[$r['status']] }}">{{ __('adminreport.status.'.$r['status']) }}</span></td>
                                <td data-label="{{ __('adminreport.col.at') }}"><time data-ad-ago="{{ $r['created_at'] }}"></time></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ad-pager">
                <span>{{ __('adminreport.result', ['n' => $n($list['total'])]) }} · {{ __('admin.common.page_of', ['a' => $digits($list['page']), 'b' => $digits($list['pages'])]) }}</span>
                <div>
                    @if ($list['page'] > 1) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] - 1]) }}" rel="prev">{{ __('admin.common.prev') }}</a> @endif
                    @if ($list['page'] < $list['pages']) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] + 1]) }}" rel="next">{{ __('admin.common.next') }}</a> @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
