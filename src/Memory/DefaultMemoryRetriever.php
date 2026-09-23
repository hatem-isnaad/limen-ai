<?php

namespace LimenAi\Memory;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Contracts\Memory\MemoryRetriever;
use LimenAi\Contracts\Memory\MemoryStore;

class DefaultMemoryRetriever implements MemoryRetriever
{
    public function __construct(
        private readonly AgentRepository $agents,
        private readonly MemoryStore $store,
        private readonly MemoryFormatter $formatter,
        private readonly ConfigRepository $config,
    ) {}

    public function retrieve(string $agentKey, array $context): array
    {
        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            return [];
        }

        $memoryConfig = $agent->memoryConfig();
        $limit = (int) $this->config->get('limen-ai.memory.limit', 20);
        $entries = [];

        if ($memoryConfig['user'] ?? false) {
            $entries = array_merge($entries, $this->entriesForScope(MemoryScope::USER, $context, $agentKey));
        }

        if ($memoryConfig['conversation'] ?? false) {
            $entries = array_merge($entries, $this->entriesForScope(MemoryScope::CONVERSATION, $context, $agentKey));
        }

        if ($memoryConfig['agent'] ?? false) {
            $entries = array_merge($entries, $this->entriesForScope(MemoryScope::AGENT, $context, $agentKey));
        }

        if ($entries === []) {
            return [];
        }

        return $this->formatter->toAgentMessages(array_slice($entries, -$limit));
    }

    protected function entriesForScope(string $scope, array $context, string $agentKey): array
    {
        $scopeId = $this->resolveScopeId($scope, $context);

        if ($scopeId === '') {
            return [];
        }

        return $this->store->all($scope, [
            'scope_id' => $scopeId,
            'agent_key' => $agentKey,
        ]);
    }

    protected function resolveScopeId(string $scope, array $context): string
    {
        return match ($scope) {
            MemoryScope::USER => (string) ($context['user_id'] ?? $context['guest_token'] ?? ''),
            MemoryScope::CONVERSATION => (string) ($context['conversation_id'] ?? ''),
            MemoryScope::AGENT => (string) ($context['agent_key'] ?? ''),
            default => '',
        };
    }
}
