{{-- سربرگ مشترک مرحله‌های Shadowing: نوار «چطور کار می‌کند» + شمارنده --}}
<div class="flex flex-wrap items-center justify-between gap-x-4 gap-y-3 rounded-2xl bg-brand-50/70 px-4 py-3 md:px-5">
    <p class="flex items-center gap-2 text-sm font-bold text-brand-900">
        <x-icon :name="$icon" class="size-5 shrink-0 text-brand-700" />
        {{ $hint }}
    </p>
    <span class="lyrics-chip pointer-events-none" aria-live="polite">
        <x-icon name="check-circle" class="size-4 text-emerald-600" />
        <b class="tabular-nums" data-shc-done>{{ num(0) }}</b>/<span class="tabular-nums">{{ num($count) }}</span>
    </span>
</div>
