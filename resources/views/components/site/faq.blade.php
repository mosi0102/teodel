@props(['items' => [], 'open' => 0, 'schema' => false])

{{-- آکاردئون سوال و جواب (details بومی: بدون JS هم کار می‌کند؛ site.js فقط باز بودن یکی‌یکی را می‌گیرد) --}}
<div class="st-faq" data-st-faq>
    @foreach ($items as $i => $it)
        <details class="st-q" @if ($i === $open) open @endif>
            <summary>
                <span>{{ $it['q'] }}</span>
                <span class="st-q__ic" aria-hidden="true"><x-icon name="chevron-down" class="size-4" /></span>
            </summary>
            <div class="st-q__a"><p>{{ $it['a'] }}</p></div>
        </details>
    @endforeach
</div>

@if ($schema)
    @push('head')
        <script type="application/ld+json">{!! json_encode([
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => array_map(fn ($it) => ['@type' => 'Question', 'name' => $it['q'], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $it['a']]], array_values($items)),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
    @endpush
@endif
