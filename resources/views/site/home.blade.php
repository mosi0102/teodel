@extends('layouts.site')

@section('page', 'home')

{{--
    صفحهٔ نخست: هیرو ← تئودل چیست؟ ← ویژگی‌ها ← اشتراک‌ها (همان Subscription::plans) ← راهنما ← نظرات ← اینستاگرام ← سوالات ← متن سئو.
    متن‌ها: lang/{fa,en}/site.php — تصویرها: <x-site.art> (فایل اصلی را در public/images/landing بگذارید). رفتار: resources/js/site.js
--}}
@php
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $money = fn ($n) => $digits(number_format((int) $n, 0, '.', $fa ? '٬' : ','));
    $loggedIn = $loggedIn ?? false;
    $buy = fn ($id) => $loggedIn ? url('/subscription') : url('/login').'?redirect='.rawurlencode('/subscription');
    $start = $loggedIn ? url('/dashboard') : url('/register');
    $aboutIcons = ['music' => 'music', 'film' => 'video', 'podcast' => 'mic', 'idiom' => 'languages'];
    $featArt = [['teal', 'headphones'], ['yellow', 'wave'], ['blue', 'flag'], ['orange', 'thumbs']];
    $stepArt = [['blue', 'flag'], ['yellow', 'headphones'], ['teal', 'book'], ['orange', 'thumbs']];
    $avatars = \App\Support\SiteContent::reviewAvatars();
    $c = $contacts ?? \App\Support\SiteContent::contacts();
    $heroFile = false;
    foreach (['webp', 'png', 'svg'] as $e) { if (is_file(public_path("images/landing/hero.{$e}"))) { $heroFile = true; break; } }
    $faqItems = array_slice(__('site.faq.items'), 0, 4);
@endphp

@section('content')
{{-- ───────── هیرو ───────── --}}
<section class="st-hero st-wrap" aria-labelledby="st-hero-t">
    <div class="st-hero__txt" data-st-in>
        <span class="st-eyebrow"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.hero.eyebrow') }}</span>
        <h1 id="st-hero-t" class="st-hero__t">{{ __('site.hero.title') }}</h1>
        <p class="st-hero__p">{{ __('site.hero.text') }}</p>
        <div class="st-hero__acts">
            <a href="{{ $start }}" class="st-cta st-cta--lg">{{ __('site.hero.cta') }}<x-icon name="arrow-end" class="size-4 rtl:-scale-x-100" /></a>
            <a href="#about" class="st-ghost">{{ __('site.hero.cta2') }}</a>
        </div>
        <ul class="st-chips">
            @foreach (__('site.hero.chips') as $chip)
                <li><x-icon name="check-circle" class="size-4" />{{ $chip }}</li>
            @endforeach
        </ul>
    </div>
    <div class="st-hero__art" aria-hidden="false">
        @unless ($heroFile)<div class="st-hero__glow" aria-hidden="true"></div>@endunless
        @if ($heroFile)
            <x-site.art name="hero" :alt="__('site.hero.art_alt')" :eager="true" :w="1000" :h="721" class="st-float" />
        @else
            {{-- جایگزین موقت تا تصویر اصلی هیرو (public/images/landing/hero.webp) را بگذارید --}}
            <div class="st-cluster" role="img" aria-label="{{ __('site.hero.art_alt') }}">
                <x-site.mascot color="teal" pose="headphones" />
                <x-site.mascot color="blue" pose="laptop" />
                <x-site.mascot color="yellow" pose="wave" />
                <x-site.mascot color="orange" pose="book" />
            </div>
        @endif
    </div>
</section>

{{-- ───────── تئودل چیست؟ ───────── --}}
<section id="about" class="st-sec st-wrap" aria-labelledby="st-about-t">
    <div class="st-about">
        <div data-st-in>
            <h2 id="st-about-t" class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.about.title') }}</h2>
            <p class="st-about__p">{{ __('site.about.text') }}</p>
        </div>
        <div data-st-in>
            <h3 class="st-about__sub">{{ __('site.about.content_title') }}</h3>
            <ul class="st-about__grid">
                @foreach (__('site.about.items') as $k => $it)
                    <li>
                        <span class="st-about__ic"><x-icon :name="$aboutIcons[$k] ?? 'music'" class="size-5" /></span>
                        <span class="min-w-0"><b>{{ $it['t'] }}</b><small>{{ $it['d'] }}</small></span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>
