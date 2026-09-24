<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Tools\ToolDefinition;

final class ConfigToolDefinition implements ToolDefinition
{
    /**
     * @param  array<string, mixed>  $inputSchema
     * @param  array<string, mixed>  $authorizationConfig
     * @param  array<string, mixed>  $httpIntegration
     */
    public function __construct(
        private readonly string $key,
        private readonly string $name,
        private readonly string $description,
        private readonly array $inputSchema,
        private readonly array $authorizationConfig,
        private readonly bool $requiresConfirmation,
        private readonly int $timeoutSeconds,
        private readonly string $executorClass,
        private readonly array $httpIntegration,
        private readonly string $version,
        private readonly bool $enabled,
    ) {}

    /**
     * @param  array<string, mixed>  $config
     */
    public static function fromConfig(string $key, array $config): self
    {
        $executorClass = $config['class'] ?? $config['executor'] ?? '';

        return new self(
            key: $key,
            name: (string) ($config['name'] ?? $key),
            description: (string) ($config['description'] ?? ''),
            inputSchema: $config['input_schema'] ?? [],
            authorizationConfig: $config['authorization'] ?? [],
            requiresConfirmation: (bool) ($config['confirmation'] ?? false),
            timeoutSeconds: (int) ($config['timeout'] ?? 30),
            executorClass: is_string($executorClass) ? $executorClass : '',
            httpIntegration: is_array($config['integration'] ?? null) ? $config['integration'] : [],
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

    public function description(): string
    {
        return $this->description;
    }

    public function inputSchema(): array
    {
        return $this->inputSchema;
    }

    public function authorizationConfig(): array
    {
        return $this->authorizationConfig;
    }

    public function requiresConfirmation(): bool
    {
        return $this->requiresConfirmation;
    }

    public function timeoutSeconds(): int
    {
        return $this->timeoutSeconds;
    }

    public function executorClass(): string
    {
        return $this->executorClass;
    }

    public function httpIntegration(): array
    {
        return $this->httpIntegration;
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
