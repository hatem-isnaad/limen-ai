<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\AgentRepository;

/**
 * Merges config/class agents with optional database-backed definitions.
 *
 * Source order in config (default: config, database) — first match wins for {@see find()}.
 * The first source in the list wins when keys collide in {@see all()}.
 */
final class CompositeAgentRepository implements AgentRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly ConfigAgentRepository $configAgents,
        private readonly DatabaseAgentRepository $databaseAgents,
    ) {}

    public function find(string $key): ?AgentDefinition
    {
        foreach ($this->sources() as $source) {
            $definition = $this->repository($source)->find($key);

            if ($definition !== null) {
                return $definition;
            }
        }

        return null;
    }

    public function all(): array
    {
        $map = [];

        foreach (array_reverse($this->sources()) as $source) {
            foreach ($this->repository($source)->all() as $agent) {
                $map[$agent->key()] = $agent;
            }
        }

        return array_values($map);
    }

    public function exists(string $key): bool
    {
        return $this->find($key) !== null;
    }

    /** @return list<string> */
    protected function sources(): array
    {
        $sources = $this->config->get('limen-ai.agent_storage.definition_sources', ['config', 'database']);

        if (! is_array($sources) || $sources === []) {
            return ['config', 'database'];
        }

        return array_values(array_filter($sources, fn ($source): bool => in_array($source, ['config', 'database'], true)));
    }

    protected function repository(string $source): AgentRepository
    {
        return match ($source) {
            'database' => $this->databaseAgents,
            default => $this->configAgents,
        };
    }
}
