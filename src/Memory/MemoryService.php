<?php

namespace LimenAi\Memory;

use LimenAi\Contracts\Memory\MemoryStore;

class MemoryService
{
    public function __construct(
        private readonly MemoryStore $store,
        private readonly StrictMemoryPolicy $memoryPolicy,
    ) {}

    public function rememberUser(int|string $userId, string $key, mixed $value, ?string $agentKey = null): void
    {
        $this->remember(MemoryScope::USER, (string) $userId, $key, $value, $agentKey);
    }

    public function rememberConversation(string $conversationId, string $key, mixed $value, ?string $agentKey = null): void
    {
        $this->remember(MemoryScope::CONVERSATION, $conversationId, $key, $value, $agentKey);
    }

    public function remember(string $scope, string $scopeId, string $key, mixed $value, ?string $agentKey = null): void
    {
        $this->memoryPolicy->assertKeyAllowed($agentKey, $key);

        $context = ['scope_id' => $scopeId];

        if ($agentKey !== null) {
            $context['agent_key'] = $agentKey;
        }

        $this->store->put(
            $scope,
            $key,
            $this->memoryPolicy->normalizeValue($agentKey, $value),
            $context,
        );
    }

    public function recall(string $scope, string $scopeId, string $key, ?string $agentKey = null): mixed
    {
        $context = ['scope_id' => $scopeId];

        if ($agentKey !== null) {
            $context['agent_key'] = $agentKey;
        }

        return $this->store->get($scope, $key, $context);
    }

    public function forget(string $scope, string $scopeId, string $key, ?string $agentKey = null): void
    {
        $context = ['scope_id' => $scopeId];

        if ($agentKey !== null) {
            $context['agent_key'] = $agentKey;
        }

        $this->store->forget($scope, $key, $context);
    }
}
