<?php

namespace LimenAi\Runtime;

use Illuminate\Contracts\Bus\Dispatcher as BusDispatcher;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Jobs\CancelAgentRunJob;
use LimenAi\Jobs\RejectAgentRunJob;
use LimenAi\Jobs\ResumeAgentRunJob;
use LimenAi\Jobs\RunAgentJob;

class QueuedAgentRunDispatcher implements AgentRunDispatcher
{
    public function __construct(
        private readonly BusDispatcher $bus,
        private readonly ConfigRepository $config,
    ) {}

    public function dispatchRun(
        string $agentKey,
        string $conversationId,
        string $userMessage,
        RunContext $context,
    ): AgentRunDispatchResult {
        $this->dispatch(new RunAgentJob(
            $agentKey,
            $conversationId,
            $userMessage,
            $this->contextAttributes($context),
        ));

        return new AgentRunDispatchResult(queued: true);
    }

    public function dispatchResume(string $runId, RunContext $context): AgentRunDispatchResult
    {
        $this->dispatch(new ResumeAgentRunJob($runId, $this->contextAttributes($context)));

        return new AgentRunDispatchResult(queued: true, runId: $runId);
    }

    public function dispatchCancel(string $runId, RunContext $context): AgentRunDispatchResult
    {
        $this->dispatch(new CancelAgentRunJob($runId, $this->contextAttributes($context)));

        return new AgentRunDispatchResult(queued: true, runId: $runId);
    }

    public function dispatchReject(string $runId, RunContext $context): AgentRunDispatchResult
    {
        $this->dispatch(new RejectAgentRunJob($runId, $this->contextAttributes($context)));

        return new AgentRunDispatchResult(queued: true, runId: $runId);
    }

    /** @return array<string, mixed> */
    protected function contextAttributes(RunContext $context): array
    {
        return [
            'user_id' => $context->userId(),
            'guest_token' => $context->guestToken(),
            'metadata' => $context->metadata(),
            'locale' => $context->locale(),
        ];
    }

    protected function dispatch(object $job): void
    {
        $connection = $this->config->get('limen-ai.queue.connection');
        $queue = $this->config->get('limen-ai.queue.name', 'default');

        if ($connection !== null && $connection !== '' && method_exists($job, 'onConnection')) {
            $job->onConnection((string) $connection);
        }

        if ($queue !== null && $queue !== '' && method_exists($job, 'onQueue')) {
            $job->onQueue((string) $queue);
        }

        $this->bus->dispatch($job);
    }
}
