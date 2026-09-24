<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Models\AgentDefinitionModel;

final class DatabaseAgentDefinition implements AgentDefinition
{
    /**
     * @param  list<string>  $skills
     * @param  list<string>  $tools
     * @param  list<string>  $knowledge
     * @param  array<string, mixed>  $memoryConfig
     * @param  array<string, mixed>  $authorizationConfig
     * @param  array<string, mixed>  $outputConfig
     * @param  array<string, mixed>  $limits
     */
    public function __construct(
        private readonly string $key,
        private readonly string $name,
        private readonly ?string $description,
        private readonly string $model,
        private readonly string $provider,
        private readonly string $instructions,
        private readonly array $skills,
        private readonly array $tools,
        private readonly array $knowledge,
        private readonly array $memoryConfig,
        private readonly array $authorizationConfig,
        private readonly array $outputConfig,
        private readonly array $limits,
        private readonly string $version,
        private readonly bool $enabled,
    ) {}

    public static function fromModel(AgentDefinitionModel $model): self
    {
        return self::fromConfigArray($model->key, $model->toAgentConfigArray());
    }

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfigArray(string $key, array $config): self
    {
        return new self(
            key: $key,
            name: (string) ($config['name'] ?? $key),
            description: isset($config['description']) ? (string) $config['description'] : null,
            model: (string) ($config['model'] ?? ''),
            provider: (string) ($config['provider'] ?? config('limen-ai.providers.default', 'fake')),
            instructions: (string) ($config['instructions'] ?? ''),
            skills: array_values($config['skills'] ?? []),
            tools: array_values($config['tools'] ?? []),
            knowledge: array_values($config['knowledge'] ?? []),
            memoryConfig: $config['memory'] ?? [],
            authorizationConfig: $config['authorization'] ?? [],
            outputConfig: $config['output'] ?? [],
            limits: $config['limits'] ?? [],
            version: (string) ($config['version'] ?? '1.0.0'),
            enabled: (bool) ($config['enabled'] ?? true),
        );
    }

    public function key(): string
    {
        return $this->key;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): ?string
    {
        return $this->description;
    }

    public function model(): string
    {
        return $this->model;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function instructions(): string
    {
        return $this->instructions;
    }

    public function skills(): array
    {
        return $this->skills;
    }

    public function tools(): array
    {
        return $this->tools;
    }

    public function knowledge(): array
    {
        return $this->knowledge;
    }

    public function memoryConfig(): array
    {
        return $this->memoryConfig;
    }

    public function authorizationConfig(): array
    {
        return $this->authorizationConfig;
    }

    public function outputConfig(): array
    {
        return $this->outputConfig;
    }

    public function limits(): array
    {
        return $this->limits;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }
}
