{{-- قالب مشترک یک لغت در ویرایشگرها (پادکست، Shadowing، کتاب). $pdf: گزینه‌ی «در PDF» | $where: «شماره‌ی جمله» --}}
@php
    $W = fn ($k) => __('admincontent.words.'.$k);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
@endphp
<article class="ed-item" data-ce-item data-ai-label="{{ __('admin.ai.chip') }}">
    <header class="ed-item__h">
        <b class="ed-item__n">#<span data-ce-n></span></b>
        <button type="button" class="ad-ai ad-ai--sm ms-auto" data-ce-ai-item="word_fill">{!! $ic('sparkles', 'size-3.5') !!}{{ $W('fill') }}</button>
        <button type="button" class="ed-del" data-ce-del aria-label="{{ __('admin.common.delete') }}">{!! $ic('x') !!}</button>
    </header>
    <div class="ad-form-grid">
        <x-ad.f k="phrase" :label="$W('phrase')" dir="ltr" req />
        <x-ad.f k="type" :label="$W('type')" dir="ltr" ph="noun / verb / idiom…" />
        <x-ad.f k="level" :label="$W('level')" type="select" :options="['' => '—', 'A1' => 'A1', 'A2' => 'A2', 'B1' => 'B1', 'B2' => 'B2', 'C1' => 'C1', 'C2' => 'C2']" />
        <x-ad.f k="translate" :label="$W('translate')" />
        <x-ad.f k="meaning" :label="$W('meaning')" dir="ltr" wide />
        @if (! empty($syn))
            <x-ad.f k="synonyms" :label="$W('synonyms')" dir="ltr" />
            <x-ad.f k="antonyms" :label="$W('antonyms')" dir="ltr" />
        @endif
        @if (! empty($where))
            <x-ad.f k="where" :label="$W('where')" type="number" :hint="$W('where_hint')" />
        @endif
        @if (! empty($pdf))
            <label class="ad-switch is-wide !p-2.5"><input type="checkbox" data-k="pdf"><span class="ad-switch__t"><b>{{ $W('pdf') }}</b></span></label>
        @endif
    </div>
</article>
