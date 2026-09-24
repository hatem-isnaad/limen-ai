<?php

namespace LimenAi\Ai;

use LimenAi\Ai\Sdk\DynamicPromptAgent;
use LimenAi\Registry\LimenAiRegistry;

final class AnonymousAgentRegistrar
{
    public function __construct(
        private readonly LimenAiRegistry $registry,
    ) {}

    public function register(
        string $key,
        string $instructions,
        array $tools = [],
        ?string $model = null,
        ?string $provider = null,
    ): DynamicPromptAgent {
        $this->registry->agent($key, [
            'name' => 'Anonymous Agent',
            'description' => 'Runtime anonymous agent',
            'model' => $model ?? (string) config('limen-ai.agents.example.model', 'gpt-4.1-mini'),
            'provider' => $provider ?? (string) config('limen-ai.providers.default', 'fake'),
            'instructions' => $instructions,
            'tools' => array_values($tools),
            'skills' => [],
            'knowledge' => [],
            'authorization' => [
                'required' => true,
                'abilities' => [],
                'guest_allowed' => false,
            ],
            'enabled' => true,
        ]);

        return new DynamicPromptAgent($key);
    }
}
