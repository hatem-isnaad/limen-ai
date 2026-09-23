<div class="sidebar-brand">
    <a href="{{ $hubUrl }}" style="color:inherit;text-decoration:none;display:flex;align-items:center;gap:0.65rem;">
        <span class="brand-icon" aria-hidden="true"><svg class="ico"><use href="#i-sparkles"/></svg></span>
        <div>
            <h1>Markdown guide</h1>
            <p>{{ $relativePath }}</p>
        </div>
    </a>
</div>

<nav class="sidebar-md" aria-label="Markdown guides">
    <div class="nav-group">
        <div class="nav-group-title">Browse</div>
        <a class="nav-link" href="{{ $hubUrl }}">← Interactive hub</a>
        @foreach($index as $item)
            <a class="nav-link @if($item['path'] === $relativePath) active @endif"
               href="{{ url('/demo/limen-ai/docs/'.rawurlencode($item['path'])) }}">{{ $item['label'] }}</a>
        @endforeach
    </div>
</nav>
