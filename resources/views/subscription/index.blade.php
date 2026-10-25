@extends('layouts.app')

@push('scripts')
    @vite('resources/js/pages/subscription.js')
@endpush

@section('title', __('subscription.title'))

{{--
    خرید اشتراک: کارت پلن‌ها ← مودال تأیید خرید ← انتقال به درگاه ← /subscription/result (موفق / ناموفق / لغو).
    رفتار: resources/js/modules/subscription.js — قرارداد داده و آدرس‌ها: app/Support/Subscription.php و README.
--}}
@php
    $ic = fn (string $d, string $c = 'size-5') => '<svg viewBox="0 0 24 24" class="'.$c.'" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">'.$d.'</svg>';
    $urls = $urls ?? [];
    $current = $current ?? ['plan' => 'free', 'ends_at' => null, 'days_left' => 0];
    $isPaid = ($current['plan'] ?? 'free') !== 'free';
    $fa = app()->getLocale() === 'fa';
    $fmt = fn ($n) => number_format((int) $n, 0, '.', $fa ? '٬' : ',');
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $money = fn ($n) => $digits($fmt($n));
@endphp
@section('content')
<div class="page-wrap" data-sb
    data-plans='@json($plans ?? [], JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'
    data-current="{{ $current['plan'] ?? 'free' }}"
    data-result-url="{{ $urls['result'] ?? url('/subscription/result') }}"
    @isset($urls['checkout']) data-checkout-url="{{ $urls['checkout'] }}" @endisset
    data-t='@json(__('subscription'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'>

    <header class="sb-head">
        <h1 class="text-xl font-extrabold text-brand-900 md:text-3xl">{{ __('subscription.title') }}</h1>
        <p class="mx-auto mt-2 max-w-xl text-sm leading-7 text-slate-500 md:text-base">{{ __('subscription.subtitle') }}</p>
    </header>

    @if ($isPaid)
        <div class="sb-current" role="status">
            {!! $ic('<path d="M20 6 9 17l-5-5"/>', 'size-5 shrink-0') !!}
            <span>
                <b>{{ __('subscription.current') }}:</b>
                {{ __('subscription.plans.'.$current['plan'].'.name') }}
                @if (! empty($current['days_left'])) · {{ __('subscription.days_left', ['n' => $digits($current['days_left'])]) }} @endif
            </span>
        </div>
    @endif

    <ul class="sb-grid" data-sb-grid>
        @foreach ($plans as $p)
            @php
                $isFree = $p['price'] === 0;
                $off = ! empty($p['old_price']) ? (int) round((1 - $p['price'] / $p['old_price']) * 100) : 0;
                $mine = ($current['plan'] ?? 'free') === $p['id'];
            @endphp
            <li class="sb-plan {{ $p['recommended'] ? 'is-pick' : '' }} {{ $isFree ? 'is-free' : '' }}" data-plan="{{ $p['id'] }}">
                @if ($p['recommended'])
                    <span class="sb-ribbon"><span>{{ __('subscription.pick') }}</span></span>
                @endif

                <div class="sb-plan__top">
                    <h2 class="sb-plan__name">{{ __('subscription.prefix') }} <b>{{ __('subscription.plans.'.$p['id'].'.term') }}</b></h2>
                    @if ($mine)
                        <span class="sb-plan__mine">{{ __('subscription.current') }}</span>
                    @endif
                    @if (! $mine && $off > 0)
                        <span class="sb-plan__off">{{ __('subscription.off', ['n' => $digits($off)]) }}</span>
                    @endif
                </div>

                <div class="sb-price">
                    @if (! empty($p['old_price']))
                        <del class="sb-price__old">{{ $money($p['old_price']) }}</del>
                    @endif
                    <b class="sb-price__now">{{ $money($p['price']) }}</b>
                    <span class="sb-price__cur">{{ __('subscription.currency') }}</span>
                </div>
                @if ($p['months'] > 1)
                    <p class="sb-plan__per">{{ __('subscription.per_month', ['price' => $money((int) round($p['price'] / $p['months'], -3))]) }}</p>
                @else
                    <p class="sb-plan__per" aria-hidden="true">&nbsp;</p>
                @endif

                @if ($isFree)
                    <a href="{{ url('/music') }}" class="btn-outline sb-cta">{{ __('subscription.plans.free.cta') }}</a>
                @else
                    <button type="button" class="{{ $p['recommended'] ? 'btn-primary' : 'btn-outline' }} sb-cta" data-sb-buy="{{ $p['id'] }}" aria-haspopup="dialog">
                        {{ $mine ? __('subscription.renew') : __('subscription.plans.'.$p['id'].'.cta') }}
                    </button>
                @endif

                <ul class="sb-feats">
                    @foreach ($p['features'] as $f)
                        <li>
                            <span class="sb-feats__ic">{!! $ic('<path d="M20 6 9 17l-5-5"/>', 'size-3.5') !!}</span>
                            <span>{{ __('subscription.features.'.$f, ['n' => $digits(3)]) }}</span>
                        </li>
                    @endforeach
                </ul>

                @if ($isFree)
                    <p class="sb-tag" dir="auto">
                        <span class="font-bold text-brand-700" dir="ltr">{{ __('subscription.tagline_en') }}</span>
                        <span class="text-slate-600">{{ __('subscription.tagline_fa') }}</span>
                    </p>
                @else
                    <p class="sb-guarantee">{!! __('subscription.guarantee') !!}</p>
                @endif
            </li>
        @endforeach
    </ul>

    <ul class="sb-trust">
        @foreach (__('subscription.trust') as $i => $tr)
            <li>
                <span class="sb-trust__ic">
                    {!! $ic([
                        '<path d="M12 3 5 6v5c0 4.4 2.9 8.3 7 10 4.1-1.7 7-5.6 7-10V6z"/><path d="m9 12 2 2 4-4"/>',
                        '<path d="M13 2 4 14h7l-1 8 9-12h-7z"/>',
                        '<path d="M4 13v-1a8 8 0 0 1 16 0v1"/><rect x="3" y="13" width="4" height="6" rx="1.5"/><rect x="17" y="13" width="4" height="6" rx="1.5"/>',
                    ][$i] ?? '') !!}
                </span>
                <span class="min-w-0">
                    <b>{{ $tr['t'] }}</b>
                    <small>{{ $tr['d'] }}</small>
                </span>
            </li>
        @endforeach
    </ul>

    <section class="sb-faq" aria-labelledby="sb-faq-t">
        <h2 id="sb-faq-t" class="text-base font-extrabold text-brand-900 md:text-lg">{{ __('subscription.faq.title') }}</h2>
        <div class="mt-3 grid gap-2.5 md:grid-cols-2">
            @foreach (__('subscription.faq.items') as $it)
                <details class="sb-q">
                    <summary>
                        <span>{{ $it['q'] }}</span>
                        {!! $ic('<path d="m6 9 6 6 6-6"/>', 'sb-q__chev size-4 shrink-0') !!}
                    </summary>
                    <p>{{ $it['a'] }}</p>
                </details>
            @endforeach
        </div>
        <a href="{{ url('/support?new=1') }}" class="sb-help">{{ __('subscription.result.support') }} ←</a>
    </section>
