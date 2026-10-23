@extends('layouts.admin')

@section('title', __('adminticket.title'))

{{--
    تیکت‌ها: فیلتر وضعیت/بخش (GET؛ بدون JS هم کار می‌کند)، جدول (موبایل: کارت). «منتظر پاسخ» های قدیمی‌تر بالاترند.
    داده: app/Support/AdminTickets.php — گفتگو: resources/views/admin/tickets/show.blade.php
--}}
@php
    use App\Support\AdminTickets;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $n = fn ($v) => $digits(number_format((int) $v, 0, '.', $fa ? '٬' : ','));
    $status = $p['status'] ?? 'all';
    $dept = $p['dept'] ?? 'all';
    $sort = $p['sort'] ?? 'waiting';
    $q = $p['q'] ?? '';
    $uid = (int) ($p['user'] ?? 0);
    $qs = fn (array $o) => '?'.http_build_query(array_filter(array_merge(['q' => $q, 'status' => $status === 'all' ? null : $status, 'dept' => $dept === 'all' ? null : $dept, 'user' => $uid ?: null, 'sort' => $sort === 'waiting' ? null : $sort], $o), fn ($v) => $v !== null && $v !== ''));
    $tone = ['open' => 'warn', 'answered' => 'ok', 'closed' => ''];
    $waitFor = function (array $t) use ($digits, $fa) {
        $h = max(1, (int) floor((time() - $t['updated_at']) / 3600));

        return $h >= 48 ? __('adminticket.waiting_days', ['n' => $digits(intdiv($h, 24))]) : __('adminticket.waiting', ['n' => $digits($h)]);
    };
    $uname = $uid && $list['rows'] ? $list['rows'][0]['user']['name'] : '';
@endphp

@section('content')
<div>
    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('adminticket.title') }}</h1>
            <p class="ad-sub">{{ __('adminticket.sub') }}</p>
        </div>
    </div>

    @if ($uid)
        <p class="mb-3 flex flex-wrap items-center gap-2 text-sm font-bold text-slate-600">
            <span class="ad-badge" data-tone="info">{{ __('adminticket.user_filter', ['name' => $uname ?: '#'.$uid]) }}</span>
            <a class="text-xs text-brand-700 hover:underline" href="{{ $qs(['user' => null, 'page' => null]) ?: '?' }}">{{ __('adminticket.user_filter_clear') }}</a>
        </p>
    @endif

    <form method="GET" class="ad-tools" data-ad-filter role="search">
        <div class="ad-tools__q"><input type="search" name="q" value="{{ $q }}" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('adminticket.search_ph') }}" aria-label="{{ __('adminticket.search_ph') }}" autocomplete="off" data-ad-q></div>
        <label class="sr-only" for="td-dept">{{ __('adminticket.col.dept') }}</label>
        <select id="td-dept" name="dept" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (array_merge(['all'], AdminTickets::DEPTS) as $o)<option value="{{ $o }}" @selected($dept === $o)>{{ __('adminticket.dept.'.$o) }}</option>@endforeach
        </select>
        <label class="sr-only" for="td-sort">{{ __('adminticket.sort.label') }}</label>
        <select id="td-sort" name="sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['waiting', 'newest'] as $o)<option value="{{ $o }}" @selected($sort === $o)>{{ __('adminticket.sort.'.$o) }}</option>@endforeach
        </select>
        @if ($status !== 'all') <input type="hidden" name="status" value="{{ $status }}"> @endif
        @if ($uid) <input type="hidden" name="user" value="{{ $uid }}"> @endif
        <button type="submit" class="btn-outline !min-h-10 !text-xs sm:hidden">{{ __('admin.common.filter') }}</button>
    </form>

    <nav class="ad-filters" aria-label="{{ __('adminticket.col.status') }}">
        <div class="ad-chips">
            @foreach (array_merge(['all'], AdminTickets::STATUSES) as $f)
                <a href="{{ $qs(['status' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($status === $f) aria-current="true" @endif>
                    {{ __('adminticket.status.'.$f) }} <b class="ms-1 tabular-nums">{{ $n($list['counts'][$f]) }}</b>
                </a>
            @endforeach
        </div>
    </nav>

    <section class="ad-card !p-3 md:!p-5">
        @if (! $list['rows'])
            <p class="ad-empty">{{ __('adminticket.empty') }}</p>
        @else
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ __('adminticket.col.subject') }}</th><th>{{ __('adminticket.col.user') }}</th><th>{{ __('adminticket.col.dept') }}</th>
                        <th>{{ __('adminticket.col.status') }}</th><th>{{ __('adminticket.col.at') }}</th>
                    </tr></thead>
                    <tbody data-td-rows>
                        @foreach ($list['rows'] as $t)
                            @php $old = $t['status'] === 'open' && time() - $t['updated_at'] > 86400; @endphp
                            <tr data-status="{{ $t['status'] }}">
                                <td>
                                    <a href="{{ url('/admin/tickets/'.$t['id']) }}" class="ad-user">
                                        <span class="min-w-0"><b>{{ $t['subject'] }}</b><small dir="ltr">{{ $t['code'] }}</small></span>
                                    </a>
                                </td>
                                <td data-label="{{ __('adminticket.col.user') }}">
                                    <span class="ad-user"><i class="ad-av !size-8 !text-xs" style="--h: {{ $t['user']['color'] }}" aria-hidden="true">{{ mb_substr($t['user']['name'], 0, 1) }}</i><span class="min-w-0"><b>{{ $t['user']['name'] }}</b></span></span>
                                </td>
                                <td data-label="{{ __('adminticket.col.dept') }}"><span class="ad-badge">{{ __('adminticket.dept.'.$t['dept']) }}</span></td>
                                <td data-label="{{ __('adminticket.col.status') }}">
                                    <span class="ad-badge" data-tone="{{ $tone[$t['status']] }}">{{ __('adminticket.status.'.$t['status']) }}</span>
                                    @if ($old) <small class="ms-1 text-[0.7rem] font-extrabold text-rose-500">{{ $waitFor($t) }}</small> @endif
                                </td>
                                <td data-label="{{ __('adminticket.col.at') }}"><time data-ad-ago="{{ $t['updated_at'] }}"></time></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ad-pager">
                <span>{{ __('adminticket.result', ['n' => $n($list['total'])]) }} · {{ __('admin.common.page_of', ['a' => $digits($list['page']), 'b' => $digits($list['pages'])]) }}</span>
                <div>
                    @if ($list['page'] > 1) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] - 1]) }}" rel="prev">{{ __('admin.common.prev') }}</a> @endif
                    @if ($list['page'] < $list['pages']) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] + 1]) }}" rel="next">{{ __('admin.common.next') }}</a> @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection
