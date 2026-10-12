@props(['step', 'stepKey'])

{{-- جای موقت محتوای مرحله (هر مرحله که ساخته شد، این کامپوننت از فایلش حذف می‌شود) --}}
<div class="grid min-h-[16rem] place-items-center rounded-2xl border-2 border-dashed border-slate-200 p-6 text-center text-slate-400 md:min-h-[24rem]">
    <div>
        <p class="text-sm font-bold">{{ __('lesson.placeholder', ['n' => num($step)]) }}</p>
        <p class="mt-1 text-xs" dir="ltr">resources/views/music/steps/{{ $stepKey }}.blade.php</p>

    </div>
</div>
