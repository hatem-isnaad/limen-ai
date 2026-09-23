<div class="sidebar-brand">
    <span class="brand-icon" aria-hidden="true"><svg class="ico"><use href="#i-sparkles"/></svg></span>
    <div>
        <h1>Package docs</h1>
        <p>Official · v1.2.2</p>
    </div>
</div>

<nav aria-label="On this page">
    <div class="nav-group">
        <div class="nav-group-title">Getting started</div>
        <a class="nav-link" href="{{ $hubUrl }}#black-box">Black-box setup</a>
        <a class="nav-link" href="{{ $hubUrl }}#overview">Overview</a>
        <a class="nav-link" href="{{ $hubUrl }}#installation">Installation</a>
        <a class="nav-link" href="{{ $hubUrl }}#quickstart">Quick start</a>
        <a class="nav-link" href="{{ $hubUrl }}#architecture">Architecture</a>
    </div>
    <div class="nav-group">
        <div class="nav-group-title">Core concepts</div>
        <a class="nav-link" href="{{ $hubUrl }}#agents">Agents</a>
        <a class="nav-link" href="{{ $hubUrl }}#tools">Tools</a>
        <a class="nav-link" href="{{ $hubUrl }}#workflows">Workflows</a>
        <a class="nav-link" href="{{ $hubUrl }}#memory-knowledge">Memory &amp; RAG</a>
        <a class="nav-link" href="{{ $hubUrl }}#providers">LLM providers</a>
    </div>
    <div class="nav-group">
        <div class="nav-group-title">Integration</div>
        <a class="nav-link" href="{{ $hubUrl }}#programmatic">Programmatic API</a>
        <a class="nav-link" href="{{ $hubUrl }}#http-api">HTTP API</a>
        <a class="nav-link" href="{{ $hubUrl }}#queue-broadcasting">Queue &amp; realtime</a>
        <a class="nav-link" href="{{ $hubUrl }}#chat-ui">Chat UI &amp; theming</a>
        <a class="nav-link" href="{{ $hubUrl }}#quality">Quality layer</a>
        <a class="nav-link" href="{{ $hubUrl }}#security">Security</a>
        <a class="nav-link" href="{{ $hubUrl }}#observability">Observability</a>
    </div>
    <div class="nav-group">
        <div class="nav-group-title">Guides</div>
        <a class="nav-link" href="{{ $hubUrl }}#use-cases">Use cases</a>
        <a class="nav-link" href="{{ $hubUrl }}#artisan">Artisan commands</a>
        <a class="nav-link" href="{{ $hubUrl }}#configuration">Configuration</a>
        <a class="nav-link" href="{{ $hubUrl }}#testing">Testing</a>
        <a class="nav-link" href="{{ $hubUrl }}#releases">Releases</a>
    </div>
    <div class="nav-group">
        <div class="nav-group-title">Markdown</div>
        @foreach($index as $item)
            <a class="nav-link sidebar-md" href="{{ url('/demo/limen-ai/docs/'.rawurlencode($item['path'])) }}">{{ $item['label'] }}</a>
        @endforeach
    </div>
</nav>
