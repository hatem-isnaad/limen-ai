<section id="advanced-tutorial">
    <div class="section-head">
        <div>
            <h2><span class="h2-icon"><svg class="ico"><use href="#i-package"/></svg></span> Advanced end-to-end: support system</h2>
            <p class="section-sub">One copy-paste story — public FAQ widget, staff agent with tools, refund approval workflow.</p>
        </div>
        <a class="btn btn-ghost" href="{{ url('/demo/limen-ai/docs/'.rawurlencode('scaling-agents-and-tools.md')) }}">Scaling guide →</a>
    </div>

    <div class="walk">
        <div class="walk-step">
            <div class="walk-head">
                <span class="step-dot">1</span>
                <span class="walk-title">Three agents, split by audience</span>
                <span class="walk-file">config/limen-ai.php</span>
            </div>
            <p class="walk-desc">Public widget stays small (FAQ only). Staff gets lookups. Admin gets mutations with confirmation.</p>
            <pre data-lang="php"><code>'agents' => [
    'app_assistant' => [
        'instructions' => 'Answer from knowledge only. Never guess order status.',
        'tools' => [],
        'knowledge' => ['store_faq'],
        'authorization' => ['required' => false, 'guest_allowed' => true],
    ],
    'support_agent' => [
        'instructions' => 'Help staff with orders and tickets. Call tools when user gives an ID.',
        'tools' => ['get_order_status', 'list_open_tickets'],
        'authorization' => ['required' => true, 'guest_allowed' => false],
    ],
    'admin_agent' => [
        'instructions' => 'Privileged actions only when staff confirms.',
        'tools' => ['refund_order'],
        'authorization' => ['required' => true, 'guest_allowed' => false],
    ],
],</code></pre>
        </div>

        <div class="walk-step">
            <div class="walk-head">
                <span class="step-dot">2</span>
                <span class="walk-title">Knowledge for the public widget</span>
                <span class="walk-file">config/limen-ai.php</span>
            </div>
            <p class="walk-desc">Short FAQ chunks — shipping, returns, hours. No vector DB required to start.</p>
            <pre data-lang="php"><code>'knowledge' => [
    'collections' => [
        'store_faq' => [
            'documents' => [
                ['content' => 'Shipping: 3–5 business days standard.'],
                ['content' => 'Returns: 14 days, unused items only.'],
                ['content' => 'Support: Sun–Thu 9–18 Cairo time.'],
            ],
        ],
    ],
],</code></pre>
        </div>

        <div class="walk-step">
            <div class="walk-head">
                <span class="step-dot">3</span>
                <span class="walk-title">Staff lookup tool</span>
                <span class="walk-file">app/LimenAi/Tools/GetOrderStatusTool.php</span>
            </div>
            <pre data-lang="bash"><code>php artisan limen-ai:make:tool GetOrderStatus --key=get_order_status --test</code></pre>
            <pre data-lang="php"><code>public function authorize(array $input, ToolExecutionContext $context): bool
{
    return $context->userId() !== null;
}

public function handle(array $input, ToolExecutionContext $context): array
{
    $order = Order::where('number', $input['order_id'])->first();

    return [
        'found' => (bool) $order,
        'status' => $order?->status ?? 'not_found',
    ];
}</code></pre>
        </div>

        <div class="walk-step">
            <div class="walk-head">
                <span class="step-dot">4</span>
                <span class="walk-title">Dangerous action + confirmation</span>
                <span class="walk-file">config/limen-ai.php</span>
            </div>
            <p class="walk-desc">Refunds require human approval — set <code>confirmation: true</code> on the tool.</p>
            <pre data-lang="php"><code>'tools' => [
    'refund_order' => [
        'class' => App\LimenAi\Tools\RefundOrderTool::class,
        'description' => 'Issue a partial or full refund for an order ID.',
        'input_schema' => [
            'order_id' => ['type' => 'string', 'required' => true],
            'amount' => ['type' => 'number', 'required' => true],
        ],
        'confirmation' => true,
    ],
],</code></pre>
        </div>

        <div class="walk-step">
            <div class="walk-head">
                <span class="step-dot">5</span>
                <span class="walk-title">Workflow: draft → approve → execute</span>
                <span class="walk-file">config/limen-ai.php</span>
            </div>
            <pre data-lang="php"><code>'workflows' => [
    'refund_request' => [
        'steps' => [
            ['type' => 'agent', 'agent' => 'support_agent', 'input' => '@{{ message }}'],
            ['type' => 'approval', 'message' => 'Confirm refund for order?'],
            ['type' => 'tool', 'tool' => 'refund_order'],
        ],
    ],
],</code></pre>
        </div>

        <div class="walk-step">
            <div class="walk-head">
                <span class="step-dot">6</span>
                <span class="walk-title">Embed UI + verify</span>
            </div>
            <pre data-lang="blade"><code>&lt;!-- Public site --&gt;
&lt;x-limen-ai::widget agent="app_assistant" /&gt;

&lt;!-- Staff area (auth middleware on route) --&gt;
&lt;x-limen-ai::chatbot agent="support_agent" /&gt;</code></pre>
            <pre data-lang="bash"><code>php artisan limen-ai:doctor
php artisan limen-ai:validate
php artisan limen-ai:agent:test app_assistant --message="What is your return policy?"
php artisan limen-ai:tool:test get_order_status --input='{"order_id":"ORD-1001"}'</code></pre>
        </div>
    </div>
</section>
