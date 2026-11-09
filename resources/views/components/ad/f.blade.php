@props([
    'f' => null,          // مسیر فیلد در مدل (data-f) — برای فیلدهای اصلی
    'k' => null,          // کلید نسبی داخل آیتم لیست (data-k) — برای قالب‌های لیست
    'label' => '',
    'type' => 'text',     // text | textarea | select | number | time | url | year
    'options' => [],      // برای select: [value => label]
    'dir' => null,        // ltr برای متن انگلیسی
    'req' => false,
    'hint' => null,
    'wide' => false,
    'ai' => false,        // نشان «ساخته‌ی AI» (وقتی AI پر کرد)
    'ph' => null,
    'rows' => null,
])

{{-- یک فیلد فرم ویرایشگر محتوا (resources/js/admin/content.js آن را با data-f / data-k به مدل وصل می‌کند) --}}
@php
    $bind = $f !== null ? 'data-f="'.e($f).'"' : 'data-k="'.e($k).'"';
    $id = 'cf-'.substr(md5(($f ?? 'k:'.$k).$label), 0, 8);
    $dirAttr = $dir ? 'dir="'.$dir.'"' : '';
    $extra = match ($type) {
        'number', 'year' => 'inputmode="numeric" data-num dir="ltr"',
        'time' => 'inputmode="decimal" data-time dir="ltr" placeholder="00:00.00"',
        'url' => 'dir="ltr" inputmode="url" placeholder="https://"',
        default => $dirAttr,
    };
    $useId = $f !== null; // داخل قالب‌های تکرارشونده id یکتا نیست
@endphp
<div {{ $attributes->class(['ad-field', 'is-wide' => $wide]) }}>
    <label class="ad-label" @if ($useId) for="{{ $id }}" @endif>
        <span>{{ $label }} @if ($req)<em>*</em>@endif</span>
        @if ($ai)<span class="ad-chip">{!! \App\Support\AdminNav::icon('sparkles', 'size-3') !!}{{ __('admin.ai.chip') }}</span>@endif
    </label>
    @if ($type === 'textarea')
        <textarea @if ($useId) id="{{ $id }}" @endif class="field" {!! $bind !!} {!! $dirAttr !!} @if ($rows) rows="{{ $rows }}" @endif @if ($ph) placeholder="{{ $ph }}" @endif></textarea>
    @elseif ($type === 'select')
        <select @if ($useId) id="{{ $id }}" @endif class="field" {!! $bind !!}>
            @foreach ($options as $v => $l)<option value="{{ $v }}">{{ $l }}</option>@endforeach
        </select>
    @else
        <input @if ($useId) id="{{ $id }}" @endif class="field" {!! $bind !!} {!! $extra !!} @if ($ph) placeholder="{{ $ph }}" @endif autocomplete="off" @if ($type === 'year') maxlength="4" @endif>
    @endif
    @if ($hint)<p class="ad-hint">{{ $hint }}</p>@endif
    <p class="ad-error" hidden></p>
</div>
