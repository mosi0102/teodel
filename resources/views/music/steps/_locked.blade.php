{{-- مرحله‌ی قفل برای کاربر رایگان (آهنگ‌های غیررایگان: فقط free_steps مرحله‌ی اول باز است) --}}
<div class="flex min-h-[18rem] flex-col items-center justify-center text-center md:min-h-[26rem]">
    <span class="mb-5 grid size-20 place-items-center rounded-full bg-amber-50 text-amber-500">
        <x-icon name="lock" class="size-9" />
    </span>

    <h3 class="text-lg font-extrabold text-brand-900">{{ __('lesson.locked.title') }}</h3>
    <p class="mt-2 max-w-md text-sm leading-7 text-slate-500">
        {{ __('lesson.locked.text', ['free' => num($song['free_steps']), 'total' => num($total)]) }}
    </p>

    <div class="mt-6 flex w-full max-w-sm flex-col gap-2.5 sm:flex-row">
        <a href="{{ url('/subscription') }}" class="btn-primary flex-1">
            <x-icon name="sparkles" class="size-4" />
            {{ __('ui.buy') }}
        </a>
        <a href="{{ route('music.step', ['id' => $song['id'], 'step' => $song['free_steps']]) }}" class="btn-ghost flex-1">
            {{ __('lesson.locked.back') }}
        </a>
    </div>
</div>
