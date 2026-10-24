@extends('layouts.site')

@section('title', __('site.meta.faq_t').' · '.__('site.brand'))
@section('description', __('site.meta.faq_d'))
@section('page', 'faq')

@section('content')
<section class="st-wrap st-page">
    <header class="st-sec__h">
        <h1 class="st-h2"><span class="st-h2__dots" aria-hidden="true"></span>{{ __('site.faq.title') }}</h1>
        <p class="st-lead">{{ __('site.faq_page.lead') }}</p>
    </header>

    <div class="mx-auto max-w-3xl">
        <x-site.faq :items="__('site.faq.items')" :schema="true" />
        <div class="st-faq__foot">
            <p>{{ __('site.faq.still') }}</p>
            <a href="{{ url('/support') }}" class="btn-primary">{{ __('site.faq.ask') }}</a>
            <a href="{{ url('/') }}" class="st-link">{{ __('site.faq_page.back') }}</a>
        </div>
    </div>
</section>
@endsection
