<?php

namespace LimenAi\Workflows;

use LimenAi\Contracts\Workflows\WorkflowDefinition;

final class ConfigWorkflowDefinition implements WorkflowDefinition
{
    /**
     * @param  array<string, mixed>  $definition
     */
    public function __construct(
        private readonly string $key,
        private readonly string $name,
        private readonly array $definition,
        private readonly string $version,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        $workflowDefinition = $config['definition'] ?? $config;

        unset($workflowDefinition['name'], $workflowDefinition['version']);

        return new self(
            key: $key,
            name: (string) ($config['name'] ?? $key),
            definition: is_array($workflowDefinition) ? $workflowDefinition : [],
            version: (string) ($config['version'] ?? '1.0.0'),
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

    public function definition(): array
    {
        return $this->definition;
    }

    public function version(): string
    {
        return $this->version;
    }

    public function startStep(): string
    {
        return (string) ($this->definition['start'] ?? '');
    }

    /** @return array<string, array<string, mixed>> */
    public function steps(): array
    {
        $steps = $this->definition['steps'] ?? [];

        return is_array($steps) ? $steps : [];
    }
}
