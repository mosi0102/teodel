@extends('layouts.admin')

@section('title', __('adminbilling.title'))

{{--
    پرداخت‌ها: کارت‌های خلاصه، فیلتر (GET؛ بدون JS هم کار می‌کند)، جدول (موبایل: کارت) و دیالوگ جزئیات + اقدام‌ها.
    داده: app/Support/AdminBilling.php — رفتار: resources/js/admin/billing.js
--}}
@php
    use App\Support\AdminBilling;
    use App\Support\AdminNav;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $n = fn ($v) => $digits(number_format((int) $v, 0, '.', $fa ? '٬' : ','));
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $k, string $c = 'size-4') => AdminNav::icon($k, $c);
    $tab = 'payments';
    $status = $p['status'] ?? 'all';
    $plan = $p['plan'] ?? 'all';
    $range = $p['range'] ?? 'all';
    $sort = $p['sort'] ?? 'newest';
    $q = $p['q'] ?? '';
    $qs = fn (array $o) => '?'.http_build_query(array_filter(array_merge(['q' => $q, 'status' => $status === 'all' ? null : $status, 'plan' => $plan === 'all' ? null : $plan, 'range' => $range === 'all' ? null : $range, 'sort' => $sort === 'newest' ? null : $sort], $o), fn ($v) => $v !== null && $v !== ''));
    $tone = ['success' => 'ok', 'failed' => 'bad', 'cancelled' => '', 'pending' => 'warn', 'refunded' => 'info'];
    $delta = $sum['prev_month'] > 0 ? (int) round(($sum['revenue_month'] - $sum['prev_month']) / $sum['prev_month'] * 100) : null;
    $trans = ['detail' => __('adminbilling.d'), 'act' => __('adminbilling.act'), 'ev' => __('adminbilling.ev'), 'status' => __('adminbilling.status'), 'plan' => __('adminbilling.plan'), 'toman' => __('adminbilling.toman'), 'col' => __('adminbilling.col')];
@endphp

