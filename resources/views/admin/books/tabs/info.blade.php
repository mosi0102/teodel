@php
    $L = fn ($k, $r = []) => __('admincontent.books.'.$k, $r);
    $F = fn ($k) => __('admincontent.f.'.$k);
    $ic = fn (string $n, string $c = 'size-4') => \App\Support\AdminNav::icon($n, $c);
    $cefr = ['A1' => 'A1', 'A2' => 'A2', 'B1' => 'B1', 'B2' => 'B2', 'C1' => 'C1', 'C2' => 'C2'];
    $types = collect(['graded', 'novel', 'short_stories', 'non_fiction'])->mapWithKeys(fn ($t) => [$t => __('books.type.'.$t)])->all();
@endphp
<div class="ad-grid">
    <section class="ad-card is-2" aria-labelledby="ce-bi-t">
        <div class="ad-card__h">
            <h2 class="ad-card__t" id="ce-bi-t">{{ $L('info_title') }}</h2>
            <button type="button" class="ad-ai" data-ce-ai="book_info" data-fields="genre,year,publisher,pages,summary_en">{!! $ic('sparkles') !!}{{ __('admincontent.ai.fill') }}</button>
        </div>
        <div class="ad-form-grid">
            <x-ad.f f="title" :label="$F('title')" dir="ltr" req />
            <x-ad.f f="author" :label="$L('author')" dir="ltr" req />
            <x-ad.f f="cefr" :label="$L('cefr')" type="select" :options="$cefr" req :hint="$L('cefr_hint')" />
            <x-ad.f f="level" :label="$F('level')" type="select" :options="['a' => __('admincontent.level.a'), 'b' => __('admincontent.level.b'), 'c' => __('admincontent.level.c')]" :hint="$L('level_hint')" />
            <x-ad.f f="type" :label="$L('type')" type="select" :options="$types" req />
            <x-ad.f f="genre" :label="$L('genre')" dir="ltr" ai />
            <x-ad.f f="year" :label="$L('year')" type="year" ai />
            <x-ad.f f="publisher" :label="$L('publisher')" dir="ltr" ai />
            <x-ad.f f="pages" :label="$L('pages')" type="number" ai />
            <x-ad.f f="isbn" :label="$L('isbn')" dir="ltr" ai />
            <label class="ad-switch"><input type="checkbox" data-f="is_free"><span class="ad-switch__t"><b>{{ $F('free') }}</b><small>{{ $L('free_hint') }}</small></span></label>
            <label class="ad-switch"><input type="checkbox" data-f="recommended"><span class="ad-switch__t"><b>{{ $F('recommended') }}</b><small>{{ $L('reco_hint') }}</small></span></label>
        </div>
    </section>

    <section class="ad-card" aria-labelledby="ce-bc-t">
        <h2 class="ad-card__t mb-3" id="ce-bc-t">{{ $F('cover') }}</h2>
        <x-ad.up f="cover" type="image" :label="$F('cover')" :hint="$L('cover_hint')" />
    </section>
</div>
