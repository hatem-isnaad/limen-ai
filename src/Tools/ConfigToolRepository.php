<?php

namespace LimenAi\Tools;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolRepository;
use LimenAi\Support\DefinitionLoader;
use LimenAi\Support\Enablement;

class ConfigToolRepository implements ToolRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AgentRepository $agents,
        private readonly DefinitionLoader $definitions,
    ) {}

    public function find(string $key): ?ToolDefinition
    {
        $definition = $this->definitions->tools()[$key] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        return ConfigToolDefinition::fromConfig($key, $definition);
    }

    public function all(): array
    {
        $tools = $this->definitions->tools();

        $definitions = array_map(
            fn (string $key, array $definition): ToolDefinition => ConfigToolDefinition::fromConfig($key, $definition),
            array_keys($tools),
            $tools,
        );

        return array_values(array_filter(
            $definitions,
            fn (ToolDefinition $tool): bool => Enablement::isEnabled($tool),
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

            if ($tool !== null && Enablement::isEnabled($tool)) {
                $tools[] = $tool;
            }
        }

        return $tools;
    }
}
