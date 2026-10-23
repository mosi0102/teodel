@extends('layouts.admin')

@section('title', $r['code'].' · '.\Illuminate\Support\Str::limit($r['description'], 60))

{{--
    جزئیات یک گزارش خطا: اسکرین‌شات (بزرگ‌نمایی)، شرح، وضعیت/اولویت، یادداشت‌های داخلی، کاربر و اطلاعات فنی.
    داده/آدرس‌ها: app/Support/AdminReports.php — رفتار: resources/js/admin/report.js
--}}
@php
    use App\Support\AdminNav;
    use App\Support\AdminReports;
    $fa = app()->getLocale() === 'fa';
    $digits = fn ($s) => $fa ? strtr((string) $s, ['0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹']) : (string) $s;
    $urls = $urls ?? [];
    $flags = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $ic = fn (string $k, string $c = 'size-4') => AdminNav::icon($k, $c);
    $tone = ['new' => 'warn', 'in_progress' => 'info', 'resolved' => 'ok', 'rejected' => ''];
    $ptone = ['low' => '', 'normal' => 'info', 'high' => 'bad'];
    $dev = AdminReports::device($r['meta']['userAgent']);
    $area = AdminReports::area($r['meta']['url']);
    $sent = strtotime($r['meta']['sentAt']) ?: $r['created_at'];
    $path = (string) (parse_url($r['meta']['url'], PHP_URL_PATH) ?? '').(($qq = parse_url($r['meta']['url'], PHP_URL_QUERY)) ? '?'.$qq : '');
    $copy = implode("\n", [$r['code'], $r['description'], $r['meta']['url'], $dev['os'].' · '.$dev['browser'].' · '.$r['meta']['viewport'], $r['meta']['userAgent']]);
    $trans = ['s' => __('adminreport.s'), 'errors' => __('adminreport.errors'), 'status' => __('adminreport.status'), 'priority' => __('adminreport.priority')];
@endphp