@section('content')
<div data-bl-list data-t='@json($trans, $flags)'
    @isset($urls['action']) data-action-url="{{ $urls['action'] }}" @endisset
    @isset($urls['export']) data-export-url="{{ $urls['export'] }}" @endisset>

    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('adminbilling.title') }}</h1>
            <p class="ad-sub">{{ __('adminbilling.sub') }}</p>
        </div>
        <div class="ad-acts">
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-bl-export>{{ __('adminbilling.export') }}</button>
        </div>
    </div>

    @include('admin.billing._tabs')

    <div class="ad-stats">
        <div class="ad-stat">
            <span class="ad-stat__l">{{ __('adminbilling.stat.revenue') }}</span>
            <b class="ad-stat__v">{{ $n($sum['revenue_month']) }}</b>
            <small class="ad-stat__s">{{ __('adminbilling.toman') }}@if ($delta !== null) · <span class="{{ $delta >= 0 ? 'text-emerald-600' : 'text-rose-600' }}" dir="ltr">{{ $delta >= 0 ? '+' : '' }}{{ $digits($delta) }}%</span>@endif</small>
        </div>
        <div class="ad-stat">
            <span class="ad-stat__l">{{ __('adminbilling.stat.success') }}</span>
            <b class="ad-stat__v">{{ $n($sum['success']) }}</b>
            <small class="ad-stat__s">{{ __('adminbilling.status.refunded') }}: {{ $n($list['counts']['refunded']) }}</small>
        </div>
        <a class="ad-stat is-link" href="{{ $qs(['status' => 'failed', 'page' => null]) }}">
            <span class="ad-stat__l">{{ __('adminbilling.stat.fail_rate') }}</span>
            <b class="ad-stat__v">{{ $digits($sum['fail_rate']) }}٪</b>
            <small class="ad-stat__s">{{ __('adminbilling.stat.fail_hint') }}</small>
        </a>
        <a class="ad-stat is-link" href="{{ $qs(['status' => 'pending', 'page' => null]) }}">
            <span class="ad-stat__l">{{ __('adminbilling.stat.pending') }}</span>
            <b class="ad-stat__v">{{ $n($sum['pending']) }}</b>
            <small class="ad-stat__s">{{ __('adminbilling.status.pending') }}</small>
        </a>
    </div>

    <form method="GET" class="ad-tools mt-4" data-ad-filter role="search">
        <div class="ad-tools__q"><input type="search" name="q" value="{{ $q }}" class="field !min-h-10 !rounded-full !text-xs" placeholder="{{ __('adminbilling.search_ph') }}" aria-label="{{ __('adminbilling.search_ph') }}" autocomplete="off" data-ad-q></div>
        <label class="sr-only" for="bl-plan">{{ __('adminbilling.col.plan') }}</label>
        <select id="bl-plan" name="plan" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['all', '1m', '6m', '12m'] as $o)<option value="{{ $o }}" @selected($plan === $o)>{{ __('adminbilling.plan.'.$o) }}</option>@endforeach
        </select>
        <label class="sr-only" for="bl-range">{{ __('adminbilling.col.at') }}</label>
        <select id="bl-range" name="range" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['all', 'today', '7d', '30d'] as $o)<option value="{{ $o }}" @selected($range === $o)>{{ __('adminbilling.range.'.$o) }}</option>@endforeach
        </select>
        <label class="sr-only" for="bl-sort">{{ __('adminbilling.sort.label') }}</label>
        <select id="bl-sort" name="sort" class="field !min-h-10 !w-auto !rounded-full !text-xs" data-ad-auto>
            @foreach (['newest', 'amount'] as $o)<option value="{{ $o }}" @selected($sort === $o)>{{ __('adminbilling.sort.'.$o) }}</option>@endforeach
        </select>
        @if ($status !== 'all') <input type="hidden" name="status" value="{{ $status }}"> @endif
        <button type="submit" class="btn-outline !min-h-10 !text-xs sm:hidden">{{ __('admin.common.filter') }}</button>
    </form>

    <nav class="ad-filters" aria-label="{{ __('adminbilling.col.status') }}">
        <div class="ad-chips">
            @foreach (array_merge(['all'], AdminBilling::STATUSES) as $f)
                <a href="{{ $qs(['status' => $f === 'all' ? null : $f, 'page' => null]) ?: '?' }}" class="chip-btn shrink-0" @if ($status === $f) aria-current="true" @endif>
                    {{ __('adminbilling.status.'.$f) }} <b class="ms-1 tabular-nums">{{ $n($list['counts'][$f]) }}</b>
                </a>
            @endforeach
        </div>
    </nav>

    <section class="ad-card !p-3 md:!p-5">
        @if (! $list['rows'])
            <p class="ad-empty">{{ __('adminbilling.empty') }}</p>
        @else
            <div class="ad-tbl-wrap">
                <table class="ad-tbl">
                    <thead><tr>
                        <th>{{ __('adminbilling.col.ref') }}</th><th>{{ __('adminbilling.col.user') }}</th><th>{{ __('adminbilling.col.plan') }}</th>
                        <th>{{ __('adminbilling.col.amount') }}</th><th>{{ __('adminbilling.col.status') }}</th><th>{{ __('adminbilling.col.at') }}</th>
                    </tr></thead>
                    <tbody data-bl-rows>
                        @foreach ($list['rows'] as $r)
                            <tr data-p='@json($r, $flags)'>
                                <td>
                                    <button type="button" class="ad-user text-start" data-bl-open aria-label="{{ __('adminbilling.details') }} {{ $r['ref'] }}">
                                        <span class="min-w-0"><b dir="ltr">{{ $r['ref'] }}</b><small dir="ltr">{{ $r['bank_ref'] ?? '—' }}</small></span>
                                    </button>
                                </td>
                                <td data-label="{{ __('adminbilling.col.user') }}">
                                    <span class="ad-user"><i class="ad-av !size-8 !text-xs" style="--h: {{ $r['user']['color'] ?? 220 }}" aria-hidden="true">{{ mb_substr($r['user']['name'], 0, 1) }}</i><span class="min-w-0"><b>{{ $r['user']['name'] }}</b></span></span>
                                </td>
                                <td data-label="{{ __('adminbilling.col.plan') }}"><span class="ad-badge" data-tone="info">{{ __('adminbilling.plan.'.$r['plan']) }}</span></td>
                                <td data-label="{{ __('adminbilling.col.amount') }}" class="ad-num">{{ $n($r['amount']) }} <small class="text-xs font-medium text-slate-400">{{ __('adminbilling.toman') }}</small></td>
                                <td data-label="{{ __('adminbilling.col.status') }}"><span class="ad-badge" data-tone="{{ $tone[$r['status']] }}" data-bl-status>{{ __('adminbilling.status.'.$r['status']) }}</span></td>
                                <td data-label="{{ __('adminbilling.col.at') }}"><time data-ad-ago="{{ $r['at'] }}"></time></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="ad-pager">
                <span>{{ __('adminbilling.result', ['n' => $n($list['total'])]) }} · {{ __('admin.common.page_of', ['a' => $digits($list['page']), 'b' => $digits($list['pages'])]) }}</span>
                <div>
                    @if ($list['page'] > 1) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] - 1]) }}" rel="prev">{{ __('admin.common.prev') }}</a> @endif
                    @if ($list['page'] < $list['pages']) <a class="btn-outline" href="{{ $qs(['page' => $list['page'] + 1]) }}" rel="next">{{ __('admin.common.next') }}</a> @endif
                </div>
            </div>
        @endif
    </section>