</section>

{{-- ───────── ویژگی‌ها ───────── --}}
<section id="features" class="st-sec st-wrap" aria-labelledby="st-feat-t">
    <header class="st-sec__h">
        <h2 id="st-feat-t" class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.features.title') }}</h2>
        <p class="st-lead">{{ __('site.features.lead') }}</p>
    </header>
    <ul class="st-feats">
        @foreach (__('site.features.items') as $i => $f)
            <li class="st-feat" data-st-in style="--d: {{ $i * 70 }}ms">
                <span class="st-feat__art"><x-site.art :name="'feature-'.($i + 1)" :w="360" :h="360" :color="$featArt[$i][0]" :pose="$featArt[$i][1]" /></span>
                <h3>{{ $f['t'] }}</h3>
                <p>{{ $f['d'] }}</p>
            </li>
        @endforeach
    </ul>
</section>

{{-- ───────── اشتراک‌ها (استایل کارت‌ها همان صفحهٔ خرید اشتراک: sb-*) ───────── --}}
<section id="pricing" class="st-sec st-wrap" aria-labelledby="st-price-t">
    <header class="st-sec__h">
        <h2 id="st-price-t" class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.pricing.title') }}</h2>
        <p class="st-lead">{{ __('site.pricing.lead') }}</p>
    </header>
    <ul class="sb-grid">
        @foreach ($plans as $p)
            @php
                $isFree = $p['price'] === 0;
                $off = ! empty($p['old_price']) ? (int) round((1 - $p['price'] / $p['old_price']) * 100) : 0;
            @endphp
            <li class="sb-plan {{ $p['recommended'] ? 'is-pick' : '' }} {{ $isFree ? 'is-free' : '' }}">
                @if ($p['recommended'])<span class="sb-ribbon"><span>{{ __('subscription.pick') }}</span></span>@endif
                <div class="sb-plan__top">
                    <h3 class="sb-plan__name">{{ __('subscription.prefix') }} <b>{{ __('subscription.plans.'.$p['id'].'.term') }}</b></h3>
                    @if ($off > 0)<span class="sb-plan__off">{{ __('subscription.off', ['n' => $digits($off)]) }}</span>@endif
                </div>
                <div class="sb-price">
                    @if (! empty($p['old_price']))<del class="sb-price__old">{{ $money($p['old_price']) }}</del>@endif
                    <b class="sb-price__now">{{ $money($p['price']) }}</b>
                    <span class="sb-price__cur">{{ __('subscription.currency') }}</span>
                </div>
                @if ($p['months'] > 1)
                    <p class="sb-plan__per">{{ __('subscription.per_month', ['price' => $money((int) round($p['price'] / $p['months'], -3))]) }}</p>
                @else
                    <p class="sb-plan__per" aria-hidden="true">&nbsp;</p>
                @endif
                <a href="{{ $isFree ? $start : $buy($p['id']) }}" class="{{ $p['recommended'] ? 'btn-primary' : 'btn-outline' }} sb-cta">{{ __('subscription.plans.'.$p['id'].'.cta') }}</a>
                <ul class="sb-feats">
                    @foreach ($p['features'] as $f)
                        <li>
                            <span class="sb-feats__ic"><x-icon name="check" class="size-3.5" /></span>
                            <span>{{ __('subscription.features.'.$f, ['n' => $digits(3)]) }}</span>
                        </li>
                    @endforeach
                </ul>
                @if ($isFree)
                    <p class="sb-tag" dir="auto"><span class="font-bold text-brand-700" dir="ltr">{{ __('subscription.tagline_en') }}</span><span class="text-slate-600">{{ __('subscription.tagline_fa') }}</span></p>
                @else
                    <p class="sb-guarantee">{!! __('subscription.guarantee') !!}</p>
                @endif
            </li>
        @endforeach
    </ul>
</section>

