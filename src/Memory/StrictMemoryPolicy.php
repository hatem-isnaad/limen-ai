<?php

namespace LimenAi\Memory;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Agents\AgentRepository;
use LimenAi\Exceptions\MemoryPolicyException;

class StrictMemoryPolicy
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly AgentRepository $agents,
    ) {}

    public function assertKeyAllowed(?string $agentKey, string $key): void
    {
        if (! $this->isStrictEnabled()) {
            return;
        }

        $pattern = (string) $this->config->get('limen-ai.memory.strict.allowed_key_pattern', '/^[a-z][a-z0-9_]*$/');

        if ($pattern !== '' && ! preg_match($pattern, $key)) {
            throw MemoryPolicyException::forKey($key, 'Memory key format is not allowed.');
        }

        $maxKeyLength = (int) $this->config->get('limen-ai.memory.strict.max_key_length', 64);

        if ($maxKeyLength > 0 && mb_strlen($key) > $maxKeyLength) {
            throw MemoryPolicyException::forKey($key, 'Memory key exceeds maximum length.');
        }

        if ($agentKey === null) {
            return;
        }

        $agent = $this->agents->find($agentKey);

        if ($agent === null) {
            return;
        }

        $allowed = $agent->memoryConfig()['allowed_keys'] ?? null;

        if (! is_array($allowed) || $allowed === []) {
            return;
        }

        if (! in_array($key, $allowed, true)) {
            throw MemoryPolicyException::forKey($key, 'Memory key is not in the agent allowlist.');
        }
    }

    public function normalizeValue(?string $agentKey, mixed $value): mixed
    {
        if (! is_string($value)) {
            return $value;
        }

        $maxLength = $this->resolveMaxValueLength($agentKey);

        if ($maxLength > 0 && mb_strlen($value) > $maxLength) {
            return mb_substr($value, 0, $maxLength);
        }

        return $value;
    }

    public function resolveMemoryLimit(?string $agentKey): int
    {
        if ($agentKey !== null) {
            $agent = $this->agents->find($agentKey);

            if ($agent !== null && isset($agent->memoryConfig()['limit'])) {
                return max(0, (int) $agent->memoryConfig()['limit']);
            }
        }

        return (int) $this->config->get('limen-ai.memory.limit', 20);
    }

    public function filterEntries(?string $agentKey, array $entries): array
    {
        if ($entries === []) {
            return [];
        }

        $allowed = null;

        if ($agentKey !== null && $this->isStrictEnabled()) {
            $agent = $this->agents->find($agentKey);

            if ($agent !== null) {
                $configured = $agent->memoryConfig()['allowed_keys'] ?? null;

                if (is_array($configured) && $configured !== []) {
                    $allowed = $configured;
                }
            }
        }

        $filtered = [];

        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            $key = (string) ($entry['key'] ?? '');

            if ($key === '') {
                continue;
            }

            try {
                $this->assertKeyAllowed($agentKey, $key);
            } catch (MemoryPolicyException) {
                continue;
            }

            if ($allowed !== null && ! in_array($key, $allowed, true)) {
                continue;
            }

            $entry['value'] = $this->normalizeValue($agentKey, $entry['value'] ?? null);
            $filtered[] = $entry;
        }

        return $filtered;
    }

    protected function resolveMaxValueLength(?string $agentKey): int
    {
        if ($agentKey !== null) {
            $agent = $this->agents->find($agentKey);

            if ($agent !== null && isset($agent->memoryConfig()['max_value_length'])) {
                return max(0, (int) $agent->memoryConfig()['max_value_length']);
            }
        }

        return (int) $this->config->get('limen-ai.memory.strict.max_value_length', 512);
    }

    protected function isStrictEnabled(): bool
    {
        return (bool) $this->config->get('limen-ai.memory.strict.enforce_allowlist', true);
    }
}
