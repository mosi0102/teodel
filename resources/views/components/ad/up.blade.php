@props([
    'f' => null,          // مسیر در مدل (data-ce-up)
    'k' => null,          // کلید نسبی داخل آیتم لیست (data-ce-up-k)
    'type' => 'file',     // image | audio | file
    'label' => '',
    'hint' => null,
    'accept' => null,
    'duration' => null,   // مسیر/کلیدی که مدت فایل صوتی (ثانیه) در آن نوشته شود
    'req' => false,
    'url' => true,        // فیلد «آدرس» هم نمایش داده شود
])

{{-- آپلود فایل (کاور، صوت، فایل کتاب). رفتار: resources/js/admin/content.js (بخش «آپلود») --}}
@php
    $icon = ['image' => 'image', 'audio' => 'mic', 'file' => 'file'][$type] ?? 'file';
    $accept = $accept ?? ['image' => 'image/jpeg,image/png,image/webp', 'audio' => 'audio/*', 'file' => '.pdf,.epub,application/pdf,application/epub+zip'][$type];
@endphp
<div {{ $attributes->class(['ed-up ce-up']) }} @if ($f) data-ce-up="{{ $f }}" @else data-ce-up-k="{{ $k }}" @endif data-type="{{ $type }}" @if ($duration) data-duration="{{ $duration }}" @endif>
    <div class="ed-up__prev {{ $type === 'image' ? 'ed-up__prev--img' : '' }}" data-ce-up-prev aria-hidden="true">{!! \App\Support\AdminNav::icon($icon, 'size-6') !!}</div>
    <div class="min-w-0 flex-1">
        <p class="text-sm font-extrabold text-brand-900">{{ $label }} @if ($req)<span class="text-rose-500">*</span>@endif</p>
        @if ($hint)<p class="ad-hint !mt-0.5">{{ $hint }}</p>@endif
        <div class="mt-2 flex flex-wrap items-center gap-2">
            <label class="btn-outline !min-h-9 cursor-pointer !px-3 !text-xs">
                <input type="file" class="sr-only" accept="{{ $accept }}" data-ce-file>{!! \App\Support\AdminNav::icon('upload', 'size-4') !!}{{ __('admincontent.up.upload') }}
            </label>
            <button type="button" class="btn-ghost !min-h-9 !px-3 !text-xs !text-rose-600" data-ce-up-clear hidden>{{ __('admincontent.up.remove') }}</button>
            {{ $slot }}
        </div>
        <div class="mt-2 min-w-0" data-ce-up-meta></div>
        @if ($url)
            <input class="field mt-2 !min-h-10 !text-xs" dir="ltr" placeholder="https://" data-ce-up-url aria-label="{{ $label }} — {{ __('admincontent.up.url') }}">
        @endif
        <p class="ad-error" hidden data-ce-up-err></p>
    </div>
</div>
