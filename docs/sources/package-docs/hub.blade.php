@extends('demo.package-docs.layout')

@section('title', 'Documentation Hub')

@section('hero')
    <header class="hero">
        <div class="badge-row">
            <span class="badge badge-ai">Official package docs · v1.2.2</span>
            <span class="badge badge-live"><span class="live-dot" aria-hidden="true"></span> Learning paths</span>
            <span class="badge">Advanced tutorial included</span>
        </div>

        <h1>Limen AI documentation</h1>
        <p class="lead">
            Pick a path (FAQ widget → tools → workflows), copy snippets, follow the advanced support-system tutorial, then dive into the full reference.
        </p>

        <div class="stats">
            <div class="stat">
                <div class="stat-num">3</div>
                <div class="stat-label">Learning paths</div>
            </div>
            <div class="stat">
                <div class="stat-num">6</div>
                <div class="stat-label">Tutorial steps</div>
            </div>
            <div class="stat">
                <div class="stat-num">{{ count($index) }}</div>
                <div class="stat-label">Markdown guides</div>
            </div>
            <div class="stat">
                <div class="stat-num">20+</div>
                <div class="stat-label">Hub sections</div>
            </div>
        </div>

        <div class="cta-row">
            <a class="btn btn-primary" href="#learning-paths"><svg class="ico"><use href="#i-book-open"/></svg> Pick your path</a>
            <a class="btn btn-ghost" href="#advanced-tutorial"><svg class="ico"><use href="#i-package"/></svg> Advanced tutorial</a>
            <a class="btn btn-ghost" href="{{ $hostGuideUrl }}#concepts"><svg class="ico"><use href="#i-book"/></svg> Concepts (Arabic)</a>
        </div>
    </header>
@endsection

@section('content')
    @include('demo.package-docs.partials.learning-paths')
    @include('demo.package-docs.partials.readability-tips')
    @include('demo.package-docs.partials.quick-snippets')
    @include('demo.package-docs.partials.advanced-tutorial')
    @include('demo.package-docs.partials.troubleshooting')

    <div class="content-divider hub-divider"><span>Official package reference</span></div>

    <div id="hubContent">
        {!! $content !!}
    </div>
@endsection
