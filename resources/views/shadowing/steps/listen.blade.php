{{--
    Shadowing — مرحله‌ی ۱ (listen): جمله‌ی اول را ۳ بار گوش بده (بعد از ۳ بار مرحله تمام می‌شود).
    رفتار: resources/js/modules/shadowing-topic.js ([data-shc-step])
--}}
@php $first = $song['sentences'][0]; @endphp
<div class="space-y-4" data-shc-step data-mode="listen" data-song="{{ $song['id'] }}" data-total="1" data-first-only>
    @include('shadowing.steps._head', ['icon' => 'headphones', 'hint' => __('shadowing.step.listen_hint'), 'count' => 1])
    <div class="mx-auto max-w-2xl">
        @include('shadowing.steps._card', ['s' => $first, 'mode' => 'listen'])
    </div>
    <p class="mx-auto max-w-2xl text-center text-xs leading-6 text-slate-500">{{ __('shadowing.step.listen_note') }}</p>
</div>
