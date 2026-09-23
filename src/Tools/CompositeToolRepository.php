<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolRepository;

final class CompositeToolRepository implements ToolRepository
{
    public function __construct(
        private readonly ConfigToolRepository $config,
        private readonly RuntimeToolRegistry $runtime,
    ) {}

    public function find(string $key): ?ToolDefinition
    {
        return $this->runtime->find($key) ?? $this->config->find($key);
    }

    public function all(): array
    {
        $keys = [];

        foreach (array_merge($this->config->all(), $this->runtime->all()) as $tool) {
            $keys[$tool->key()] = $tool;
        }

        return array_values($keys);
    }

    public function forAgent(string $agentKey): array
    {
        return $this->config->forAgent($agentKey);
    }
}
