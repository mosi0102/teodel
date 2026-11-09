@extends('layouts.admin')

{{--
    افزودن / ویرایش ادمین (فقط مدیر اصلی): مشخصات و شماره‌ی موبایل، رمز پیش‌فرض (ساخت خودکار، کپی، «تغییر در اولین ورود»)،
    نقش آماده + جدول دسترسی هر بخش (بدون دسترسی / دیدن / ویرایش / کامل)، فعال/غیرفعال، بازنشانی رمز و حذف.
    رفتار: resources/js/admin/staff.js — قرارداد: app/Support/AdminStaff.php
--}}
@php
    use App\Support\AdminNav;
    use App\Support\AdminStaff;
    $ic = fn (string $n, string $c = 'size-4') => AdminNav::icon($n, $c);
    $m = $member;
    $isNew = empty($m['id']);
    $isOwner = $m['role'] === 'owner';
    $urls = $urls ?? [];
    $jf = JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE;
    $roles = ['manager', 'content', 'support', 'finance', 'viewer', 'custom'];
@endphp

@section('title', $isNew ? __('adminstaff.new') : $m['name'])

@section('content')
<div data-staff
    data-model='@json($m, $jf)'
    data-presets='@json(AdminStaff::presets(), $jf)'
    data-t='@json(__('adminstaff.js'), $jf)'
    data-list-url="{{ url('/admin/staff') }}" data-edit-url="{{ url('/admin/staff/:id/edit') }}"
    @isset($urls['save']) data-save-url="{{ $urls['save'] }}" @endisset
    @isset($urls['reset']) data-reset-url="{{ $urls['reset'] }}" @endisset
    @isset($urls['delete']) data-delete-url="{{ $urls['delete'] }}" @endisset>

    <div class="ad-head">
        <div class="min-w-0">
            <a href="{{ url('/admin/staff') }}" class="mb-1 inline-flex items-center gap-1 text-xs font-bold text-slate-400 hover:text-brand-700">{!! $ic('arrow', 'size-3.5 ltr:rotate-180') !!}{{ __('adminstaff.back') }}</a>
            <h1 class="ad-h1 truncate" data-st-title>{{ $isNew ? __('adminstaff.new') : $m['name'] }}</h1>
            @unless ($isNew)
                <p class="ad-sub flex flex-wrap items-center gap-2">
                    <span class="ad-badge" data-tone="{{ $isOwner ? 'ai' : 'info' }}" data-st-role-badge>{{ __('adminstaff.roles.'.$m['role'].'.name') }}</span>
                    @if ($m['created_by'])<span class="text-xs text-slate-400">{{ __('adminstaff.created', ['by' => $m['created_by']]) }}</span>@endif
                </p>
            @endunless
        </div>
        <div class="ad-acts">
            @if (! $isNew && ! $isOwner)
                <button type="button" class="btn-ghost !min-h-10 !text-xs !text-rose-600" data-dialog-open="#st-delete">{{ __('admin.common.delete') }}</button>
            @endif
            <button type="button" class="btn-primary !min-h-10 !text-xs" data-st-save>{{ $isNew ? __('adminstaff.create') : __('adminstaff.save') }}</button>
        </div>
    </div>

    <div class="ad-grid">
        {{-- مشخصات --}}
        <section class="ad-card" aria-labelledby="st-info-t">
            <h2 class="ad-card__t mb-3" id="st-info-t">{{ __('adminstaff.info') }}</h2>
            <div class="space-y-4">
                <div class="ad-field">
                    <label class="ad-label" for="st-name"><span>{{ __('adminstaff.f.name') }} <em>*</em></span></label>
                    <input id="st-name" class="field" data-st="name" autocomplete="off">
                    <p class="ad-error" hidden data-st-err="name"></p>
                </div>
                <div class="ad-field">
                    <label class="ad-label" for="st-phone"><span>{{ __('adminstaff.f.phone') }} <em>*</em></span></label>
                    <input id="st-phone" class="field text-left" dir="ltr" inputmode="tel" autocomplete="off" placeholder="0912 345 6789" data-st="phone">
                    <p class="ad-hint">{{ __('adminstaff.f.phone_hint') }}</p>
                    <p class="ad-error" hidden data-st-err="phone"></p>
                </div>
                @unless ($isOwner)
                    <label class="ad-switch"><input type="checkbox" data-st="active"><span class="ad-switch__t"><b>{{ __('adminstaff.f.active') }}</b><small>{{ __('adminstaff.f.active_hint') }}</small></span></label>
                @endunless
            </div>
        </section>

        {{-- رمز عبور --}}
        <section class="ad-card is-2" aria-labelledby="st-pw-t">
            <h2 class="ad-card__t mb-1" id="st-pw-t">{{ __('adminstaff.pw.title') }}</h2>
            @if ($isNew)
                <p class="ad-hint mb-3">{{ __('adminstaff.pw.lead_new') }}</p>
                <div class="ad-field">
                    <label class="ad-label" for="st-pw"><span>{{ __('adminstaff.pw.default') }} <em>*</em></span></label>
                    <div class="st-pw">
                        <input id="st-pw" type="password" class="field font-mono" dir="ltr" autocomplete="new-password" spellcheck="false" data-st-pw>
                        <button type="button" class="st-pw__b" data-st-pw-toggle aria-pressed="false" aria-label="{{ __('adminstaff.pw.show') }}" title="{{ __('adminstaff.pw.show') }}">{!! $ic('eye') !!}</button>
                        <button type="button" class="st-pw__b" data-st-pw-copy aria-label="{{ __('adminstaff.pw.copy') }}" title="{{ __('adminstaff.pw.copy') }}">{!! $ic('copy') !!}</button>
                        <button type="button" class="btn-outline !min-h-11 shrink-0 !px-3 !text-xs" data-st-pw-gen>{!! $ic('refresh') !!}{{ __('adminstaff.pw.generate') }}</button>
                    </div>
                    <div class="st-meter" data-st-meter data-s="0" aria-hidden="true"><i></i><i></i><i></i><i></i></div>
                    <p class="ad-hint" data-st-meter-t>{{ __('adminstaff.pw.rule') }}</p>
                    <p class="ad-error" hidden data-st-err="password"></p>
                </div>
                <div class="mt-4 grid gap-3 md:grid-cols-2">
                    <label class="ad-switch"><input type="checkbox" data-st="must_change_password"><span class="ad-switch__t"><b>{{ __('adminstaff.pw.must') }}</b><small>{{ __('adminstaff.pw.must_hint') }}</small></span></label>
                    <label class="ad-switch"><input type="checkbox" data-st-sms checked><span class="ad-switch__t"><b>{{ __('adminstaff.pw.sms') }}</b><small>{{ __('adminstaff.pw.sms_hint') }}</small></span></label>
                </div>
            @else
                <p class="ad-hint mb-3">{{ __('adminstaff.pw.lead_edit') }}</p>
                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" class="btn-outline !min-h-10 !text-xs" data-st-reset-open>{!! $ic('key') !!}{{ __('adminstaff.pw.reset') }}</button>
                    @if ($m['must_change_password'])<span class="ad-badge" data-tone="warn">{{ __('adminstaff.pending_pw') }}</span>@endif
                </div>
            @endif
        </section>

        {{-- دسترسی‌ها --}}
        <section class="ad-card lg:col-span-3" aria-labelledby="st-acc-t">
            <div class="ad-card__h flex-wrap">
                <div class="min-w-0">
                    <h2 class="ad-card__t" id="st-acc-t">{{ __('adminstaff.access.title') }}</h2>
                    <p class="ad-hint !mt-0.5">{{ __('adminstaff.access.lead') }}</p>
                </div>
            </div>

            @if ($isOwner)
                <p class="st-note">{!! $ic('staff', 'size-5 shrink-0') !!}<span>{{ __('adminstaff.access.owner') }}</span></p>
            @else
                {{-- نقش‌های آماده --}}
                <div class="st-roles" role="radiogroup" aria-label="{{ __('adminstaff.access.role') }}">
                    @foreach ($roles as $r)
                        <label class="st-role">
                            <input type="radio" name="st-role" value="{{ $r }}" data-st-role class="sr-only">
                            <b>{{ __('adminstaff.roles.'.$r.'.name') }}</b>
                            <small>{{ __('adminstaff.roles.'.$r.'.desc') }}</small>
                        </label>
                    @endforeach
                </div>

                {{-- راهنمای سطح‌ها --}}
                <ul class="st-legend">
                    @foreach (AdminStaff::LEVELS as $lv)
                        <li><i class="st-dot" data-lv="{{ $lv }}"></i><b>{{ __('adminstaff.levels.'.$lv.'.name') }}</b><span>{{ __('adminstaff.levels.'.$lv.'.desc') }}</span></li>
                    @endforeach
                </ul>

                {{-- جدول دسترسی --}}
                <div class="st-matrix" data-st-matrix>
                    <div class="st-row st-row--head">
                        <span>{{ __('adminstaff.access.section') }}</span>
                        <div class="st-seg st-seg--all" role="group" aria-label="{{ __('adminstaff.access.all') }}">
                            @foreach (AdminStaff::LEVELS as $lv)
                                <button type="button" data-st-all="{{ $lv }}" title="{{ __('adminstaff.access.all') }}: {{ __('adminstaff.levels.'.$lv.'.name') }}">{{ __('adminstaff.levels.'.$lv.'.name') }}</button>
                            @endforeach
                        </div>
                    </div>
                    @foreach (AdminStaff::SECTIONS as $s)
                        <div class="st-row" data-st-sec="{{ $s }}">
                            <span class="flex min-w-0 items-center gap-2.5">
                                <i class="st-ic" aria-hidden="true">{!! $ic(in_array($s, array_keys(AdminNav::ICONS), true) ? $s : 'dashboard') !!}</i>
                                <span class="min-w-0"><b class="block truncate">{{ __('admin.nav.'.$s) }}</b><small class="block truncate">{{ __('adminstaff.sections.'.$s) }}</small></span>
                            </span>
                            <div class="st-seg" role="radiogroup" aria-label="{{ __('admin.nav.'.$s) }}">
                                @foreach (AdminStaff::LEVELS as $lv)
                                    <label><input type="radio" class="sr-only" name="st-{{ $s }}" value="{{ $lv }}" data-st-lv><span data-lv="{{ $lv }}">{{ __('adminstaff.levels.'.$lv.'.name') }}</span></label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
                <p class="ad-error mt-2" hidden data-st-err="permissions"></p>
                <p class="mt-3 text-xs font-bold text-slate-500" data-st-summary aria-live="polite"></p>
            @endif
        </section>
    </div>

    <div class="ed-savebar">
        <span class="text-xs font-bold text-slate-500" data-st-msg role="status" aria-live="polite"></span>
        <button type="button" class="btn-primary !min-h-10 !text-xs" data-st-save>{{ $isNew ? __('adminstaff.create') : __('adminstaff.save') }}</button>
    </div>
