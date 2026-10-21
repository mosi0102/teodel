{{-- دیالوگ مشترک «ساخت با AI» — در هر صفحه‌ای که دکمه‌ی [data-ai-open] دارد یک‌بار include شود. رفتار: resources/js/admin/ai.js --}}
@once
@push('modals')
<div id="ad-ai" class="dialog ad-ai-dlg" data-dialog aria-hidden="true" role="dialog" aria-modal="true" aria-labelledby="ad-ai-title"
    data-t='@json(__('admin.ai'), JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_TAG | JSON_UNESCAPED_UNICODE)'>
    <div class="dialog-backdrop" data-dialog-close></div>
    <div class="dialog-panel md:max-w-xl">
        <div class="dialog-handle md:hidden"></div>
        <div class="flex items-start gap-3.5">
            <span class="grid size-11 shrink-0 place-items-center rounded-full bg-violet-50 text-violet-600">{!! \App\Support\AdminNav::icon('sparkles') !!}</span>
            <div class="min-w-0">
                <h2 id="ad-ai-title" class="text-lg font-extrabold text-brand-900" data-ai-kind>{{ __('admin.ai.title') }}</h2>
                <p class="mt-1 text-sm leading-7 text-slate-500">{{ __('admin.ai.lead') }}</p>
            </div>
        </div>

        <div class="ad-ai-seeds" data-ai-seeds-box></div>
        <div class="ad-field mt-3">
            <label class="ad-label" for="ad-ai-note">{{ __('admin.ai.note') }}</label>
            <textarea id="ad-ai-note" class="field !min-h-16" data-ai-note placeholder="{{ __('admin.ai.note_ph') }}"></textarea>
        </div>
        <label class="mt-3 flex items-center gap-2 text-xs font-bold text-slate-500">
            <input type="checkbox" class="size-4 accent-brand-700" data-ai-overwrite> {{ __('admin.ai.overwrite') }}
        </label>

        <p class="sb-err mt-3" role="alert" hidden data-ai-err></p>
        <ul class="ad-ai-res" data-ai-res hidden aria-label="{{ __('admin.ai.result') }}"></ul>
        <div class="ad-ai-miss" data-ai-miss hidden></div>

        <div class="mt-5 flex flex-col-reverse gap-2.5 sm:flex-row sm:justify-end">
            <button type="button" class="btn-ghost" data-dialog-close>{{ __('admin.common.cancel') }}</button>
            <button type="button" class="btn-outline" data-ai-gen>
                <span class="ad-ai-spin !border-brand-200 !border-t-brand-700" data-ai-busy aria-hidden="true"></span>
                <span data-ai-gen-t>{{ __('admin.ai.generate') }}</span>
            </button>
            <button type="button" class="btn-primary" data-ai-apply hidden>{{ __('admin.ai.apply') }}</button>
        </div>
    </div>
</div>
@endpush
@endonce
