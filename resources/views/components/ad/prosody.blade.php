@props([
    'f' => null,     // مسیر در مدل
    'k' => null,     // کلید نسبی داخل آیتم
    'label' => null,
    'auto' => true,  // دکمه‌ی «پیشنهاد خودکار»
])

{{--
    ویرایشگر لحن (prosody) با پیش‌نمایش زنده — همان نمایش سمت کاربر (components/lesson/prosody).
    نشانه‌ها: ^ بالا | _ پایین | - یکنواخت (آخر هر گروه) | *کلمه* تأکید | «|» جداکننده‌ی گروه معنایی | هر خط = یک جمله
--}}
@php $bind = $f !== null ? 'data-f="'.e($f).'"' : 'data-k="'.e($k).'"'; @endphp
<div {{ $attributes->class(['ad-field ce-pr']) }} data-ce-prosody-wrap>
    <label class="ad-label"><span>{{ $label ?? __('admincontent.prosody.label') }}</span></label>
    <div class="ce-pr__bar" role="toolbar" aria-label="{{ __('admincontent.prosody.label') }}">
        <button type="button" class="ce-pr__b pr-seg--rise" data-ce-pmark="^" title="{{ __('admincontent.prosody.rise') }}"><x-lesson.tone-icon tone="rise" />{{ __('admincontent.prosody.rise') }}</button>
        <button type="button" class="ce-pr__b pr-seg--fall" data-ce-pmark="_" title="{{ __('admincontent.prosody.fall') }}"><x-lesson.tone-icon tone="fall" />{{ __('admincontent.prosody.fall') }}</button>
        <button type="button" class="ce-pr__b pr-seg--mid" data-ce-pmark="-" title="{{ __('admincontent.prosody.mid') }}"><x-lesson.tone-icon tone="mid" />{{ __('admincontent.prosody.mid') }}</button>
        <button type="button" class="ce-pr__b" data-ce-pmark="*" title="{{ __('admincontent.prosody.stress') }}"><b dir="ltr">*A*</b>{{ __('admincontent.prosody.stress') }}</button>
        <button type="button" class="ce-pr__b" data-ce-pmark=" | " title="{{ __('admincontent.prosody.sep') }}"><b>|</b>{{ __('admincontent.prosody.sep') }}</button>
        @if ($auto)
            <button type="button" class="ce-pr__b ce-pr__b--auto" data-ce-pmark="auto">{!! \App\Support\AdminNav::icon('wand', 'size-3.5') !!}{{ __('admincontent.prosody.auto') }}</button>
        @endif
    </div>
    <textarea class="field !min-h-20 font-mono !text-xs" dir="ltr" rows="2" spellcheck="false" {!! $bind !!} data-ce-prosody
        placeholder="I *usually* wake up at *six*-| even on *weekends*_"></textarea>
    <div class="ce-pr__out" data-ce-prosody-out aria-live="polite"></div>
    <p class="ad-hint">{{ __('admincontent.prosody.hint') }}</p>
</div>
