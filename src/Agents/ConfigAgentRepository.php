<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Support\DefinitionLoader;
use LimenAi\Support\Enablement;

class ConfigAgentRepository implements AgentRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly DefinitionLoader $definitions,
        private readonly ClassAgentDefinitionFactory $classAgents,
    ) {}

    public function find(string $key): ?AgentDefinition
    {
        $definition = $this->definitions->agents()[$key] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        if (isset($definition['class']) && is_string($definition['class'])) {
            return $this->classAgents->make($key, $definition);
        }

        return ConfigAgentDefinition::fromConfig($key, $definition);
    }

    public function all(): array
    {
        $agents = $this->definitions->agents();

        $definitions = array_map(
            fn (string $key, array $definition): AgentDefinition => isset($definition['class'])
                ? $this->classAgents->make($key, $definition)
                : ConfigAgentDefinition::fromConfig($key, $definition),
            array_keys($agents),
            $agents,
        );

        return array_values(array_filter(
            $definitions,
            fn (AgentDefinition $agent): bool => Enablement::isEnabled($agent),
        ));
    }

    public function exists(string $key): bool
    {
        return isset($this->definitions->agents()[$key]);
    }
}
