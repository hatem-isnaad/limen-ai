<?php

namespace LimenAi\Skills;

use LimenAi\Contracts\Skills\SkillDefinition;

final class ConfigSkillDefinition implements SkillDefinition
{
    /**
     * @param  list<string>  $allowedTools
     * @param  list<string>  $knowledgeSources
     */
    public function __construct(
        private readonly string $key,
        private readonly string $name,
        private readonly string $instructions,
        private readonly array $allowedTools,
        private readonly array $knowledgeSources,
        private readonly string $version,
        private readonly bool $enabled,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        return new self(
            key: $key,
            name: (string) ($config['name'] ?? $key),
            instructions: (string) ($config['instructions'] ?? ''),
            allowedTools: array_values($config['tools'] ?? $config['allowed_tools'] ?? []),
            knowledgeSources: array_values($config['knowledge'] ?? $config['knowledge_sources'] ?? []),
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

    public function instructions(): string
    {
        return $this->instructions;
    }

    public function allowedTools(): array
    {
        return $this->allowedTools;
    }

    public function knowledgeSources(): array
    {
        return $this->knowledgeSources;
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
