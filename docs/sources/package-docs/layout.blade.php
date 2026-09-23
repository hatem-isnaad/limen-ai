<!DOCTYPE html>
<html lang="en" dir="ltr" class="docs-en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Limen AI — official Laravel AI agent framework documentation.">
    <meta name="theme-color" content="#050816">
    <title>@yield('title', 'Documentation') — Limen AI</title>
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'%3E%3Crect width='100' height='100' rx='22' fill='%230ea5e9'/%3E%3Ctext x='50' y='68' font-size='58' text-anchor='middle' fill='white' font-family='sans-serif'%3E%E2%9C%A6%3C/text%3E%3C/svg%3E">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@300;400;500;600;700;800;900&family=JetBrains+Mono:wght@400;500;700&display=swap" rel="stylesheet">
    @include('demo.package-docs.partials.theme-styles')
    @stack('head')
</head>
<body>
@include('demo.package-docs.partials.icons')

<div class="progress-bar" id="progressBar" aria-hidden="true"></div>
<div class="grid-bg" aria-hidden="true"></div>

<div class="shell">
    <nav class="topnav docs-topnav" aria-label="Documentation header">
        <div class="topnav-inner docs-topnav-inner">
            <a class="brand" href="{{ $hubUrl }}">
                <span class="brand-icon" aria-hidden="true"><svg class="ico"><use href="#i-sparkles"/></svg></span>
                <span>Limen AI Docs</span>
            </a>

            @include('demo.package-docs.partials.navbar')

            <div class="docs-nav-actions">
                <div class="search-wrap">
                    <span class="search-icon" aria-hidden="true"><svg class="ico"><use href="#i-search"/></svg></span>
                    <input class="search-input" id="docsSearch" type="search" placeholder="Search…" aria-label="Search documentation">
                    <span class="search-kbd" aria-hidden="true">/</span>
                </div>
            </div>
        </div>
    </nav>

    @yield('hero')
    @yield('content')

    @hasSection('footer')
        @yield('footer')
    @else
        <footer>
            <p>
                <strong>Limen AI</strong> — Laravel AI agent framework ·
                <a href="{{ $hubUrl }}">Documentation hub</a> ·
                <a href="{{ $hostGuideUrl }}">Host demo guide (Arabic)</a>
            </p>
        </footer>
    @endif
</div>

<button class="to-top" id="toTop" type="button" aria-label="Back to top"><svg class="ico"><use href="#i-arrow-up"/></svg></button>

@include('demo.package-docs.partials.scripts')
@stack('scripts')
</body>
</html>
