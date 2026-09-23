<?php

namespace LimenAi\Tools;

use LimenAi\Contracts\Integrations\HttpToolExecutor;
use LimenAi\Contracts\Runtime\ToolExecutionContext;
use LimenAi\Contracts\Tools\Tool;
use LimenAi\Contracts\Tools\ToolDefinition;
use LimenAi\Contracts\Tools\ToolExecutor;
use LimenAi\Exceptions\ToolException;

class ClassBasedToolExecutor implements ToolExecutor
{
    public function __construct(
        private readonly HttpToolExecutor $httpTools,
        private readonly ToolInstanceResolver $instances,
    ) {}

    public function execute(ToolDefinition $tool, array $input, ToolExecutionContext $context): array
    {
        if ($tool->httpIntegration() !== []) {
            return $this->httpTools->execute(
                array_merge($tool->httpIntegration(), [
                    'tool_key' => $tool->key(),
                    'timeout' => $tool->timeoutSeconds(),
                ]),
                $input,
                $context,
            );
        }

        $instance = $this->instances->resolve($tool);

        if ($instance instanceof Tool) {
            return $instance->handle($input, $context);
        }

        if (is_callable([$instance, 'handle'])) {
            return $instance->handle($input, $context);
        }

        throw new ToolException("Tool executor [{$tool->executorClass()}] must implement handle().");
    }
}
