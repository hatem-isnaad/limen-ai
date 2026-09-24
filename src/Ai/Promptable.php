<?php

namespace LimenAi\Ai;

use Illuminate\Support\Str;
use LimenAi\Ai\Contracts\Agent;
use Generator;
use LimenAi\Contracts\Runtime\AgentRunDispatcher;
use LimenAi\Contracts\Runtime\AgentRuntime;
use LimenAi\Runtime\AgentRunDispatchResult;
use LimenAi\Providers\LlmStreamChunk;
use LimenAi\Contracts\Runtime\RunContext;
use LimenAi\Contracts\Runtime\RunRepository;
use LimenAi\Runtime\RunContextData;

trait Promptable
{
    protected ?string $limenConversationId = null;

    protected ?RunContext $limenRunContext = null;

    /**
     * @param  mixed  ...$parameters
     */
    public static function make(mixed ...$parameters): static
    {
        return app(static::class, $parameters);
    }

    public function forUser(object $user): static
    {
        $userId = method_exists($user, 'getAuthIdentifier')
            ? $user->getAuthIdentifier()
            : ($user->id ?? null);

        $this->limenRunContext = RunContextData::make([
            'user_id' => $userId !== null ? (int) $userId : null,
        ]);

        return $this;
    }

    public function continue(string $conversationId, ?object $user = null): static
    {
        $this->limenConversationId = $conversationId;

        if ($user !== null) {
            $this->forUser($user);
        }

        return $this;
    }

    public function usingContext(RunContext $context): static
    {
        $this->limenRunContext = $context;

        return $this;
    }

    public function prompt(string $prompt, ?RunContext $context = null): AgentResponse
    {
        $this->assertIsAgent();

        $agentKey = $this->key();
        $conversationId = $this->limenConversationId ?? (string) Str::uuid();
        $context ??= $this->limenRunContext ?? RunContextData::make([
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
        ]);

        $runId = app(AgentRuntime::class)->run(
            $agentKey,
            $conversationId,
            $prompt,
            $context,
        );

        $run = app(RunRepository::class)->find($runId);
        $text = is_array($run) ? (string) ($run['final_message'] ?? '') : '';

        return new AgentResponse($text, $runId, $conversationId);
    }

    public function queue(string $prompt, ?RunContext $context = null): AgentRunDispatchResult
    {
        $this->assertIsAgent();

        $agentKey = $this->key();
        $conversationId = $this->limenConversationId ?? (string) Str::uuid();
        $context ??= $this->limenRunContext ?? RunContextData::make([
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
            'user_id' => auth()->id(),
        ]);

        return app(AgentRunDispatcher::class)->dispatchRun(
            $agentKey,
            $conversationId,
            $prompt,
            $context,
        );
    }

    /** @return Generator<int, LlmStreamChunk> */
    public function stream(string $prompt, ?RunContext $context = null): Generator
    {
        $this->assertIsAgent();

        $agentKey = $this->key();
        $conversationId = $this->limenConversationId ?? (string) Str::uuid();
        $context ??= $this->limenRunContext ?? RunContextData::make([
            'agent_key' => $agentKey,
            'conversation_id' => $conversationId,
        ]);

        yield from app(AgentRuntime::class)->stream(
            $agentKey,
            $conversationId,
            $prompt,
            $context,
        );
    }

    protected function assertIsAgent(): void
    {
        if (! $this instanceof Agent) {
            throw new \LogicException(sprintf(
                'Class [%s] must implement [%s] to use Promptable.',
                static::class,
                Agent::class,
            ));
        }
    }
}
