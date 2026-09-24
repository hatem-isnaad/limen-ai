<?php

namespace LimenAi;

use Illuminate\Support\Str;
use LimenAi\Contracts\Observability\UsageReader;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Tools\ToolExecutor;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Registry\LimenAiRegistry;
use LimenAi\Runtime\RunContextData;

class LimenAiManager
{
    public function __construct(
        private readonly LimenAiRegistry $registry,
        private readonly AgentRuntime $runtime,
        private readonly ToolExecutor $tools,
        private readonly UsageReader $usage,
    ) {}

    public function configure(callable $callback): self
    {
        $callback($this->registry);

        return $this;
    }

    /** @param  array<string, mixed>|class-string  $definition */
    public function tool(string $key, array|string $definition): self
    {
        $this->registry->tool($key, $definition);

        return $this;
    }

    /** @param  array<string, mixed>  $definition */
    public function agent(string $key, array $definition): self
    {
        $this->registry->agent($key, $definition);

        return $this;
    }

    /** @param  array<string, mixed>  $definition */
    public function skill(string $key, array $definition): self
    {
        $this->registry->skill($key, $definition);

        return $this;
    }

    /** @param  array<string, mixed>  $definition */
    public function workflow(string $key, array $definition): self
    {
        $this->registry->workflow($key, $definition);

        return $this;
    }

    /** @param  array<string, mixed>  $definition */
    public function knowledge(string $collectionKey, array $definition): self
    {
        $this->registry->knowledge($collectionKey, $definition);

        return $this;
    }

    public function faq(string $collectionKey, string $question, string $answer, array $metadata = []): self
    {
        $this->registry->faq($collectionKey, $question, $answer, $metadata);

        return $this;
    }

    /** @param  array<string, mixed>  $settings */
    public function provider(string $name, array $settings): self
    {
        $this->registry->provider($name, $settings);

        return $this;
    }

    public function run(
        string $agentKey,
        string $conversationId,
        string $message,
        ?RunContext $context = null,
    ): string {
        return $this->runtime->run(
            $agentKey,
            $conversationId,
            $message,
            $context ?? RunContextData::make(['user_id' => auth()->id()]),
        );
    }

    /** @param  array<string, mixed>  $input */
    public function executeTool(
        string $toolKey,
        array $input,
        ?RunContext $context = null,
        string $runId = '',
        string $conversationId = '',
        string $agentKey = 'manual',
    ): array {
        $context ??= RunContextData::make(['user_id' => auth()->id()]);

        $tool = app(ToolRepository::class)->find($toolKey);

        if ($tool === null) {
            throw new \InvalidArgumentException("Tool [{$toolKey}] was not found.");
        }

        return $this->tools->execute(
            $tool,
            $input,
            $context->forToolExecution(
                $runId !== '' ? $runId : (string) Str::uuid(),
                $conversationId !== '' ? $conversationId : (string) Str::uuid(),
                $agentKey,
            ),
        );
    }

    /** @return array<string, mixed> */
    public function usageSummary(?string $runId = null): array
    {
        if ($runId !== null && $runId !== '') {
            return $this->usage->summarizeForRun($runId);
        }

        return $this->usage->summarize();
    }

    public function registry(): LimenAiRegistry
    {
        return $this->registry;
    }
}
