@props(['tone' => 'mid'])

{{-- نشان جهت لحن: rise ↗ | fall ↘ | mid → (رنگ از متغیر --pr والد، کلاس pr-seg--*) --}}
@php
    $d = [
        'rise' => 'M4.5 11.5 11.5 4.5M6 4.5h5.5V10',
        'fall' => 'M4.5 4.5l7 7M11.5 6v5.5H6',
        'mid' => 'M3 8h10M9.5 4.5 13 8l-3.5 3.5',
    ][$tone] ?? 'M3 8h10';
@endphp
<span {{ $attributes->merge(['class' => 'pr-tone']) }} aria-hidden="true">
    <svg viewBox="0 0 16 16"><path d="{{ $d }}" /></svg>
</span>
