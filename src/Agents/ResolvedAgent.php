<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Skills\SkillDefinition;
use LimenAi\Contracts\Providers\StreamingLlmProvider;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Exceptions\StreamingNotSupportedException;
use LimenAi\Providers\LlmStreamChunk;
use LimenAi\Support\StructuredOutput;
use LimenAi\Tools\ToolSchemaBuilder;
use Generator;

final class ResolvedAgent
{
    /** @var list<array<string, mixed>>|null */
    private ?array $cachedToolSchemas = null;

    /**
     * @param  list<ToolDefinition>  $tools
     * @param  list<SkillDefinition>  $skills
     * @param  array<string, mixed>  $limits
     */
    public function __construct(
        private readonly AgentDefinition $definition,
        private readonly LlmProvider $provider,
        private readonly string $instructions,
        private readonly array $tools,
        private readonly array $skills,
        private readonly array $limits,
        private readonly ToolSchemaBuilder $toolSchemaBuilder,
    ) {}

    public function definition(): AgentDefinition
    {
        return $this->definition;
    }

    public function key(): string
    {
        return $this->definition->key();
    }

    public function name(): string
    {
        return $this->definition->name();
    }

    public function model(): string
    {
        return $this->definition->model();
    }

    public function providerName(): string
    {
        return $this->definition->provider();
    }

    public function provider(): LlmProvider
    {
        return $this->provider;
    }

    public function instructions(): string
    {
        return $this->instructions;
    }

    /** @return list<ToolDefinition> */
    public function tools(): array
    {
        return $this->tools;
    }

    /** @return list<SkillDefinition> */
    public function skills(): array
    {
        return $this->skills;
    }

    /** @return array<string, mixed> */
    public function limits(): array
    {
        return $this->limits;
    }

    /** @return list<array<string, mixed>> */
    public function toolSchemas(): array
    {
        if ($this->cachedToolSchemas !== null) {
            return $this->cachedToolSchemas;
        }

        $this->cachedToolSchemas = $this->toolSchemaBuilder->buildMany($this->tools);

        return $this->cachedToolSchemas;
    }

    /** @return array<string, mixed> */
    public function chatOptions(): array
    {
        $options = [
            'model' => $this->model(),
            'max_tokens' => $this->limits['max_tokens'] ?? null,
        ];

        if ((bool) config('limen-ai.deferred_tool_loading', false)) {
            $options['deferred_tools'] = true;
        }

        return array_filter(array_merge(
            $options,
            StructuredOutput::chatOptions($this->definition->outputConfig(), $this->key()),
        ), fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return \LimenAi\Contracts\Providers\LlmResponse
     */
    public function chat(array $messages, array $optionsOverride = []): \LimenAi\Contracts\Providers\LlmResponse
    {
        $options = array_merge($this->chatOptions(), $optionsOverride);
        $tools = ($options['omit_tools'] ?? false) ? [] : $this->toolSchemas();
        unset($options['omit_tools'], $options['deferred_tools']);

        return $this->provider->chat(
            messages: $this->prependSystemMessage($messages),
            tools: $tools,
            options: $options,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return Generator<int, LlmStreamChunk>
     */
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  array<string, mixed>  $optionsOverride
     */
    public function stream(array $messages, array $optionsOverride = []): Generator
    {
        if (! $this->provider instanceof StreamingLlmProvider) {
            throw StreamingNotSupportedException::forReason(
                "Provider [{$this->providerName()}] does not support streaming.",
            );
        }

        $options = array_merge($this->chatOptions(), $optionsOverride);
        $tools = ($options['omit_tools'] ?? false) ? [] : $this->toolSchemas();
        unset($options['omit_tools'], $options['deferred_tools']);

        yield from $this->provider->streamChat(
            messages: $this->prependSystemMessage($messages),
            tools: $tools,
            options: $options,
        );
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return list<array<string, mixed>>
     */
    private function prependSystemMessage(array $messages): array
    {
        if ($this->instructions === '') {
            return $messages;
        }

        array_unshift($messages, [
            'role' => 'system',
            'content' => $this->instructions,
        ]);

        return $messages;
    }
}
