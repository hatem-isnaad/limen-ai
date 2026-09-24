<?php

namespace LimenAi\Agents;

use LimenAi\Contracts\Agents\AgentDefinition;

final class ClassAgentDefinition implements AgentDefinition
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
        private readonly string $class,
    ) {}

    public function className(): string
    {
        return $this->class;
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