</div>
@endsection

@push('modals')
{{-- بعد از ساخت: اطلاعات ورود (فقط همین یک بار) --}}
<div id="st-done" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="st-done-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <span class="mb-3 grid size-12 place-items-center rounded-full bg-emerald-50 text-emerald-600">{!! $ic('check', 'size-6') !!}</span>
        <h2 id="st-done-t" class="text-lg font-extrabold text-brand-900" data-st-done-title>{{ __('adminstaff.done.title') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('adminstaff.done.lead') }}</p>
        <dl class="st-cred">
            <div><dt>{{ __('adminstaff.f.phone') }}</dt><dd dir="ltr" data-st-done-phone></dd></div>
            <div><dt>{{ __('adminstaff.pw.default') }}</dt><dd dir="ltr" class="font-mono" data-st-done-pw></dd></div>
            <div><dt>{{ __('adminstaff.done.url') }}</dt><dd dir="ltr">{{ url('/login') }}</dd></div>
        </dl>
        <p class="mt-2 text-xs leading-6 text-amber-700" data-st-done-must>{{ __('adminstaff.done.must') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-outline" data-st-done-copy>{!! $ic('copy') !!}{{ __('adminstaff.done.copy') }}</button>
            <a href="{{ url('/admin/staff') }}" class="btn-primary">{{ __('adminstaff.done.go') }}</a>
        </div>
    </div>
</div>

{{-- بازنشانی رمز --}}
<div id="st-reset" class="dialog" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="st-reset-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="st-reset-t" class="text-lg font-extrabold text-brand-900">{{ __('adminstaff.pw.reset') }}</h2>
        <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('adminstaff.pw.reset_lead') }}</p>
        <div class="st-pw mt-3">
            <input type="text" class="field font-mono" dir="ltr" readonly data-st-reset-pw aria-label="{{ __('adminstaff.pw.default') }}">
            <button type="button" class="st-pw__b" data-st-reset-copy aria-label="{{ __('adminstaff.pw.copy') }}">{!! $ic('copy') !!}</button>
            <button type="button" class="st-pw__b" data-st-reset-gen aria-label="{{ __('adminstaff.pw.generate') }}">{!! $ic('refresh') !!}</button>
        </div>
        <label class="mt-3 flex items-center gap-2 text-xs font-bold text-slate-500"><input type="checkbox" class="size-4 accent-brand-700" data-st-reset-sms checked> {{ __('adminstaff.pw.sms') }}</label>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary" data-st-reset-go>{{ __('adminstaff.pw.reset_go') }}</button>
        </div>
    </div>
</div>

{{-- حذف --}}
<div id="st-delete" class="dialog" data-dialog aria-hidden="true" role="alertdialog" aria-modal="true" aria-labelledby="st-del-t">
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-md">
        <div class="dialog-handle md:hidden"></div>
        <h2 id="st-del-t" class="text-lg font-extrabold text-brand-900">{{ __('adminstaff.del.title') }}</h2>
        <p class="mt-2 text-sm leading-7 text-slate-600">{{ __('adminstaff.del.lead') }}</p>
        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-primary !bg-rose-600 hover:!bg-rose-700" data-st-delete>{{ __('adminstaff.del.go') }}</button>
        </div>
    </div>
</div>
@endpush
