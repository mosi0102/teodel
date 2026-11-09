@props(['lines'])

{{--
    لحن و ریتم جمله‌ها (shadowing پادکست/شدوئینگ).
    هر خط به «گروه‌های معنایی» (thought group) تقسیم شده؛ هر گروه یک کپسول کم‌رنگ است و در انتهایش
    یک نشان کوچک با فلش جهت لحن می‌آید: بالارونده (سبز ↗) | پایین‌رونده (قرمز ↘) | یکنواخت (کهربایی →).
    کلمه‌ی تأکیددار پررنگ است و یک نقطه‌ی تأکید بالای آن دارد.
    ورودی: خروجی Shadowing::prosody — [[ ['tone' => rise|fall|mid, 'words' => [['t' => ..., 's' => bool]]] ]]
--}}

<span class="pr" dir="ltr" lang="en">
    @foreach ($lines as $segs)
        <span class="pr-line">
            @foreach ($segs as $seg)
                <span class="pr-seg pr-seg--{{ $seg['tone'] }}">
                    <span class="pr-words">@foreach ($seg['words'] as $w)<span class="pr-w @if ($w['s']) is-stress @endif">{{ $w['t'] }}</span>@if (! $loop->last) @endif @endforeach</span>
                    <x-lesson.tone-icon :tone="$seg['tone']" />
                    <span class="sr-only">({{ __('lesson.shadowing.tone.' . $seg['tone']) }})</span>
                </span>
            @endforeach
        </span>
    @endforeach
</span>
