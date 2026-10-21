@extends('layouts.admin')

@section('title', $u['name'])

{{--
    جزئیات کاربر: حساب (امتیاز، کد معرف، ثبت‌نام، آخرین بازدید)، نمره‌ی آزمون‌ها، اشتراک فعال و قبلی، سابقه‌ی پرداخت، اقدام‌ها.
    داده: app/Support/AdminUsers.php — رفتار: resources/js/admin/users.js
--}}
@php
    use App\Support\AdminNav;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $n = fn ($v) => $digits(number_format((int) $v, 0, '.', $fa ? '٬' : ','));
    $urls = $urls ?? [];
    $blocked = $u['status'] === 'blocked';
    $stTone = ['success' => 'ok', 'failed' => 'bad', 'cancelled' => 'warn', 'pending' => 'info'];
@endphp

@section('content')
<div data-ad-user data-user="{{ $u['id'] }}" data-copied="{{ __('admin.users.show.copied') }}"
    @isset($urls['block']) data-block-url="{{ $urls['block'] }}" @endisset
    @isset($urls['grant']) data-grant-url="{{ $urls['grant'] }}" @endisset
    data-t='@json(__('admin.users.act'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'>

    <a href="{{ url('/admin/users') }}" class="ad-more mb-3">{!! AdminNav::icon('arrow') !!}{{ __('admin.users.show.back') }}</a>

    <div class="ad-head">
        <div class="ad-user ad-user--lg">
            <i class="ad-av" style="--h: {{ $u['color'] }}" aria-hidden="true">{{ mb_substr($u['name'], 0, 1) }}</i>
            <span class="min-w-0">
                <h1 class="ad-h1 truncate">{{ $u['name'] }}</h1>
                <small dir="ltr">{{ $u['contact'] }}</small>
            </span>
            <span class="ad-badge" data-tone="bad" data-ad-blocked-badge @unless ($blocked) hidden @endunless>{{ __('admin.users.blocked') }}</span>
        </div>
        <div class="ad-acts">
            <a href="{{ url('/admin/tickets?user='.$u['id']) }}" class="btn-outline">{{ __('admin.users.show.tickets_link') }}</a>
            <button type="button" class="btn-outline" data-ad-act="grant">{{ __('admin.users.act.grant') }}</button>
            <button type="button" class="btn-outline !text-rose-600" data-ad-act="block" data-blocked="{{ $blocked ? '1' : '0' }}"><span data-ad-block-t>{{ $blocked ? __('admin.users.act.unblock') : __('admin.users.act.block') }}</span></button>
        </div>
    </div>

    <div class="ad-grid">
        <section class="ad-card" aria-labelledby="u-acc">
            <h2 class="ad-card__t mb-3" id="u-acc">{{ __('admin.users.show.account') }}</h2>
            <dl class="ad-dl">
                <div><dt>{{ __('admin.users.show.registered') }}</dt><dd><time data-ad-date="{{ $u['registered_at'] }}"></time></dd></div>
                <div><dt>{{ __('admin.users.show.seen') }}</dt><dd>@if ($u['last_seen']) <time data-ad-ago="{{ $u['last_seen'] }}"></time> @else {{ __('admin.users.never') }} @endif</dd></div>
                <div><dt>{{ __('admin.users.show.score') }}</dt><dd class="ad-num">{{ $n($u['score']) }}</dd></div>
                <div>
                    <dt>{{ __('admin.users.show.referral') }}</dt>
                    <dd><code class="ad-code" dir="ltr" data-ad-code>{{ $u['referral_code'] }}</code>
                        <button type="button" class="ad-ico !size-8" data-ad-copy aria-label="{{ __('admin.users.show.copy') }}" title="{{ __('admin.users.show.copy') }}">{!! AdminNav::icon('copy', 'size-4') !!}</button></dd>
                </div>
                <div><dt>{{ __('admin.users.show.referred_by') }}</dt><dd>@if ($u['referred_by']) <a class="ad-more" href="{{ url('/admin/users/'.$u['referred_by']['id']) }}">{{ $u['referred_by']['name'] }}</a> @else {{ __('admin.users.show.none') }} @endif</dd></div>
                <div><dt>{{ __('admin.users.show.referrals') }}</dt><dd class="ad-num">{{ $n($u['referrals']) }}</dd></div>
            </dl>
            <div class="ad-mini">
                <div><b>{{ $n($u['streak']) }}</b><small>{{ __('admin.users.show.streak') }}</small></div>
                <div><b>{{ $n($u['songs_done']) }}</b><small>{{ __('admin.users.show.songs') }}</small></div>
                <div><b>{{ $n($u['words_saved']) }}</b><small>{{ __('admin.users.show.words') }}</small></div>
                <div><b>{{ $n($u['tickets_open']) }}</b><small>{{ __('admin.users.show.tickets') }}</small></div>
            </div>
        </section>

        <section class="ad-card" aria-labelledby="u-test">
            <div class="ad-card__h">
                <h2 class="ad-card__t" id="u-test">{{ __('admin.users.show.tests') }}</h2>
                @if ($u['level']) <span class="ad-badge" data-tone="info">{{ __('admin.users.show.overall') }}: {{ $u['level'] }}</span> @endif
            </div>
            <ul class="space-y-3.5">
                @foreach (['vocab', 'grammar', 'listening'] as $k)
                    @php $t = $u['tests'][$k]; @endphp
                    <li>
                        <div class="flex items-center justify-between gap-3 text-sm">
                            <span class="font-bold text-slate-600">{{ __('admin.users.show.'.$k) }}</span>
                            @if ($t) <span><b class="ad-num">{{ $digits($t['score']) }}</b><small class="text-slate-400">/{{ $digits(100) }}</small> <span class="ad-badge" data-tone="info">{{ $t['level'] }}</span></span>
                            @else <span class="text-xs text-slate-400">{{ __('admin.users.show.not_taken') }}</span> @endif
                        </div>
                        <div class="ad-meter" data-state="{{ $t ? ($t['score'] >= 60 ? 'ok' : 'low') : 'bad' }}"><i data-w="{{ $t ? $t['score'] : 0 }}"></i></div>
                        @if ($t) <small class="mt-1 block text-[0.7rem] text-slate-400"><time data-ad-date="{{ $t['at'] }}"></time></small> @endif
                    </li>
                @endforeach
            </ul>
        </section>

        <section class="ad-card" aria-labelledby="u-sub">
            <h2 class="ad-card__t mb-3" id="u-sub">{{ __('admin.users.show.sub') }}</h2>
            <div class="space-y-3">
                <div class="ad-sub-box" data-on="{{ $u['plan_active'] ? '1' : '0' }}">
                    <p class="ad-sub-box__l">{{ __('admin.users.show.active') }}</p>
                    @if ($u['plan_active'])
                        <b class="ad-sub-box__v">{{ __('admin.dash.mix.'.$u['plan_active']['plan']) }}</b>
                        <p class="text-xs text-slate-500"><time data-ad-date="{{ $u['plan_active']['started_at'] }}"></time> ← <time data-ad-date="{{ $u['plan_active']['ends_at'] }}"></time></p>
                        <span class="ad-badge mt-2" data-tone="ok">{{ __('admin.users.days_left', ['n' => $digits($u['plan_active']['days_left'])]) }}</span>
                    @else
                        <p class="text-sm text-slate-400">{{ __('admin.users.show.no_active') }}</p>
                    @endif
                </div>
                <div class="ad-sub-box">
                    <p class="ad-sub-box__l">{{ __('admin.users.show.previous') }}</p>
                    @if ($u['plan_prev'])
                        <b class="ad-sub-box__v">{{ __('admin.dash.mix.'.$u['plan_prev']['plan']) }}</b>
                        <p class="text-xs text-slate-500"><time data-ad-date="{{ $u['plan_prev']['started_at'] }}"></time> ← <time data-ad-date="{{ $u['plan_prev']['ended_at'] }}"></time></p>
                    @else
                        <p class="text-sm text-slate-400">{{ __('admin.users.show.no_prev') }}</p>
                    @endif
                </div>
            </div>
        </section>

        <section class="ad-card is-2 lg:col-span-3" aria-labelledby="u-hist">
            <h2 class="ad-card__t mb-3" id="u-hist">{{ __('admin.users.show.history') }}</h2>
            @if (! $u['history'])
                <p class="ad-empty">{{ __('admin.users.show.no_history') }}</p>
            @else
                <div class="ad-tbl-wrap">
                    <table class="ad-tbl">
                        <thead><tr><th>{{ __('admin.users.show.ref') }}</th><th>{{ __('admin.users.show.plan') }}</th><th>{{ __('admin.users.show.amount') }}</th><th>{{ __('admin.users.show.status') }}</th><th>{{ __('admin.users.show.time') }}</th></tr></thead>
                        <tbody>
                            @foreach ($u['history'] as $h)
                                <tr>
                                    <td><code class="ad-code" dir="ltr">{{ $h['ref'] }}</code></td>
                                    <td data-label="{{ __('admin.users.show.plan') }}">{{ __('admin.dash.mix.'.$h['plan']) }}</td>
                                    <td data-label="{{ __('admin.users.show.amount') }}" class="ad-num">{{ $n($h['amount']) }} <small class="font-medium text-slate-400">{{ __('admin.common.toman') }}</small></td>
                                    <td data-label="{{ __('admin.users.show.status') }}"><span class="ad-badge" data-tone="{{ $stTone[$h['status']] }}">{{ __('admin.status.'.$h['status']) }}</span></td>
                                    <td data-label="{{ __('admin.users.show.time') }}"><time data-ad-date="{{ $h['at'] }}"></time></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </section>
    </div>
