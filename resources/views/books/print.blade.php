<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}" dir="{{ app()->getLocale() === 'fa' ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>{{ $book['title'] }} — {{ __('books.print.title') }}</title>
    {{-- نسخه‌ی قابل‌چاپ کتاب (دمو برای «دانلود فایل»؛ بک‌اند فایل واقعی را می‌دهد — BookController::download) --}}
    <style>
        body { margin: 0; background: #f8fafc; color: #0f172a; font: 16px/1.6 Georgia, 'Times New Roman', serif; }
        .bar { position: sticky; top: 0; display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; justify-content: space-between; padding: .75rem 1rem; background: #fff; border-bottom: 1px solid #e2e8f0; font: 14px/1.6 system-ui, sans-serif; }
        .bar button { border: 0; border-radius: 999px; background: #3730a3; color: #fff; padding: .55rem 1.1rem; font: 700 14px system-ui, sans-serif; cursor: pointer; }
        .note { margin: 0; color: #64748b; }
        .warn { color: #b45309; }
        main { max-width: 42rem; margin: 2rem auto; padding: 2.5rem; background: #fff; border-radius: 1rem; direction: ltr; text-align: left; }
        h1 { margin: 0; font-size: 2rem; } .by { color: #64748b; margin: .25rem 0 2rem; }
        h2 { margin: 2.5rem 0 1rem; font-size: 1.35rem; } p { margin: 0 0 1rem; }
        @media print { .bar { display: none; } body { background: #fff; } main { margin: 0; padding: 0; } h2 { break-before: page; } }
    </style>
</head>
<body>
    <div class="bar">
        <div>
            <p class="note">{{ __('books.print.hint') }}</p>
            @if ($partial)<p class="note warn">{{ __('books.print.partial') }}</p>@endif
        </div>
        <button type="button" onclick="window.print()">{{ __('books.print.print') }}</button>
    </div>
    <main lang="en">
        <h1>{{ $book['title'] }}</h1>
        <p class="by">{{ $book['author'] }} · {{ $book['cefr'] }}</p>
        @foreach ($chapters as $c)
            <h2>{{ $c['n'] }}. {{ $c['title'] }}</h2>
            @foreach ($c['paragraphs'] as $p)<p>{{ $p }}</p>@endforeach
        @endforeach
    </main>
</body>
</html>
