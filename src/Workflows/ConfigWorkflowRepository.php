<?php

namespace LimenAi\Workflows;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Workflows\WorkflowDefinition;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Support\Enablement;

class ConfigWorkflowRepository implements WorkflowRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
    ) {}

    public function find(string $key): ?WorkflowDefinition
    {
        $definition = $this->config->get("limen-ai.workflows.{$key}");

        if (! is_array($definition)) {
            return null;
        }

        return ConfigWorkflowDefinition::fromConfig($key, $definition);
    }

    public function all(): array
    {
        $workflows = $this->config->get('limen-ai.workflows', []);

        if (! is_array($workflows)) {
            return [];
        }

        $definitions = array_map(
            fn (string $key, array $definition): WorkflowDefinition => ConfigWorkflowDefinition::fromConfig($key, $definition),
            array_keys($workflows),
            $workflows,
        );

        return array_values(array_filter(
            $definitions,
            fn (WorkflowDefinition $workflow): bool => Enablement::isEnabled($workflow),
        ));
    }
}