</div>
@endsection

@push('modals')
<div id="ad-block" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="ad-block-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ad-block-t" class="text-lg font-extrabold text-brand-900" data-ad-block-title></h2>
        <p class="mt-2 text-sm leading-7 text-slate-500" data-ad-block-desc></p>
        <div class="ad-field mt-4">
            <label class="ad-label" for="ad-block-reason">{{ __('admin.users.act.reason') }}</label>
            <textarea id="ad-block-reason" class="field !min-h-20" data-ad-reason></textarea>
        </div>
        <p class="sb-err" role="alert" hidden data-ad-block-err></p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-ad-block-ok></button>
        </div>
    </div>
</div>

<div id="ad-grant" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ad-grant-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="ad-grant-t" class="text-lg font-extrabold text-brand-900">{{ __('admin.users.act.grant_t') }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-500">{{ __('admin.users.act.grant_d') }}</p>
        <div class="ad-field mt-4">
            <label class="ad-label" for="ad-grant-days">{{ __('admin.users.act.days') }}</label>
            <input id="ad-grant-days" type="number" inputmode="numeric" min="1" max="365" value="30" class="field" data-ad-days>
            <p class="ad-hint">{{ __('admin.users.act.days_hint') }}</p>
        </div>
        <div class="ad-field mt-3">
            <label class="ad-label" for="ad-grant-reason">{{ __('admin.users.act.reason') }}</label>
            <textarea id="ad-grant-reason" class="field !min-h-20" data-ad-reason2></textarea>
        </div>
        <p class="sb-err" role="alert" hidden data-ad-grant-err></p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary" data-ad-grant-ok>{{ __('admin.users.act.confirm_grant') }}</button>
        </div>
    </div>
</div>
@endpush
