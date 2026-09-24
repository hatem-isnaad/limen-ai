<?php

namespace LimenAi\Workflows;

use Illuminate\Contracts\Config\Repository as ConfigRepository;
use LimenAi\Contracts\Workflows\WorkflowDefinition;
use LimenAi\Contracts\Workflows\WorkflowRepository;
use LimenAi\Support\DefinitionLoader;
use LimenAi\Support\Enablement;

class ConfigWorkflowRepository implements WorkflowRepository
{
    public function __construct(
        private readonly ConfigRepository $config,
        private readonly DefinitionLoader $definitions,
    ) {}

    public function find(string $key): ?WorkflowDefinition
    {
        $definition = $this->definitions->workflows()[$key] ?? null;

        if (! is_array($definition)) {
            return null;
        }

        return ConfigWorkflowDefinition::fromConfig($key, $definition);
    }

    public function all(): array
    {
        $workflows = $this->definitions->workflows();

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
