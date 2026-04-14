<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Sitemap Preview | {{ config('app.name', 'FOCUS AGENCY') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="focus-body">
        <div class="focus-noise"></div>
        <div class="focus-shell focus-main">
            <header class="focus-sitemap-header">
                <a href="{{ route('home') }}" class="focus-logo">
                    <span class="focus-logo-dot"></span>
                    <span>FOCUS AGENCY</span>
                </a>
                <h1>Sitemap Imported Local</h1>
                <p>{{ $pageCount }} pagini importate | {{ $generatedAt }}</p>
            </header>

            <section class="focus-sitemap-grid">
                @foreach ($pages as $page)
                    <a href="{{ $page['path'] }}" class="focus-related-card">
                        <h3>{{ $page['title'] }}</h3>
                        <p>{{ $page['excerpt'] }}</p>
                        <span>{{ $page['path'] }}</span>
                    </a>
                @endforeach
            </section>
        </div>
    </body>
</html>
