<?php

namespace LimenAi\Integrations;

use LimenAi\Contracts\Integrations\HttpConnectorRepository;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolRepository;

class HttpIntegrationValidator
{
    public function __construct(
        private readonly ToolRepository $tools,
        private readonly HttpConnectorRepository $connectors,
    ) {}

    /** @return list<string> */
    public function validateAll(): array
    {
        $errors = [];

        foreach ($this->tools->all() as $tool) {
            $errors = array_merge($errors, $this->validateTool($tool));
        }

        return $errors;
    }

    /** @return list<string> */
    protected function validateTool(ToolDefinition $tool): array
    {
        $integration = $tool->httpIntegration();

        if ($integration === []) {
            return [];
        }

        $errors = [];
        $connectorKey = (string) ($integration['connector'] ?? '');

        if ($connectorKey === '' || $this->connectors->find($connectorKey) === null) {
            $errors[] = "Tool [{$tool->key()}] references unknown HTTP connector [{$connectorKey}].";
        }

        if (! isset($integration['path']) || (string) $integration['path'] === '') {
            $errors[] = "Tool [{$tool->key()}] HTTP integration is missing a path.";
        }

        return $errors;
    }
}
