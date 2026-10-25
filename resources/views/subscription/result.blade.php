@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/subscription.js')
@endpush

@section('title', __('subscription.result.title'))

{{--
    نتیجه‌ی پرداخت: موفق (رسید + اعتبار تا…) | ناموفق (پیام بانک، تلاش دوباره، پشتیبانی) | لغو.
    بک‌اند بعد از verify تراکنش، $result را می‌دهد (قرارداد: app/Support/Subscription.php). دمو: ?status=success|failed|cancelled
    رفتار (تاریخ شمسی، کپی کد پیگیری): resources/js/modules/subscription.js
--}}
@php
    $ic = fn (string $d, string $c = 'size-5') => '<svg viewBox="0 0 24 24" class="'.$c.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $money = fn ($n) => $digits(number_format((int) $n, 0, '.', $fa ? '٬' : ','));
    $st = $result['status'] ?? 'failed';
    $ok = $st === 'success';
    $plan = collect($plans ?? [])->firstWhere('id', $result['plan'] ?? '');
    $planName = $plan ? __('subscription.plans.'.$plan['id'].'.name') : '—';
    $tone = ['success' => 'good', 'failed' => 'bad', 'cancelled' => 'warn'][$st] ?? 'bad';
    $icons = [
        'success' => '<path d="m5 12.5 4.5 4.5L19 7.5"/>',
        'failed' => '<path d="M6 6l12 12M18 6 6 18"/>',
        'cancelled' => '<path d="M12 7v6"/><path d="M12 17h.01"/>',
    ];
@endphp
@section('content')
<div class="page-wrap" data-sb-result data-status="{{ $st }}"
    data-t='@json(__('subscription'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'>
    <section class="sr" data-tone="{{ $tone }}" aria-live="polite">
        <div class="sr-hero">
            <span class="sr-badge">{!! $ic($icons[$st] ?? $icons['failed'], 'size-9') !!}</span>
            <h1 class="sr-title">{{ __('subscription.result.'.$st.'.title') }}</h1>
            <p class="sr-text">{{ __('subscription.result.'.$st.'.text') }}</p>
            @if ($st === 'failed')
                <p class="sr-note">{{ __('subscription.result.failed.money') }}</p>
            @endif
        </div>

        <div class="sr-card">
            <h2 class="sr-card__t">{{ __('subscription.result.receipt') }}</h2>
            <dl class="sr-rows">
                <div><dt>{{ __('subscription.result.plan') }}</dt><dd>{{ $planName }}</dd></div>
                <div><dt>{{ __('subscription.result.amount') }}</dt><dd><b>{{ $money($result['amount'] ?? 0) }}</b> <small>{{ __('subscription.currency') }}</small></dd></div>
                @if (! empty($result['ref']))
                    <div>
                        <dt>{{ __('subscription.result.ref') }}</dt>
                        <dd>
                            <bdi class="sr-code" dir="ltr" data-sr-ref>{{ $result['ref'] }}</bdi>
                            <button type="button" class="sr-copy" data-sr-copy aria-label="{{ __('subscription.result.copy') }}">{!! $ic('<rect x="9" y="9" width="11" height="11" rx="2.5"/><path d="M5 15V6a2 2 0 0 1 2-2h8"/>', 'size-4') !!}</button>
                        </dd>
                    </div>
                @endif
                @if (! empty($result['bank_ref']))
                    <div><dt>{{ __('subscription.result.bank_ref') }}</dt><dd><bdi class="sr-code" dir="ltr">{{ $result['bank_ref'] }}</bdi></dd></div>
                @endif
                @if (! empty($result['paid_at']))
                    <div><dt>{{ __('subscription.result.paid_at') }}</dt><dd data-sr-ts="{{ $result['paid_at'] }}" data-sr-fmt="dt">{{ $result['paid_at'] }}</dd></div>
                @endif
                @if ($ok && ! empty($result['ends_at']))
                    <div class="is-hi"><dt>{{ __('subscription.result.ends_at') }}</dt><dd data-sr-ts="{{ $result['ends_at'] }}" data-sr-fmt="d">{{ $result['ends_at'] }}</dd></div>
                @endif
                @if ($st === 'failed' && ! empty($result['message']))
                    <div class="is-bad"><dt>{{ __('subscription.result.failed.reason') }}</dt><dd dir="auto">{{ $result['message'] }}</dd></div>
                @endif
            </dl>
        </div>

        <div class="sr-actions">
            @if ($ok)
                <a href="{{ url('/music') }}" class="btn-primary">{{ __('subscription.result.start') }}</a>
                <a href="{{ url('/dashboard') }}" class="btn-outline">{{ __('subscription.result.dashboard') }}</a>
            @else
                <a href="{{ url('/subscription') }}" class="btn-primary">{{ $st === 'failed' ? __('subscription.result.retry') : __('subscription.result.back') }}</a>
                @if ($st === 'failed')
                    <a href="{{ url('/tickets?new=1') }}" class="btn-outline">{{ __('subscription.result.support') }}</a>
                @else
                    <a href="{{ url('/dashboard') }}" class="btn-outline">{{ __('subscription.result.dashboard') }}</a>
                @endif
            @endif
        </div>
    </section>
</div>
<div class="pf-toast" role="status" aria-live="polite" hidden data-sr-toast></div>
@endsection
