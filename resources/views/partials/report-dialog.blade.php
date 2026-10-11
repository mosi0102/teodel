{{-- گزارش خرابی: اسکرین‌شات + توضیحات کاربر. ارسال به سرور با بک‌اند (data-endpoint یا رویداد report:submit) --}}
<div id="report-dialog" class="dialog" data-dialog data-report-ignore aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="report-title">
    <div class="dialog-backdrop" data-dialog-close></div>

    <div class="dialog-panel">
        <div class="dialog-handle md:hidden"></div>

        {{-- مرحله‌ی فرم --}}
        <form id="report-form" novalidate data-endpoint="" data-error-text="{{ __('ui.report_failed') }}">
            <div id="report-step-form">
                <div class="mb-1 flex items-start justify-between gap-3">
                    <h2 id="report-title" class="text-lg font-extrabold text-brand-900">{{ __('ui.report') }}</h2>
                    <button type="button" data-dialog-close class="-me-2 -mt-1 grid size-10 place-items-center rounded-full text-slate-400 transition hover:bg-slate-100 hover:text-slate-600" aria-label="{{ __('ui.close') }}">
                        <x-icon name="x" class="size-5" />
                    </button>
                </div>
                <p class="mb-4 text-sm leading-6 text-slate-500">{{ __('ui.report_hint') }}</p>

                <div id="report-shot-wrap" class="mb-2 overflow-hidden rounded-2xl border border-slate-200 bg-slate-50 transition-opacity">
                    <img id="report-shot-img" alt="{{ __('ui.report_shot_alt') }}" hidden class="max-h-44 w-full object-cover object-top md:max-h-52">
                    <p id="report-shot-empty" class="flex min-h-24 items-center justify-center gap-2 px-4 text-center text-sm text-slate-400">
                        <x-icon name="camera" class="size-5" />
                        {{ __('ui.report_shot_none') }}
                    </p>
                </div>

                <label class="mb-4 flex min-h-10 cursor-pointer items-center gap-2.5 text-sm font-medium text-slate-600">
                    <input id="report-attach" type="checkbox" checked class="size-5 rounded accent-brand-700">
                    {{ __('ui.report_attach') }}
                </label>

                <div class="relative">
                    <textarea id="report-text" name="description" rows="4" maxlength="1000" required data-autofocus
                        class="field resize-none py-3 pb-8" placeholder="{{ __('ui.report_placeholder') }}"></textarea>
                    <span class="pointer-events-none absolute bottom-2.5 end-3.5 text-xs text-slate-400"><span id="report-count">0</span>/1000</span>
                </div>
                <p id="report-error" hidden class="mt-2 text-sm font-medium text-rose-600">{{ __('ui.report_required') }}</p>

                <div class="mt-5 flex gap-2.5">
                    <button type="button" data-dialog-close class="btn-ghost flex-1">{{ __('ui.cancel') }}</button>
                    <button type="submit" class="btn-primary flex-[2]">{{ __('ui.report_send') }}</button>
                </div>
            </div>

            {{-- مرحله‌ی موفقیت --}}
            <div id="report-step-done" hidden class="py-4 text-center">
                <span class="mx-auto mb-4 grid size-16 place-items-center rounded-full bg-emerald-50 text-emerald-500">
                    <x-icon name="check-circle" class="size-8" />
                </span>
                <h2 class="text-lg font-extrabold text-brand-900">{{ __('ui.report_done_title') }}</h2>
                <p class="mt-1.5 text-sm leading-6 text-slate-500">{{ __('ui.report_done_text') }}</p>
                <button type="button" id="report-done-btn" class="btn-primary mt-6 w-full">{{ __('ui.close') }}</button>
            </div>
        </form>
    </div>
</div>