</div>
@endsection

@push('modals')
<div id="bl-detail" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="bl-d-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-lg">
        <div class="dialog-handle md:hidden"></div>
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <p class="text-xs font-extrabold text-slate-400">{{ __('adminbilling.d.title') }}</p>
                <h2 id="bl-d-t" class="mt-0.5 flex flex-wrap items-center gap-2 text-lg font-extrabold text-brand-900"><span dir="ltr" data-bl-d="ref"></span><span class="ad-badge" data-bl-d="status"></span></h2>
            </div>
            <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs" data-bl-copy>{{ __('adminbilling.d.copy') }}</button>
        </div>

        <dl class="ad-dl mt-3">
            <div><dt>{{ __('adminbilling.d.user') }}</dt><dd><a class="text-brand-700 hover:underline" data-bl-d="user" href="#"></a></dd></div>
            <div><dt>{{ __('adminbilling.d.plan') }}</dt><dd data-bl-d="plan"></dd></div>
            <div><dt>{{ __('adminbilling.d.amount') }}</dt><dd data-bl-d="amount"></dd></div>
            <div><dt>{{ __('adminbilling.d.gateway') }}</dt><dd data-bl-d="gateway"></dd></div>
            <div><dt>{{ __('adminbilling.d.bank_ref') }}</dt><dd dir="ltr" data-bl-d="bank_ref"></dd></div>
            <div><dt>{{ __('adminbilling.d.paid_at') }}</dt><dd data-bl-d="at"></dd></div>
            <div data-bl-row="ends_at"><dt>{{ __('adminbilling.d.ends_at') }}</dt><dd data-bl-d="ends_at"></dd></div>
            <div data-bl-row="message"><dt>{{ __('adminbilling.d.message') }}</dt><dd class="text-rose-600" data-bl-d="message"></dd></div>
        </dl>

        <h3 class="mt-4 text-sm font-extrabold text-brand-900">{{ __('adminbilling.d.timeline') }}</h3>
        <ol class="bl-tl" data-bl-events></ol>

        <div class="mt-4 flex flex-col gap-2 sm:flex-row" data-bl-acts>
            <button type="button" class="btn-outline" data-bl-act="verify" hidden>{{ __('adminbilling.act.verify') }}</button>
            <button type="button" class="btn-outline !text-rose-600" data-bl-act="refund" hidden>{{ __('adminbilling.act.refund') }}</button>
        </div>

        <div class="bl-confirm" data-bl-confirm hidden>
            <p class="text-sm leading-7 text-slate-600" data-bl-lead></p>
            <div class="ad-field mt-2"><label class="ad-label" for="bl-reason">{{ __('adminbilling.act.reason') }}</label>
                <textarea id="bl-reason" class="field !min-h-16" placeholder="{{ __('adminbilling.act.reason_ph') }}" data-bl-reason></textarea></div>
            <p class="sb-err mt-2" role="alert" hidden data-bl-err></p>
            <div class="mt-3 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
                <button type="button" class="btn-ghost" data-bl-cancel>{{ __('adminbilling.act.cancel') }}</button>
                <button type="button" class="btn-primary" data-bl-ok></button>
            </div>
        </div>

        <div class="mt-4 flex justify-end" data-bl-close-row>
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.back') }}</button>
        </div>
    </div>
</div>
@endpush