@section('content')
<div data-rp-show data-id="{{ $r['id'] }}" data-status="{{ $r['status'] }}" data-priority="{{ $r['priority'] }}"
    data-t='@json($trans, $flags)' data-copy='@json($copy, $flags)'
    @isset($urls['update']) data-update-url="{{ $urls['update'] }}" @endisset
    @isset($urls['note']) data-note-url="{{ $urls['note'] }}" @endisset>

    <a href="{{ url('/admin/reports') }}" class="ad-more mb-3">{!! $ic('arrow') !!}{{ __('adminreport.back') }}</a>

    <div class="ad-head">
        <div class="min-w-0">
            <h1 class="ad-h1 !text-xl md:!text-2xl" dir="auto">{{ \Illuminate\Support\Str::limit($r['description'], 90) }}</h1>
            <p class="ad-sub flex flex-wrap items-center gap-2">
                <span class="ad-badge" data-tone="{{ $tone[$r['status']] }}" data-rp-badge>{{ __('adminreport.status.'.$r['status']) }}</span>
                <span class="ad-badge" data-tone="{{ $ptone[$r['priority']] }}" data-rp-pbadge>{{ __('adminreport.priority.'.$r['priority']) }}</span>
                <span class="ad-badge">{{ __('adminreport.area.'.$area) }}</span>
                <span dir="ltr" class="text-xs font-bold text-slate-400">{{ $r['code'] }}</span>
            </p>
        </div>
        <div class="ad-acts">
            @if ($nav['prev'])<a href="{{ url('/admin/reports/'.$nav['prev']['id']) }}" class="btn-outline !min-h-10 !text-xs" rel="prev">{{ __('adminreport.s.prev') }}</a>@endif
            @if ($nav['next'])<a href="{{ url('/admin/reports/'.$nav['next']['id']) }}" class="btn-outline !min-h-10 !text-xs" rel="next">{{ __('adminreport.s.next') }}</a>@endif
        </div>
    </div>

    <div class="ad-grid">
        <div class="is-2 grid content-start gap-4 xl:gap-5">
            {{-- اسکرین‌شات + شرح --}}
            <section class="ad-card" aria-labelledby="rp-shot-t">
                <div class="ad-card__h">
                    <h2 class="ad-card__t" id="rp-shot-t">{{ __('adminreport.s.shot') }}</h2>
                    @if ($r['screenshot'])
                        <div class="flex flex-wrap items-center gap-1.5">
                            <button type="button" class="btn-outline !min-h-9 !px-3 !text-xs" data-rp-zoom>{{ __('adminreport.s.zoom') }}</button>
                            <a href="{{ $r['screenshot'] }}" target="_blank" rel="noopener" class="btn-ghost !min-h-9 !px-3 !text-xs">{{ __('adminreport.s.open') }}</a>
                            <a href="{{ $r['screenshot'] }}" download="{{ $r['code'] }}.{{ str_contains($r['screenshot'], 'svg') ? 'svg' : 'jpg' }}" class="btn-ghost !min-h-9 !px-3 !text-xs">{{ __('adminreport.s.download') }}</a>
                        </div>
                    @endif
                </div>
                @if ($r['screenshot'])
                    <button type="button" class="rp-shot {{ $dev['mobile'] ? 'is-mobile' : '' }}" data-rp-zoom aria-label="{{ __('adminreport.s.zoom') }}">
                        <img src="{{ $r['screenshot'] }}" alt="{{ __('adminreport.s.shot') }}" decoding="async" data-rp-img>
                    </button>
                    <p class="ad-hint mt-2">{{ __('adminreport.s.shot_hint') }}</p>
                @else
                    <p class="rp-noshot">{!! $ic('image', 'size-6') !!}<span>{{ __('adminreport.s.no_shot') }}</span></p>
                @endif

                <h3 class="mt-5 text-sm font-extrabold text-brand-900">{{ __('adminreport.s.desc') }}</h3>
                <p class="rp-desc" dir="auto">{{ $r['description'] }}</p>
            </section>

            {{-- یادداشت‌های داخلی --}}
            <section class="ad-card" aria-labelledby="rp-notes-t">
                <h2 class="ad-card__t" id="rp-notes-t">{{ __('adminreport.s.notes') }}</h2>
                <p class="ad-hint !mt-0 mb-3">{{ __('adminreport.s.notes_hint') }}</p>
                <ol class="rp-notes" data-rp-notes>
                    @foreach ($r['notes'] as $nt)
                        <li class="td-msg" data-from="note">
                            <div class="td-msg__h"><b>{{ $nt['by'] }}</b><time data-ad-ago="{{ $nt['at'] }}"></time></div>
                            <p class="td-msg__t" dir="auto">{{ $nt['text'] }}</p>
                        </li>
                    @endforeach
                </ol>
                <p class="text-sm text-slate-400" data-rp-nonotes @if ($r['notes']) hidden @endif>{{ __('adminreport.s.no_notes') }}</p>
                <div class="ad-field mt-3">
                    <label class="sr-only" for="rp-note">{{ __('adminreport.s.notes') }}</label>
                    <textarea id="rp-note" class="field !min-h-20" dir="auto" placeholder="{{ __('adminreport.s.note_ph') }}" data-rp-note></textarea>
                    <p class="ad-error" hidden data-rp-note-err></p>
                </div>
                <div class="mt-2 flex justify-end">
                    <button type="button" class="btn-primary !min-h-10 !text-xs" data-rp-add><span data-rp-add-t>{{ __('adminreport.s.add_note') }}</span></button>
                </div>
            </section>
        </div>

        <div class="grid content-start gap-4 xl:gap-5">
            {{-- وضعیت و اولویت --}}
            <section class="ad-card" aria-labelledby="rp-st-t">
                <h2 class="ad-card__t mb-3" id="rp-st-t">{{ __('adminreport.s.status') }}</h2>
                <div class="rp-seg" role="group" aria-label="{{ __('adminreport.s.status') }}" data-rp-status>
                    @foreach (AdminReports::STATUSES as $s)
                        <button type="button" data-v="{{ $s }}" data-tone="{{ $tone[$s] }}" aria-pressed="{{ $r['status'] === $s ? 'true' : 'false' }}">{{ __('adminreport.status.'.$s) }}</button>
                    @endforeach
                </div>
                <h3 class="mb-2 mt-4 text-xs font-extrabold text-slate-400">{{ __('adminreport.s.priority') }}</h3>
                <div class="rp-seg is-3" role="group" aria-label="{{ __('adminreport.s.priority') }}" data-rp-prio>
                    @foreach (AdminReports::PRIORITIES as $s)
                        <button type="button" data-v="{{ $s }}" data-tone="{{ $ptone[$s] }}" aria-pressed="{{ $r['priority'] === $s ? 'true' : 'false' }}">{{ __('adminreport.priority.'.$s) }}</button>
                    @endforeach
                </div>
                <label class="mt-4 flex cursor-pointer items-start gap-2 text-xs font-bold leading-5 text-slate-500"><input type="checkbox" class="mt-0.5 size-4 shrink-0 accent-brand-700" data-rp-notify checked>{{ __('adminreport.s.notify') }}</label>
            </section>

            <section class="ad-card" aria-labelledby="rp-user-t">
                <h2 class="ad-card__t mb-3" id="rp-user-t">{{ __('adminreport.s.user_card') }}</h2>
                <div class="ad-user">
                    <i class="ad-av" style="--h: {{ $r['user']['color'] }}" aria-hidden="true">{{ mb_substr($r['user']['name'], 0, 1) }}</i>
                    <span class="min-w-0"><b>{{ $r['user']['name'] }}</b><small dir="ltr">{{ $r['user']['contact'] }}</small></span>
                </div>
                <div class="mt-3 flex flex-wrap gap-1.5"><span class="ad-badge" data-tone="{{ $r['user']['plan'] === 'free' ? '' : 'ok' }}">{{ __('adminreport.plan.'.$r['user']['plan']) }}</span></div>
                <a href="{{ url('/admin/users/'.$r['user']['id']) }}" class="btn-outline mt-3 w-full !min-h-9 !text-xs">{{ __('adminreport.s.open_user') }}</a>
            </section>

            <section class="ad-card" aria-labelledby="rp-info-t">
                <div class="ad-card__h">
                    <h2 class="ad-card__t" id="rp-info-t">{{ __('adminreport.s.info') }}</h2>
                    <button type="button" class="btn-ghost !min-h-8 !px-2.5 !text-xs" data-rp-copy>{!! $ic('copy', 'size-3.5') !!}{{ __('adminreport.s.copy') }}</button>
                </div>
                <dl class="ad-dl">
                    <div><dt>{{ __('adminreport.s.code') }}</dt><dd dir="ltr">{{ $r['code'] }}</dd></div>
                    <div><dt>{{ __('adminreport.s.page') }}</dt><dd class="min-w-0"><a href="{{ $r['meta']['url'] }}" target="_blank" rel="noopener" class="rp-url" dir="ltr">{{ $path }}</a></dd></div>
                    <div><dt>{{ __('adminreport.s.area') }}</dt><dd>{{ __('adminreport.area.'.$area) }}</dd></div>
                    <div><dt>{{ __('adminreport.s.locale') }}</dt><dd dir="ltr">{{ strtoupper($r['meta']['locale']) }}</dd></div>
                    <div><dt>{{ __('adminreport.s.device') }}</dt><dd>{{ $dev['os'] }} · {{ $dev['browser'] }} · {{ __('adminreport.s.'.($dev['mobile'] ? 'mobile' : 'desktop')) }}</dd></div>
                    <div><dt>{{ __('adminreport.s.viewport') }}</dt><dd dir="ltr">{{ $r['meta']['viewport'] }}</dd></div>
                    <div><dt>{{ __('adminreport.s.sent') }}</dt><dd><time data-ad-date="{{ $sent }}"></time></dd></div>
                </dl>
            </section>

            <section class="ad-card" aria-labelledby="rp-oth-t">
                <h2 class="ad-card__t mb-2" id="rp-oth-t">{{ __('adminreport.s.others') }}</h2>
                @foreach ($others as $o)
                    <a href="{{ url('/admin/reports/'.$o['id']) }}" class="td-other">
                        <span class="min-w-0 truncate" dir="auto">{{ $o['description'] }}</span>
                        <span class="ad-badge shrink-0" data-tone="{{ $tone[$o['status']] }}">{{ __('adminreport.status.'.$o['status']) }}</span>
                    </a>
                @endforeach
                @if (! $others)
                    <p class="text-sm text-slate-400">{{ __('adminreport.s.no_others') }}</p>
                @endif
            </section>
        </div>
    </div>
</div>
@endsection

@push('modals')
@if ($r['screenshot'])
<div id="rp-zoom" class="dialog rp-zoom" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-label="{{ __('adminreport.s.shot') }}">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel rp-zoom__panel">
        <div class="rp-zoom__bar">
            <b class="text-sm font-extrabold text-brand-900" dir="ltr">{{ $r['code'] }}</b>
            <div class="flex items-center gap-2">
                <button type="button" class="btn-outline !min-h-9 !px-3 !text-xs" data-rp-fit data-fit="{{ __('adminreport.s.fit') }}" data-actual="{{ __('adminreport.s.actual') }}">{{ __('adminreport.s.actual') }}</button>
                <button type="button" class="btn-primary !min-h-9 !px-3 !text-xs" data-dialog-close>{{ __('adminreport.s.close') }}</button>
            </div>
        </div>
        <div class="rp-zoom__body" data-rp-zbody><img src="{{ $r['screenshot'] }}" alt="{{ __('adminreport.s.shot') }}" data-rp-zimg></div>
    </div>
</div>
@endif
@endpush
