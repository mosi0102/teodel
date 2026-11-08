{{-- Shadowing — مرحله‌ی ۲ (record): جمله‌ی اول را با صدای خودت بگو؛ هوش مصنوعی شباهت و تلفظ را بررسی می‌کند. --}}
@php $first = $song['sentences'][0]; @endphp
<div class="space-y-4" data-shc-step data-mode="record" data-song="{{ $song['id'] }}" data-total="1" data-first-only
    data-check-url="{{ $urls['check'] ?? '' }}">
    @include('shadowing.steps._head', ['icon' => 'mic', 'hint' => __('shadowing.step.record_hint'), 'count' => 1])
    <div class="mx-auto max-w-2xl">
        @include('shadowing.steps._card', ['s' => $first, 'mode' => 'record'])
    </div>
    <p class="mx-auto max-w-2xl text-center text-xs leading-6 text-slate-500">{{ __('shadowing.step.record_note') }}</p>
</div>
