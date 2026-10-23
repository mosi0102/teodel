@extends('layouts.admin')

@section('title', $t['code'].' · '.$t['subject'])

{{--
    گفتگوی یک تیکت: پیام‌ها + پاسخ (پاسخ آماده، AI، پیوست، یادداشت داخلی) + اطلاعات کاربر.
    داده/آدرس‌ها: app/Support/AdminTickets.php — رفتار: resources/js/admin/desk.js
--}}
@php
    use App\Support\AdminNav;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $k, string $c = 'size-4') => AdminNav::icon($k, $c);
    $tone = ['open' => 'warn', 'answered' => 'ok', 'closed' => ''];
    $closed = $t['status'] === 'closed';
    $lastUser = '';
    foreach ($t['messages'] as $m) { if ($m['from'] === 'user') { $lastUser = $m['text']; } }
    $trans = ['s' => __('adminticket.s'), 'errors' => __('adminticket.errors'), 'status' => __('adminticket.status')];
    $last = $t['messages'][count($t['messages']) - 1]['at'] ?? $t['updated_at'];
@endphp

@section('content')
<div data-td-show data-id="{{ $t['id'] }}" data-status="{{ $t['status'] }}" data-user-name="{{ $t['user']['name'] }}"
    data-t='@json($trans, $flags)' data-canned='@json(array_values($canned), $flags)'
    @isset($urls['reply']) data-reply-url="{{ $urls['reply'] }}" @endisset
    @isset($urls['close']) data-close-url="{{ $urls['close'] }}" @endisset
    @isset($urls['reopen']) data-reopen-url="{{ $urls['reopen'] }}" @endisset
    @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset>

    <a href="{{ url('/admin/tickets') }}" class="ad-more mb-3">{!! $ic('arrow') !!}{{ __('adminticket.back') }}</a>

    <div class="ad-head">
        <div class="min-w-0">
            <h1 class="ad-h1 !text-xl md:!text-2xl">{{ $t['subject'] }}</h1>
            <p class="ad-sub flex flex-wrap items-center gap-2">
                <span class="ad-badge" data-tone="{{ $tone[$t['status']] }}" data-td-status>{{ __('adminticket.status.'.$t['status']) }}</span>
                <span class="ad-badge">{{ __('adminticket.dept.'.$t['dept']) }}</span>
                <span dir="ltr" class="text-xs font-bold text-slate-400">{{ $t['code'] }}</span>
            </p>
        </div>
        <div class="ad-acts">
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-td-close @if ($closed) hidden @endif>{{ __('adminticket.s.close') }}</button>
            <button type="button" class="btn-outline !min-h-10 !text-xs" data-td-reopen @unless ($closed) hidden @endunless>{{ __('adminticket.s.reopen') }}</button>
        </div>
    </div>

    <div class="ad-grid">
        <section class="ad-card is-2 !p-3 md:!p-5" aria-labelledby="td-thread-t">
            <h2 class="sr-only" id="td-thread-t">{{ __('adminticket.s.thread') }}</h2>
            <ol class="td-thread" data-td-thread>
                @foreach ($t['messages'] as $m)
                    <li class="td-msg" data-from="{{ $m['from'] }}">
                        <div class="td-msg__h">
                            <b>{{ $m['from'] === 'user' ? $t['user']['name'] : ($m['name'] ?: __('adminticket.s.you')) }}</b>
                            @if ($m['from'] === 'note') <span class="ad-badge" data-tone="warn">{{ __('adminticket.s.note_tag') }}</span> @endif
                            <time data-ad-ago="{{ $m['at'] }}"></time>
                        </div>
                        <p class="td-msg__t" dir="auto">{{ $m['text'] }}</p>
                        @if (! empty($m['files']))
                            <ul class="td-files">@foreach ($m['files'] as $f)<li><a href="{{ $f['url'] }}" target="_blank" rel="noopener">{!! $ic('image', 'size-3.5') !!}<span dir="ltr">{{ $f['name'] }}</span></a></li>@endforeach</ul>
                        @endif
                    </li>
                @endforeach
            </ol>

            {{-- پاسخ --}}
            <form class="td-composer" data-td-composer data-ai-form="ticket_reply" @isset($urls['ai']) data-ai-url="{{ $urls['ai'] }}" @endisset onsubmit="return false" novalidate @if ($closed) hidden @endif>
                <input type="hidden" name="subject" value="{{ $t['subject'] }}" data-ai-field>
                <input type="hidden" name="last_message" value="{{ $lastUser }}" data-ai-field>
                <div class="ad-field">
                    <label class="ad-label" for="td-reply"><span data-td-label>{{ __('adminticket.s.reply') }}</span>
                        <span class="flex items-center gap-1.5">
                            <select class="field !min-h-8 !w-auto !rounded-full !px-3 !py-0 !text-xs" data-td-canned aria-label="{{ __('adminticket.s.canned') }}">
                                <option value="">{{ __('adminticket.s.canned') }}</option>
                                @foreach ($canned as $c)<option value="{{ $c['id'] }}">{{ $c['title'] }}</option>@endforeach
                            </select>
                            <button type="button" class="ad-ai ad-ai--sm" data-ai-one="reply" data-ai-seeds="subject,last_message">{!! $ic('sparkles', 'size-3.5') !!}{{ __('adminticket.ai.reply') }}</button>
                        </span></label>
                    <textarea id="td-reply" name="reply" class="field !min-h-28" dir="auto" placeholder="{{ __('adminticket.s.reply_ph') }}" data-td-text data-ai-field></textarea>
                    <p class="ad-error" hidden data-td-err></p>
                </div>

                <ul class="td-chips" data-td-chips></ul>

                <div class="td-composer__bar">
                    <div class="flex flex-wrap items-center gap-3">
                        <label class="btn-ghost !min-h-9 !px-3 !text-xs cursor-pointer">{!! $ic('image', 'size-4') !!}{{ __('adminticket.s.attach') }}<input type="file" class="sr-only" accept="image/*" multiple data-td-file></label>
                        <label class="flex cursor-pointer items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="size-4 accent-amber-500" data-td-internal>{{ __('adminticket.s.internal') }}</label>
                    </div>
                    <div class="flex flex-wrap items-center gap-2">
                        <button type="button" class="btn-outline !min-h-10 !text-xs" data-td-send="close"><span data-td-send-close-t>{{ __('adminticket.s.send_close') }}</span></button>
                        <button type="button" class="btn-primary !min-h-10 !text-xs" data-td-send="reply"><span data-td-send-t>{{ __('adminticket.s.send') }}</span></button>
                    </div>
                </div>
                <p class="ad-hint !mt-1">{{ __('adminticket.s.attach_hint') }} · {{ __('adminticket.s.internal_hint') }}</p>
            </form>
            <p class="td-closed" data-td-closed @unless ($closed) hidden @endunless>{{ __('adminticket.s.closed_bar') }}</p>
        </section>

        <div class="grid content-start gap-4 xl:gap-5">
            <section class="ad-card" aria-labelledby="td-user-t">
                <h2 class="ad-card__t mb-3" id="td-user-t">{{ __('adminticket.s.user_card') }}</h2>
                <div class="ad-user">
                    <i class="ad-av" style="--h: {{ $t['user']['color'] }}" aria-hidden="true">{{ mb_substr($t['user']['name'], 0, 1) }}</i>
                    <span class="min-w-0"><b>{{ $t['user']['name'] }}</b><small dir="ltr">{{ $t['user']['contact'] }}</small></span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5">
                    <span class="ad-badge" data-tone="{{ $t['user']['plan'] === 'free' ? '' : 'ok' }}">{{ __('adminticket.plan.'.$t['user']['plan']) }}</span>
                    @if (! empty($t['user']['level'])) <span class="ad-badge" data-tone="info">{{ $t['user']['level'] }}</span> @endif
                </div>
                <a href="{{ url('/admin/users/'.$t['user']['id']) }}" class="btn-outline mt-3 w-full !min-h-9 !text-xs">{{ __('adminticket.s.open_user') }}</a>
            </section>

            <section class="ad-card" aria-labelledby="td-info-t">
                <h2 class="ad-card__t mb-2" id="td-info-t">{{ __('adminticket.s.info') }}</h2>
                <dl class="ad-dl">
                    <div><dt>{{ __('adminticket.s.code') }}</dt><dd dir="ltr">{{ $t['code'] }}</dd></div>
                    <div><dt>{{ __('adminticket.s.created') }}</dt><dd><time data-ad-date="{{ $t['created_at'] }}"></time></dd></div>
                    <div><dt>{{ __('adminticket.s.updated') }}</dt><dd><time data-ad-ago="{{ $last }}" data-td-updated></time></dd></div>
                </dl>
            </section>

            <section class="ad-card" aria-labelledby="td-oth-t">
                <h2 class="ad-card__t mb-2" id="td-oth-t">{{ __('adminticket.s.others') }}</h2>
                @foreach ($others as $o)
                    <a href="{{ url('/admin/tickets/'.$o['id']) }}" class="td-other">
                        <span class="min-w-0 truncate">{{ $o['subject'] }}</span>
                        <span class="ad-badge shrink-0" data-tone="{{ $tone[$o['status']] }}">{{ __('adminticket.status.'.$o['status']) }}</span>
                    </a>
                @endforeach
                @if (! $others)
                    <p class="text-sm text-slate-400">{{ __('adminticket.s.no_others') }}</p>
                @endif
            </section>
        </div>
    </div>
</div>
@include('admin.partials.ai-dialog')
@endsection
