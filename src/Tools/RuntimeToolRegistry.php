<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Tools\ToolDefinition;

final class RuntimeToolRegistry
{
    /** @var array<string, array<string, mixed>> */
    private array $tools = [];

    /**
     * @param  array<string, mixed>  $config
     */
    public function register(string $key, array $config): void
    {
        $this->tools[$key] = $config;
    }

    public function find(string $key): ?ToolDefinition
    {
        $config = $this->tools[$key] ?? null;

        if (! is_array($config)) {
            return null;
        }

        return ConfigToolDefinition::fromConfig($key, $config);
    }

    /** @return list<ToolDefinition> */
    public function all(): array
    {
        return array_values(array_map(
            fn (string $key, array $config): ToolDefinition => ConfigToolDefinition::fromConfig($key, $config),
            array_keys($this->tools),
            $this->tools,
        ));
    }
}
