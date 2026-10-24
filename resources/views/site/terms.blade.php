@extends('layouts.site')

@section('title', __('terms.meta_t').' · '.__('site.brand'))
@section('description', __('terms.meta_d'))
@section('page', 'terms')

{{-- قوانین و مقررات: فهرست چسبان (دسکتاپ) / فهرست جمع‌شونده (موبایل) + بخش‌ها. متن: lang/{fa,en}/terms.php --}}
@php
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $secs = __('terms.sections');
@endphp

@section('content')
<div class="st-wrap st-page">
    <header class="st-sec__h">
        <h1 class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('terms.title') }}</h1>
        <p class="st-lead">{{ __('terms.lead') }}</p>
        <p class="st-upd">{{ __('terms.updated', ['date' => __('terms.updated_at')]) }}</p>
    </header>

    <div class="st-terms">
        <aside class="st-toc" data-st-toc>
            <details class="st-toc__box" open>
                <summary>{{ __('terms.toc') }}<x-icon name="chevron-down" class="size-4" /></summary>
                <ol>
                    @foreach ($secs as $i => $s)
                        <li><a href="#t{{ $i + 1 }}" data-st-toc-a="t{{ $i + 1 }}"><b>{{ $digits($i + 1) }}</b><span>{{ $s['t'] }}</span></a></li>
                    @endforeach
                </ol>
            </details>
        </aside>

        <article class="st-doc">
            @foreach ($secs as $i => $s)
                <section id="t{{ $i + 1 }}" class="st-doc__s" aria-labelledby="t{{ $i + 1 }}-h">
                    <h2 id="t{{ $i + 1 }}-h"><b>{{ $digits($i + 1) }}</b>{{ $s['t'] }}</h2>
                    @foreach ($s['p'] as $p)<p>{{ $p }}</p>@endforeach
                    @if (! empty($s['l']))
                        <ul>@foreach ($s['l'] as $li)<li>{{ $li }}</li>@endforeach</ul>
                    @endif
                </section>
            @endforeach

            <div class="st-faq__foot">
                <p>{{ __('terms.support') }}</p>
                <a href="{{ url('/support') }}" class="btn-primary">{{ __('terms.support_cta') }}</a>
                <a href="{{ url('/') }}" class="st-link">{{ __('site.faq_page.back') }}</a>
            </div>
        </article>
    </div>
</div>
@endsection