{{-- ───────── راهنمای کار ───────── --}}
<section id="steps" class="st-sec st-wrap" aria-labelledby="st-steps-t">
    <header class="st-sec__h">
        <h2 id="st-steps-t" class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.steps.title') }}</h2>
        <p class="st-lead">{{ __('site.steps.lead') }}</p>
    </header>
    <ol class="st-steps">
        @foreach (__('site.steps.items') as $i => $s)
            <li class="st-step" data-st-in style="--d: {{ $i * 80 }}ms">
                <span class="st-step__art"><x-site.art :name="'step-'.($i + 1)" :w="340" :h="340" :color="$stepArt[$i][0]" :pose="$stepArt[$i][1]" /><b class="st-step__n">{{ $digits($i + 1) }}</b></span>
                <h3 class="text-center mx-auto">{{ $s['t'] }}</h3>
                <p class="text-center mx-auto">{{ $s['d'] }}</p>
            </li>
        @endforeach
    </ol>
</section>

{{-- ───────── نظرات کاربران ───────── --}}
<section id="reviews" class="st-sec st-wrap" aria-labelledby="st-rev-t">
    <header class="st-sec__h">
        <h2 id="st-rev-t" class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.reviews.title') }}</h2>
        <p class="st-lead">{{ __('site.reviews.lead') }}</p>
    </header>
    <div class="st-fold" data-st-fold>
        <ul class="st-revs" id="st-revs">
            @foreach (__('site.reviews.items') as $i => $r)
                <li class="st-rev" data-st-in style="--d: {{ ($i % 3) * 70 }}ms">
                    <div class="st-rev__top">
                        <img src="{{ asset('images/avatars/avatar-'.sprintf('%02d', $avatars[$i % count($avatars)]).'.svg') }}" alt="" width="44" height="44" loading="lazy" decoding="async" class="st-rev__av">
                        <div class="min-w-0"><b>{{ $r['n'] }}</b><small>{{ $r['d'] }}</small></div>
                    </div>
                    <p>{{ $r['t'] }}</p>
                </li>
            @endforeach
        </ul>
        <div class="st-fold__more">
            <button type="button" class="st-more" data-st-more aria-expanded="false" aria-controls="st-revs" data-more="{{ __('site.reviews.more') }}" data-less="{{ __('site.reviews.less') }}">
                <span data-st-more-t>{{ __('site.reviews.more') }}</span><x-icon name="chevron-down" class="size-4" />
            </button>
        </div>
    </div>
</section>

{{-- ───────── اینستاگرام ───────── --}}
<section class="st-wrap st-sec st-sec--tight">
    <div class="st-insta" data-st-in>
        <p>{{ __('site.insta.text') }}</p>
        <a href="{{ $c['instagram_url'] }}" target="_blank" rel="noopener" class="st-insta__btn"><x-icon name="instagram" class="size-4" />{{ __('site.insta.cta') }}</a>
    </div>
</section>

{{-- ───────── سوالات متداول (نمونه‌ی کوتاه) ───────── --}}
<section id="faq" class="st-sec st-wrap" aria-labelledby="st-faq-t">
    <header class="st-sec__h">
        <h2 id="st-faq-t" class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.faq.title') }}</h2>
        <p class="st-lead">{{ __('site.faq.lead') }}</p>
    </header>
    <div class="mx-auto max-w-3xl">
        <x-site.faq :items="$faqItems" :schema="true" />
        <div class="st-faq__foot">
            <a href="{{ url('/faq') }}" class="btn-outline">{{ __('site.faq.more') }}</a>
        </div>
    </div>
</section>

{{-- ───────── متن سئو ───────── --}}
<section class="st-wrap st-sec st-sec--tight" aria-labelledby="st-seo-t">
    <div class="st-seo st-fold" data-st-fold>
        <h2 id="st-seo-t" class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.seo.title') }}</h2>
        <div class="st-seo__body" id="st-seo-body">
            @foreach (__('site.seo.paras') as $para)<p>{{ $para }}</p>@endforeach
        </div>
        <div class="st-fold__more">
            <button type="button" class="st-more" data-st-more aria-expanded="false" aria-controls="st-seo-body" data-more="{{ __('site.seo.more') }}" data-less="{{ __('site.seo.less') }}">
                <span data-st-more-t>{{ __('site.seo.more') }}</span><x-icon name="chevron-down" class="size-4" />
            </button>
        </div>
    </div>
</section>
@endsection
