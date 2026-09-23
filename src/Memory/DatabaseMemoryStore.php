<?php

namespace LimenAi\Memory;

use Illuminate\Database\ConnectionInterface;
use LimenAi\Contracts\Memory\MemoryStore;

class DatabaseMemoryStore implements MemoryStore
{
    public function __construct(
        private readonly ConnectionInterface $db,
    ) {}

    public function put(string $scope, string $key, mixed $value, array $context = []): void
    {
        $scopeId = (string) ($context['scope_id'] ?? '');

        if ($scopeId === '') {
            return;
        }

        $agentKey = $context['agent_key'] ?? null;
        $payload = [
            'value' => json_encode($value, JSON_THROW_ON_ERROR),
            'metadata' => isset($context['metadata']) ? json_encode($context['metadata'], JSON_THROW_ON_ERROR) : null,
            'updated_at' => now(),
        ];

        $query = $this->db->table('limen_ai_memories')
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('memory_key', $key);

        if ($agentKey === null) {
            $query->whereNull('agent_key');
        } else {
            $query->where('agent_key', $agentKey);
        }

        if ($query->exists()) {
            $query->update($payload);

            return;
        }

        $this->db->table('limen_ai_memories')->insert(array_merge($payload, [
            'scope' => $scope,
            'scope_id' => $scopeId,
            'agent_key' => $agentKey,
            'memory_key' => $key,
            'created_at' => now(),
        ]));
    }

    public function get(string $scope, string $key, array $context = []): mixed
    {
        $row = $this->findRow($scope, $key, $context);

        if ($row === null) {
            return null;
        }

        return json_decode((string) $row['value'], true, 512, JSON_THROW_ON_ERROR);
    }

    public function forget(string $scope, string $key, array $context = []): void
    {
        $scopeId = (string) ($context['scope_id'] ?? '');

        if ($scopeId === '') {
            return;
        }

        $query = $this->db->table('limen_ai_memories')
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('memory_key', $key);

        if (array_key_exists('agent_key', $context)) {
            $query->where('agent_key', $context['agent_key']);
        }

        $query->delete();
    }

    public function all(string $scope, array $context = []): array
    {
        $scopeId = (string) ($context['scope_id'] ?? '');

        if ($scopeId === '') {
            return [];
        }

        $query = $this->db->table('limen_ai_memories')
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->orderBy('updated_at');

        if (array_key_exists('agent_key', $context)) {
            $query->where('agent_key', $context['agent_key']);
        }

        return array_map(
            fn (object $row): array => $this->mapRow((array) $row),
            $query->get()->all(),
        );
    }

    protected function findRow(string $scope, string $key, array $context): ?array
    {
        $scopeId = (string) ($context['scope_id'] ?? '');

        if ($scopeId === '') {
            return null;
        }

        $query = $this->db->table('limen_ai_memories')
            ->where('scope', $scope)
            ->where('scope_id', $scopeId)
            ->where('memory_key', $key);

        if (array_key_exists('agent_key', $context)) {
            $query->where('agent_key', $context['agent_key']);
        }

        $row = $query->first();

        return $row === null ? null : (array) $row;
    }

    protected function mapRow(array $row): array
    {
        return [
            'scope' => (string) $row['scope'],
            'scope_id' => (string) $row['scope_id'],
            'key' => (string) $row['memory_key'],
            'value' => json_decode((string) $row['value'], true, 512, JSON_THROW_ON_ERROR),
            'agent_key' => $row['agent_key'] ?? null,
            'metadata' => isset($row['metadata']) ? json_decode((string) $row['metadata'], true, 512, JSON_THROW_ON_ERROR) : [],
            'updated_at' => $row['updated_at'] ?? null,
        ];
    }
}
