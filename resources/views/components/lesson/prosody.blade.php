@props(['lines'])

{{--
    لحن و ریتم جمله‌ها (shadowing پادکست): هر خط به بخش‌های فکری (thought group) تقسیم شده؛ زیر هر بخش منحنی لحن می‌آید:
    بالارونده (سبز) | پایین‌رونده (قرمز) | متوسط (کهربایی). کلمه‌ی تأکیددار پررنگ و زیرخط‌دار است.
    ورودی: خروجی Shadowing::prosody — [[ ['tone' => rise|fall|mid, 'words' => [['t' => ..., 's' => bool]]] ]]
--}}
@php
    $paths = ['rise' => 'M2 13 C38 13 62 3 98 3', 'fall' => 'M2 3 C38 3 62 13 98 13', 'mid' => 'M2 8 L98 8'];
@endphp

<span class="pr" dir="ltr" lang="en">
    @foreach ($lines as $segs)
        <span class="pr-line">
            @foreach ($segs as $seg)
                <span class="pr-seg pr-seg--{{ $seg['tone'] }}">
                    <span class="pr-words">@foreach ($seg['words'] as $w)<span class="pr-w @if ($w['s']) is-stress @endif">{{ $w['t'] }}</span> @endforeach</span>
                    <svg class="pr-curve" viewBox="0 0 100 16" preserveAspectRatio="none" aria-hidden="true"><path d="{{ $paths[$seg['tone']] }}" vector-effect="non-scaling-stroke" /></svg>
                    <span class="sr-only">({{ __('lesson.shadowing.tone.' . $seg['tone']) }})</span>
                </span>
            @endforeach
        </span>
    @endforeach
</span>
