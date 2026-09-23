<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Runtime\RunContextData;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Runtime\RunStatus;

class DelegateToAgentTool extends BaseTool
{
    public function __construct(
        private readonly AgentRuntime $runtime,
        private readonly RunRepository $runs,
    ) {}

    public function key(): string
    {
        return 'delegate_to_agent';
    }

    public function definition(): ToolDefinition
    {
        return ConfigToolDefinition::fromConfig($this->key(), config('limen-ai.tools.delegate_to_agent', []));
    }

    public function authorize(array $input, ToolExecutionContext $context): bool
    {
        return $context->userId() !== null;
    }

    /**
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function handle(array $input, ToolExecutionContext $context): array
    {
        $delegateKey = (string) ($input['agent_key'] ?? '');
        $message = (string) ($input['message'] ?? '');
        $allowed = config('limen-ai.router.delegates', []);

        if ($delegateKey === '' || $message === '') {
            return ['delegated' => false, 'reason' => 'missing_agent_or_message'];
        }

        if (is_array($allowed) && $allowed !== [] && ! in_array($delegateKey, $allowed, true)) {
            return ['delegated' => false, 'reason' => 'delegate_not_allowed', 'agent_key' => $delegateKey];
        }

        $conversationId = $context->conversationId() ?: 'delegate-'.uniqid();
        $runId = $this->runtime->run(
            $delegateKey,
            $conversationId,
            $message,
            RunContextData::make([
                'user_id' => $context->userId(),
                'conversation_id' => $conversationId,
                'agent_key' => $delegateKey,
            ]),
        );

        $run = $this->runs->find($runId);

        return [
            'delegated' => true,
            'agent_key' => $delegateKey,
            'run_id' => $runId,
            'status' => $run['status'] ?? RunStatus::FAILED,
            'final_message' => (string) ($run['final_message'] ?? ''),
        ];
    }
}
