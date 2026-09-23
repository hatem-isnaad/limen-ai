@extends('demo.package-docs.layout')

@section('title', $title)

@section('hero')
    <header class="hero hero-compact">
        <div class="badge-row">
            <span class="badge badge-ai">Markdown guide</span>
            @if(! empty($readingMinutes))
                <span class="badge">~{{ $readingMinutes }} min read</span>
            @endif
        </div>
        <h1>{{ $title }}</h1>
        <p class="lead"><code>{{ $relativePath }}</code></p>
        <div class="cta-row">
            <a class="btn btn-primary" href="{{ $hubUrl }}#learning-paths"><svg class="ico"><use href="#i-book-open"/></svg> Learning paths</a>
            <a class="btn btn-ghost" href="{{ $hubUrl }}"><svg class="ico"><use href="#i-book"/></svg> Documentation hub</a>
            <a class="btn btn-ghost" href="{{ $hostGuideUrl }}"><svg class="ico"><use href="#i-external"/></svg> Host demo (Arabic)</a>
        </div>
    </header>
@endsection

@section('content')
    @include('demo.package-docs.partials.learning-paths-banner')
    <article class="prose" id="proseArticle">{!! $html !!}</article>
@endsection
