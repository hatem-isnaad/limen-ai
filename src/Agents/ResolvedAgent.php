<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Providers\LlmResponse;
use LimenAi\Tools\ToolSchemaBuilder;

final class ResolvedAgent
{
    private ?array $cachedToolSchemas = null;

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

    public function tools(): array
    {
        return $this->tools;
    }

    public function skills(): array
    {
        return $this->skills;
    }

    public function limits(): array
    {
        return $this->limits;
    }

    public function toolSchemas(): array
    {
        if ($this->cachedToolSchemas !== null) {
            return $this->cachedToolSchemas;
        }
        $this->cachedToolSchemas = $this->toolSchemaBuilder->buildMany($this->tools);

        return $this->cachedToolSchemas;
    }

    public function chatOptions(): array
    {
        return array_filter([
            'model' => $this->model(),
            'max_tokens' => $this->limits['max_tokens'] ?? null,
            'temperature' => $this->limits['temperature'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    public function chat(array $messages): LlmResponse
    {
        return $this->provider->chat(
            messages: $this->prependSystemMessage($messages),
            tools: $this->toolSchemas(),
            options: $this->chatOptions(),
        );
    }

    private function prependSystemMessage(array $messages): array
    {
        if ($this->instructions === '') {
            return $messages;
        }
        array_unshift($messages, ['role' => 'system', 'content' => $this->instructions]);

        return $messages;
    }
}
