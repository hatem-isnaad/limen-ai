<section id="snippets">
    <div class="section-head">
        <div>
            <h2><span class="h2-icon"><svg class="ico"><use href="#i-terminal"/></svg></span> Copy-ready snippets</h2>
            <p class="section-sub">Common setup blocks — pick a tab, click Copy, paste into your app.</p>
        </div>
        <a class="btn btn-ghost" href="{{ url('/demo/limen-ai/docs/'.rawurlencode('black-box-host-guide.md')) }}">Full black-box guide →</a>
    </div>

    <div class="tabs" role="tablist">
        <button class="tab active" type="button" data-tab="install">Install</button>
        <button class="tab" type="button" data-tab="env">.env</button>
        <button class="tab" type="button" data-tab="agent">Agent</button>
        <button class="tab" type="button" data-tab="tool">Tool</button>
        <button class="tab" type="button" data-tab="widget">Widget</button>
        <button class="tab" type="button" data-tab="ollama">Ollama</button>
        <button class="tab" type="button" data-tab="test">Verify</button>
    </div>

    <div class="tab-panel active" id="panel-install">
        <p class="panel-note">Minimum path from zero to a working install.</p>
        <pre data-lang="bash"><code>composer require limen-ai/limen-ai
php artisan limen-ai:install --migrate
php artisan config:clear
php artisan limen-ai:doctor
php artisan limen-ai:validate</code></pre>
    </div>

    <div class="tab-panel" id="panel-env">
        <p class="panel-note">Black-box defaults — no Laravel Gates required.</p>
        <pre data-lang="env"><code>LIMEN_AI_DEFAULT_AGENT=app_assistant
LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=your-key

LIMEN_AI_AUTHORIZATION_MODE=simple
LIMEN_AI_REQUIRE_AUTH=false

LIMEN_AI_UI_TITLE="Support"
LIMEN_AI_UI_GUEST_ENABLED=true
LIMEN_AI_UI_HISTORY_ENABLED=true
LIMEN_AI_PERSISTENCE_DRIVER=database</code></pre>
    </div>

    <div class="tab-panel" id="panel-agent">
        <p class="panel-note">FAQ-only agent — knowledge injected every turn, no tools needed.</p>
        <pre data-lang="php"><code>'knowledge' => [
    'driver' => 'config',
    'collections' => [
        'product_help' => [
            'documents' => [
                ['content' => 'Shipping: 3–5 business days.'],
                ['content' => 'Returns within 14 days.'],
            ],
        ],
    ],
],
'agents' => [
    'app_assistant' => [
        'instructions' => 'Answer from knowledge. Be concise.',
        'tools' => [],
        'knowledge' => ['product_help'],
        'authorization' => ['required' => false, 'guest_allowed' => true],
    ],
],</code></pre>
    </div>

    <div class="tab-panel" id="panel-tool">
        <p class="panel-note">Generate a tool, implement authorize() + handle(), register in config.</p>
        <pre data-lang="bash"><code>php artisan limen-ai:make:tool GetOrderStatus --key=get_order_status --test</code></pre>
        <pre data-lang="php"><code>public function authorize(array $input, ToolExecutionContext $context): bool
{
    return $context->userId() !== null;
}

public function handle(array $input, ToolExecutionContext $context): array
{
    return ['status' => 'shipped', 'order_id' => $input['order_id']];
}</code></pre>
    </div>

    <div class="tab-panel" id="panel-widget">
        <p class="panel-note">One Blade line — chat UI, history, guest sessions.</p>
        <pre data-lang="blade"><code>&lt;x-limen-ai::widget /&gt;

&lt;!-- Or full page chat --&gt;
&lt;x-limen-ai::chatbot agent="app_assistant" /&gt;</code></pre>
    </div>

    <div class="tab-panel" id="panel-ollama">
        <p class="panel-note">Local model via OpenAI-compatible driver (e.g. qwen3:8b).</p>
        <pre data-lang="env"><code>LIMEN_AI_PROVIDER=openai
OPENAI_API_KEY=ollama
OPENAI_BASE_URL=http://localhost:11434/v1
OPENAI_TIMEOUT=120
LIMEN_AI_APP_ASSISTANT_MODEL=qwen3:8b
LIMEN_AI_QUEUE_AGENT_RUNS=false
LIMEN_AI_BROADCAST_DRIVER=null</code></pre>
    </div>

    <div class="tab-panel" id="panel-test">
        <p class="panel-note">Smoke-test without opening the browser.</p>
        <pre data-lang="bash"><code>php artisan limen-ai:doctor
php artisan limen-ai:validate
php artisan limen-ai:tool:test get_order_status --input='{"order_id":"ORD-1001"}'
php artisan limen-ai:agent:test app_assistant --message="What is your return policy?"</code></pre>
    </div>
</section>
