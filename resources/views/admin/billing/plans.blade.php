@extends('layouts.admin')

@section('title', __('adminbilling.p.title'))

{{--
    قیمت پلن‌ها: ویرایش قیمت نهایی، قیمت قبل از تخفیف (درصد تخفیف خودکار)، پیشنهادی بودن و قابل خرید بودن.
    داده: app/Support/AdminBilling.php (plans) — رفتار: resources/js/admin/billing.js
--}}
@php
    use App\Support\AdminNav;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $n = fn ($v) => $digits(number_format((int) $v, 0, '.', $fa ? '٬' : ','));
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $tab = 'plans';
    $paid = array_values(array_filter($plans, fn ($x) => $x['months'] > 0));
    $trans = ['p' => __('adminbilling.p'), 'toman' => __('adminbilling.toman')];
@endphp

@section('content')
<div data-bl-plans data-plans='@json($paid, $flags)' data-t='@json($trans, $flags)'
    @isset($urls['save']) data-save-url="{{ $urls['save'] }}" @endisset>

    <div class="ad-head">
        <div>
            <h1 class="ad-h1">{{ __('adminbilling.title') }}</h1>
            <p class="ad-sub">{{ __('adminbilling.p.sub') }}</p>
        </div>
        <div class="ad-acts">
            <span class="text-xs text-slate-400" data-bl-dirty hidden>{{ __('adminbilling.p.unsaved') }}</span>
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-bl-save>{{ __('adminbilling.p.save') }}</button>
        </div>
    </div>

    @include('admin.billing._tabs')

    <p class="ad-hint !mt-0 mb-3">{{ __('adminbilling.p.warn_active') }}</p>

    <div class="bl-plans" role="radiogroup" aria-label="{{ __('adminbilling.p.recommended') }}">
        <article class="ad-card bl-plan is-free">
            <header class="flex items-center justify-between gap-2"><h2 class="ad-card__t">{{ __('adminbilling.plan.free') }}</h2><span class="ad-badge">0</span></header>
            <p class="ad-hint">{{ __('adminbilling.p.free_note') }}</p>
        </article>

        @foreach ($paid as $pl)
            <article class="ad-card bl-plan" data-id="{{ $pl['id'] }}">
                <header class="flex flex-wrap items-center justify-between gap-2">
                    <h2 class="ad-card__t">{{ __('adminbilling.plan.'.$pl['id']) }}</h2>
                    <span class="ad-badge" data-tone="info">{{ $digits($n($pl['subscribers'])) }} {{ __('adminbilling.p.subscribers') }}</span>
                </header>

                <div class="ad-field mt-3"><label class="ad-label" for="pl-{{ $pl['id'] }}-price">{{ __('adminbilling.p.price') }}</label>
                    <input id="pl-{{ $pl['id'] }}-price" class="field" dir="ltr" inputmode="numeric" autocomplete="off" data-bl-f="price" value="{{ $pl['price'] }}"><p class="ad-error" hidden></p></div>
                <div class="ad-field mt-3"><label class="ad-label" for="pl-{{ $pl['id'] }}-old">{{ __('adminbilling.p.old_price') }}</label>
                    <input id="pl-{{ $pl['id'] }}-old" class="field" dir="ltr" inputmode="numeric" autocomplete="off" data-bl-f="old_price" value="{{ $pl['old_price'] }}">
                    <p class="ad-hint">{{ __('adminbilling.p.old_hint') }}</p><p class="ad-error" hidden></p></div>

                <p class="bl-plan__calc" data-bl-calc aria-live="polite"></p>

                <label class="ad-switch mt-3"><input type="radio" name="recommended" value="{{ $pl['id'] }}" data-bl-f="recommended" @checked($pl['recommended'])>
                    <span class="ad-switch__t"><b>{{ __('adminbilling.p.recommended') }}</b></span></label>
                <label class="ad-switch mt-2"><input type="checkbox" data-bl-f="active" @checked($pl['active'])>
                    <span class="ad-switch__t"><b>{{ __('adminbilling.p.active') }}</b></span></label>
            </article>
        @endforeach
    </div>
    <p class="sb-err mt-3" role="alert" hidden data-bl-plans-err></p>
</div>
@endsection
