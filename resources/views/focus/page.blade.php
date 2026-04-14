<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $page['title'] }} | {{ config('app.name', 'FOCUS AGENCY') }}</title>
        <meta name="description" content="{{ $page['excerpt'] }}">
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;700;800&family=Sora:wght@400;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="focus-body">
        <div class="focus-noise"></div>
        <div class="focus-glow focus-glow-left"></div>
        <div class="focus-glow focus-glow-right"></div>

        <header class="focus-shell focus-header">
            <a href="{{ route('home') }}" class="focus-logo">
                <span class="focus-logo-dot"></span>
                <span>FOCUS AGENCY</span>
            </a>

            <nav class="focus-nav">
                <a href="{{ route('home') }}">Home</a>
                <a href="{{ route('sitemap.preview') }}">Sitemap</a>
                @foreach ($primaryNavigation as $navPage)
                    <a href="{{ $navPage['path'] }}">{{ $navPage['title'] }}</a>
                @endforeach
                @if (Route::has('login'))
                    <a href="{{ route('login') }}">Login</a>
                @endif
            </nav>
        </header>

        <main class="focus-shell focus-main">
            <section class="focus-hero">
                <p class="focus-kicker">Website Facelift</p>
                <h1>{{ $page['title'] }}</h1>
                <p class="focus-excerpt">{{ $page['excerpt'] }}</p>
                <div class="focus-meta">
                    <span>{{ $page['path'] }}</span>
                    <a href="{{ $page['source_url'] }}" target="_blank" rel="noreferrer">Vezi pagina originală</a>
                </div>
            </section>

            @if (!empty($page['image_placeholders']))
                <section class="focus-image-row" aria-label="Image placeholders">
                    @foreach (array_slice($page['image_placeholders'], 0, 4) as $imageLabel)
                        <article class="focus-image-card">
                            <p>AI Image Placeholder</p>
                            <small>{{ $imageLabel }}</small>
                        </article>
                    @endforeach
                </section>
            @endif

            <section class="focus-structured-layout">
                <aside class="focus-toc">
                    <h2>Pe această pagină</h2>
                    <ul>
                        @foreach ($page['sections'] as $index => $section)
                            <li>
                                <a href="#section-{{ $index }}">
                                    {{ $section['heading'] ?: 'Secțiune '.($index + 1) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                </aside>

                <article class="focus-content-wrap">
                    <div class="focus-content">
                        @foreach ($page['sections'] as $index => $section)
                            <section id="section-{{ $index }}" class="focus-section">
                                <h2>{{ $section['heading'] ?: 'Secțiune '.($index + 1) }}</h2>
                                @if (!empty($section['body']))
                                    @foreach ($section['body'] as $line)
                                        @if (str_starts_with($line, '• '))
                                            <p class="focus-list-item">{{ $line }}</p>
                                        @else
                                            <p>{{ $line }}</p>
                                        @endif
                                    @endforeach
                                @else
                                    <p>Conținut în curs de structurare pentru această secțiune.</p>
                                @endif
                            </section>
                        @endforeach
                    </div>
                </article>
            </section>

            @if ($relatedPages->isNotEmpty())
                <section class="focus-related">
                    <div class="focus-related-head">
                        <h2>Pagini similare din sitemap</h2>
                        <p>Conținutul este păstrat complet, iar structura este uniformă pentru un redesign coerent.</p>
                    </div>
                    <div class="focus-related-grid">
                        @foreach ($relatedPages as $related)
                            <a href="{{ $related['path'] }}" class="focus-related-card">
                                <h3>{{ $related['title'] }}</h3>
                                <p>{{ $related['excerpt'] }}</p>
                                <span>{{ $related['path'] }}</span>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif
        </main>

        <footer class="focus-shell focus-footer">
            <p>Imported pages: {{ $pageCount }} | Generated: {{ $generatedAt }}</p>
        </footer>
    </body>
</html>
