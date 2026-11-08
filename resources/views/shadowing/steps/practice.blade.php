{{-- Shadowing — مرحله‌ی ۳ (practice): همان کار مرحله‌ی ۱ و ۲ (۳ بار گوش دادن، سپس ضبط) برای بقیه‌ی جمله‌ها. --}}
@php $rest = array_slice($song['sentences'], 1); @endphp
<div class="space-y-4" data-shc-step data-mode="full" data-song="{{ $song['id'] }}" data-total="{{ count($rest) }}"
    data-check-url="{{ $urls['check'] ?? '' }}">
    @include('shadowing.steps._head', ['icon' => 'repeat', 'hint' => __('shadowing.step.practice_hint'), 'count' => count($rest)])
    <div class="grid gap-4 lg:grid-cols-2">
        @foreach ($rest as $s)
            @include('shadowing.steps._card', ['s' => $s, 'mode' => 'full'])
        @endforeach
    </div>
    <div class="lx-summary" data-shc-summary hidden role="status">
        <span class="grid size-12 shrink-0 place-items-center rounded-full bg-emerald-500 text-white"><x-icon name="check-circle" class="size-6" /></span>
        <p class="text-sm font-extrabold leading-7 text-emerald-900 md:text-base">{{ __('shadowing.step.practice_done') }}</p>
    </div>
</div>