</div>
@endsection

@push('modals')
<div id="sb-confirm" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="sb-confirm-title">
    <div class="dialog-backdrop" data-sb-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <div class="flex items-start gap-3.5">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-brand-50 text-brand-700">
                {!! $ic('<rect x="3" y="5" width="18" height="14" rx="2.5"/><path d="M3 10h18M7 15h3"/>') !!}
            </span>
            <div class="min-w-0">
                <h2 id="sb-confirm-title" class="text-lg font-extrabold text-brand-900">{{ __('subscription.confirm.title') }}</h2>
                <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('subscription.confirm.lead') }}</p>
            </div>
        </div>

        <dl class="sb-sum" data-sb-sum>
            <div><dt>{{ __('subscription.confirm.plan') }}</dt><dd data-sb-s="name"></dd></div>
            <div><dt>{{ __('subscription.confirm.period') }}</dt><dd data-sb-s="period"></dd></div>
            <div data-sb-row="old"><dt>{{ __('subscription.confirm.price') }}</dt><dd><del data-sb-s="old"></del></dd></div>
            <div data-sb-row="disc" class="is-good"><dt>{{ __('subscription.confirm.discount') }}</dt><dd data-sb-s="disc"></dd></div>
            <div class="is-total"><dt>{{ __('subscription.confirm.total') }}</dt><dd><b data-sb-s="total"></b> <small>{{ __('subscription.currency') }}</small></dd></div>
        </dl>

        <p class="sb-secure">{!! $ic('<rect x="5" y="11" width="14" height="9" rx="2"/><path d="M8 11V8a4 4 0 0 1 8 0v3"/>', 'size-4 shrink-0') !!} {{ __('subscription.confirm.secure') }}</p>
        <p class="sb-err" role="alert" hidden data-sb-err></p>

        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-sb-close>{{ __('subscription.confirm.cancel') }}</button>
            <button type="button" class="btn-primary sm:min-w-48" data-sb-pay>
                <span data-sb-pay-t></span>
            </button>
        </div>
    </div>
</div>
@endpush
