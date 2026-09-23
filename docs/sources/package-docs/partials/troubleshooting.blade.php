<section id="troubleshooting">
    <div class="section-head">
        <div>
            <h2><span class="h2-icon"><svg class="ico"><use href="#i-alert"/></svg></span> Troubleshooting</h2>
            <p class="section-sub">Common failures when building agents — symptom, cause, fix.</p>
        </div>
    </div>

    <div class="accordion">
        <div class="acc-item open">
            <button class="acc-trigger" type="button">
                <span>Agent ignores my tools</span>
                <span class="acc-icon"><svg class="ico"><use href="#i-chevron"/></svg></span>
            </button>
            <div class="acc-body">
                <p><strong>Cause:</strong> Tool not on agent's <code>tools</code> array, vague <code>description</code>, or skill/agent intersection empty.</p>
                <p><strong>Fix:</strong> Add tool key to agent config. Write description like “Call when user mentions order number.” Run <code>php artisan limen-ai:tools</code> to confirm registration.</p>
            </div>
        </div>

        <div class="acc-item">
            <button class="acc-trigger" type="button">
                <span>Wrong tool picked every time</span>
                <span class="acc-icon"><svg class="ico"><use href="#i-chevron"/></svg></span>
            </button>
            <div class="acc-body">
                <p><strong>Cause:</strong> Too many tools on one agent (especially local 7B–8B models).</p>
                <p><strong>Fix:</strong> Split agents per role. Keep public widget at 0–2 tools. See <a href="{{ url('/demo/limen-ai/docs/'.rawurlencode('scaling-agents-and-tools.md')) }}">scaling guide</a>.</p>
            </div>
        </div>

        <div class="acc-item">
            <button class="acc-trigger" type="button">
                <span>Guest blocked / unauthorized tool</span>
                <span class="acc-icon"><svg class="ico"><use href="#i-chevron"/></svg></span>
            </button>
            <div class="acc-body">
                <p><strong>Cause:</strong> <code>authorize()</code> returns false, or agent requires auth while widget is guest.</p>
                <p><strong>Fix:</strong> Public agent: <code>guest_allowed: true</code> and no privileged tools. Staff tools: separate agent on authenticated routes only.</p>
            </div>
        </div>

        <div class="acc-item">
            <button class="acc-trigger" type="button">
                <span>Ollama timeout / empty replies</span>
                <span class="acc-icon"><svg class="ico"><use href="#i-chevron"/></svg></span>
            </button>
            <div class="acc-body">
                <p><strong>Cause:</strong> Model cold start, low timeout, too many tools in context.</p>
                <p><strong>Fix:</strong> <code>OPENAI_TIMEOUT=120</code>, <code>LIMEN_AI_QUEUE_AGENT_RUNS=false</code> for local dev, reduce tools, run <code>ollama serve</code>.</p>
            </div>
        </div>

        <div class="acc-item">
            <button class="acc-trigger" type="button">
                <span>Knowledge not used in answers</span>
                <span class="acc-icon"><svg class="ico"><use href="#i-chevron"/></svg></span>
            </button>
            <div class="acc-body">
                <p><strong>Cause:</strong> Collection not attached to agent <code>knowledge</code> array, or chunks too long/vague.</p>
                <p><strong>Fix:</strong> Attach collection key to agent. Use 2–4 sentence factual chunks. Test with <code>limen-ai:agent:test</code>.</p>
            </div>
        </div>

        <div class="acc-item">
            <button class="acc-trigger" type="button">
                <span>Skill tools never run</span>
                <span class="acc-icon"><svg class="ico"><use href="#i-chevron"/></svg></span>
            </button>
            <div class="acc-body">
                <p><strong>Cause:</strong> Tool in skill but not in agent <code>tools</code> — intersection must include both.</p>
                <p><strong>Fix:</strong> Add tool key to agent AND skill, or drop skill tools and list on agent only. See <a href="{{ $hostGuideUrl }}#concepts">concepts (AR)</a>.</p>
            </div>
        </div>
    </div>
</section>

<section id="markdown-guides">
    <div class="section-head">
        <div>
            <h2><span class="h2-icon"><svg class="ico"><use href="#i-folder"/></svg></span> Markdown guides</h2>
            <p class="section-sub">Deep dives — click any guide for the full document.</p>
        </div>
    </div>

    <div class="grid grid-3 md-guide-grid">
        @foreach($index as $item)
            <a class="card md-guide-card" href="{{ url('/demo/limen-ai/docs/'.rawurlencode($item['path'])) }}">
                <h4>{{ $item['label'] }}</h4>
                <p><code>{{ $item['path'] }}</code></p>
            </a>
        @endforeach
    </div>
</section>
