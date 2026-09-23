<?php

namespace LimenAi\Memory;

use LimenAi\Contracts\Memory\MemoryStore;

class InMemoryMemoryStore implements MemoryStore
{
    /** @var array<string, array<string, array<string, array<string, mixed>>>> */
    private array $memories = [];

    public function put(string $scope, string $key, mixed $value, array $context = []): void
    {
        $scopeId = $this->scopeId($context);

        if ($scopeId === '') {
            return;
        }

        $bucket = $this->bucketKey($scope, $scopeId, $context);

        $this->memories[$bucket] ??= [];
        $this->memories[$bucket][$key] = [
            'scope' => $scope,
            'scope_id' => $scopeId,
            'key' => $key,
            'value' => $value,
            'agent_key' => $context['agent_key'] ?? null,
            'metadata' => $context['metadata'] ?? [],
            'updated_at' => now()->toIso8601String(),
        ];
    }

    public function get(string $scope, string $key, array $context = []): mixed
    {
        $scopeId = $this->scopeId($context);

        if ($scopeId === '') {
            return null;
        }

        $entry = $this->memories[$this->bucketKey($scope, $scopeId, $context)][$key] ?? null;

        return $entry['value'] ?? null;
    }

    public function forget(string $scope, string $key, array $context = []): void
    {
        $scopeId = $this->scopeId($context);

        if ($scopeId === '') {
            return;
        }

        unset($this->memories[$this->bucketKey($scope, $scopeId, $context)][$key]);
    }

    public function all(string $scope, array $context = []): array
    {
        $scopeId = $this->scopeId($context);

        if ($scopeId === '') {
            return [];
        }

        return array_values($this->memories[$this->bucketKey($scope, $scopeId, $context)] ?? []);
    }

    /** @param  array<string, mixed>  $context */
    protected function scopeId(array $context): string
    {
        return (string) ($context['scope_id'] ?? '');
    }

    /** @param  array<string, mixed>  $context */
    protected function bucketKey(string $scope, string $scopeId, array $context): string
    {
        $agentKey = (string) ($context['agent_key'] ?? '');

        return implode(':', [$scope, $scopeId, $agentKey]);
    }
}
