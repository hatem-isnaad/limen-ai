<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Providers\LlmProvider;
use LimenAi\Contracts\Skills\SkillDefinition;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Tools\ToolSchemaBuilder;

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
        return array_filter([
            'model' => $this->model(),
            'max_tokens' => $this->limits['max_tokens'] ?? null,
        ], fn ($value) => $value !== null && $value !== '');
    }

    /**
     * @param  list<array<string, mixed>>  $messages
     * @return \LimenAi\Contracts\Providers\LlmResponse
     */
    public function chat(array $messages): \LimenAi\Contracts\Providers\LlmResponse
    {
        return $this->provider->chat(
            messages: $this->prependSystemMessage($messages),
            tools: $this->toolSchemas(),
            options: $this->chatOptions(),
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
