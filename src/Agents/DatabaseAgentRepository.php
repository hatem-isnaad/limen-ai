<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Schema;
use LimenAi\Contracts\Agents\AgentDefinition;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Models\AgentDefinitionModel;
use LimenAi\Support\Enablement;

class DatabaseAgentRepository implements AgentRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly CacheRepository $cache,
        private readonly ClassAgentDefinitionFactory $classAgents,
    ) {}

    public function find(string $key): ?AgentDefinition
    {
        if (! $this->isEnabled() || ! $this->tableExists()) {
            return null;
        }

        $resolver = fn (): ?AgentDefinitionModel => $this->newQuery()->where('key', $key)->first();

        $model = $this->shouldCache()
            ? $this->cache->remember(
                "limen-ai:agent-definition:{$key}",
                (int) $this->config->get('limen-ai.agent_storage.database.cache_ttl', 300),
                $resolver,
            )
            : $resolver();

        if (! $model instanceof AgentDefinitionModel) {
            return null;
        }

        return $this->definitionFromModel($model);
    }

    public function all(): array
    {
        if (! $this->isEnabled() || ! $this->tableExists()) {
            return [];
        }

        return $this->newQuery()
            ->where('enabled', true)
            ->orderBy('key')
            ->get()
            ->map(fn (AgentDefinitionModel $model): AgentDefinition => $this->definitionFromModel($model))
            ->filter(fn (AgentDefinition $agent): bool => Enablement::isEnabled($agent))
            ->values()
            ->all();
    }

    public function exists(string $key): bool
    {
        if (! $this->isEnabled() || ! $this->tableExists()) {
            return false;
        }

        return $this->newQuery()->where('key', $key)->exists();
    }

    protected function definitionFromModel(AgentDefinitionModel $model): AgentDefinition
    {
        $config = $model->toAgentConfigArray();

        if (isset($config['class']) && is_string($config['class'])) {
            return $this->classAgents->make($model->key, $config);
        }

        return DatabaseAgentDefinition::fromModel($model);
    }

    /** @return Builder<AgentDefinitionModel> */
    protected function newQuery(): Builder
    {
        $connection = $this->config->get('limen-ai.agent_storage.database.connection');
        $table = (string) $this->config->get('limen-ai.agent_storage.database.table', 'limen_ai_agent_definitions');

        $model = new AgentDefinitionModel;
        $model->setTable($table);

        if (is_string($connection) && $connection !== '') {
            $model->setConnection($connection);
        }

        return $model->newQuery();
    }

    protected function isEnabled(): bool
    {
        return (bool) $this->config->get('limen-ai.agent_storage.database.enabled', false);
    }

    protected function shouldCache(): bool
    {
        return (bool) $this->config->get('limen-ai.agent_storage.database.cache', true);
    }

    protected function tableExists(): bool
    {
        $connection = $this->config->get('limen-ai.agent_storage.database.connection');
        $table = (string) $this->config->get('limen-ai.agent_storage.database.table', 'limen_ai_agent_definitions');

        return Schema::connection($connection)->hasTable($table);
    }
}
