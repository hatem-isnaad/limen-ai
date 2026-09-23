<section id="learning-paths">
    <div class="section-head">
        <div>
            <h2><span class="h2-icon"><svg class="ico"><use href="#i-book-open"/></svg></span> Pick your learning path</h2>
            <p class="section-sub">Three curated tracks — start where you are, finish with a production-ready agent.</p>
        </div>
    </div>

    <div class="grid grid-3 path-grid">
        <article class="card path-card">
            <span class="pill pill-live">Path A · ~30 min</span>
            <h3>Widget only (FAQ bot)</h3>
            <p>No tools, no auth wiring. Knowledge + widget for public support.</p>
            <ol class="path-steps">
                <li><a href="#snippets">Copy install + .env snippets</a></li>
                <li><a href="{{ $hubUrl }}#black-box">Black-box checklist</a></li>
                <li><a href="{{ url('/demo/limen-ai/docs/'.rawurlencode('knowledge-base-setup.md')) }}">Knowledge base setup</a></li>
                <li><a href="{{ $hubUrl }}#chat-ui">Embed the widget</a></li>
                <li><a href="{{ $hubUrl }}#testing">Verify with CLI</a></li>
            </ol>
            <a class="btn btn-ghost path-go" href="#snippets">Start Path A →</a>
        </article>

        <article class="card path-card path-featured">
            <span class="pill pill-live">Path B · ~2 hours</span>
            <h3>Tools + authorization</h3>
            <p>Agent calls Laravel — lookups, tickets, safe mutations with <code>authorize()</code>.</p>
            <ol class="path-steps">
                <li><a href="{{ $hubUrl }}#installation">Install &amp; doctor</a></li>
                <li><a href="{{ $hubUrl }}#quickstart">Make your first tool</a></li>
                <li><a href="{{ $hubUrl }}#agents">Register agents in config</a></li>
                <li><a href="{{ url('/demo/limen-ai/docs/'.rawurlencode('scaling-agents-and-tools.md')) }}">Split public vs staff agents</a></li>
                <li><a href="{{ $hubUrl }}#security">Security checklist</a></li>
            </ol>
            <a class="btn btn-primary path-go" href="{{ $hubUrl }}#quickstart">Start Path B →</a>
        </article>

        <article class="card path-card">
            <span class="pill pill-warn">Path C · ~1 day</span>
            <h3>Workflows + staff ops</h3>
            <p>Multi-step flows, approvals, queue, and observability for serious ops.</p>
            <ol class="path-steps">
                <li><a href="#advanced-tutorial">End-to-end support system</a></li>
                <li><a href="{{ $hubUrl }}#workflows">Workflow config</a></li>
                <li><a href="{{ $hubUrl }}#queue-broadcasting">Queue &amp; realtime</a></li>
                <li><a href="{{ $hubUrl }}#observability">Logs &amp; metrics</a></li>
                <li><a href="{{ $hostGuideUrl }}#registered">Live 3PL demo (AR)</a></li>
            </ol>
            <a class="btn btn-ghost path-go" href="#advanced-tutorial">Start Path C →</a>
        </article>
    </div>

    <div class="alert alert-info path-note">
        <svg class="ico"><use href="#i-info"/></svg>
        <div>
            <strong>Arabic host guide — concepts with plain analogies</strong>
            Agent, Tool, Skill, Workflow explained simply.
            <a href="{{ $hostGuideUrl }}#concepts">Open concepts (AR)</a>
            · English paths above ·
            <a href="{{ $hubUrl }}#markdown-guides">markdown guides</a>
        </div>
    </div>
</section>
