<!doctype html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $song['title'] }} — {{ __('podcast.pdf.title') }}</title>
    <style>
        body { font-family: system-ui, 'Yekan Bakh', 'Funnel Display', sans-serif; margin: 2rem auto; max-width: 46rem; padding: 0 1rem; color: #0f172a; line-height: 1.8; }
        h1 { font-size: 1.4rem; margin: 0 0 .25rem; } p.sub { color: #64748b; margin: 0 0 1.5rem; font-size: .9rem; }
        .w { break-inside: avoid; border-bottom: 1px solid #e2e8f0; padding: .8rem 0; }
        .w b { font-size: 1.1rem; } .w small { color: #64748b; margin-inline-start: .5rem; }
        .w p { margin: .2rem 0; } .tr { color: #1d4ed8; font-weight: 700; }
        button { margin-bottom: 1rem; padding: .5rem 1rem; border: 0; border-radius: .6rem; background: #1d4ed8; color: #fff; font: inherit; cursor: pointer; }
        @media print { button { display: none; } body { margin: 0; } }
    </style>
</head>
<body>
    <button type="button" onclick="window.print()">{{ __('podcast.pdf.print') }}</button>
    <h1><bdi>{{ $song['title'] }}</bdi></h1>
    <p class="sub"><bdi>{{ $song['artist'] }}</bdi> · {{ __('podcast.pdf.title') }}</p>
    @foreach (\App\Support\Words::forSong($song) as $w)
        <div class="w">
            <b dir="ltr">{{ $w['phrase'] }}</b><small>{{ $w['type'] }} @if ($w['level']) · {{ $w['level'] }} @endif @if ($w['ipa']['us']) · {{ $w['ipa']['us'] }} @endif</small>
            <p class="tr">{{ $w['translate'] }}</p>
            <p dir="ltr" lang="en">{{ $w['meaning'] }}</p>
            @if ($w['synonyms'])<p dir="ltr" lang="en"><small>≈ {{ implode(', ', $w['synonyms']) }}</small></p>@endif
        </div>
    @endforeach
</body>
</html>
