<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\AgentRepository;

class ConfigAgentRepository implements AgentRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AgentConfigurationMerger $merger,
    ) {}

    public function find(string $key): ?AgentDefinition
    {
        $definition = $this->config->get("limen-ai.agents.{$key}");

        if (! is_array($definition)) {
            return null;
        }

        return ConfigAgentDefinition::fromConfig($key, $this->merger->merge($definition));
    }

    public function all(): array
    {
        $agents = $this->config->get('limen-ai.agents', []);

        if (! is_array($agents)) {
            return [];
        }

        return array_values(array_map(
            fn (string $key, array $definition): AgentDefinition => ConfigAgentDefinition::fromConfig(
                $key,
                $this->merger->merge($definition),
            ),
            array_keys($agents),
            $agents,
        ));
    }

    public function exists(string $key): bool
    {
        return is_array($this->config->get("limen-ai.agents.{$key}"));
    }
}
