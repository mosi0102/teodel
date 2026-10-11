{{--
    ابزارهای مشترک هر صفحه: «حالت تمرکز» و «گزارش خرابی».
    در هدر هر صفحه (و مراحل ۱ تا ۸) همین کامپوننت گذاشته می‌شود.
    منطق در resources/js/modules/focus-mode.js و report.js است.
--}}
<div {{ $attributes->class('flex items-center gap-2') }}>
    <button type="button" class="chip-btn" data-focus-toggle aria-pressed="false"
        data-label-on="{{ __('ui.focus') }}" data-label-off="{{ __('ui.focus_exit') }}">
        <x-icon name="glasses" class="size-5" />
        <span data-focus-label>{{ __('ui.focus') }}</span>
    </button>

    <button type="button" class="chip-btn" data-report-open aria-haspopup="dialog">
        <x-icon name="alert-circle" class="size-5" />
        <span>{{ __('ui.report') }}</span>
    </button>
</div>
