<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolRepository;

class ConfigToolRepository implements ToolRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AgentRepository $agents,
    ) {}

    public function find(string $key): ?ToolDefinition
    {
        $definition = $this->config->get("limen-ai.tools.{$key}");

        if (! is_array($definition)) {
            return null;
        }

        return ConfigToolDefinition::fromConfig($key, $definition);
    }

    public function all(): array
    {
        $tools = $this->config->get('limen-ai.tools', []);

        if (! is_array($tools)) {
            return [];
        }

        return array_values(array_map(
            fn (string $key, array $definition): ToolDefinition => ConfigToolDefinition::fromConfig($key, $definition),
            array_keys($tools),
            $tools,
        ));
    }

    public function forAgent(string $agentKey): array
    {
        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            return [];
        }

        $tools = [];

        foreach ($agent->tools() as $toolKey) {
            $tool = $this->find($toolKey);

            if ($tool !== null) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }
}
