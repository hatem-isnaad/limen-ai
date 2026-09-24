<?php

namespace LimenAi\Agents;

use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Facades\Schema;
use LimenAi\Models\AgentDefinitionModel;

final class AgentDefinitionStore
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly CacheRepository $cache,
    ) {}

    public function isAvailable(): bool
    {
        if (! (bool) $this->config->get('limen-ai.agent_storage.database.enabled', false)) {
            return false;
        }

        $connection = $this->config->get('limen-ai.agent_storage.database.connection');
        $table = (string) $this->config->get('limen-ai.agent_storage.database.table', 'limen_ai_agent_definitions');

        return Schema::connection($connection)->hasTable($table);
    }

    /** @return list<AgentDefinitionModel> */
    public function all(): array
    {
        return $this->query()->orderBy('key')->get()->all();
    }

    public function find(string $key): ?AgentDefinitionModel
    {
        return $this->query()->where('key', $key)->first();
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function upsert(string $key, array $attributes): AgentDefinitionModel
    {
        $model = $this->query()->updateOrCreate(
            ['key' => $key],
            array_merge($attributes, ['key' => $key]),
        );

        $this->forgetCache($key);

        if (($model->version ?? '') === '') {
            $model->version = '1.0.0';
            $model->save();
        }

        return $model->fresh() ?? $model;
    }

    public function delete(string $key): bool
    {
        $deleted = (bool) $this->query()->where('key', $key)->delete();
        $this->forgetCache($key);

        return $deleted;
    }

    public function bumpVersion(string $key): ?AgentDefinitionModel
    {
        $model = $this->find($key);

        if ($model === null) {
            return null;
        }

        $parts = explode('.', (string) $model->version);
        $patch = (int) ($parts[2] ?? 0);
        $parts[2] = (string) ($patch + 1);
        $model->version = implode('.', array_pad($parts, 3, '0'));
        $model->save();

        $this->forgetCache($key);

        return $model;
    }

    protected function forgetCache(string $key): void
    {
        if ((bool) $this->config->get('limen-ai.agent_storage.database.cache', true)) {
            $this->cache->forget("limen-ai:agent-definition:{$key}");
        }
    }

    /** @return \Illuminate\Database\Eloquent\Builder<AgentDefinitionModel> */
    protected function query(): \Illuminate\Database\Eloquent\Builder
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
}
