@php
    $learnLinks = [
        ['label' => 'Learning paths', 'href' => $hubUrl.'#learning-paths'],
        ['label' => 'Copy snippets', 'href' => $hubUrl.'#snippets'],
        ['label' => 'Advanced tutorial', 'href' => $hubUrl.'#advanced-tutorial'],
        ['label' => 'Troubleshooting', 'href' => $hubUrl.'#troubleshooting'],
        ['label' => 'Black-box setup', 'href' => $hubUrl.'#black-box'],
    ];

    $referenceGroups = [
        'Getting started' => [
            ['label' => 'Installation', 'href' => $hubUrl.'#installation'],
            ['label' => 'Quick start', 'href' => $hubUrl.'#quickstart'],
            ['label' => 'Architecture', 'href' => $hubUrl.'#architecture'],
        ],
        'Core concepts' => [
            ['label' => 'Agents', 'href' => $hubUrl.'#agents'],
            ['label' => 'Tools', 'href' => $hubUrl.'#tools'],
            ['label' => 'Workflows', 'href' => $hubUrl.'#workflows'],
            ['label' => 'Memory & RAG', 'href' => $hubUrl.'#memory-knowledge'],
            ['label' => 'Providers', 'href' => $hubUrl.'#providers'],
        ],
        'Integration' => [
            ['label' => 'Programmatic API', 'href' => $hubUrl.'#programmatic'],
            ['label' => 'HTTP API', 'href' => $hubUrl.'#http-api'],
            ['label' => 'Queue & realtime', 'href' => $hubUrl.'#queue-broadcasting'],
            ['label' => 'Chat UI', 'href' => $hubUrl.'#chat-ui'],
            ['label' => 'Quality layer', 'href' => $hubUrl.'#quality'],
            ['label' => 'Security', 'href' => $hubUrl.'#security'],
            ['label' => 'Observability', 'href' => $hubUrl.'#observability'],
        ],
        'Guides' => [
            ['label' => 'Use cases', 'href' => $hubUrl.'#use-cases'],
            ['label' => 'Artisan CLI', 'href' => $hubUrl.'#artisan'],
            ['label' => 'Configuration', 'href' => $hubUrl.'#configuration'],
            ['label' => 'Testing', 'href' => $hubUrl.'#testing'],
            ['label' => 'Markdown guides', 'href' => $hubUrl.'#markdown-guides'],
        ],
    ];
@endphp

<div class="docs-nav" id="docsNav">
    <div class="docs-nav-main">
        <a class="docs-nav-link docs-nav-link-primary" href="{{ $hubUrl }}#learning-paths">Learning paths</a>
        <a class="docs-nav-link" href="{{ $hubUrl }}#snippets">Snippets</a>
        <a class="docs-nav-link" href="{{ $hubUrl }}#advanced-tutorial">Advanced</a>

        <div class="nav-drop" data-nav-drop>
            <button class="nav-drop-trigger" type="button" aria-expanded="false" aria-haspopup="true">
                Learn
                <svg class="ico nav-chevron"><use href="#i-chevron"/></svg>
            </button>
            <div class="nav-drop-panel nav-drop-panel-sm" hidden>
                @foreach($learnLinks as $link)
                    <a class="nav-drop-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                @endforeach
            </div>
        </div>

        <div class="nav-drop" data-nav-drop>
            <button class="nav-drop-trigger" type="button" aria-expanded="false" aria-haspopup="true">
                Reference
                <svg class="ico nav-chevron"><use href="#i-chevron"/></svg>
            </button>
            <div class="nav-drop-panel nav-drop-panel-wide" hidden>
                <div class="nav-drop-grid">
                    @foreach($referenceGroups as $title => $links)
                        <div class="nav-drop-col">
                            <div class="nav-drop-title">{{ $title }}</div>
                            @foreach($links as $link)
                                <a class="nav-drop-link" href="{{ $link['href'] }}">{{ $link['label'] }}</a>
                            @endforeach
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <a class="docs-nav-link docs-nav-host" href="{{ $hostGuideUrl }}">
            <svg class="ico"><use href="#i-book"/></svg>
            Host (AR)
        </a>
    </div>

    <button class="docs-nav-toggle" type="button" id="docsNavToggle" aria-expanded="false" aria-controls="docsNavPanel">
        <svg class="ico"><use href="#i-menu"/></svg>
        <span>Sections</span>
    </button>
</div>

<div class="docs-nav-backdrop" id="docsNavBackdrop" hidden></div>

<aside class="docs-nav-panel" id="docsNavPanel" aria-label="Documentation sections" hidden>
    <div class="docs-nav-panel-head">
        <strong>Jump to section</strong>
        <button class="docs-nav-close" type="button" id="docsNavClose" aria-label="Close menu">
            <svg class="ico"><use href="#i-x"/></svg>
        </button>
    </div>

    <div class="docs-nav-panel-group">
        <div class="docs-nav-panel-label">Start here</div>
        <a href="{{ $hubUrl }}#learning-paths">Learning paths</a>
        <a href="{{ $hubUrl }}#snippets">Copy snippets</a>
        <a href="{{ $hubUrl }}#advanced-tutorial">Advanced tutorial</a>
        <a href="{{ $hubUrl }}#troubleshooting">Troubleshooting</a>
    </div>

    @foreach($referenceGroups as $title => $links)
        <div class="docs-nav-panel-group">
            <div class="docs-nav-panel-label">{{ $title }}</div>
            @foreach($links as $link)
                <a href="{{ $link['href'] }}">{{ $link['label'] }}</a>
            @endforeach
        </div>
    @endforeach

    <div class="docs-nav-panel-group">
        <div class="docs-nav-panel-label">Host demo</div>
        <a href="{{ $hostGuideUrl }}">Arabic integration guide</a>
        <a href="{{ $hostGuideUrl }}#concepts">Concepts (AR)</a>
    </div>
</aside>
